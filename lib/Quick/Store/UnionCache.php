<?php

/**
 * Union cache, uses overlay but if not found will look in master.
 * Writes and deletes affect only the overlay cache and do not modify
 * the underlying mmaster store, but the combination will correctly track
 * state changes.  Modeled after BSD Unix union mounts.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * In readthrough mode values read from the master are cached in the overlay.
 * In readonly mode set() and delete() fail and have no effect.
 */

class Quick_Store_UnionCache
    implements Quick_Store
{
    protected $_deleted;
    protected $_readthrough = false;
    protected $_readonly = false;

    public function __construct( Quick_Store $master, Quick_Store $overlay ) {
        $this->_master = $master;
        $this->_overlay = $overlay;
    }

    public function get( $name ) {
        if (isset($this->_deleted[$name]))
            return false;
        if (($value = $this->_overlay->get($name)) !== false)
            return $value;
        if (($value = $this->_master->get($name)) !== false && $this->_readthrough && !$this->_readonly)
            $this->_overlay->set($name, $value);
        return $value;
    }

    public function set( $name, $value ) {
        if ($this->_readonly) return false;
        unset($this->_deleted[$name]);
        return $this->_overlay->set($name, $value);
    }

    public function delete( $name ) {
        if ($this->_readonly) return false;
        $this->_deleted[$name] = true;
        return $this->_overlay->delete($name);
    }
}
