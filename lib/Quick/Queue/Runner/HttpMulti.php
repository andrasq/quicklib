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
    protected $_multi;
    protected $_ch = array();
    protected $_donech = array();

    public function __construct( $method, $url ) {
        $this->_method = $method;
        $this->_url = $url;
        $this->_insertJobtype = strpos($url, '{JOBTYPE}') !== false;
        $this->_insertData = strpos($url, '{DATA}') !== false;
        $this->_editUrlData = $this->_insertJobtype || $this->_insertData;
        $this->_postData = ($method !== 'GET');

        // reuse the curl_multi handle for all calls, it reuses the open connections!
        $this->_multi = new Quick_Rest_Call_CurlMulti($mh = curl_multi_init());
        $this->_multi->setTimeout(.0005); // almost non-blocking
        $this->_multi->setWindowSize(5);
    }

    public function isDone( ) {
        return $this->_isDone;
    }

    public function runBatch( $jobtype, Quick_Queue_Batch $batch ) {
        $this->_batch = $batch;
        $this->_batch->startTm = microtime(true);
        $this->_jobtype = $jobtype;

        foreach ($batch->jobs as $jobKey => $data) {
            $handles[] = $this->_ch[$jobKey] = $this->_createCurlRequest($data);
        }
        $this->_multi->addHandles($handles);

        $this->_multi->exec();
        $this->_isDone = false;
        $this->_isError = false;
        return true;
    }

    public function getDoneJobtypes( ) {
        if (!$this->_multi->exec())
            return array();

        $this->_batch->runtime = microtime(true) - $this->_batch->startTm;
        $this->_isDone = true;
        return array($this->_jobtype);
    }

    public function getDoneBatch( $jobtype ) {
        if ($this->_isDone) {
            $runtime = sprintf("%.6f", $this->_batch->runtime / $this->_batch->count);
            $results = array();
            foreach ($this->_ch as $jobKey => $ch) {
// FIXME: act on isError!
                $output = $this->_multi->getDoneContent($ch);
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
            }
            $this->_multi->getDoneHandles();

            $this->_donech += $this->_ch;
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
        if ($this->_editUrlData) {
            $url = $this->_url;
            if ($this->_insertJobtype)
                $url = str_replace("{JOBTYPE}", urlencode($this->_jobtype), $url);
            if ($this->_insertData)
                $url = str_replace("{DATA}", urlencode($data), $url);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        // else same url, same data as before
        return $ch;
    }
}
