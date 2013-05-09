<?php
/*
    UrTStats - ~/crons/plugins/ping.collector.php
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

    Do ping stats 
    
*/

    $plug_pingData = array("ping" => 0,
                           "players" => 0);

function ping_work($s){
    global $plug_pingData;

    foreach ($s->playerlist as $playerData) {
        $plug_pingData['ping'] += $playerData['ping'];
        $plug_pingData['players'] ++;
    }

}

function ping_save($id){
    global $plug_pingData;

    file_put_contents(ROOT_DIR."/slots/".$id."/ping.json", json_encode($plug_pingData));

}

function ping_statify($workers){
    global $plug_pingData;
    
    for ($i=0; $i < $workers-1 ; $i++) { 
        $workerData = json_decode(file_get_contents(ROOT_DIR."/slots/".$i."/ping.json"), true);

        $plug_pingData['ping'] += $workerData['ping'];
        $plug_pingData['players'] += $workerData['players'];

    }

    $plug_pingData['ping'] = $plug_pingData['ping'] / $plug_pingData['players'];

    $rrdUpdater = new RRDUpdater(DATA_DIR . "/ping.rrd");
    $rrdUpdater->update(array('ping' => $plug_pingData['ping']), time());

    file_put_contents(DATA_DIR."/ping.last", json_encode($plug_pingData));

}