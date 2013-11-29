<?php

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0.  See LICENSE for details.
 *
 * 2013-03-07 - AR.
 */

class Bin_QmergeTest
    extends Quick_Test_Case
{
    public function setUp( ) {
    }

    public function testTrue( ) {
    }

    public function qmergeDataProvider( ) {
        return array(
            // OR-merge (union)
            array("-o -n -u", array(), null, null, array(), "empty file"),
            array("-o -n -u", array(1), null, null, array(1), "single element"),
            array("-o -n -u", array(1,2,3,4), null, null, array(1,2,3,4), "identity"),
            array("-o -n -u", array(1,2), array(3,4), null, array(1,2,3,4), "R append"),
            array("-o -n -u", array(1,4), array(2,3), null, array(1,2,3,4), "R insert"),
            array("-o -n -u", array(1,3), array(2,4), null, array(1,2,3,4), "R interleave"),
            array("-o -n -u", array(2,3), array(1,4), null, array(1,2,3,4), "R prepend"),
            // the same, switched A and B
            array("-o -n -u", array(3,4), array(1,2), null, array(1,2,3,4), "L append"),
            array("-o -n -u", array(2,3), array(1,4), null, array(1,2,3,4), "L insert"),
            array("-o -n -u", array(2,4), array(1,3), null, array(1,2,3,4), "L interleave"),
            array("-o -n -u", array(1,4), array(2,3), null, array(1,2,3,4), "L prepend"),
            // three source files
            array("-o -n -u", array(1), array(2), array(3,4), array(1,2,3,4), "R append"),
            array("-o -n -u", array(4), array(1), array(2,3), array(1,2,3,4), "R insert"),
            array("-o -n -u", array(1), array(3), array(2,4), array(1,2,3,4), "R interleave"),
            array("-o -n -u", array(2), array(3), array(1,4), array(1,2,3,4), "R prepend"),
            // edge cases
            array("-o -n", array(1,2), array(2,3), null, array(1,2,2,3), "dupe"),
            array("-o -n -u", array(1,2), array(2,3), null, array(1,2,3), "no dupe"),

            // AND-merge (intersection)
            array("-a -n -u", array(), null, null, array(), "empty file"),
            array("-a -n -u", array(1), null, null, array(1), "single element"),
            array("-a -n -u", array(1,2,3), null, null, array(1,2,3), "identity"),
            //
            array("-a -n -u", array(1,2), array(3), null, array(), "empty"),
            array("-a -n -u", array(1,2), array(2,3), null, array(2), "identity R"),
            array("-a -n -u", array(1), array(1,2,3), null, array(1), "identity L"),
            array("-a -n -u", array(1,2), array(2,3), array(1,3), array(), "empty from 3"),
            array("-a -n -u", array(1,2,3), array(2,3), array(1,2), array(2), "2"),

            // NOT-merge (set difference)
            array("-v -n -u", array(), null, null, array(), "empty file"),
            array("-v -n -u", array(1), null, null, array(1), "single element"),
            array("-v -n -u", array(1,2,3), null, null, array(1,2,3), "identity"),
            //
            array("-v -n -u", array(1,2,3,4), array(0, 33), null, array(1,2,3,4), "del miss"),
            array("-v -n -u", array(1,2,3,4), array(1,2), null, array(3,4), "del head"),
            array("-v -n -u", array(1,2,3,4), array(3,4), null, array(1,2), "del tail"),
            array("-v -n -u", array(1,2,3,4), array(1,4), null, array(2,3), "del ends"),
            array("-v -n -u", array(1,2,3,4), array(2,3), null, array(1,4), "del mid"),
            array("-v -n -u", array(1,2,3,4), array(1,3), null, array(2,4), "del interleaved"),
            // fixed bugs:
            array("-v -n -u", array(24, 49, 52), array(23, 49), array(24, 41), array(52), "fix retest of master"),
        );
    }

    /**
     * @dataProvider    qmergeDataProvider
     */
    public function testMergeTestShouldReturnExpectedData( $switches, $a, $b, $c, $expect, $comment ) {
        $afile = $a !== null ? $this->_makeTempfile($a) : "";
        $bfile = $b !== null ? $this->_makeTempfile($b) : "";
        $cfile = $c !== null ? $this->_makeTempfile($c) : "";
        $outfile = $this->_makeTempfile(array());
        $cmdline = TEST_ROOT . "/../bin/qmerge $switches $afile $bfile $cfile > $outfile";
        $output = `$cmdline`;
        $contents = file($outfile);
        unset($afile);
        unset($bfile);
        unset($cfile);
        unset($outfile);
        $this->assertEquals($expect, array_map('rtrim', $contents), "failed $comment test");
    }

    public function qmergeFuzzDataProvider( ) {
        $nloops = 1000;
        $maxRows = 5;
        $minValue = 1;
        $maxValue = 5;
        $valueScale = 1;
        for ($j=0; $j<$nloops; $j++) {
            $data1 = $data2 = $data3 = array();
            $nrows = rand(0, $maxRows);
            for ($i=0; $i<$nrows; $i++) $data1[$i] = rand($minValue, $maxValue) * $valueScale;
            $nrows = rand(0, $maxRows);
            for ($i=0; $i<$nrows; $i++) $data2[$i] = rand($minValue, $maxValue) * $valueScale;
            $nrows = rand(0, $maxRows);
            for ($i=0; $i<$nrows; $i++) $data3[$i] = rand($minValue, $maxValue) * $valueScale;
            sort($data1);
            sort($data2);
            sort($data3);
            $ret[] = array($data1, $data2, $data3);
        }
        return $ret;
    }

    /**
     * Fuzz test, runs random data sets to probabilistically probe the edge cases.
     * @dataProvider qmergeFuzzDataProvider
     */
    public function xx_testFuzz( $a1, $a2, $a3 ) {
        // 35% faster if tests are run from a single method

        static $afile, $bfile, $cfile, $outfile;
        if (!$afile) {
            $file1 = new Quick_Test_Tempfile();
            $file2 = new Quick_Test_Tempfile();
            $file3 = new Quick_Test_Tempfile();
            $outfile = new Quick_Test_Tempfile();
        }

        file_put_contents($file1, $a1 ? implode("\n", $a1)."\n" : "");
        file_put_contents($file2, $a2 ? implode("\n", $a2)."\n" : "");
        file_put_contents($file3, $a3 ? implode("\n", $a3)."\n" : "");

        // union
        $expect = array_unique(array_merge($a1, $a2, $a3));
        sort($expect);
//        $this->testMergeTestShouldReturnExpectedData("-o -u -n", $a1, $a2, $a3, $expect, "or numeric unique");
        $this->assertEquals($expect, $this->_runQmerge("-o -u -n", $file1, $file2, $file3, $outfile), "or numeric unique");

        // intersection
        $expect = array_unique(array_intersect($a1, $a2, $a3));
        sort($expect);
//        $this->testMergeTestShouldReturnExpectedData("-a -u -n", $a1, $a2, $a3, $expect, "and numeric unique");
        $this->assertEquals($expect, $this->_runQmerge("-a -u -n", $file1, $file2, $file3, $outfile));

        // difference
        $expect = array_unique(array_diff($a1, $a2, $a3));
        sort($expect);
//        $this->testMergeTestShouldReturnExpectedData("-v -u -n", $a1, $a2, $a3, $expect, "not numeric unique");
        $this->assertEquals($expect, $this->_runQmerge("-v -u -n", $file1, $file2, $file3, $outfile));
    }

    protected function _runQmerge( $switches, $file1, $file2, $file3, $outfile ) {
        $cmdline = TEST_ROOT . "/../bin/qmerge $switches $file1 $file2 $file3 > $outfile";
        $str = `$cmdline`;
        return array_map('trim', file($outfile));
    }

    protected function _makeTempfile( $data ) {
        $temp = new Quick_Test_Tempfile();
        file_put_contents($temp, $data ? implode("\n", $data)."\n" : "");
        return $temp;
    }
}
