<?

class Quick_Store_FileContentTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_store = array();
        $this->_cut = new Quick_Store_FileContent(new Quick_Store_Array($this->_store));
    }

    public function testAccessingMissingFileShouldReturnFalse( ) {
        $this->assertFalse($this->_cut->set("/nonesuch", "data"));
        $this->assertFalse($this->_cut->get("/nonesuch"));
        $this->assertFalse($this->_cut->add("/nonesuch", "data"));
        $this->assertFalse($this->_cut->delete("/nonesuch"));
    }

    public function testSetShouldStoreUnderFilenameAndFilemtime( ) {
        $this->_cut->set("/etc/passwd", "<data>");
        $this->assertContains("/etc/passwd", implode("", array_keys($this->_store)));
        $this->assertContains((string)filemtime("/etc/passwd"), implode("", array_keys($this->_store)));
    }

    public function testGetShouldRetrieveSetContent( ) {
        $id = uniqid();
        $this->_cut->set("/etc/passwd", $id);
        $this->assertEquals($id, $this->_cut->get("/etc/passwd"));
    }

    public function testSetOfMissingFileShouldNotStore( ) {
        $this->_cut->set("/nonesuch", "data");
        $this->assertEquals(array(), $this->_store);
    }

    public function testGetOfRemovedFileShouldReturnFalse( ) {
        $tempfile = new Quick_Test_Tempfile();
        $this->_cut->set($tempfile, "data");
        unlink($tempfile);
        $this->assertEquals(false, $this->_cut->get($tempfile));
    }

    public function testGetAfterTimestampIsChangedShouldReturnFalse( ) {
        $tempfile = new Quick_Test_Tempfile();
        $this->_cut->set($tempfile, "data");
        $this->assertEquals("data", $this->_cut->get($tempfile));
        touch($tempfile, filemtime($tempfile)+1);
        $this->assertFalse($this->_cut->get($tempfile));
    }

    public function testDeleteShouldNotRemoveFile( ) {
        $tempfile = new Quick_Test_Tempfile();
        $this->_cut->set($tempfile, "data");
        $this->_cut->delete($tempfile);
        $this->assertTrue(file_exists($tempfile));
        unlink($tempfile);
    }

    public function testDeleteShouldRemoveStoredContent( ) {
        $this->_cut->set("/etc/passwd", "data");
        $this->_cut->delete("/etc/passwd");
        $this->assertFalse($this->_cut->get("/etc/passwd"));
    }

}
