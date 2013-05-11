<?php
/*
    UrTStats - ~/crons/plugins/ping.collector.php
  
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