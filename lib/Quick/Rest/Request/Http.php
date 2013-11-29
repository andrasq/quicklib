<?

/**
 * REST request, conveys the request params to the call handler.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Request_Http
    implements Quick_Rest_Request
{
    protected $_protocol = 'HTTP/1.0', $_method = 'GET', $_path, $_query;
    protected $_params = array(), $_server, $_rawPostData, $_files;

    public function setParamsFromGlobals( & $globals ) {
        // extract http protocol, method and uri path from the globals
        // note: cookies, templated pathname vars not addressed here
        $server = & $globals['_SERVER'];
        if (isset($server['SERVER_PROTOCOL'])) $this->_protocol = $server['SERVER_PROTOCOL'];
        if (isset($server['REQUEST_METHOD'])) $this->_method = $server['REQUEST_METHOD'];

        $this->_server = $server;
        if ($this->_method === 'POST' && isset($globals['HTTP_RAW_POST_DATA']))
            $this->_rawPostData = $globals['HTTP_RAW_POST_DATA'];

        if (isset($globals['_FILES'])) $this->_files = $globals['_FILES'];

        $this->_path = $this->_query = null;

        $this->_params = $globals['_GET'] + $globals['_POST'];
        return $this;
    }

    public function getMethod( ) {
        return $this->_method;
    }

    public function getPath( ) {
        if (empty($this->_path)) {
            $server = $this->_server;
            // path_info is set to the path "/foo/bar" appended to script_name "index.php/foo/bar"
            if (!empty($server['PATH_INFO']))
                $this->_path = $server['PATH_INFO'];
            elseif (isset($server['REQUEST_URI']))
                $this->_path = substr(($s = $server['REQUEST_URI']), 0, strcspn($s, '?'));
            elseif (isset($server['SCRIPT_NAME']))
                $this->_path = $server['SCRIPT_NAME'];
            else
                $this->_path = "/";
        }
        return $this->_path;
    }

    public function getParam( $name ) {
        return isset($this->_params[$name]) ? $this->_params[$name] : null;
    }

    public function requireParam( $name ) {
        if (isset($this->_params[$name]) && (string)$this->_params[$name] > '')
            return $this->_params[$name];
        else
            throw new Quick_Rest_Exception("required parameter ``$name'' missing or empty");
    }

    public function setParam( $name, $value ) {
        $this->_params[$name] = $value;
        return $this;
    }

    // return the value of each named param or null if missing; return all params if no names
    // allows eg:  list($a, $b, $c) = $request->getParams(array('a', 'b', 'c'), $missing = array());
    public function getParams( Array $names = array(), Array & $missing = null ) {
        $ret = array();
        $missing = array();

        if (! $names) return $this->_params;

        foreach ($names as $name) {
            if (isset($this->_params[$name]) || array_key_exists($name, $this->_params))
                $ret[$name] = $this->_params[$name];
            else {
                $ret[$name] = null;
                $missing[] = $name;
            }
        }
        return $ret;
    }

    // check that all required parameters are present
    public function checkRequiredParams( Array $required ) {
        return array_diff_key($required, $this->_params) ? false : true;
    }

    public function getUnknownParams( Array $required, Array $optional ) {
        return array_diff_key($this->_params, $required, $optional);
    }

    public function getRequestQueryString( ) {
        if (isset($this->_server['QUERY_STRING']))
            return $this->_server['QUERY_STRING'];
        elseif (isset($this->_server['REQUEST_URI']) && ($p = strpos($this->_server['REQUEST_URI'], '?')))
            return substr(($s = $this->_server['REQUEST_URI']), $p + 1);
        else
            return "";
    }

    public function getPostBody( ) {
        if (isset($this->_rawPostData))
            return $this->_rawPostData;
        else
            return file_get_contents("php://input");
    }

    public function getUploadFilepaths( ) {
        $ret = array();
        if ($this->_files) foreach ($this->_files as $name => $info) {
            if ($info['error']) continue;
            $ret[$name] = $info['tmp_name'];
        }
        return $ret;
    }

    public function getCombinedQueryString( ) {
        if (empty($this->_query)) {
            $this->_query = $this->getRequestQueryString();
            if ($this->_method === 'POST') {
                $this->_query .= ($this->_query > '' ? '&' : '') . $this->getPostBody();
            }
        }
        return $this->_query;
    }

    public function getHeaders( $name = null ) {
        if ($name !== null) {
            $key = 'HTTP_' . str_replace(" ", "_", strtoupper(str_replace("-", " ", $name)));
            return isset($this->_server[$key]) ? $this->_server[$key] : null;
        }
        $hdr = array();
        foreach ($this->_server as $name => $value) {
            if (strncmp($name, 'HTTP_', 5) === 0) {
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($name, 5)))));
                $hdr[$key] = $value;
                if ($name === $key) return $value;
            }
        }
        return $hdr;
    }
}