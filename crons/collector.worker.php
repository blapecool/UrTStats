<?php
/*
    UrTStats - ~/crons/collector.worker.php
  
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
