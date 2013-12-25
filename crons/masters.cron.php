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
    6 Steps :
        - 1 : Get known servers by UrTStats
        - 2 : Get known servers by FS via master1 and master2
        - 3 : Compare both list, and add new servers and save the list
        - 4 : Add new numbers in RRD file
        - 5 : Write theses numbers in json formated file
        - 6 : Checking extrema
*/

define('ROOT_DIR', dirname(__FILE__).'/');
define('DATA_DIR', ROOT_DIR.'../data/');

require ROOT_DIR.'/libs/q3master.class.php';

$conf = parse_ini_file(DATA_DIR ."conf.ini", true);

// Step 1 - Get known servers by UrTStats
$knownServers = json_decode(file_get_contents(DATA_DIR.'server_list.json'), true);

// Step 2 - Get known servers by FS via master1 and master2
$master1_27950 = new q3master($conf['master']['master1'], 27950); 
$master1_27900 = new q3master($conf['master']['master1'], 27900); 

$master2_27950 = new q3master($conf['master']['master2'], 27950); 
$master2_27900 = new q3master($conf['master']['master2'], 27900); 

$serversKnownByMaster1 = $master1_27950->getServers() + $master1_27900->getServers();
$serversKnownByMaster2 = $master2_27950->getServers() + $master2_27900->getServers();

$serversKnownByFS = $serversKnownByMaster1 + $serversKnownByMaster2;

// Step 3 - Compare both list, and add new servers and save the list
foreach($serversKnownByFS as $server){

    // Yey, new server, let's add it!
    if(!isset($knownServers[$server])){
        $knownServers[$server] = array( 'address'   => $server,
                                        'firstSeen' => time(),
                                        'fails'     => 0,
                                        'last_fail' => -1);
    }
    else{
        // Reset the fail counter if every thing was okay in the last 2 hours ;)
        if ($knownServers[$server]['last_fail'] != -1 && $knownServers[$server]['last_fail'] < time() - 3600*2) {
            $db->query("UPDATE `stats_serverlist` SET `server_fails` = '0', `server_lastFail` = '0',`server_disabled` = '0' WHERE `server_id` = ".$knownServers[$server]['id'].";");
        }
    }
}

// Save the server list...
file_put_contents(DATA_DIR.'server_list.json', json_encode($knownServers));

// Step 4 - Add new numbers in RRD file
$rrdUpdater = new RRDUpdater(DATA_DIR . $conf['master']['rrdFile']);

$rrdUpdater->update(array("masters" => count($serversKnownByFS),
                          "master1" => count($serversKnownByMaster1),
                          "master2" => count($serversKnownByMaster2)), time());

// Step 5 - Write theses numbers in json formated file
$data = array("masters" => count($serversKnownByFS),
              "master1" => count($serversKnownByMaster1),
              "master2" => count($serversKnownByMaster2));

// Step 6 - Checking extrema :d
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