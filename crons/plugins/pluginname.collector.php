<?php
/*
    UrTStats - ~/crons/plugins/{ pluginName }.collector.php
  
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