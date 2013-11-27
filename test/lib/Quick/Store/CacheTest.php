<?

class Quick_Store_CacheTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_master = $this->getMock('Quick_Store_Null', array('get', 'set', 'delete'));
        $this->_cache = $this->getMock('Quick_Store_Null', array('get', 'set', 'delete'));
        $this->_cut = new Quick_Store_Cache($this->_master, $this->_cache);
    }

    public function testSetShouldUpdateMaster( ) {
        $this->_master->expects($this->once())->method('set')->with("name", "value")->will($this->returnValue(true));
        $this->_cut->set('name', 'value');
    }

    public function testSetShouldUpdateCacheIfMasterSucceeds( ) {
        $this->_master->expects($this->once())->method('set')->with("name", "value")->will($this->returnValue(true));
        $this->_cache->expects($this->once())->method('set')->with("name", "value")->will($this->returnValue(true));
        $this->_cut->set('name', 'value');
    }

    public function testSetShouldNotUpdateCacheIfMasterFails( ) {
        $this->_master->expects($this->once())->method('set')->with("name", "value")->will($this->returnValue(false));
        $this->_cache->expects($this->never())->method('set');
        $this->_cut->set('name', 'value');
    }

    public function testGetShouldTryCache( ) {
        $this->_cache->expects($this->once())->method('get')->with("name")->will($this->returnValue("value"));
        $this->assertEquals("value", $this->_cut->get("name"));
    }

    public function testGetShouldNotTryMasterIfCacheSucceeds( ) {
        $this->_cache->expects($this->once())->method('get')->with("name")->will($this->returnValue("value"));
        $this->_master->expects($this->never())->method('get');
        $this->assertEquals("value", $this->_cut->get("name"));
    }

    public function testGetShouldTryMasterAndCacheValueIfCacheFails( ) {
        $this->_cache->expects($this->once())->method('get')->with("name")->will($this->returnValue(false));
        $this->_master->expects($this->once())->method('get')->with("name")->will($this->returnValue("value"));
        $this->_cache->expects($this->once())->method('set')->with("name", "value")->will($this->returnValue(true));
        $this->assertEquals("value", $this->_cut->get("name"));
    }

    public function testDeleteShouldDeleteFromBothCacheAndMaster( ) {
        $this->_cache->expects($this->once())->method('delete')->with("name");
        $this->_master->expects($this->once())->method('delete')->with("name");
        $this->_cut->delete("name");
    }
}
