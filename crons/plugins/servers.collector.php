<?php
/*
    UrTStats - ~/crons/plugins/servers.collector.php
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

	Do server stats
*/

    $plug_serverData = array("servers" => 0,
                              "serversPV" => 0);

function servers_work($s){
    global $plug_serverData;

    $plug_serverData['servers']++;

    if($s->get_cvar('g_needpass') == 1)
        $plug_serverData['serversPV']++;
}

function servers_save($id){
    global $plug_serverData;
    
    file_put_contents(ROOT_DIR."/slots/".$id."/servers.json", json_encode($plug_serverData));

}

function servers_statify($workers){
    global $plug_serverData;
    
    for ($i=0; $i < $workers-1 ; $i++) { 
        $workerData = json_decode(file_get_contents(ROOT_DIR."/slots/".$i."/servers.json"), true);

        $plug_serverData['servers'] += $workerData['servers'];
        $plug_serverData['serversPV'] += $workerData['serversPV'];
    }

    $rrdUpdater = new RRDUpdater(DATA_DIR . "/servers.rrd");
    $rrdUpdater->update($plug_serverData, time());

    file_put_contents(DATA_DIR."/servers.last", json_encode($plug_serverData));
}