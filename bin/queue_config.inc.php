<?php

global $queueConfig;
$queueConfig = array(
    'queuedir' => "/var/run/qqueue",
    'jobsdir' => "/var/run/qqueue/jobs",
    'jobs' => array(
        '__default' => array(
            // default settings
            //'runner' => '(undefined_runner)',
            //'runner' => "!/bin/cat",
            //'runner' => "!usleep 100000; /bin/cat",

            // 'runner' => "!/usr/bin/awk '{print \"{\\\"x\\\":\" $0 \"}\"}'",
            // 340/s 1x1, 1300 4x1, 2900/s 10x1, 15000/s 10x10 20x4, 20800/s 100x1, 38400/s 100x4 500x2 500x4 500x10, 47000/s 2000x4
            // w/ universal + taskset 1: 350/s 1x1, 500/s 1x2 5x2, 3200/s 10x1, **7900/s 10x10 (!?), 21000/s 100x1, 34500/s 100x4, 45000/s 2000x4

            'runner' => "http://localhost:80/pp/aradics/echo.php?op=echo&jobtype={JOBTYPE}&data={DATA}",
            // 1jt: 430/s 1x1, 6800,4400/s 4x1, 7100 1x4, 3200/s 10x1, 11000/s 100x1, 10600 10x4, 13000/s 100x4
            // with CurlMulti can be pushed to 13k/s with setWindowSize(20) 100x4.  Window=5 yields 10.5k/s 50x1
            // note: with CurlMulti win=5 (reusing connection): 3400/s 1x1, 8700/s 10x1, 12000/s 100x1
            // 10jt: 6500/s 1x1, 7700/s 4x1, 6500 1x4, 10800 3x4, 7400 4x4, 8300 2x8, 6600 1x16
            // 2.25% slower w/ universal runner: 425/s 1x1, 3500/s 10x1, 11500/s 40x1, BUT 20/s 100x1
            // Any jobtype can use a generic {JOBTYPE} runner, no need to configure a job runner
            // note: time both w/ and w/o taskset 1; sometimes one is faster, sometimes the other

            'batchsize' => 1,
            'batchlimit' => 1,
        ),
        'jobtype1' => array(
            // 'runner' => "!/usr/bin/awk '{print \"{\\\"x\\\":\" $0 \"}\"}'",
            // Any jobtype can use a generic {JOBTYPE} runner, no need to configure a job runner
        ),
        'jobtype2' => array(
        ),
        'jobtype3' => array(
        ),
        'jobtype4' => array(
        ),
        'jobtype5' => array(
        ),
        'jobtype6' => array(
        ),
        'jobtype7' => array(
        ),
        'jobtype8' => array(
        ),
        'jobtype9' => array(
        ),
        'jobtype10' => array(
        ),
    ),
);
