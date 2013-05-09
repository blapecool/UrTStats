<?php
/*
    UrTStats - ~/crons/plugins/maps.collector.php
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
    
    Do maps stats 
    
*/

    $plug_mapsData = array();
    $plug_mapArray = array( "players" => 0,
                            "playersPV" => 0,
                            "servers" => 0,
                            "serversPV" =>0);

function maps_work($s){
    global $plug_mapsData;
    global $plug_mapArray;

    $map = $s->get_cvar("mapname");
    $numPlayers = $s->get_numPlayers();  

    if(!isset($plug_mapsData[$map]))
        $plug_mapsData[$map] = $plug_mapArray;

    $plug_mapsData[$map]['players'] += $numPlayers;
    $plug_mapsData[$map]['servers'] ++;

    if($s->get_cvar('g_needpass') == 1) {
        $plug_mapsData[$map]['playersPV'] += $numPlayers;
        $plug_mapsData[$map]['serversPV'] ++;
    }


}

function maps_save($id){
    global $plug_mapsData;

    file_put_contents(ROOT_DIR."/slots/".$id."/maps.json", json_encode($plug_mapsData));

}

function maps_statify($workers){
    global $plug_mapsData;
    global $plug_mapArray;
    
    for ($i=0; $i < $workers-1 ; $i++) { 
        $workerData = json_decode(file_get_contents(ROOT_DIR."/slots/".$i."/maps.json"), true);

        foreach ($workerData as $map => $mapData) {

            if(!isset($plug_mapsData[$map]))
                $plug_mapsData[$map] = $plug_mapArray;

            $plug_mapsData[$map]['players'] += $mapData['players'];
            $plug_mapsData[$map]['servers'] += $mapData['servers'];
            $plug_mapsData[$map]['playersPV'] += $mapData['playersPV'];
            $plug_mapsData[$map]['serversPV'] += $mapData['serversPV'];
        }

    }
    file_put_contents(DATA_DIR."/maps.last", json_encode($plug_mapsData));


}