<?php
/*
    UrTStats - ~/crons/masters.rrdinit.php
  
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

    Create and init rrd file for masters stats ;)
*/

$rrdFile = dirname(__FILE__) . "/../data/masters.rrd";

if(!file_exists($rrdFile))
{
    $creator = new RRDCreator($rrdFile, "now -1d", 1800);
    $creator->addDataSource("masters:GAUGE:600:0:U");
    $creator->addDataSource("master1:GAUGE:600:0:U");
    $creator->addDataSource("master2:GAUGE:600:0:U");
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