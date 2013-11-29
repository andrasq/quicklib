<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

require_once 'FileTestBase.php';

class Quick_Logger_FileTest
    extends Quick_Logger_FileTestBase
{
    protected function _createClassUnderTest( ) {
	return new Quick_Logger_FileAtomic($this->_filename);
    }

    public function xx_testSpeed( ) {
        parent::xx_testSpeed();
    }
}
