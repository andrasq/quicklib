<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Test_Tempfile
    extends Quick_Data_Tempfile
{
    // original was moved to Quick_Data_Tempfile, this left here
    // for backward compatibility

    public function __construct( $dir = "/tmp", $prefix = "test-" ) {
        parent::__construct($dir, $prefix);
    }
}
