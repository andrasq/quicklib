<?

/**
 * No-op store: fails to set, fails to find and fails to delete.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-14 - AR.
 */

class Quick_Store_Null
    implements Quick_Store
{
    public function withTtl( $ttl ) {
        return clone $this;
    }

    public function set( $name, $value ) {
        return false;
    }

    public function get( $name ) {
        return false;
    }

    public function delete( $name ) {
        return false;
    }

    public function add( $name ) {
        return false;
    }
}
