<?php
/*
    UrTStats - ~/crons/plugins/{ pluginName }.collector.php
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
    
    Replace all { pluginName } and do your own plugin :D
    Do { pluginName } stats 
    
*/


    $plug_{ pluginName }Data = array();

function { pluginName }_work($s){
    global $plug_{ pluginName }Data;

}

function { pluginName }_save($id){
    global $plug_{ pluginName }Data;
    
    file_put_contents(ROOT_DIR."/slots/".$id."/{ pluginName }.json", json_encode($plug_{ pluginName }Data));

}

function { pluginName }_statify($workers){
    global $plug_{ pluginName }Data;
    
    for ($i=0; $i < $workers-1 ; $i++) { 
        $workerData = json_decode(file_get_contents(ROOT_DIR."/slots/".$i."/{ pluginName }.json"), true);

    }


}