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
    * Steps :
        - 1 : Get known servers by UrTStats
        - 2 : Get known servers by FS via master1 and master2
        - 3 : Compare both list, and add new servers and save the list
        - 4 : Grab Urban terror servers on an other master
        - 5 : Reset fail counters if there were no fail in the last hour
        - 6 : Save the server list...
        - 7 : Add new numbers in RRD file
        - 8 : Write theses numbers in json formated file
        - 9 : Checking extrema
*/

define('ROOT_DIR', dirname(__FILE__).'/');
define('DATA_DIR', ROOT_DIR.'../data/');

require ROOT_DIR.'/libs/q3master.class.php';
require ROOT_DIR.'/libs/q3status.class.php';

$conf = parse_ini_file(ROOT_DIR .'/../conf.ini', true);

// Step 1 - Get known servers by UrTStats
$knownServers = json_decode(file_get_contents(DATA_DIR.'server_list.json'), true);

// Step 2 - Get known servers by FS via master1 and master2
list($serverIP, $serverPort) = explode(':', $conf['master']['master1'], 2);
$master1 = new q3master($serverIP, $serverPort); 

list($serverIP, $serverPort) = explode(':', $conf['master']['master2'], 2);
$master2 = new q3master($serverIP, $serverPort); 

$serversKnownByMaster1 = $master1->getServers();
$serversKnownByMaster2 = $master2->getServers();

$serversKnownByFS = $serversKnownByMaster1 + $serversKnownByMaster2;

// Step 3 - Compare both list, and add new servers and save the list
foreach($serversKnownByFS as $server) {

    // Yey, new server, let's add it!
    if(!isset($knownServers[$server])){
        $knownServers[$server] = array( 'address'   => $server,
                                        'firstSeen' => time(),
                                        'fails'     => 0,
                                        'last_fail' => -1);
    }
}

// Step 4 - Grab Urban terror servers on an other master
if($conf['master']['additionalMaster']) {
    list($serverIP, $serverPort) = explode(':', $conf['master']['additionalMaster'], 2);
    $master = new q3master($serverIP, $serverPort); 

    $otherServers = $master->getServers();

    // Let's check if it's an UrT server, and not something else :)
    foreach($otherServers as $server) {
        if(!isset($knownServers[$server])){
            list($serverIP, $serverPort) = explode(':', $server, 2);

            $s = new q3status($serverIP, $serverPort); 
            $result = $s->updateStatus(); 

            if (!$result) 
                $result = $s->updateStatus(); 

            if($result) {
                // Yes ! Server is up :)
                if($s->get_cvar('gamename') == 'q3ut4' || $s->get_cvar('gamename') == 'q3urt42') {
                    $knownServers[$server] = array( 'address'   => $server,
                                                    'firstSeen' => time(),
                                                    'fails'     => 0,
                                                    'last_fail' => -1);
                }
            }
        }
    }
}

// Step 5 - Reset fail counters if there were no fail in the last hour
foreach($knownServers as $server => $serverInfo) {
    if($serverInfo['fails'] > 0 && $serverInfo['last_fail'] < time() - 3600) {
        $knownServers[$server]['fails'] = 0;
        $knownServers[$server]['last_fail'] = -1;
    }
}

// Step 6 - Save the server list...
file_put_contents(DATA_DIR.'server_list.json', json_encode($knownServers));

// Step 7 - Add new numbers in RRD file
$rrdUpdater = new RRDUpdater(DATA_DIR . $conf['master']['rrdFile']);

$rrdUpdater->update(array('masters' => count($serversKnownByFS),
                          'master1' => count($serversKnownByMaster1),
                          'master2' => count($serversKnownByMaster2)), time());

// Step 8 - Write theses numbers in json formated file
$data = array('masters' => count($serversKnownByFS),
              'master1' => count($serversKnownByMaster1),
              'master2' => count($serversKnownByMaster2));

file_put_contents(DATA_DIR.'/masters.last', json_encode($data));

// Step 9 - Checking extrema :d
if(file_exists(DATA_DIR.'/masters.extrema')){
    $extrema = json_decode(file_get_contents(DATA_DIR.'/masters.extrema'), true);

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

file_put_contents(DATA_DIR.'/masters.extrema', json_encode($extrema));
