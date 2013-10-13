<?

class Quick_Autoloader_PathRewriterTest
    extends Quick_Test_Case
{
    public function testShouldRequireMatchingPrefix( ) {
        $cut = new Quick_Autoloader_PathRewriter("Foo", null, array());
        $this->assertFalse($cut->buildPath("Test_Case"));
        $this->assertEquals("Foo/Bar", $cut->buildPath("Foo_Bar"));
    }

    public function testShouldRequireMatchingSuffix( ) {
        $cut = new Quick_Autoloader_PathRewriter(null, "Bar", array());
        $this->assertFalse($cut->buildPath("Test_Case"));
        $this->assertEquals("Foo/Bar", $cut->buildPath("Foo_Bar"));
    }

    public function testShouldRequireMatchingPrefixAndSuffix( ) {
        $cut = new Quick_Autoloader_PathRewriter("Foo", "Bar", array());
        $this->assertFalse($cut->buildPath("Test_Case"));
        $this->assertFalse($cut->buildPath("Test_Bar"));
        $this->assertFalse($cut->buildPath("Foo_Case"));
        $this->assertEquals("Foo/Bar", $cut->buildPath("Foo_Bar"));
        $this->assertEquals("Foo/Test/Case/Bar", $cut->buildPath("Foo_Test_Case_Bar"));
    }

    public function testShouldApplyInsertSplice( ) {
        $cut = new Quick_Autoloader_PathRewriter(null, null, array(array('offset' => 0, 'text' => 'More/Added')));
        $this->assertEquals("More/Added/Foo/Bar", $cut->buildPath("Foo_Bar"));
    }

    public function testShouldApplyDeleteSplice( ) {
        $cut = new Quick_Autoloader_PathRewriter(null, null, array(array('offset' => 1, 'count' => 1)));
        $this->assertEquals("Foo/Baz", $cut->buildPath("Foo_Bar_Baz"));
    }

    public function testShouldApplyReplaceSplice( ) {
        $cut = new Quick_Autoloader_PathRewriter(null, null, array(array('offset' => 0, 'count' => 1, 'text' => 'More')));
        $this->assertEquals("More/Bar/Baz", $cut->buildPath("Foo_Bar_Baz"));
    }

    public function invalidSpliceRuleProvider( ) {
        return array(
            array(false, array()),
            array(false, array('count' => 1)),
            array(false, array('text' => 'sdf')),
            array(true, array('offset' => 0, 'count' => 0)),
            array(true, array('offset' => -1, 'count' => 1)),
        );
    }

    /**
     * @dataProvider            invalidSpliceRuleProvider
     */
    public function testConstructorShouldRejectInvalidSplice( $valid, $splice ) {
        try {
            $cut = new Quick_Autoloader_PathRewriter(null, null, array($splice));
            $this->assertTrue($valid);
        }
        catch (Exception $e) {
            $this->assertFalse($valid);
        }
    }

    public function testShouldApplyMultipleSplices( ) {
        $cut = new Quick_Autoloader_PathRewriter(
            null, '_Gateway',
            array(array('offset' => 1, 'count' => 0, 'text' => 'gateways'),
                  array('offset' => -1, 'count' => 1))
        );
        $this->assertEquals("Foo/gateways/Bar", $cut->buildPath("Foo_Bar_Gateway"));
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $cut = new Quick_Autoloader_PathRewriter(
            null, '_Gateway',
            array(array('offset' => 1, 'count' => 0, 'text' => 'gateways'),
                  array('offset' => -1, 'count' => 1))
        );
        echo "\n";
        $timer->calibrate(10000, array($this, '_testSpeedNoop'), array($cut));
        echo $timer->timeit(10000, "gateway rewrite", array($this, '_testSpeedBuildPath'), array($cut));
    }

    public function _testSpeedNoop( ) {
    }

    public function _testSpeedBuildPath( $cut ) {
        $cut->buildPath("Foo_Bar_Baz_Gateway");
    }
}
