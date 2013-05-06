UrTStats
========

Global stats system for _Urban Terror_ <--- That's a cool FPS, seriously, try it !

![Player Graph](http://urtstats.f1m.fr/uploads/playersGraph.readme.png)
![Data display](http://urtstats.f1m.fr/uploads/websitePreview.readme.png)

### Want to run your own ?
#### Requirement 
* A Server with a decent php version
* A mysql server and mysqli activated
* php-rrd (`apt-get install php5-rrd`)

#### How to make it work ?
5 Steps :

1. Create a mysql user with a database, import the table structure on it (It's on ./init/dump.sql)
2. Edit `./data/conf.ini` with your settings
3. Create directories for **each** workers on `./crons/slots/` (0, 1, 2,....)
4. Run all php scrips on `./init/` to create all .rdd files :)
5. Add `./crons/collector.cron.php` and `./crons/masters.cron.php` on your crontab :)

`*/5 *  *    *   * php /path/to/Urtstats/crons/masters.cron.php` **AND**
`*/5 *  *    *   * php /path/to/Urtstats/crons/collector.cron.php`


### About me 
I'm blapecool ( [@blapecool](http://www.twitter.com/blapecool) ). 
You can contact me by mail (Blapecool [A:T) gmail (`dot`) com)

### Thanks to
* Barbatos for hosting me :)
