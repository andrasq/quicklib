<?

class Quick_Db_Sql_SelectBatch
    implements Quick_Db_Fetchable
{
    protected $_db, $_base = null;
    protected $_selectSql, $_whereSql, $_table, $_key, $_batchSize = 500;

    public function __construct( Quick_Db $db, $selectSql, $whereSql, $tableName, $key, $batchSize = 500 ) {
        $this->_db = $db;
        $this->_selectSql = "";
        $this->_whereSql = "";
        $this->_table = $tableName;
        $this->_key = $key;
        $this->_batchSize = $batchSize;
    }

    public function setSelectSql( $selectSql ) {
        $this->_selectSql = $selectSql;
        return $this;
    }

    public function setWhereSql( $whereSql ) {
        $this->_whereSql = $whereSql;
        return $this;
    }

    public function setBatchSize( $batchSize ) {
        $this->_batchSize = $batchSize;
        return $this;
    }

    public function reset( ) {
        $this->_base = null;
    }

    public function fetch( ) {
        return $this->fetchBatch($this->_batchSize);
    }

    public function fetchBatch( $count ) {
        if ($count === null) $count = $this->_batchSize;
        $base = $this->_base;
        $key = $this->_key;
        $table = $this->_table;
        if ($base === false) {
            // no more results, return false
            return false;
        }
        elseif ($base === null) {
            $bound = $this->_db->select("SELECT MAX($key) FROM (SELECT $key FROM $table ORDER BY $key LIMIT $count) tt")->asColumn()->fetch();
            $batch_sql = "$key IS NULL OR $key <= '$bound'";
            $this->_base = $bound;
        }
        else {
            $bound = $this->_db->select("SELECT MAX($key) FROM (SELECT $key FROM $table WHERE $key > '$base' ORDER BY $key LIMIT $count) tt")->asColumn()->fetch();
            $batch_sql = "$key > '$base' AND $key <= '$bound'";
            $this->_base = $bound;
        }
        // if no more results, return false on next call
        if ($bound === null) $this->_base = false;

        if ($this->_whereSql)
            $whereClause = "WHERE ($this->_whereSql) AND ($batch_sql)";
        else
            $whereClause = "WHERE ($batch_sql)";

        return $this->_db->select("$this->_selectSql $whereClause ORDER BY $key");
    }
}
