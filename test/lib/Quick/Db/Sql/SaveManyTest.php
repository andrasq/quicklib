<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Sql_SaveManyTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_db = $this->getTestDb();
        $this->_db->execute("CREATE TEMPORARY TABLE IF NOT EXISTS keyval (id int primary key auto_increment, val int) ENGINE=MyISAM");
        $this->_cut = new Quick_Db_Sql_SaveMany($this->_db, "keyval", "id");
    }

    public function testSaveManyShouldSaveHashesWithPrimaryKeys( ) {
        $items = array(
            array('id' => 1, 'val' => 11),
            array('id' => 2, 'val' => 22),
            array('id' => 3, 'val' => 33),
        );
        $expect = $items;
        $this->_cut->saveMany($items);
        $this->assertEquals($expect, $rs = $this->_db->select("SELECT * FROM keyval")->asHash()->fetchAll());
        $this->assertEquals($expect, $items);
    }

    public function testTestTableShouldBeEmptyOnEachRun( ) {
        $this->assertEquals(array(), $rs = $this->_db->select("SELECT * FROM keyval")->asHash()->fetchAll());
    }

    public function testSaveManyShouldSaveHashesWithoutPrimaryKeys( ) {
        $items = array(
            array('val' => 11),
            array('val' => 22),
            array('val' => 33),
        );
        $expect = array(
            array('id' => 1, 'val' => 11),
            array('id' => 2, 'val' => 22),
            array('id' => 3, 'val' => 33),
        );
        $this->_cut->saveMany($items);
        $this->assertEquals($expect, $rs = $this->_db->select("SELECT * FROM keyval")->asHash()->fetchAll());
        $this->assertNotEquals($expect, $items);
    }

    public function testSaveManyShouldSaveHashesWithMixWithAndWithoutPrimaryKeysAndNotSetPrimaryKeyInArray( ) {
        $items = array(
            array('id' => 1, 'val' => 11),
            array('id' => null, 'val' => 22),
            array('id' => 3, 'val' => 33),
        );
        $expect = array(
            array('id' => 1, 'val' => 11),
            array('id' => 3, 'val' => 33),
            array('id' => 4, 'val' => 22),      // missing id is inserted after and gets next available auto-assigned
        );
        $this->_cut->saveMany($items);
        $this->assertEquals($expect, $rs = $this->_db->select("SELECT * FROM keyval")->asHash()->fetchAll());
        $this->assertNotEquals($expect, $items);
    }

    public function testSaveManyShouldSaveObjectsWithoutPrimaryKeysAndSetPrimaryKeyOnObjects( ) {
        $items = array(
            (object) array('val' => 11),
            (object) array('val' => 22),
            (object) array('val' => 33),
        );
        $expect = array(
            (object) array('id' => 1, 'val' => 11),
            (object) array('id' => 2, 'val' => 22),
            (object) array('id' => 3, 'val' => 33),
        );
        $this->_cut->saveMany($items);
        $this->assertEquals($expect, $rs = $this->_db->select("SELECT * FROM keyval")->asObject(new StdClass)->fetchAll());
        $this->assertEquals($expect, $items);
    }

    public function testSaveManyShouldSaveObjectWithAndWithoutPrimaryKeysAndSetPrimaryKeyOnObjects( ) {
        $items = array(
            (object) array('val' => 11),
            (object) array('id' => 2, 'val' => 22),
            (object) array('val' => 33),
        );
        $this->_cut->saveMany($items);
        $this->assertEquals(3, $items[0]->id);
        $this->assertEquals(2, $items[1]->id);
        $this->assertEquals(4, $items[2]->id);
    }
}
