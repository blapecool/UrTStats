<?php
/*
    UrTStats - ~/crons/plugins/gametypes.collector.php
  
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

    Do gametypes stats
*/
	$gametypes = array("0", "1", "3", "4", "5", "6", "7", "8", "9", "10");
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
