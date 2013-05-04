<?php
/*
    UrTStats - ~/crons/gametype.rrdinit.php
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

    Create and init rrd file for gametype stats ;)
*/
$gametypes = array("0", "1", "3", "4", "5", "6", "7", "8", "9");


foreach ($gametypes as $gametype) {
    $rrdFile = dirname(__FILE__) . "/../data/gametypes_".$gametype.".rrd";
  
    if(!file_exists($rrdFile))
    {
        $creator = new RRDCreator($rrdFile, "now -1d", 300);
        $creator->addDataSource("playersGT".$gametype.":GAUGE:600:0:U"); 
        $creator->addDataSource("serversGT".$gametype.":GAUGE:600:0:U");
        $creator->addDataSource("playersGT".$gametype."PV:GAUGE:600:0:U"); 
        $creator->addDataSource("serversGT".$gametype."PV:GAUGE:600:0:U");
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

}
