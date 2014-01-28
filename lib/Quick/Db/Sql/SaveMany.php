<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Sql_SaveMany
{
    protected $_db;                             // database with table
    protected $_name;                           // table name
    protected $_primaryKey;                     // auto-increment primary key field
    protected $_hashFilters = array();          // TBD
    protected $_updateItemKeys = true;

    public function __construct( Quick_Db $db, $tableName, $primaryKey ) {
        $this->_db = $db;
        $this->_name = $tableName;
        $this->_primaryKey = $primaryKey;
    }

    public function setUpdateItemKeys( $yesno = true ) {
        $this->_updateItemKeys = $yesno;
    }

    /**
     * Save the array of items to this table.
     * The array may contain either objects or hashes (but not both).
     * If objects, the auto-assigned primary keys will get set.
     * The items are may not always be saved in the order passed.
     */
    public function saveMany( Array $items ) {
        if (!$items) return true;

        if (is_object($first = reset($items))) {
            $objects = & $items;
            $hashes = $this->_convertObjectsToHashes($items);
        }
        else {
            $objects = false;
            $hashes = & $items;
        }

        // set create_dt on objects as appropriate
        if ($this->_hashFilters)
            $this->_filterHashes($hashes, $objects);

        $itemsUpdated = $this->_removeItemsWithPrimaryKeys($hashes);
        $itemsInserted = & $hashes;

        // first update the items that already have a primary key
        if ($itemsUpdated) {
            $fields = $this->_findFieldsBeingSaved($itemsUpdated);
            $update_sql = implode(", ", array_map(array($this, '_valuesXSql'), $fields));
            $values_sql = "(" . implode(",", array_fill(0, count($fields), '?')) . ")";
            $save_sql = "INSERT INTO $this->_name (`" . implode("`, `", $fields) . "`) " .
                        "VALUES " . implode(", ", array_fill(0, count($itemsUpdated), $values_sql)) .
                        " ON DUPLICATE KEY UPDATE $update_sql";
            $values = array();
            foreach ($itemsUpdated as $item) {
                foreach ($fields as $field) $values[] = $item[$field];
            }
            $this->_db->execute($save_sql, $values);
        }

        // then save the new items without a primary key
        if ($itemsInserted) {
            $fields = $this->_findFieldsBeingSaved($itemsInserted);
            $values_sql = "(" . implode(",", array_fill(0, count($fields), '?')) . ")";
            $save_sql = "INSERT INTO $this->_name (`" . implode("`, `", $fields) . "`) " .
                        "VALUES " . implode(", ", array_fill(0, count($itemsInserted), $values_sql));
            $values = array();
            foreach ($itemsInserted as $item) {
                foreach ($fields as $field) $values[] = $item[$field];
            }
            $this->_db->execute($save_sql, $values);

            // set the auto-assigned primary key of the newly inserted objects
            if ($this->_updateItemKeys) {
                $key = $this->_primaryKey;
                $firstId = $this->_db->select("SELECT LAST_INSERT_ID()")->asColumn()->fetch();
                if (!$firstId) throw new Quick_Db_Exception("internal error: insert succeeded but no last_insert_id()");
                if ($objects) {
                    foreach ($objects as $obj) {
                        if (!isset($obj->$key)) $obj->$key = $firstId++;
                    }
                }
                // Q: set primary key in hashes too? ...would need items passed in by reference
                elseif (0) {
                    foreach ($items as & $item) {
                        if (!isset($item[$key])) $item[$key] = $firstId++;
                    }
                }
            }
        }
    }


    protected function _valuesXSql( $x ) {
        return "`$x` = VALUES(`$x`)";
    }

    protected function _getPrimaryKey( ) {
        return $this->_primaryKey;
    }

    protected function & _convertObjectsToHashes( Array $items ) {
        foreach ($items as $item) {
            $hashes[] = get_object_vars($item);
        }
        return $hashes;
    }

    protected function _filterHashes( Array & $hashes, Array $objects ) {
        if ($this->_hashFilters)
            foreach ($this->_hashFilters as $filter)
                $this->$filter($hashes);
        // FIXME: objects need to be updated to have the same edits as the hashes!
    }

    // remove and return those items that have their primary keys set.  Modifies $items.
    protected function & _removeItemsWithPrimaryKeys( Array & $items ) {
        $key = $this->_getPrimaryKey();
        if (is_array($key)) throw new Quick_Db_Exception("composite keys not supported yet");

        $keyed = array();
        foreach ($items as $k => $v) {
            // mysql treats null, '0', '0.0' and blank keys as not set
            if (isset($v[$key])) {
                $keyed[$v[$key]] = $v;
                unset($items[$k]);
            }
        }

        // batch updates using INSERT ... UPDATE into InnoDB tables can deadlock,
        // and ORDER BY is not supported.  Order the VALUES here instead.
        ksort($keyed);

        return $keyed;
    }

    protected function _findFieldsBeingSaved( Array & $items ) {
        // FIXME: assume that the first object has the same fields as all the others
        // FIXME: this may not always be the case, might have to merge together the fields found
        if ($items)
            return array_keys(reset($items));
        else
            return array();
    }

    protected function _typesetPairs( $nameTypes, $nameVals ) {
        $ret = array();
        foreach ($nameVals as $name => $value) {
            if (isset($nameTypes[$name]))
                $ret[] = $value === NULL ? 'NULL' : "$name = " . addslashes($value);
        }
        return $ret;
    }

    protected function _setCreateDt( Array & $hashes ) {
        $dt = date('Y-m-d H:i:s');
        foreach ($hashes as & $hash)
            if (!isset($hash['create_dt'])) $hash['create_dt'] = $dt;
    }
}
