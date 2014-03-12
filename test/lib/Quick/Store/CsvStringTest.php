<?

class Quick_Store_CsvStringTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        //$this->_cut = new Quick_Store_CsvString("a=1,b=two");
        $this->_cut = new Quick_Store_CsvString(array('a' => 1, 'b' => 'two'));
    }

    public function testConstructorShouldExtractNameValsFromString( ) {
        $string = "a=1,b=two,c=333,f.our=4,+*@=5";
        $hash = array('a' => 1, 'b' => 'two', 'c' => 333, 'f.our' => 4, '+*@' => 5);
        $cut = new Quick_Store_CsvString($string);
        $this->assertEquals($hash, (array)$cut);
        $this->assertEquals($string, (string)$cut);
        return;

        // WARNING: PHP BUG: can set object attribute "5" but cannot access it when cast to array!
        // WARNING: however, get_object_vars() recovers field "5" just fine
        $o = new StdClass;
        $f = "5";
        $o->$f = 5;
        $hash = (array)$o;
        var_dump($hash["5"]);   // Undefined offset: 5
        $hash = get_object_vars($o);
        var_dump($hash["5"]);   // 5
    }

    public function testConstructorShouldSaveEmptyString( ) {
        $cut = new Quick_Store_CsvString("a=,b=");
        $this->assertEquals('', $cut->a);
        $this->assertEquals('', $cut->b);
    }

    public function testConstructorShouldConsiderExtraEqualsAndCommasToBePartOfValue( ) {
        $cut = new Quick_Store_CsvString("a=a=1&b=2&c,,,c,b=,");
        $this->assertEquals('a=1&b=2&c,,,c', $cut->a);
        $this->assertEquals(',', $cut->b);
    }

    public function testConstructorShouldInitializeFromArray( ) {
        $cut = new Quick_Store_CsvString(array('c' => 3, 'd' => 4));
        $this->assertEquals("c=3,d=4", (string)$cut);
    }

    public function testConstructorShouldInitializeFromObject( ) {
        $cut = new Quick_Store_CsvString((object)array('e' => 5, 'f' => 6.5));
        $this->assertEquals("e=5,f=6.5", (string)$cut);
    }

    public function testCastToArrayShouldReturnHash( ) {
        $hash = (array)$this->_cut;
        $this->assertEquals(array('a' => 1, 'b' => 'two'), $hash);
    }

    public function testObjectVarsShouldBeSameAsHash( ) {
        $hash = get_object_vars($this->_cut);
        $this->assertEquals((array)$this->_cut, $hash);
    }

    public function testReadingObjectVarShouldReturnSetValue( ) {
        $this->_cut->set('c', 3);
        $this->assertEquals(3, $this->_cut->c);
    }

    public function testSettingObjectVarShouldUpdateHash( ) {
        $this->_cut->c = 3;
        $this->assertEquals(array('a' => 1, 'b' => 'two', 'c' => 3), (array)$this->_cut);
        $this->assertEquals("a=1,b=two,c=3", (string)$this->_cut);
    }

    public function testSetShouldAddField( ) {
        $this->_cut->set('c', '333');
        $this->assertEquals(array('a' => 1, 'b' => 'two', 'c' => '333'), (array)$this->_cut);
    }

    public function testGetShouldReturnFalseIfFieldNotFound( ) {
        $this->assertFalse($this->_cut->get('c'));
    }

    public function testGetShouldReturnField( ) {
        $this->assertEquals('two', $this->_cut->get('b'));
    }

    public function testAddShouldAddFieldIfNotAlreadySet( ) {
        $this->_cut->add('b', 2);
        $this->_cut->add('c', 3);
        $this->assertEquals(array('a' => 1, 'b' => 'two', 'c' => 3), (array)$this->_cut);
    }

    public function testDeleteShouldUnsetValue( ) {
        $this->_cut->delete('b');
        $this->_cut->delete('c');
        $this->assertEquals(array('a' => 1), (array)$this->_cut);
    }

    public function testStringCastShouldPackValuesAsCsvString( ) {
        $this->_cut->add('c', 3);
        $this->_cut->set('b', 2);
        $this->assertEquals("a=1,b=2,c=3", (string)$this->_cut);
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $cut = new Quick_Store_CsvString("a=1,b=2,c=3");
        $timer->calibrate(1000, array($this, '_testSpeedNull'), array($cut));
        echo $timer->timeit(200000, "null", array($this, '_testSpeedNull'), array($cut));
        echo $timer->timeit(100000, "(string)", array($this, '_testSpeedString'), array($cut));
        // 430k/s (if disallow commas in values), 540k/s w/ http_build_query()
        // 300k/s if exploded on $separator
        // 430k/s if preg_split (keeping extra commas as part of value w/ =([^=]*))
        echo $timer->timeit(200000, "(array)", array($this, '_testSpeedArray'), array($cut));
        // 2.9m/s
        echo $timer->timeit(200000, "create", array($this, '_testSpeedCreate'), array($cut));
        // 485k/s
        echo $timer->timeit(200000, "get_object_vars()", array($this, '_testSpeedObjectVars'), array($cut));
        // 1.6m/s
    }

    public function _testSpeedNull( $cut ) {
        return $cut;
    }

    public function _testSpeedString( $cut ) {
        return (string)$cut;
    }

    public function _testSpeedArray( $cut ) {
        return (array)$cut;
    }

    public function _testSpeedObjectVars( $cut ) {
        return get_object_vars($cut);
    }

    public function _testSpeedCreate( $array ) {
        return new Quick_Store_CsvString($array);
    }
}
