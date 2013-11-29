<?

/**
 * Db that tags all queries with "label file(line)" for visibility.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Decorator_QueryTagger
    implements Quick_Db
{
    protected $_db;
    protected $_commentStart = " /* ";          // "\n-- " for generic sql
    protected $_commentEnd = " */";             // "\n" for generic sql

    public function __construct( Quick_Db $db, $label = "" ) {
        $this->_db = $db;
        $this->_label = $label;
    }

    public function query( $sql, $tag = '' ) {
        return $this->_db->query($this->_tagSql($sql));
    }

    public function select( $sql, Array $values = null ) {
        return $this->_db->select($this->_tagSql($sql), $values);
    }

    public function execute( $sql, Array $values = null ) {
        return $this->_db->execute($this->_tagSql($sql), $values);
    }

    public function getQueryInfo( ) {
        return $this->_db->getQueryInfo();
    }

    protected function _tagSql( $sql ) {
        $trace = debug_backtrace(false);                                        // php 5.2.5, omit objects
        // $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);                  // php 5.3.6, omit args too
        // $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);              // php 5.4, omit args and show last 10 calls

        // $trace is an array of stack frame summaries.
        // Each frame has the {$file} and {$line} of the caller
        // and the {$class}{$type}{$function} of the function being called.

        // {$object} is the full object being called, and {$args} is the func_get_args() array that was passed.
        // The frame may contain as little as file/line/function/args, and occasionally not even function
        // debug_backtrace() omits from the stack frames the current function that actually called it
        // (ie, the first entry in the array is the caller of _tagSql())

        // scanning the backtrace slows us to 41k tags/sec, and this is inside the unit tests
        $depth = count($trace);
        for ($i=0; $i<$depth; ++$i) {
            if (strncmp($trace[$i]['class'], 'Quick_Db', 8) === 0) $entry = $i;
            else break;
        }
        // on exit $entry points to the top-level Quick_Db class called, and the file(line) that called it

        // tag the sql with the file(line) of where the Quick_Db method was called from
        $tag = $trace[$entry]['file'] . "(" . $trace[$entry]['line'] . ")";

        return $sql . $this->_commentStart . $this->_label . " " . $tag . $this->_commentEnd;
    }
}
