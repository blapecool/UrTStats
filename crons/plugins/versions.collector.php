<?php
/*
    UrTStats - ~/crons/plugins/versions.collector.php
  
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