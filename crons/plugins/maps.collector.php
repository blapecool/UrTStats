<?php
/*
    UrTStats - ~/crons/plugins/maps.collector.php
  
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

    $map = strtolower($s->get_cvar("mapname"));
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
    global $conf;
    
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

    if($conf['collector']['keepHistoricalDataForMaps']) {
        foreach ($plug_mapsData as $map => $mapData) {
            $rrdFile = DATA_DIR."/maps_".$map.".rrd";

           if(!file_exists($rrdFile)){
                map_generateRRDFile($map);
            }

            $rrdUpdater = new RRDUpdater($rrdFile);

            $rrdUpdater->update(array('playersMap' => $mapData['players'],
                                      'serversMap' => $mapData['servers'],
                                      'playersMapPV' => $mapData['playersPV'],
                                      'serversMapPV' => $mapData['serversPV']), time());
            
        }
    }

    file_put_contents(DATA_DIR."/maps.last", json_encode($plug_mapsData));
}

function map_generateRRDFile($map){

    $rrdFile = DATA_DIR."/maps_".$map.".rrd";

    $creator = new RRDCreator($rrdFile, "now -1d", 300);
    $creator->addDataSource("playersMap:GAUGE:600:0:U");
    $creator->addDataSource("playersMapPV:GAUGE:600:0:U");
    $creator->addDataSource("serversMap:GAUGE:600:0:U");
    $creator->addDataSource("serversMapPV:GAUGE:600:0:U");
    $creator->addArchive("AVERAGE:0.5:1:864");   // 3 days - 5 mins 
    $creator->addArchive("MIN:0.5:1:864");
    $creator->addArchive("MAX:0.5:1:864");
    $creator->addArchive("AVERAGE:0.5:4:720");   // 10 days - 20 mins
    $creator->addArchive("MIN:0.5:4:720");
    $creator->addArchive("MAX:0.5:4:720");
    $creator->addArchive("AVERAGE:0.5:24:540");  // 45 days - 2 hours
    $creator->addArchive("MIN:0.5:24:540");
    $creator->addArchive("MAX:0.5:24:540");
    $creator->addArchive("AVERAGE:0.5:288:5000"); // 5000 days - 1 day
    $creator->addArchive("MIN:0.5:288:5000");
    $creator->addArchive("MAX:0.5:288:5000");
    $creator->save();
}