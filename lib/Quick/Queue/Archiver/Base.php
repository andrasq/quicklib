<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Archiver_Base
    implements Quick_Queue_Archiver
{
    public function archiveJobResults( $jobtype, Array $jobdatasets, Array $jobresults ) {
        static $last_time, $last_date;
        static $dt;
        $tm = microtime(true);
        if ((int)$tm != $last_time) {
            $last_time = (int)$tm;
            $last_date = date("Y-m-d H:i:s", $tm);
        }
        $dt = $last_date . substr(($tm - (int)$tm), 1, 7);
        $rows = array();
        foreach ($jobresults as $key => $result) {
            $rows[] = json_encode(array(
                'jobtype' => $jobtype,
                'date' => $dt,
                'data' => $jobdatasets[$key],
                'results' => $result,
            )) . "\n";
        }
        $this->_archive($rows);
    }
}
