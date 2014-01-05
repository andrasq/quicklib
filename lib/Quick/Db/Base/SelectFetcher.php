<?

/**
 * Base results fetcher, intended to be subclassed by each database.
 * This is on the critical path, so subclasses make direct native db calls.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

abstract class Quick_Db_Base_SelectFetcher
    implements Quick_Db_SelectFetcher
{
    protected $_rs, $_fetchMethod, $_selectResult;
    protected $_columnIndex;
    //protected $_objectClass, $_objectTemplate;
    //protected $_fetch_assoc_function = 'override_fetch_assoc';          // OVERRIDE in derived class
    //protected $_fetch_row_function = 'override_fetch_row';              // OVERRIDE in derived class

    // the constructor does not impose a type on $rs, easier to support all derived classes
    public function __construct( $rs, $fetchMethod, Quick_Db_SelectResult $selectResult, $arg = null, $arg2 = null ) {
        // we trust the caller to only specify methods that exist
        $this->_rs = $rs;
        $this->_fetchMethod = $fetchMethod;

        // retain a reference to the result object so its destructor will free
        // the result resource only after all fetchers have been destroyed
        $this->_selectResult = $selectResult;

        if ($arg !== null) {
            if ($fetchMethod === 'fetchListColumn' || $fetchMethod === 'fetchHashColumn') {
                $this->_columnIndex = $arg;
            }
            elseif ($fetchMethod === 'asColumn') {
                $this->_fetchMethod = is_integer($arg) ? 'fetchListColumn' : 'fetchHashColumn';
                $this->_columnIndex = $arg;
            }
            elseif ($fetchMethod === 'asObject') {
                // NOTE: should break this up into asClonedObject, asBuiltObject, asNewObject, etc.
                // The unified asObject implementation keeps the interface simple, but it`s too large
                if (is_callable($arg) || $arg instanceof Closure) {
                    // construct by passing the values hash to the callback
                    $this->_fetchMethod = 'fetchObjectCallback';
                    $this->_callback = $arg;
                }
                elseif (is_object($arg)) {
                    if (0 && $arg instanceof Quick_Db_ObjectBuilder) {
                        $this->_fetchMethod = 'fetchObjectBuilder';
                        $this->_callback = $arg;
                        $this->_builderMethod = 'buildObject';
                    }
                    // construct by cloning the template object
                    $this->_fetchMethod = 'fetchObjectClone';
                    $this->_objectTemplate = $arg;
                }
/**
                elseif (is_string($arg) && class_exists($arg)) {
                    // construct by creating a new object on each call
                    // NOTE: constructor params not supported
                    $this->_fetchMethod = 'fetchObjectNew';
                    $this->_objectClass = $arg;
                    $this->_objectClassParams = $arg2;
                    $this->_reflector = new ReflectionClass($arg);
                }
**/
                else {
                    $spec = var_export($arg, true);
                    throw new Quick_Db_Exception("asObject: unknown object fetcher specifier ``$spec''");
                }
            }
            else {
                // fetchObjectClone, fetchBuiltObject etc fetch methods
                $this->_objectClass = $arg;
                $this->_objectClassParams = $arg2;
                $this->_callback = $arg;
                // nb: 2x faster to === compare two methods than to is_object() test
                if (is_object($arg))
                    $this->_objectTemplate = $arg;
                //elseif (is_callable($arg))
                //    $this->_callback = $arg;
                //elseif (class_exists($arg))
                //    $this->_objectTemplate = new $arg();
            }
        }
    }


    public function fetch( ) {
        $m = $this->_fetchMethod;
        return $this->$m();
    }
    // OVERRIDE
    abstract public function reset();

    // OVERRIDE
    abstract protected function _numRows($rs);

    // OVERRIDE for speed
    public function fetchAll( $limit = null ) {
        //
        // override if possible!  this is a stupid slow implementation
        //
        $ret = array();
        $fetchMethod = $this->_fetchMethod;
        if ($limit === null) {
            while (($ret[] = $this->$fetchMethod()) !== false)
                ;
            array_pop($ret);
        }
        else {
            $k = 0;
            while (++$k <= $limit && ($ret[] = $this->$fetchMethod()) !== false)
                ;
            if (end($ret) === false) array_pop($ret);
        }
        return $ret;
    }

    public function getIterator( ) {
        return new Quick_Db_Iterator($this);
    }

    // OVERRIDE
    abstract protected function fetchList();

    // OVERRIDE
    abstract protected function fetchHash();

    // OVERRIDE for speed
    protected function fetchListColumn( ) {
        // override for speed
        return ($r = $this->fetchList($this->_rs)) ? $r[$this->_columnIndex] : false;
    }
    // OVERRIDE for speed
    protected function fetchHashColumn( ) {
        // override for speed
        return ($r = $this->fetchHash($this->_rs)) ? $r[$this->_columnIndex] : false;
    }

    protected function fetchObjectClone( ) {
        if ($r = $this->fetchHash()) {
            $object = clone($this->_objectTemplate);
            foreach ($r as $k => $v) $object->$k = $v;
            return $object;
        }
        return false;
    }

    protected function fetchObjectNew( ) {
        if ($r = $this->fetchHash()) {
            $class = $this->_objectClass;
            // @FIXME: do we want to support constructor params? 
            $o = $this->_reflector->newInstanceArg($this->_objectClassParams);
            foreach ($k as $k => $v) $o->$k = $v;
            return $o;
        }
        return false;
    }

    protected function fetchObjectCallback( ) {
        $callback = $this->_callback;
        // note: call_user_func() is slow if called with classname and a dynamic method, but is ok otherwise
        // (not as fast a direct call, but faster than a decision tree to figure out how to handle it)
        return ($r = $this->fetchHash()) ? call_user_func($this->_callback, $r) : false;
    }

    protected function fetchObjectBuilder( ) {
        $m = $this->_builderMethod;
        return ($r = $this->fetchHash()) ? $this->_callback->$m($r) : false;
    }
}
