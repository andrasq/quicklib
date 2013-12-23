<?

class Quick_Queue_ConfigTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Queue_Config();
        $this->_config = & $this->_cut->shareConfig();
    }

    public function testGetConfigOfNullShouldReturnAllConfigs( ) {
        $this->_cut->configure('a', 'a1', 1);
        $this->_cut->configure('a', 'a2', 2);
        $this->_cut->configure('b', 'b1', 11);
        $expect = array('a' => array('a1' => 1, 'a2' => 2), 'b' => array('b1' => 11));
        $this->assertEquals($expect, $this->_cut->getConfig());
    }

    public function testConfigShouldUseConstructorArrayToStoreSettings( ) {
        $config = array('a' => uniqid());
        $cut = new Quick_Queue_Config($config);
        $this->assertEquals($config['a'], $cut->getConfig('a'));
    }

    public function testGetConfigOfNameShouldReturnNamedConfigs( ) {
        $this->_cut->configure('a', 'a1', 1);
        $this->_cut->configure('b', 'b1', 11);
        $this->assertEquals(array('a1' => 1), $this->_cut->getConfig('a'));
    }

    public function testSetConfigShouldSetNamedConfigs( ) {
        $this->_cut->setConfig('b', array('b1' => 11, 'b2' => 22));
        $this->assertEquals(array('b1' => 11, 'b2' => 22), $this->_cut->getConfig('b'));
    }

    public function testShareConfigShouldReturnReferenceToConfig( ) {
        $config = & $this->_cut->shareConfig();
        $config['a'] = 123;
        $this->assertEquals(123, $this->_cut->getConfig('a'));
    }

    public function testSelectFieldFromBundlesShouldReturnMapIndexedByName( ) {
        $bundles = array(
            'a' => array('a' => 1, 'b' => 2, 'f' => 11),
            'b' => array('a' => 2, 'b' => 3, 'f' => 22),
            'c' => array('a' => 3, 'b' => 4),
            'd' => array('f' => 44, 'a' => 4),
        );
        // without default missing, fields are skipped
        $map1 = $this->_cut->selectFieldFromBundles('f', $bundles);
        $this->assertEquals(array('a' => 11, 'b' => 22, 'd' => 44), $map1);
        // if default is available, missing fields are created
        $map2 = $this->_cut->selectFieldFromBundlesOrDefault('f', $bundles, 1234);
        $this->assertEquals(array('a' => 11, 'b' => 22, 'c' => 1234, 'd' => 44), $map2);
    }

    public function testConfigureShouldCallSet( ) {
        $cut = $this->getMockSkipConstructor('Quick_Queue_Config', array('set'));
        $cut->expects($this->once())->method('set')->with('a', $id = uniqid(), 123);
        $cut->configure('a', $id, 123);
    }

    public function testSetShouldSetNamedValue( ) {
        $this->_cut->set('a', 'a1', 1);
        $this->_cut->set('b', 'b1', 11);
        $this->assertEquals(array('a' => array('a1' => 1), 'b' => array('b1' => 11)), $this->_config);
    }

    public function testGetShouldReturnNamedValue( ) {
        $this->_config = array('a' => array('a1' => 1, 'a3' => 3));
        $this->assertEquals(3, $this->_cut->get('a', 'a3'));
    }
}
