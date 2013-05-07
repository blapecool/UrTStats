<?php
/*
    UrTStats - ~/crons/plugins/versions.collector.php
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
    
    Do versions stats 
    
*/

    $plug_versionsData = array();
    $plug_versionArray = array( "players" => 0,
                                "playersPV" => 0,
                                "servers" => 0,
                                "serversPV" =>0);

    $plug_versionsData['4.1'] = $plug_versionArray;        // 4.1
    $plug_versionsData['4.2.012'] = $plug_versionArray;    // 4.2.012 - Latest 4.2
    $plug_versionsData["UNKNOWN"] = $plug_versionArray;

function versions_work($s){
    global $plug_versionsData;
    global $plug_versionArray;

    $version = $s->get_cvar("g_modversion");
    $numPlayers = $s->get_numPlayers();  

    if(!isset($plug_versionsData[$version]))
    {
        if(preg_match("`^4\.2\.[0-9]+$`", $version))            // Official 4.2 versions
            $plug_versionsData[$version] = $plug_versionArray;
        elseif(substr($version, 0, 3) == "4.1")
            $version = "4.1";
        else 
            $version = "UNKNOWN";
    }

    $plug_versionsData[$version]['players'] += $numPlayers;
    $plug_versionsData[$version]['servers'] ++;

    if($s->get_cvar('g_needpass') == 1) {
        $plug_versionsData[$version]['playersPV'] += $numPlayers;
        $plug_versionsData[$version]['serversPV'] ++;
    }


}

function versions_save($id){
    global $plug_versionsData;

    file_put_contents(ROOT_DIR."/slots/".$id."/versions.json", json_encode($plug_versionsData));

}

function versions_statify($workers){
    global $plug_versionsData;
    global $plug_versionArray;
    
    for ($i=0; $i < $workers-1 ; $i++) { 
        $workerData = json_decode(file_get_contents(ROOT_DIR."/slots/".$i."/versions.json"), true);

        foreach ($workerData as $version => $versionsData) {

            if(!isset($plug_versionsData[$version]))
                $plug_versionsData[$version] = $plug_versionArray;

            $plug_versionsData[$version]['players'] += $versionsData['players'];
            $plug_versionsData[$version]['servers'] += $versionsData['servers'];
            $plug_versionsData[$version]['playersPV'] += $versionsData['playersPV'];
            $plug_versionsData[$version]['serversPV'] += $versionsData['serversPV'];
        }

    }
    $rrdUpdater = new RRDUpdater(DATA_DIR . "/versions.rrd");
    $rrdUpdater->update(array("41players" => $plug_versionsData['4.1']['players'],
                              "41playersPV" => $plug_versionsData['4.1']['playersPV'],
                              "41servers" => $plug_versionsData['4.1']['servers'],
                              "41serversPV" => $plug_versionsData['4.1']['serversPV'],

                              "42players" =>  $plug_versionsData['4.2.012']['players'],
                              "42playersPV" => $plug_versionsData['4.2.012']['playersPV'],
                              "42servers" => $plug_versionsData['4.2.012']['servers'],
                              "42serversPV" => $plug_versionsData['4.2.012']['serversPV']  ), time());

    file_put_contents(DATA_DIR."/versions.last", json_encode($plug_versionsData));


}