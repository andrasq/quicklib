<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_Bundle_BaseTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Data_Bundle_Base();
    }

    public function testGetShouldReturnSetValue( ) {
        $this->_cut->set('a', 1);
        $this->assertEquals(1, $this->_cut->get('a'));
    }

    public function testSetShouldReturnTrue( ) {
        $this->assertTrue($this->_cut->set('a', 1));
    }

    public function testGetShouldReturnFalseIfElementNotFound( ) {
        $this->assertFalse($this->_cut->get('a'));
    }

    public function testConstructorShouldAcceptPresetValues( ) {
        $cut = new Quick_Data_Bundle_Base(array('a' => 1));
        $this->assertEquals(1, $cut->get('a'));
    }

    public function testShareShouldSetReferenceToStoreAndReturnReferenceToNewStore( ) {
        $store = array();
        $ret = & $this->_cut->shareValues($store);
        $store['a'] = 1;
        $this->assertEquals(1, $this->_cut->get('a'));
        $this->assertEquals($store, $ret);
    }

    public function testShareShouldReturnReferenceToCurrentStore( ) {
        $store = & $this->_cut->shareValues();
        $store['a'] = 1;
        $this->assertEquals(1, $this->_cut->get('a'));
    }

    public function testWithSeparatorShouldReturnObject( ) {
        //$this->assertTrue($this->_cut->withSeparator('.') instanceof Quick_Data_Bundle);
        $this->assertType('Quick_Data_Bundle_Base', $this->_cut->withSeparator('.'));
    }

    public function testWithSeparatorShouldCreateHierarchy( ) {
        $this->_cut->withSeparator('x')->set('axb', 1);
        $this->assertType('array', $this->_cut->get('a'));
        $this->assertEquals(array('b' => 1), $this->_cut->get('a'));
        $this->assertEquals(1, $this->_cut->withSeparator('x')->get('axb'));
    }

    public function testPushShouldReturnTrue( ) {
        $this->assertTrue($this->_cut->push('a', 1));
    }

    public function testPushShouldAppendToNamedArray( ) {
        $this->_cut->push('a', 1);
        $this->_cut->push('a', 2);
        $this->assertEquals(array(1, 2), $this->_cut->get('a'));
    }

    public function testDeleteShouldUnsetElement( ) {
        $this->_cut->set('a', 1);
        $this->_cut->set('b', 2);
        $this->_cut->delete('a');
        $this->assertFalse(array_key_exists('a', $this->_cut->shareValues()));
    }

    public function testDeleteShouldReturnTrueIfDeleted( ) {
        $this->_cut->set('a', 1);
        $this->assertTrue($this->_cut->delete('a'));
    }

    public function testDeleteShouldReturnFalseIfElementNotFound( ) {
        $this->assertFalse($this->_cut->delete('a'));
    }

    public function testGetShouldTraverseHierarchy( ) {
        $cut = new Quick_Data_Bundle_Base(array('a' => array('b' => array('c' => 1))));
        $this->assertEquals(1, $cut->withSeparator('.')->get('a.b.c'));
        $this->assertEquals(array('c' => 1), $cut->withSeparator('.')->get('a.b'));
    }

    public function testSetShouldCreateHierarchy( ) {
        $cut = $this->_cut->withSeparator('.');
        $cut->set('a.b.c', 1);
        $this->assertType('array', $cut->get('a'));
        $this->assertType('array', $cut->get('a.b'));
        $this->assertEquals(1, $cut->get('a.b.c'));
    }

    public function testSetShouldOverwriteValueWithValue( ) {
        $this->_cut->withSeparator('.')->set('a.b', 1);
        $this->_cut->withSeparator('.')->set('a.b', 2);
        $this->assertEquals(array('b' => 2), $this->_cut->get('a'));
    }

    public function testSetShouldOvwriteValueWithHierarchy( ) {
        $this->_cut->set('a', 1);
        $this->_cut->withSeparator('.')->set('a.b', 1);
        $this->assertEquals(array('b' => 1), $this->_cut->get('a'));
    }

    public function testSetshouldOverwriteHierarchyWithValue( ) {
        $this->_cut->withSeparator('.')->set('a.b.c', 1);
        $this->_cut->withSeparator('.')->set('a.b', 1);
        $this->assertEquals(array('b' => 1), $this->_cut->get('a'));
    }

    public function testPushShouldAppendToNamedArrayInHierarchy( ) {
        $cut = $this->_cut->withSeparator('.');
        $cut->push('a.b.c', 1);
        $cut->push('a.b.c', 2);
        $this->assertEquals(array(1,2), $cut->get('a.b.c'));
        $this->assertEquals(array('a' => array('b' => array('c' => array(1, 2)))), $cut->shareValues());
    }

    public function testDeleteShouldDeleteNamedElementAndRemoveEmptyHierarchy( ) {
        $cut = $this->_cut->withSeparator('.');
        $cut->set('a.b.c.d', 1);
        $cut->set('a.b.c.e', 2);
        $cut->set('a.b.f', 3);
        $cut->delete('a.b.c.d');
        $this->assertEquals(array('e' => 2), $cut->get('a.b.c'));
        $cut->delete('a.b.c.e');
        $this->assertFalse($cut->get('a.b.c'));
        $this->assertEquals(array('f' => 3), $cut->get('a.b'));
    }

    public function testToStringShouldReturnBundleAsString( ) {
        $id = uniqid();
        $this->_cut->set('a', $id);
        $this->assertType('string', $this->_cut->__toString());
        $this->assertType('string', (string) $this->_cut);
        $this->assertGreaterThan(0, strpos((string)$this->_cut, $id));
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(1000, array($this, '_testNull'), array($this->_cut));
        $cut2 = $this->_cut->withSeparator('.');
        //$cut2 = $this->_cut;
        echo $timer->timeit(100000, "set", array($this, '_testSet'), array($this->_cut));
        // 1060k/sec
        echo $timer->timeit(100000, "with separator", array($this, '_testWithSeparator'), array($this->_cut));
        // 1220k/sec
        echo $timer->timeit(100000, "pre-set sep a.b.c.d.e = 1", array($this, '_testSetDepth5'), array($cut2));
        // 345k/sec depth 5, 440k/sec d 3
        echo $timer->timeit(100000, "set sep a.b.c.d.e = 1", array($this, '_testSetWithSeparatorDepth5'), array($cut2));
        // 250k/sec depth 5, 285k/sec depth 3 ...wth?? 307k/sec d3 if already cloned??

        //$cut3 = new Quick_Data_Bundle_Xml();
        $cut3 = new Quick_Data_Bundle_Json();
        $cut3->withSeparator('.')->set('a.b.c', array(1,2,3));
        $cut3->withSeparator('.')->setListItemName('a.b.c', 'x');
        echo $timer->timeit(100000, "emit a.b.c = [1,2,3]", array($this, '_testSpeedEmit'), array($cut3));
        // 840k/sec json, 57k/sec php, 35k/s xml, 31k/s named items
    }

    public function _testNull( $cut ) {
    }

    public function _testSet( $cut ) {
        $cut->set('a', 1);
    }

    public function _testWithSeparator( $cut ) {
        $cut->withSeparator('x');
    }

    public function _testSetDepth5( $cut ) {
        $cut->set('a.b.c.d.e', 1);
    }

    public function _testSetWithSeparatorDepth5( $cut ) {
        $cut->withSeparator('.')->set('a.b.c.d.e', 1);
    }

    public function _testSpeedEmit( $cut ) {
        return (string) $cut;
    }
}
