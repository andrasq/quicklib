<?

/**
 * Http protocol REST caller.
 * Basically wrappers curl_exec.
 *
 * Copyright (C) 2013-2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Call_Http
    implements Quick_Rest_Call
{
    protected $_profiler;
    protected $_method, $_methodArg;
    protected $_url, $_urlParts = array();
    protected $_params = array(), $_reply = "";
    protected $_timeout = 600;
    protected $_connectTimeout = 5.0;
    protected $_headers = array();
    protected $_curlConfig = array();
    protected $_curlinfo;
    protected $_replyFile = false;

    public function __construct( $url = null, $method = 'GET', $methodArg = null ) {
        if ($url !== null) $this->setUrl($url);
        $this->setMethod($method, $methodArg);
    }

    public function setProfiling( Quick_Data_Datalogger $profiler = null ) {
        $this->_profiler = $profiler;
        return $this;
    }

    public function setReplyFile( $file ) {
        $this->_replyFile = $file;
        return $this;
    }

    public function setMethod( $method, $methodArg = null ) {
        $this->_method = strtoupper($method);
        $this->_methodArg = $methodArg;
        return $this;
    }

    public function setHeader( $name, $value ) {
        $this->_headers[$name] = "$name: $value";
        return $this;
    }

    public function getHeader( $name ) {
        if (!isset($this->_headers[$name])) return null;
        return substr($this->_headers[$name], strpos($this->_headers[$name], ": ")+2);
    }

    public function setParam( $name, $value ) {
        $this->_params[$name] = $value;
        return $this;
    }

    public function getParam( $name = null ) {
        if ($name === null) return $this->_params;
        return isset($this->_params[$name]) ? $this->_params[$name] : null;
    }

    public function setUrl( $url ) {
        if (strpos($url, "://") === false)
            $url = "http://$url";

        // http://username:password@hostname:portnum/path?arg=value#anchor
        // (([^:]+)://)? (([^:@]+)(:([^@]+))?@)? ([^:/]+)(:([0-9]+))? ([^?]+) (\?([^#]*))? (#(.*))? ($)
        // NOTE: #anchor must follow args
        try { $parts = parse_url($url); } catch (Exception $e) { $parts = array(); }
        if (empty($parts)) throw new Quick_Rest_Exception("unable to decode url $url");

        // the full list of possible fields created by parse_url():
        static $emptyParts = array(
            'scheme' => "", 'host' => "", 'port' => "", 'user' => "", 'pass' => "",
            'path' => "", 'query' => "", 'fragment' => ""
        );

        $parts += $emptyParts;
        $this->_url = $url;
        $this->_urlParts = $parts;

        return $this;
    }

    public function call( ) {
        $this->_reply = null;
        switch ($this->_method) {
        case 'SCRIPT':
            if (!$this->_methodArg) throw new Quick_Rest_Exception("SCRIPT call has no script runner defined");
            $call = new Quick_Rest_Call_Script();
            $call->setMethod($this->_method, $this->_methodArg);
            $call->setUrl($this->_url);
            $call->setParams($this->_params);
            $this->_reply = $call->call();
            if ($this->_replyFile) file_put_contents($this->_replyFile, $this->_reply);
            break;
        case 'GET':
        case 'POST':
        case 'POSTFILE':
        case 'PUT':
        case 'PUTFILE':
        case 'DELETE':
        case 'HEAD':
        case 'UPLOAD':
            $this->_runCall($this->_method, $this->_url, $this->_params);
            break;
        default:
            throw new Quick_Rest_Exception("$this->_method: unsupported http call method");
            break;
        }
        return $this;
    }

    public function getReply( Quick_Rest_Reply $reply = null ) {
        if ($this->_replyFile)
            return file_get_contents($this->_replyFile);
        elseif ($this->_reply !== false && $this->_reply !== null)
            return $this->_reply;
    }

    public function getReplyFile( ) {
        return $this->_replyFile;
    }

    public function getHeaderSize( ) {
        // proxy servers can add header lines, so cannot rely on _curlinfo['header_size']
        if ($this->_replyFile) {
            clearstatcache();
            $size = filesize($this->_replyFile);
            return ($size - $this->_curlinfo['download_content_length']);
        }
        elseif ($this->_reply) {
            // @NOTE: mb_str overloading will break strlen and substr
            $len = strlen($this->_reply);
            return $len - $this->_curlinfo['download_content_length'];
        }
    }

    public function getReplyHeader( ) {
        if ($this->_replyFile) {
            return file_get_contents($this->_replyFile, false, NULL, 0, $this->getHeaderSize());
        }
        elseif ($this->_reply) {
            // @NOTE: mb_str overloading will break strlen and substr
            return substr($this->_reply, 0, -$this->_curlinfo['download_content_length']);
        }
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

    public function setCurlConfig( $name, $value ) {
        $this->_curlConfig[$name] = $value;
        return $this;
    }

    protected function _runCall( $method, $url, $params ) {
        if ($params && $method !== 'POST')
            $url = $this->_appendParamsToUrl($url, $params);

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_HEADER => true,
            //CURLOPT_TIMEOUT => $this->_timeout,
            CURLOPT_CONNECTTIMEOUT_MS => 1000 * $this->_connectTimeout,
            CURLOPT_FOLLOWLOCATION => (bool)$this->_replyFile,
            CURLOPT_RETURNTRANSFER => !$this->_replyFile,
        ));
        if ($this->_curlConfig) curl_setopt_array($ch, $this->_curlConfig);

        // each set of CURLOPT_HTTPHEADERS unsets the previous, so send all headers together
        $headers = $this->_headers;

        switch ($method) {
        case 'GET':
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            break;
        case 'UPLOAD':
            // This works, uses an Expect: 100-Continue handshake; $_FILES has uploaded file info
            // the argument keys become the keys to the $_FILES info arrays
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array("$this->_methodArg" => "@{$this->_methodArg}"));
            break;
        case 'PUTFILE':
        case 'POSTFILE':
            // PUT or POST the file contents in the request.
            // Unlike UPLOAD, php will deliver the data in the call body, not in a tempfile
            curl_setopt($ch, ($method === 'PUTFILE' ? CURLOPT_PUT : CURLOPT_POST), 1);
            curl_setopt($ch, CURLOPT_INFILE, $fp = @fopen($this->_methodArg, "r"));
            if (!$fp) throw new Quick_Rest_Exception("$this->_methodArg: unable to read file");
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($this->_methodArg));
            $headers[] = "Content-Length: " . filesize($this->_methodArg);
            break;
        case 'PUT':
        case 'POST':
            // PUT is like POST but used to update the value of an entity
            if (isset($this->_methodArg)) {
                // if body is provided, pass any provided params in the url and send the body in the body
                $body = $this->_methodArg;
                if ($params) curl_setopt($ch, CURLOPT_URL, $url = $this->_appendParamsToUrl($url, $params));
            }
            else {
                // if only params provided, pass them in the post body
                $body = http_build_query($params);
            }
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body > '' && substr_compare($body, "\n", -1) !== 0) $body .= "\n";
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            break;
        default:
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            break;
        }

        if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($this->_replyFile) ob_start(array($this, '_appendReplyFile'), 20480);
        $this->_reply = $this->_curlExec($ch, 10);
        if ($this->_replyFile) ob_end_flush();

        if ($this->_reply === false)
            throw new Quick_Rest_Exception("rest error calling $this->_url: " . curl_error($ch) . " (errno " . curl_errno($ch) . ")");
        curl_close($ch);
        return true;
    }

    protected function _curlExec( $ch, $maxRedirects ) {
        while (--$maxRedirects >= 0) {
            $reply = curl_exec($ch);
            $info = curl_getinfo($ch);
            if ($this->_profiler) {
                $this->_profiler->logData(array(
                    'url' => $info['url'],
                    'duration' => sprintf("%.6f", $info['total_time']),
                    'http_code' => $info['http_code'],
                    'namelookup_time' => sprintf("%.6f", $info['namelookup_time']),
                    'connect_time' => sprintf("%.6f", $info['connect_time']),
                    'pretransfer_time' => sprintf("%.6f", $info['pretransfer_time']),
                    'starttransfer_time' => sprintf("%.6f", $info['starttransfer_time']),
                    'redirect_time' => sprintf("%.6f", $info['redirect_time']),
                    'total_time' => sprintf("%.6f", $info['total_time']),
                    'size_upload' => $info['size_upload'],
                    'speed_upload' => $info['speed_upload'],
                    'size_download' => $info['size_download'],
                    'speed_download' => $info['speed_download'],
                    // not all versions of curl have these:
                    'primary_ip' => isset($info['primary_ip']) ? $info['primary_ip'] : '',
                    'primary_port' => isset($info['primary_port']) ? $info['primary_port'] : '',
                ));
            }
            // curl_exec returns false on error, true if sent output to stdout
            if ($reply === false) return false;
            if ($reply === true) $reply = "";
            $this->_curlinfo = & $info;
            $status = isset($info['http_code']) ? $info['http_code'] : 0;
            // if redirected with MovedPermanently (301) or Found (302), try the new location
            if ($status != 301 && $status != 302) {
                if (strncmp($reply, "HTTP/1.1 100 Continue", 20) == 0) {
                    // presume that the 100 Continue response is a single line followed by a newline
                    $e1 = strpos($reply, "\n");
                    $e2 = strpos($reply, "\n", $e1+1);
                    $reply = substr($reply, $e2+1);
                }
                return $reply;
            }
            curl_setopt($ch, CURLOPT_URL, $info['redirect_url']);
        }
        // too many redirects
        return $reply;
    }

    protected function _appendParamsToUrl( $url, $params ) {
        $url .=
            ((strpos($url, '?') === false) ? '?' : '&') .
            (is_array($params) ? http_build_query($params) : $params);
        return $url;
    }

    // callback to chunk and save curl reply, for large streaming datasets
    public function _appendReplyFile( $buf ) {
        file_put_contents($this->_replyFile, $buf, FILE_APPEND);
    }
}
