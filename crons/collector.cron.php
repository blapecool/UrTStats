<?php
/*
    UrTStats - ~/crons/collector.cron.php

    Copyright (c) 2013 Blapecool (Blapecool AT gmail D0T com)

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.
    
    Query all servers and build awesome stats :D
    4 Steps :
        - 1 : Get list of UrT servers
        - 2 : Dispatch list on workers
        - 3 : Wait for them
        - 4 : Save all our cool stuff in RRD files (+ json for latest data only)
*/
define('START', microtime(true));

define("ROOT_DIR", dirname(__FILE__));
define("DATA_DIR", ROOT_DIR."/../data/");

require ROOT_DIR.'/libs/q3status.class.php';

$conf = parse_ini_file(DATA_DIR ."conf.ini", true);
$knownServers = array();

foreach ($conf['collector']['plugins'] as $pluginName) 
    require ROOT_DIR . '/plugins/'.$pluginName.'.collector.php';

$db = new mysqli($conf['mysql']['address'], $conf['mysql']['user'], $conf['mysql']['pass'], $conf['mysql']['db']);

// Step 1 
$result = $db->query("SELECT  `server_id`, `server_address`, `server_fails`, `server_lastFail` FROM `stats_serverlist` WHERE `server_disabled` = 0");
while($data = $result->fetch_assoc()){

    $knownServers[] =  array( 'id' => $data['server_id'],
                              'address' => $data['server_address'],
                              'fails' => $data['server_fails'],
                              'lastFail' => $data['server_lastFail']);
}
$result->free();

shuffle($knownServers);

// Step 2
$serversPerWorker = ceil( count($knownServers)/$conf['collector']['workers'] );
$workerServers = array_chunk($knownServers, $serversPerWorker);
$workingWorkers = array();

foreach ($workerServers as $id => $serverList) {
    if(file_exists(ROOT_DIR."/slots/".$id."/finish"))
        unlink(ROOT_DIR."/slots/".$id."/finish");

    file_put_contents(ROOT_DIR."/slots/".$id."/serverList.json", json_encode($serverList));
    shell_exec("php ".ROOT_DIR."/collector.worker.php ".$id." >> /dev/null &");

    $workingWorkers[] =  $id;
}


// Step 3
while(count($workingWorkers))
{
    foreach ($workingWorkers as $key => $id) {
        if(file_exists(ROOT_DIR."/slots/".$id."/finish"))
            unset($workingWorkers[$key]);
    }
    sleep(1);
}


// Step 4
foreach ($conf['collector']['plugins'] as $pluginName)  {
    $funcName = $pluginName."_statify";

    if(function_exists($funcName))
        $funcName($conf['collector']['workers']);
}

// Step 999 - Save processing time for monitoring
define('END', microtime(true));
$runTime = END - START;

$timeData = array("time" => $runTime);

$rrdUpdater = new RRDUpdater(DATA_DIR . "/time.rrd");
$rrdUpdater->update($timeData, time());

$timeData['dateStart'] = START;
$timeData['dateEnd'] = END;
file_put_contents(DATA_DIR."/time.last", json_encode($timeData));

