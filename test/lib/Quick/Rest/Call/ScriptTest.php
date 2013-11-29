<?

class Quick_Rest_Call_ScriptTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Rest_Call_Script();
    }

    public function testCallShouldNotAlterGlobals( ) {
        ksort($GLOBALS);
        $md5 = md5(print_r($GLOBALS, true));
        $globals = $GLOBALS;
        $this->_cut->setMethod(dirname(__FILE__).'/ScriptCmdline.php', "/bin/ls /");
        $this->_cut->call();
        $this->assertEquals(array_keys($globals), array_keys($GLOBALS));
        $this->assertEquals($globals['argv'], $GLOBALS['argv']);
        global $argv;
        $this->assertEquals($globals['argv'], $argv);
        ksort($GLOBALS);
        $this->assertEquals(print_r($globals, true), print_r($GLOBALS, true));
        $this->assertEquals($md5, md5(print_r($GLOBALS, true)));
    }

    public function testCallShouldRunScript( ) {
        $id = uniqid();
        $file = tempnam("/tmp", "test-$id-");
        $this->_cut->setMethod(dirname(__FILE__).'/ScriptCmdline.php', "/bin/ls /tmp");
        $output = $this->_cut->call()->getReply();
        unlink($file);
        $this->assertContains($id, $output);
    }
}
