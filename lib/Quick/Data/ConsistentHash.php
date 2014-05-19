<?

/**
 * Consistent hashing.  Hashes strings to one of the nodes added, with
 * nodes with a higher count (or added more often) getting a proportionately
 * larger share of the hits.  Nodes and lookup values should be strings.
 *
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_ConsistentHash
{
    protected $_nodes = array();                // nodes to hash to, with lists of keys
    protected $_keyMap = array();               // key to node map
    protected $_keys = array();                 // sorted list of keys
    protected $_nkeys = 0;

    public function add( $node, $count = 5 ) {
        // add $count control points around the hash ring for $node
        for ($i=0; $i<$count; ++$i) {
            // probabilistic collision detection: ok for a few thousand nodes
            do { $key = mt_rand(0, 999999999); } while (isset($this->_keyMap[$key]));
            $this->_nodes[$node][] = $key;
            $this->_keyMap[$key] = $node;
        }
        // keys changed, force a rebuild on next lookup
        $this->_nkeys += $count;
        $this->_keys = array();
        return $this;
    }

    public function delete( $node ) {
        if (isset($this->_nodes[$node])) {
            $this->_nkeys -= count($this->_nodes[$node]);
            foreach ($this->_nodes[$node] as $key)
                unset($this->_keyMap[$key]);
            unset($this->_nodes[$node]);
            $this->_keys = array();
        }
        return $this;
    }

    // return the first node in the hash after $name
    public function get( $name ) {
        // it is an error if no nodes
        $key = $this->_hash($name);
        $ix = $this->_findkey($key);
        return $this->_keyMap[$this->_keys[$ix]];
    }

    // return the first $count distinct nodes in the hash ring after $name
    public function getMany( $name, $count ) {
        $ix = $this->_findKey($this->_hash($name));
        $node = $this->_keyMap[$this->_keys[$ix]];
        $nodes[$node] = $node;

        // also return the subsequent nodes in the hash ring
        if ($count > 1) {
            // scan the keys from > $ix to the end, then
            // scan the keys from 0 to < $ix, gathering unique nodes
            $this->_gatherDistinctNodes($count-1, $ix+1, $this->_nkeys, $nodes);
            $this->_gatherDistinctNodes($count-count($nodes), 0, $ix, $nodes);
        }

        return $nodes;
    }


    protected function _hash( $name ) {
        return crc32($name) & 0x3FFFFFFF;       // 0 .. 1 billion (2^30)
    }

    // gather $count more nodes into $nodes from _keys offsets $fm to $until
    protected function _gatherDistinctNodes( $count, $fm, $until, & $nodes ) {
        if ($count <= 0) return;
        for ($i = $fm; $i < $until; ++$i) {
            $node = $this->_keyMap[$this->_keys[$i]];
            if (!isset($nodes[$node])) {
                $nodes[$node] = $node;
                if (--$count === 0) break;
            }
        }
    }

    // return the index of the next node control point >= $val
    protected function _findkey( $val ) {
        if (!$this->_keys) {
            $this->_keys = array_keys($this->_keyMap);
            sort($this->_keys);
            // count() is linear in the length of the list, and is slower than iterating!
        }

        $keys = & $this->_keys;

        // if not too many control points, faster to iterate than to bisect
        if ($this->_nkeys < 100) {
            foreach ($keys as $ix => $key)
                if ($key >= $val) return $ix;
            return 0;
        }

        // binary search to narrow the possible range
        // NOTE: avoid count() on every lookup, is linear in list len
        $gap = 20;
        for ($i=0, $j=$this->_nkeys-1; $j - $i > $gap; ) { 
            $mid = (int) (($i + $j) / 2);
            if ($keys[$mid] < $val) $i = $mid + 1; else $j = $mid;
        }

        // then quick linear search to find the next point >= $key
        for ( ; $i<=$j; ++$i) {
            if ($keys[$i] >= $val) break;
        }

        // return the next point, or the first one if no next point
        $ix = $i < $this->_nkeys ? $i : 0;

        return $ix;
    }
}
