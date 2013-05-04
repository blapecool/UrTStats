<?php
/*
    UrTStats - ~/crons/masters.cron.php
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

    Get new servers from master servers
    5 Steps :
        - 1 : Get known servers by UrTStats
        - 2 : Get known servers by FS via master1 and master2
        - 3 : Compare both list, and add new servers in db
        - 4 : Add new numbers in RRD file
        - 5 : Write stuff in json formated file for easy use of the latest data
*/

define("ROOT_DIR", dirname(__FILE__)."/");
define("DATA_DIR", ROOT_DIR."/../data/");

require ROOT_DIR.'/libs/q3master.class.php';

$conf = parse_ini_file(DATA_DIR ."conf.ini", true);
$knownServers = array();

$db = new mysqli($conf['mysql']['address'], $conf['mysql']['user'], $conf['mysql']['pass'], $conf['mysql']['db']);

// Step 1 
$result = $db->query("SELECT `server_id`, `server_address`, `server_disabled` FROM `stats_serverlist`");
while($data = $result->fetch_assoc()){

    $knownServers[$data['server_address']] = array( 'id' => $data['server_id'],
                                                    'disabled' => $data['server_disabled']);
}
$result->free();

// Step 2
$master1 = new q3master($conf['master']['master1'], 27950); 
$master2 = new q3master($conf['master']['master2'], 27950); 

$serversKnownByMaster1 = $master1->getServers();
$serversKnownByMaster2 = $master2->getServers();
$serversKnownByFS = $serversKnownByMaster1 + $serversKnownByMaster2;

// Step 3
foreach($serversKnownByFS as $server){

    if(!isset($knownServers[$server])){
        $db->query("INSERT INTO `stats_serverlist` VALUES ('' , '".$server."', UNIX_TIMESTAMP( ) , '', '', '');");
    }
    else{
        if ($knownServers[$server]['disabled'] == 1) {
            $db->query("UPDATE `stats_serverlist` SET `server_fails` = '0', `server_lastFail` = '0',`server_disabled` = '0' WHERE `server_id` = ".$knownServers[$server]['id'].";");
        }
    }
}

// Step 4
$rrdUpdater = new RRDUpdater(DATA_DIR . $conf['master']['rrdFile']);

$rrdUpdater->update(array("masters" => count($serversKnownByFS),
                          "master1" => count($serversKnownByMaster1),
                          "master2" => count($serversKnownByMaster2)), time());

// Step 5
$data = array("masters" => count($serversKnownByFS),
              "master1" => count($serversKnownByMaster1),
              "master2" => count($serversKnownByMaster2));

file_put_contents(DATA_DIR."/masters.last", json_encode($data));