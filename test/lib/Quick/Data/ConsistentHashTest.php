<?php

/**
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_ConsistentHashExposer extends Quick_Data_ConsistentHash {
    public $_nodes = array();
    public $_keys = array();
    public function _findkey( $val ) {
        return parent::_findkey($val);
    }
}

class Quick_Data_ConsistentHashTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Data_ConsistentHashExposer();
    }

    public function testAddShouldAddNode( ) {
        $this->_cut->add("node1");
        $this->assertTrue(isset($this->_cut->_nodes["node1"]));
        $this->_cut->add("node2");
        $this->assertTrue(isset($this->_cut->_nodes["node1"]));
        $this->assertTrue(isset($this->_cut->_nodes["node2"]));
    }

    public function testAddShouldAddRequestedControlPoints( ) {
        $this->_cut->add("node", 10000);
        $this->_cut->get("name");
        $this->assertEquals(10000, count($this->_cut->_keys));
        $this->_cut->add("node2", 3);
        $this->_cut->get("name");
        $this->assertEquals(10003, count($this->_cut->_keys));
    }

    public function testGetShouldReturnNode( ) {
        $this->_cut->add("1");
        $node = $this->_cut->get("test");
        $this->assertEquals("1", $node);
    }

    public function testDeleteShouldRemoveNode( ) {
        $this->_addNodes(array('a', 'b', 'c'));
        $this->_cut->delete("b");
        $this->_assertIsNodes(array("a", "c"), $this->_cut->getMany("test", 1000));
    }

    public function testGetManyShouldReturnNodesInOrder( ) {
        $this->_addNodes(array('a', 'b', 'c', 'd', 'e', 'f'));
        for ($i=0; $i<100; ++$i) {
            $name = "$i";
            $nodes1 = $this->_cut->getMany($name, 1);
            $this->assertEquals(1, count($nodes1));
            $nodes2 = $this->_cut->getMany($name, 2);
            $this->assertEquals(2, count($nodes2));
            $this->assertEquals(array(end($nodes2)), array_values(array_diff($nodes2, $nodes1)));
            $nodes3 = $this->_cut->getMany($name, 3);
            $this->assertEquals(3, count($nodes3));
            $this->assertEquals(array(end($nodes3)), array_values(array_diff($nodes3, $nodes2)));
        }
        $this->_assertIsNodes(array('a', 'b', 'c', 'd', 'e', 'f'), $this->_cut->getMany("test", 1000));
    }

    public function testFindkeyShouldLocateAKeyNotLessThanValue( ) {
        $this->_cut->add("node1", 100);
        $this->_cut->add("node2", 100);
        $this->_cut->add("node3", 100);
        $this->assertEquals(0, $this->_cut->_findkey(0));
        $this->assertEquals(0, $this->_cut->_findkey(1100000000));
        for ($i=0; $i<1000; ++$i) {
            $val = mt_rand(0, 1000000000);
            $ix = $this->_cut->_findkey($val);
            if ($ix > 0)
                $this->assertGreaterThanOrEqual($val, $this->_cut->_keys[$ix], "val = $val, ix = $ix ({$this->_cut->_keys[$ix]})");
            else
                $this->assertTrue(
                    $val < $this->_cut->_keys[0] ||
                    $val > end($this->_cut->_keys)
                );
        }
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $cut = $this->_cut;
        $count = 5;
        $this->_cut->add("a", $count);
        $this->_cut->add("b", $count);
        $this->_cut->add("c", $count);
        $timer->calibrate(20000, array($this, '_testSpeedNull'), array($cut, "x"));
        echo $timer->timeit(50000, "get()", array($this, '_testSpeedGet'), array($cut, "x"));
        // 580k/s for 3/15 nodes/points; 620k/s for 3/3; 300k/s for 3/300; 250k/s for 3/3000; 220k/s 3/30000
        // 480k/s for 9/45 nodes/points
    }

    public function _testSpeedNull( $cut, $name ) {
    }

    public function _testSpeedGet( $cut, $name ) {
        $cut->get($name);
    }


    protected function _addNodes( $nodes ) {
        foreach ($nodes as $node)
            $this->_cut->add($node);
    }

    protected function _assertIsNodes( $expect, $returned ) {
        $returned = array_values($returned);
        sort($expect);
        sort($returned);
        $this->assertEquals($expect, $returned);
    }
}
