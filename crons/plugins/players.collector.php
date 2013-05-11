<?php
/*
    UrTStats - ~/crons/plugins/players.collector.php
  
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