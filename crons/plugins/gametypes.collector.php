<?php
/*
    UrTStats - ~/crons/plugins/gametypes.collector.php
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

    Do gametypes stats
*/
	$gametypes = array("0", "1", "3", "4", "5", "6", "7", "8", "9");
    $plug_gametypesData = array();

foreach ($gametypes as $gametype) {
    $plug_gametypesData['playersGT'.$gametype] = 0;
    $plug_gametypesData['serversGT'.$gametype] = 0;
    $plug_gametypesData['playersGT'.$gametype.'PV'] = 0;
    $plug_gametypesData['serversGT'.$gametype.'PV'] = 0;
}

function gametypes_work($s){
    global $plug_gametypesData;

    $numPlayers = $s->get_numPlayers();  
    $gametype = intval(substr($s->get_cvar('g_gametype'),0,1));		// For retarmins setting crap as g_gametype (ie: "war", "3^7", "8    // 0=FFA 3=TDM, 4=TS, 5=FTL, 6=CAH, 7=CTF, 8=BM"...)


    $plug_gametypesData['playersGT'.$gametype] += $numPlayers;
    $plug_gametypesData['serversGT'.$gametype]++;


    if($s->get_cvar('g_needpass') == 1) {
    	$plug_gametypesData['playersGT'.$gametype.'PV'] += $numPlayers;
   		$plug_gametypesData['serversGT'.$gametype.'PV']++;
    }
}

function gametypes_save($id){
    global $plug_gametypesData;
    
    file_put_contents(ROOT_DIR."/slots/".$id."/gametypes.json", json_encode($plug_gametypesData));

}

function gametypes_statify($workers){
    global $plug_gametypesData;
    global $gametypes;
    
    for ($i=0; $i < $workers-1 ; $i++) { 
        $workerData = json_decode(file_get_contents(ROOT_DIR."/slots/".$i."/gametypes.json"), true);

        foreach ($gametypes as $gametype) {
		    $plug_gametypesData['playersGT'.$gametype] += $workerData['playersGT'.$gametype];
		    $plug_gametypesData['serversGT'.$gametype] += $workerData['serversGT'.$gametype];
		    $plug_gametypesData['playersGT'.$gametype.'PV'] += $workerData['playersGT'.$gametype.'PV'];
		    $plug_gametypesData['serversGT'.$gametype.'PV'] += $workerData['serversGT'.$gametype.'PV'];
		}
    }

    foreach ($gametypes as $gametype) {
    	$rrdUpdater = new RRDUpdater(DATA_DIR . "/gametypes_".$gametype.".rrd");

    	$rrdUpdater->update(array('playersGT'.$gametype => $plug_gametypesData['playersGT'.$gametype],
    							  'serversGT'.$gametype => $plug_gametypesData['serversGT'.$gametype],
    							  'playersGT'.$gametype.'PV' => $plug_gametypesData['playersGT'.$gametype.'PV'],
    							  'serversGT'.$gametype.'PV' => $plug_gametypesData['serversGT'.$gametype.'PV']), time());
	}

    file_put_contents(DATA_DIR."/gametypes.last", json_encode($plug_gametypesData));
}