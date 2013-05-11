<?php
/*
    UrTStats - ~/crons/plugins/servers.collector.php
  
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