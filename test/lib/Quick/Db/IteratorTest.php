<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-17 - AR.
 */

class Quick_Db_IteratorTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_items = array(1,2,3);
        $this->_cut = new Quick_Db_Iterator(new Quick_Db_Fetchable_Array($this->_items));
    }

    /*
     * test iterator methods
     */

    public function testClassShouldBeAnIterator( ) {
        $this->assertType('Iterator', $this->_cut);
    }

    public function testKeyShouldReturnCurrentItemIndex( ) {
        $this->assertEquals(0, $this->_cut->key());
    }

    public function testFirstKeyShouldMatchFirstItem( ) {
        $this->assertEquals(0, $this->_cut->key());
        $this->assertEquals(1, $this->_cut->current());
    }

    public function testSecondKeyShouldMatchSecondItem( ) {
        $this->_cut->next();
        $this->assertEquals(1, $this->_cut->key());
        $this->assertEquals(2, $this->_cut->current());
    }

    public function testNextShouldAdvanceIndex( ) {
        $this->_cut->next();
        $this->assertEquals(1, $this->_cut->key());
    }

    public function testRewindShouldResetItemIndex( ) {
        $this->_cut->next();
        $this->_cut->rewind();
        $this->assertEquals(0, $this->_cut->key());
        $this->assertEquals(1, $this->_cut->current());
    }

    public function testCurrentShouldReturnCurrentItem( ) {
        $this->assertEquals(1, $this->_cut->current());
        $this->_cut->next();
        $this->assertEquals(2, $this->_cut->current());
    }

    public function testCurrentShouldReturnCurrentAndNotAdvanceIndex( ) {
        $this->assertEquals(1, $this->_cut->current());
        $this->assertEquals(1, $this->_cut->current());
        $this->_cut->next();
        $this->assertEquals(2, $this->_cut->current());
        $this->assertEquals(2, $this->_cut->current());
    }

    public function testValidShouldReturnTrueIfCurrentItemExists( ) {
        $this->assertTrue($this->_cut->valid());
        $this->_cut->next();
        $this->assertTrue($this->_cut->valid());
        $this->_cut->next();
        $this->assertTrue($this->_cut->valid());
    }

    public function testValidShouldReturnFalseOnceNoMoreItems( ) {
        $this->_cut->next();
        $this->_cut->next();
        $this->_cut->next();
        $this->assertFalse($this->_cut->valid());
    }

    public function testCurrentShouldReturnFalseOnceNoMoreItems( ) {
        // this is an iterator usage error, current is not defined if valid is false.
        // Db_Iterator just hands back the value returned by the fetcher, FALSE.
        $this->_cut->next();
        $this->_cut->next();
        $this->_cut->next();
        $this->assertFalse($this->_cut->current());
    }

    public function testNextShouldAdvancePastUncachedElement( ) {
        $this->_cut->next();
        $this->assertEquals(2, $this->_cut->current());
    }

    public function testNextShouldAdvancePastCachedElement( ) {
        $this->_cut->current();
        $this->_cut->next();
        $this->assertEquals(2, $this->_cut->current());
    }

    public function testNextShouldAdvanceIndexSkippingElementsAsNecessary( ) {
        $this->_cut->next();
        $this->_cut->next();
        $this->assertEquals(3, $this->_cut->current());
    }

    /*
     * test fetch methods
     */

    public function testFetchShouldReturnCurrentAndAdvanceIndex( ) {
        $this->assertEquals(1, $this->_cut->fetch());
        $this->assertEquals(1, $this->_cut->key());
        $this->assertEquals(2, $this->_cut->current());
    }

    public function testFetchFollowingCurrentShouldReturnCurrent( ) {
        $this->assertEquals(1, $this->_cut->current());
        $this->assertEquals(1, $this->_cut->fetch());
    }

    public function testResetShouldResetItemIndex( ) {
        $this->_cut->next();
        $this->_cut->next();
        $this->_cut->reset();
        $this->assertEquals(0, $this->_cut->key());
        $this->assertEquals(1, $this->_cut->current());
    }

    public function testNextAfterFetchShouldSkipAnItem( ) {
        $this->assertEquals(1, $this->_cut->fetch());
        $this->_cut->next();
        $this->assertEquals(3, $this->_cut->fetch());
    }

    public function testFetchAfterNextShouldReturnCurrentItem( ) {
        $this->_cut->next();
        $this->assertEquals(2, $this->_cut->fetch());
    }

    /*
     * Test iterator when used in a foreach (valid, key, current, next)
     */

    public function testIteratorShouldReturnAllItemsInOrder( ) {
        foreach ($this->_cut as $key => $item) {
            $keys[] = $key;
            $items[] = $item;
        }
        $this->assertEquals(array(0,1,2), $keys);
        $this->assertEquals(array(1,2,3), $items);
    }

    public function testFetcherShouldReturnAllItemsInOrderFollowedByFalse( ) {
        while ($items[] = $this->_cut->fetch())
            ;
        $this->assertEquals(array(1,2,3,false), $items);
    }

    public function testIterationShouldBeRepeatable( ) {
        foreach ($this->_cut as $k => $v) $rows1[$k] = $v;
        foreach ($this->_cut as $k => $v) $rows2[$k] = $v;
        $this->assertEquals($rows1, $rows2);
    }
}
