<?php
/*
    UrTStats - ~/crons/collector.cron.php
    Copyright 2013 Blapecool <Blapecool AT gmail D0T com>

    This file is part of UrTStats.

    UrTStats is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    UrTStats is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with UrTStats.  If not, see <http://www.gnu.org/licenses/>.

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