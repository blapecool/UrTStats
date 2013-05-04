<?php
/*
    UrTStats - ~/crons/plugins/players.collector.php
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

    Do player stats
*/

    $plug_playersData = array("players" => 0,
                              "playersPV" => 0);

function players_work($s){
    global $plug_playersData;

    $numPlayers = $s->get_numPlayers();  

    $plug_playersData['players'] += $numPlayers;

    if($s->get_cvar('g_needpass') == 1)
        $plug_playersData['playersPV'] += $numPlayers;
}

function players_save($id){
    global $plug_playersData;
    
    file_put_contents(ROOT_DIR."/slots/".$id."/players.json", json_encode($plug_playersData));

}

function players_statify($workers){
    global $plug_playersData;
    
    for ($i=0; $i < $workers-1 ; $i++) { 
        $workerData = json_decode(file_get_contents(ROOT_DIR."/slots/".$i."/players.json"), true);

        $plug_playersData['players'] += $workerData['players'];
        $plug_playersData['playersPV'] += $workerData['playersPV'];
    }

    $rrdUpdater = new RRDUpdater(DATA_DIR . "/players.rrd");
    $rrdUpdater->update($plug_playersData, time());

    file_put_contents(DATA_DIR."/players.last", json_encode($plug_playersData));
}