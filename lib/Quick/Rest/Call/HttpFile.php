<?php

/**
 * Http protocol REST caller that captures the output into a file.
 * Supports arbitrarily long streaming responses.
 *
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Call_HttpFile
    extends Quick_Rest_Call_Http
    implements Quick_Rest_Call
{
    protected $_replyFile;

    public function __construct( $replyFile, $url = null, $method = 'GET', $methodArg = null ) {
        $this->_replyFile = $replyFile;
        parent::__construct($url, $method, $methodArg);
    }

    public function setReplyFile( $file ) {
        $this->_replyFile = $file;
        return $this;
    }


    public function getReply( ) {
        return file_get_contents($this->_replyFile);
    }

    public function getContentOffset( ) {
        // proxy servers can add header lines, so cannot rely on _curlinfo['header_size']
        clearstatcache();
        @$size = filesize($this->_replyFile);
        if ($size === false) throw new Quick_Rest_Exception("$this->_replyFile: unable to determine file size");
        return $size - $this->getContentLength();
    }

    // getContentLength is inherited from parent

    public function getReplyHeader( ) {
        return `/usr/bin/head -c {$this->getContentOffset()} $this->_replyFile`;
    }

    public function getContentFile( $filename ) {
        $offset = $this->getContentOffset();

        @$rfp = fopen($this->_replyFile, "r");
        if (!$rfp) throw new Quick_Rest_Exception("$this->_replyFile: unable to open file for reading");
        @$wfp = fopen($filename, "w");
        if (!$wfp) throw new Quick_Rest_Exception("$filename: unable to open file for writing");
        fseek($rfp, $offs = $this->getContentOffset(), SEEK_SET);
        $nb = 0;
        while (($buf = fread($rfp, 20480)) > "") {
            $nb = fwrite($wfp, $buf);
            if ($nb === false) break;
        }
        if ($buf === false) throw new Quick_Rest_Exception("$this->_replyFile: error reading file");
        if ($nb === false) throw new Quick_Rest_Exception("$filename: error writing file");
        return $this;
    }

    // synthesize a header to match the body
    protected function _makeHeaderForReplyData( ) {
        $date = date("D, d M Y H:i:s T");
        $header =
"HTTP/1.1 {$this->_curlinfo['http_code']} -\r
Date: $date\r
Content-Length: {$this->_curlinfo['download_content_length']}\r
Content-Type: {$this->_curlinfo['content_type']}\r
X-Synthesized-By: Rest_Call_Http\r
\r"
        ;
    }


    protected function _runCall( ) {
        $ch = $this->_curlConfigure();
        curl_setopt_array($ch, array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => false,
        ));
        $this->_reply = $this->_curlRun($ch);
        curl_close($ch);
    }

    protected function & _curlRun( $ch ) {
        ob_start(array($this, '_appendReplyFile'), 20480);
        $reply = & $this->_curlExec($ch, 10);
        ob_end_flush();

        if ($reply === false)
            throw new Quick_Rest_Exception("rest error calling $this->_url: " . curl_error($ch) . " (errno " . curl_errno($ch) . ")");
        return $reply;
    }

    // callback to chunk and save curl reply, for large streaming datasets
    public function _appendReplyFile( $buf ) {
        $nb = file_put_contents($this->_replyFile, $buf, FILE_APPEND);
        if ($nb === false) throw new Quick_Rest_Exception("$this->_replyFile: error writing file");
    }
}
