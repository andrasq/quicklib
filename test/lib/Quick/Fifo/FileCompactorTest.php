<?

/**
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Fifo_FileCompactorTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_tempfile = new Quick_Test_Tempfile();
        $this->_cut = new Quick_Fifo_FileCompactor($this->_tempfile);
    }

    public function compactionRangeProvider( ) {
        // run with chunkSize = 3
        return array(
            array("abcdef", 0, 0, "abcdef"),            // zero chars at zero offset
            array("abcdef", 100, 0, "abcdef"),          // past end of string
            array("abcdef", 1, 0, "abcdef"),            // zero chars at positive offset
            array("abcdef", -1, 0, "abcdef"),           // zero chars at negative

            array("", 0, 1, ""),
            array("", 1, 1, ""),
            array("", -1, 1, ""),

            array("abcdef", 1, 1, "acdef"),             // inside first chunk
            array("abcdef", 4, 1, "abcdf"),             // inside second chunk
            array("abcdef", 0, 2, "cdef"),              // inside first chunk
            array("abcdef", 1, 2, "adef"),              // inside first chunk
            array("abcdef", 2, 2, "abef"),              // straddling chunks
            array("abcdef", 0, 4, "ef"),                // straddling chunks
            array("abcdef", 1, 4, "af"),                // straddling chunks
            array("abcdefghi", 0, 8, "i"),              // straddling three chunks
            array("abcdefghi", 1, 7, "ai"),             // straddling three chunks

            array("abcdef", -1, 1, "bcdef"),            // clipped by origin
            array("abcdef", -3, 1, "bcdef"),            // clipped by origin
            array("abcdef", 1, 400, "a"),               // clipped by end
            array("abcdef", -2, 4, "ef"),               // clipped by origin
            array("abcdef", -2, 100, ""),

            array("abcdef", 100, 4, "abcdef"),          // past end of string
            array("abcdef", 100, 500, "abcdef"),        // past end of string
            array("abcdef", -100, 500, ""),             // clipped by both ends, whole string

            // large data, tested with actual chunk size used (20k)
            array(str_repeat("abc", 100000), 10, 2, "abcabcabca" . str_repeat("abc", 100000-4)),
            array(str_repeat("abc", 100000), 10, 2+3*10000, "abcabcabca" . str_repeat("abc", 100000-4-10000)),
        );
    }

    /**
     * @dataProvider    compactionRangeProvider
     */
    public function testCompactShouldProcessRangeAndGetExpectedResults( $data, $offset, $length, $expect ) {
        file_put_contents($this->_tempfile, $data);
        if (strlen($data) < 100) $this->_cut->setChunkSize(3);
        $this->_cut->compact($offset, $length);
        $this->assertEquals($expect, file_get_contents($this->_tempfile));
    }
}
