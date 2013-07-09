<?php
/*
    UrTStats - ~/crons/plugins/matches.collector.php
  
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
    
    Do matches stats 
    
*/

    $plug_matchesData = 0;

function matches_work($s){
    global $plug_matchesData;

    $numPlayers = $s->get_numPlayers();  

    // Let's assume that if this condition is true, a match is being played.
    if($s->get_cvar('g_needpass') == 1 && $numPlayers >= 2) {
        $plug_matchesData++;
    }
}

function matches_save($id){
    global $plug_matchesData;
    
    file_put_contents(ROOT_DIR."/slots/".$id."/matches.json", json_encode($plug_matchesData));
}

function matches_statify($workers){
    global $plug_matchesData;
    
    for ($i=0; $i < $workers-1 ; $i++) { 
        $workerData = json_decode(file_get_contents(ROOT_DIR."/slots/".$i."/matches.json"), true);
        $plug_matchesData += $workerData;
    }

    file_put_contents(DATA_DIR."/matches.last", json_encode($plug_matchesData));

    $rrdUpdater = new RRDUpdater(DATA_DIR . "/matches.rrd");
    $rrdUpdater->update(array('matches' => $plug_matchesData), time());

}