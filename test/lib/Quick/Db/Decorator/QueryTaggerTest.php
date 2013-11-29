<?

class _Quick_Db_Decorator_QueryTagger_Exposer extends Quick_Db_Decorator_QueryTagger {
    public function _tagSql( $sql ) {
        return parent::_tagSql($sql);
    }
}

class Quick_Db_Decorator_QueryTaggerTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new _Quick_Db_Decorator_QueryTagger_Exposer($this->getMockSkipConstructor('Quick_Db_Mysql_Db', array()));
    }

    public function testTaggerShouldDecorateQuickDb( ) {
        $this->assertType('Quick_Db', $this->_cut);
    }

    public function testTaggerShouldAppendComment( ) {
        $tagged = $this->_cut->_tagSql("sql");
        $this->assertEquals(0, substr_compare($tagged, " */", -3));
        // note: test method does not have file/line info in debug backtrace within phpunit ??
    }

    public function testTaggerShouldIncludeLabelWithComments( ) {
        $label = uniqid();
        $cut = new _Quick_Db_Decorator_QueryTagger_Exposer($this->getMockSkipConstructor('Quick_Db_Mysql_Db', array()), $label);
        $tagged = $cut->_tagSql("sql");
        $this->assertContains($label, $tagged);
        $this->assertGreaterThan(strpos($tagged, "/*"), strpos($tagged, $label));
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        echo $timer->calibrate(10000, array($this, '_testSpeedNull'), array($this->_cut, "sql"));
        // 1.96m/s
        echo $timer->timeit(10000, "debug_backtrace", array($this, "_testSpeedDebugBacktrace"), array($this->_cut, "sql"));
        // 54k/s debug_backtrace calls from within phpunit
        echo $timer->timeit(10000, "_tagSql", array($this, "_testSpeedTagSql"), array($this->_cut, "sql"));
        // 41k/s tagged lines ; 135k w/ php-5.4 debug_backtrace (ignoreargs/5 deep)
    }

    public function _testSpeedNull( $cut, & $sql ) {
    }

    public function _testSpeedDebugBacktrace( $cut, & $sql ) {
        $trace = debug_backtrace();
    }

    public function _testSpeedTagSql( $cut, & $sql ) {
        return $cut->_tagSql($sql);
    }
}
