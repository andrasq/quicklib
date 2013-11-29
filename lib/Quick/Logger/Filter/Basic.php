<?

/**
 * Basic logging filter, timestamps the line and tags with the message reporting level.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 *
 * 2013-02-09 - AR.
 */

class Quick_Logger_Filter_Basic
    implements Quick_Logger_Filter
{
    protected $_tag;
    protected $_dateFormat = '';
    protected $_msecFormat = ".%03d ";
    protected $_last_tm = 0, $_last_dt;

    public function __construct( $tag = '', $dateFormat = 'Y-m-d H:i:s' ) {
	$this->_tag = $tag . ($tag > '' ? ' ' : '');
        $this->_dateFormat = $dateFormat;
        if (($p = strpos($dateFormat, "s") === false) || ($p > 0 && $dateFormat[$p-1] === '\\'))
            $this->_msecFormat = ($dateFormat == '' ? '' : ' ');
    }

    public function filterLogline( $msg, $method ) {
	// date() is slow, reuse the old timestamp string if possible
        // how slow you ask?  10x slower; filtering drops from 240k lines/sec to 24k
        $now_tm = microtime(true);
        if (($delta = ($now_tm - $this->_last_tm)) > 1) {
            $this->_last_tm = (int)$now_tm;
            $this->_last_dt = date($this->_dateFormat, $now_tm);
            $delta -= (int)$delta;
        }
        $msec = sprintf($this->_msecFormat, $delta * 1000);

	// 300k lines/sec filtered (252k if msec is computed), 245k w/ msec, 240k w/ tag too
	return "{$this->_last_dt}{$msec}{$this->_tag}[{$method}] $msg";
    }
}
