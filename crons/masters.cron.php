<?php
/*
    UrTStats - ~/crons/masters.cron.php
  
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

    Get new servers from master servers
    5 Steps :
        - 1 : Get known servers by UrTStats
        - 2 : Get known servers by FS via master1 and master2
        - 3 : Compare both list, and add new servers in db
        - 4 : Add new numbers in RRD file
        - 5 : Write stuff in json formated file for easy use of the latest and extreme data
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

if(file_exists(DATA_DIR."/masters.extrema")){
    $extrema = json_decode(file_get_contents(DATA_DIR."/masters.extrema"), true);

    if($extrema['max']['value'] < count($serversKnownByFS)){
        $extrema['max']['value'] = count($serversKnownByFS);
        $extrema['max']['date'] = time();
    }
    elseif($extrema['min']['value'] > count($serversKnownByFS)){
        $extrema['min']['value'] = count($serversKnownByFS);
        $extrema['min']['date'] = time();
    }  
}
else{
    $extrema = array();

    $extrema['max']['value'] = count($serversKnownByFS);
    $extrema['max']['date'] = time();

    $extrema['min']['value'] = count($serversKnownByFS);
    $extrema['min']['date'] = time();
}

file_put_contents(DATA_DIR."/masters.extrema", json_encode($extrema));
file_put_contents(DATA_DIR."/masters.last", json_encode($data));