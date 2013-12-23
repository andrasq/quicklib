<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_UniqueNumberTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Data_UniqueNumber();
    }

    public function testFetchDecimalShouldReturnANumber( ) {
        $this->assertRegexp('/^[0-9]+$/', $this->_cut->asDecimal()->fetch());
    }

    public function testFetchDecimalShouldReturnOverTwentyDigits( ) {
        // 1+5 digits pid, 10 digits seconds, 1+9 digits nanoseconds
        $this->assertEquals(26, strlen($this->_cut->asDecimal()->fetch()));
    }

    public function fetchTypeProvider( ) {
        return array(
            array('asDecimal', 26),     // 1+5 digits pid, 10 digits seconds, 1+9 digits nanoseconds
            array('asHex', 20),         // 4 digits pid, 8 digits seconds, 8 digits nanonseconds
            array('asBase64', 14),      // 10 bytes base64 encoded become 14 (contstant AA/==  prefix/suffix stripped)
        );
    }

    /**
     * @dataProvider    fetchTypeProvider
     */
    public function testFetchShouldReturnIdenticalLengthNumbers( $asType, $digitCount ) {
        $cut = $this->_cut->$asType();
        $len = strlen($cut->fetch());
        $this->assertEquals($digitCount, $len);
        for ($i=0; $i<2000; ++$i)
            $this->assertEquals($len, strlen($cut->fetch()));
    }

    /**
     * @dataProvider    fetchTypeProvider
     */
    public function testFetchShouldReturnUniqueValues( $asType ) {
        $cut = $this->_cut->$asType();
        for ($i=0; $i<2000; ++$i)
            $x[] = $cut->fetch();
        $this->assertEquals($x, array_unique($x));
    }

    public function testStringCastShouldReturnUniqueValues( ) {
        for ($i=0; $i<2000; ++$i)
            $x[] = (string)($this->_cut);
        $this->assertEquals($x, array_unique($x));
    }

    public function testOneshotStringsShouldReturnDifferentNumbers( ) {
        $s1 = (string)(new Quick_Data_UniqueNumber());
        $s2 = (string)(new Quick_Data_UniqueNumber());
        // warning: php compares two numeric-looking strings as ints,
        // which breaks if the strings are the same in their leading digits
        // past the native int size.  Compare as strings instead.
        // eg: Failed asserting that <string:13018713857335141700508117> is not equal to <string:13018713857335141700501918>.
        $this->assertFalse(strcmp($s1, $s2) === 0);
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(10000, array($this, '_testSpeedNull'), array($this->_cut));
        echo $timer->timeit(200000, 'fetch default: ', array($this, '_testSpeedFetch'), array($this->_cut));
        echo $timer->timeit(200000, 'fetch default: ', array($this, '_testSpeedOneshot'), array($this->_cut));
        // 550k/sec direct, 490k/sec via fetcher; 365k/sec oneshot
        echo $timer->timeit(200000, 'fetch asDecimal: ', array($this, '_testSpeedFetch'), array($this->_cut->asDecimal()));
        echo $timer->timeit(200000, 'fetch asHex: ', array($this, '_testSpeedFetch'), array($this->_cut->asHex()));
        echo $timer->timeit(200000, 'fetch asBase64: ', array($this, '_testSpeedFetch'), array($this->_cut->asBase64()));
        // 487k/sec as hex via indirect fetch
        echo $timer->timeit(200000, 'fetchDecimal: ', array($this, '_testSpeedFetchHex'), array($this->_cut));
        echo $timer->timeit(200000, 'fetchHex: ', array($this, '_testSpeedFetchHex'), array($this->_cut));
        echo $timer->timeit(200000, 'fetchBase64: ', array($this, '_testSpeedFetchBase64'), array($this->_cut));
        // 550k/sec via direct fetchHex; 500k/sec fetchBase64
    }

    public function _testSpeedNull( $cut ) {
    }

    public function _testSpeedFetch( $cut ) {
        return $cut->fetch();
    }

    public function _testSpeedOneshot( $cut ) {
        return (string)(new Quick_Data_UniqueNumber());
    }

    public function _testSpeedFetchDecimal( $cut ) {
        return $cut->fetchDecimal();
    }

    public function _testSpeedFetchHex( $cut ) {
        return $cut->fetchHex();
    }

    public function _testSpeedFetchBase64( $cut ) {
        return $cut->fetchBase64();
    }
}
