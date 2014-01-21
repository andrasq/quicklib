<?

/**
 * Batch runner that makes multiple http calls to the same url in parallel.
 * Can be reused for subsequent calls to the same url.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Runner_HttpMulti
{
    protected $_method;
    protected $_url;
    protected $_batch;
    protected $_isDone = false;
    protected $_isError = false;
    protected $_mh;
    protected $_ch = array();
    protected $_donech = array();

    public function __construct( $method, $url ) {
        $this->_method = $method;
        $this->_url = $url;
        $this->_editUrlData = strpos($url, '{DATA}') !== false;
        $this->_postData = ($method !== 'GET');
    }

    public function isDone( ) {
        return $this->_isDone;
    }

    public function runBatch( $jobtype, Quick_Queue_Batch $batch ) {
        $this->_batch = $batch;
        $this->_batch->startTm = microtime(true);
        $this->_jobtype = $jobtype;
        // reuse the curl_multi handle for all calls, it reuses the open connections!
        if (!$this->_mh) $this->_mh = curl_multi_init();
        $mh = $this->_mh;
        foreach ($batch->jobs as $jobKey => $data) {
            $ch = $this->_ch[$jobKey] = $this->_createCurlRequest($data);
            curl_multi_add_handle($mh, $ch);
        }
        curl_multi_exec($mh, $running);
        $this->_isDone = false;
        $this->_isError = false;
        return true;
    }

    public function getDoneJobtypes( ) {
        if (!$this->_isDone) {
            $rv = curl_multi_exec($this->_mh, $running);
            // there is an obscure curl_multi bug that people work around;
            // exec can return CURLM_OK even though it has not finished.
            // The fixes test $running in addition to the call return value.
            // See the implementation in eg https://bugs.php.net/bug.php?id=42300
            if ($rv === CURLM_CALL_MULTI_PERFORM || $rv === CURLM_OK && $running)
                return array();
            if ($rv !== CURLM_OK) $this->_isError = $rv;
            $this->_batch->runtime = microtime(true) - $this->_batch->startTm;
            $this->_isDone = true;
        }
        return array($this->_jobtype);
    }

    public function & getDoneBatch( $jobtype ) {
        if ($this->_isDone) {
            $runtime = sprintf("%.6f", $this->_batch->runtime / $this->_batch->count);
            $mh = $this->_mh;
            $results = array();
            foreach ($this->_ch as $jobKey => $ch) {
// FIXME: act on isError!
                $output = curl_multi_getcontent($ch);
                if (($code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) < 200 || $code >= 300) {
                    $results[$jobKey] = array(
                        'status' => Quick_Queue_Runner::RUN_ERROR,
                        'runtime' => $runtime,
                        'stats' => curl_getinfo($ch),
                        'output' => $output,
                    );
                }
                // task results are always an array
                elseif ($output[0] === '{' && ($json = json_decode($output, true))) {
                    $json['status'] = 0;
                    $json['runtime'] = $runtime;
                    $results[$jobKey] = $json;
                }
                else {
                    $results[$jobKey] = array(
                        'status' => 0,
                        'runtime' => $runtime,
                        'output' => $output,
                    );
                }
                curl_multi_remove_handle($mh, $ch);
            }
            $this->_donech += $this->_ch;
            // keep $ch and $mh open for reuse next time, both faster and does not leak sockets
            $this->_ch = array();
            $this->_batch->results = & $results;
            return $this->_batch;
        }
        // null if not yet done
        return null;
    }

    public function _createCurlRequest( $data ) {
        if (!empty($this->_donech)) {
            $ch = array_pop($this->_donech);
        }
        else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: text/plain",
            ));
            curl_setopt($ch, CURLOPT_URL, $this->_url);
            if ($this->_postData) curl_setopt($ch, CURLOPT_POST, true);
        }
        if ($this->_postData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, urlencode($data));
        }
        elseif ($this->_editUrlData) {
            curl_setopt($ch, CURLOPT_URL, str_replace('{DATA}', urlencode($data), $this->_url));
        }
        // else same url, same data as before
        return $ch;
    }
}
