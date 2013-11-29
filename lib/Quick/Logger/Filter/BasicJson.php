<?

/**
 * Basic JSON logging filter
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 */

class Quick_Logger_Filter_BasicJson
    implements Quick_Logger_Filter
{
    protected $_template, $_startTm;

    public function __construct( Array $template, $startTm = null ) {
        $this->_template = $template;
        $this->_startTm = $startTm;
    }

    public function filterLogline( $message, $method ) {
        $info = $this->_template;

        // we set timestamp and message, and optionally level and duration; others are preset in template
        $info['timestamp'] = $tm = microtime(true);
        if (is_array($message))
            $info = array_merge($info, $message);
        else
            $info['message'] = $message;
        if (isset($info['level'])) $info['level'] = $method;
        if (isset($info['duration'])) $info['duration'] = $tm - $this->_startTm;

        return json_encode($info) . "\n";
    }
}
