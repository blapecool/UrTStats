<?php
/*
    UrTStats - ~/crons/collector.worker.php
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
    3 Steps :
        - 1 : Get list of UrT servers
        - 2 : Query them
        - 3 : Save collected data for master.
*/
define("ROOT_DIR", dirname(__FILE__));
define("DATA_DIR", ROOT_DIR."/../data/");

require ROOT_DIR.'/libs/q3status.class.php';

$id = $argv[1];
$conf = parse_ini_file(DATA_DIR ."conf.ini", true);
$knownServers = array();

foreach ($conf['collector']['plugins'] as $pluginName) 
    require ROOT_DIR . '/plugins/'.$pluginName.'.collector.php';

$db = new mysqli($conf['mysql']['address'], $conf['mysql']['user'], $conf['mysql']['pass'], $conf['mysql']['db']);

// Step 1 
$knownServers =  json_decode(file_get_contents(ROOT_DIR."/slots/".$id."/serverList.json"),true);


// Step 2
foreach ($knownServers as $serverInfo) {
    list($serverIP, $serverPort) = explode(":", $serverInfo['address'], 2);

    $s = new q3status($serverIP, $serverPort); 
    $result = $s->updateStatus(); 

    if (!$result) 
        $result = $s->updateStatus(); 

    if (!$result) {
        sleep(1);
        $result = $s->updateStatus(); 
    }

    if (!$result) {     // Server is down, let's add a fail or disable it if there too much fails
        if($serverInfo['fails'] > 5 AND $serverInfo['lastFail'] > time()-3600 )
            $db->query("UPDATE `stats_serverlist` SET `server_fails` = '6', `server_lastFail` = UNIX_TIMESTAMP(), `server_disabled` = '1' WHERE `server_id` = ".$serverInfo['id'].";");
        elseif ($serverInfo['fails'] > 5 AND $serverInfo['lastFail'] < time()-3600) 
            $db->query("UPDATE `stats_serverlist` SET `server_fails` = '1', `server_lastFail` = UNIX_TIMESTAMP() WHERE `server_id` = ".$serverInfo['id'].";");
        else
            $db->query("UPDATE `stats_serverlist` SET `server_fails` = `server_fails` +1, `server_lastFail` = UNIX_TIMESTAMP() WHERE `server_id` = ".$serverInfo['id'].";");

    }
    else {              // Yes ! Server is up :)
        foreach ($conf['collector']['plugins'] as $pluginName)  {
            $funcName = $pluginName."_work";

            if(function_exists($funcName))
                $funcName($s);
        }
    } 
}


// Step 3
foreach ($conf['collector']['plugins'] as $pluginName)  {
    $funcName = $pluginName."_save";

    if(function_exists($funcName))
        $funcName($id);
}

file_put_contents(ROOT_DIR."/slots/".$id."/finish", "");
