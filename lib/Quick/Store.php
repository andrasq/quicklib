<?

/**
 * Simple key/value store interface
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-11 - AR.
 */

interface Quick_Store {
    public function set( $name, $value );
    public function get( $name );
    public function delete( $name );
}
