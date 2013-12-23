<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Archiver_Base
    implements Quick_Queue_Archiver
{
    public function archiveJobResults( $jobtype, Array $jobdatasets, Array $jobresults ) {
        $tm = microtime(true);
        $dt = date("Y-m-d H:i:s") . substr(sprintf("%.6f", ($tm - (int)$tm)), 1);
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
