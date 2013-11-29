<?

/**
 * Loggers append messages (lines) to their data stream.
 * Each message can be an arbitrary string.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     Quicklib
 *
 * Some loggers newline-terminate all messages, others allow
 * arbitrary strings to be appended.
 *
 * The string may be modified by filters, which are run before logging.
 * Multiple filters may be specified, they are run in the order added.
 * Logging non-strings is possible if one of the filters serializes it.
 *
 * 2013-02-17 - AR.
 */

interface Quick_Logger
{
    // log levels same as PEAR::Log and syslog(2)
    const DEBUG = 7;
    const INFO = 6;
    const ERR = 3;

    public function info($msg);
    public function debug($msg);
    public function err($msg);
    public function addFilter(Quick_Logger_Filter $filter);
}
