<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_QueryTagTest
    extends Quick_Test_Case
{
    public function testTagShouldCaptureCaller( ) {
        $tag = new Quick_Db_QueryTag(); $line = __LINE__;
        $info = $tag->getCaller();
        $this->assertEquals(__FILE__, $info->file);
        $this->assertEquals($line, $info->line);
        $this->assertEquals(__CLASS__, $info->class);
        $this->assertEquals('->', $info->type);
        $this->assertEquals(__FUNCTION__, $info->function);
    }

    public function testTagShouldReturnCallersCaller( ) {
        foreach (array(1, 2) as $uplevel) {
            $method = "_getCaller{$uplevel}";
            $info = $this->$method();  $line = __LINE__;
            $this->assertEquals(__FILE__, $info->file);
            $this->assertEquals($line, $info->line);
            $this->assertEquals(__CLASS__, $info->class);
            $this->assertEquals('->', $info->type);
            $this->assertEquals(__FUNCTION__, $info->function);
        }
    }

    public function testGetTraceShouldReturnArrayOfArrays( ) {
        $tag = new Quick_Db_QueryTag();
        $trace = $tag->getTrace();
        $this->assertTrue(is_array($trace));
        $this->assertTrue(is_array($trace[0]));
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(100000, array($this, '_testSpeedNull'), array());
        echo $timer->timeit(100000, "make tag", array($this, '_testSpeedTag'), array());
    }

    public function _testSpeedNull( ) {
    }

    public function _testSpeedTag( ) {
        return new Quick_Db_QueryTag();
    }

    protected function _getCaller1( ) {
        // return caller info from depth of 1
        $tag = new Quick_Db_QueryTag(1);
        return $tag->getCaller(1);
    }

    protected function _getCaller2( ) {
        return $this->_getCaller2b();
    }

    protected function _getCaller2b( ) {
        // return caller info from depth of 2
        $tag = new Quick_Db_QueryTag(1);
        return $tag->getCaller(2);
    }
}
