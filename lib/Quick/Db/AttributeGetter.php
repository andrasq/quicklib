<?

/**
 * Get_object_vars() returns all visible attributes, which varies per calling context.
 * Array cast includes all attributes, public and private.
 *
 * This class efficiently returns just the public vars (without filtering), by
 * guaranteeing that it is never part of the inheritance hierarchy of the object
 * (or that it is reporting on itself, which will have only public attributes.)
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-16 - AR.
 */

final class Quick_Db_AttributeGetter
{
    public static function create( ) {
        return new self();
    }

    public function getPublicAttributes( $object ) {
        return get_object_vars($object);
    }

    public function getProtectedAttributes( $object ) {
        // php array cast prefixes protected attribute names with "\0*\0"
        return $this->_getPrefixedAttributes($object, "\0*\0");
    }

    public function getPrivateAttributes( $object ) {
        $class = get_class($object);
        return $this->_getPrefixedAttributes($object, "\0{$class}\0");
    }

    protected function _getPrefixedAttributes( $object, $prefix ) {
        $prefixlen = strlen($prefix);
        $ret = array();
        foreach ((array)$object as $k => $v) {
            if (strncmp($k, $prefix, $prefixlen) === 0)
                $ret[substr($k, $prefixlen)] = $v;
        }
        return $ret;
    }
}
