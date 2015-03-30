# NginX Reverse Proxy for Froxlor

This script allows a Froxlor setup to use Nginx as reverse proxy.

To use, you must move the scripts folder into your nginx directory and configure the init.php

After this copy to nrp_cron into your cron.d directory. Finally you have to change the default ports from apache:

http: 8888
<br>
https: 8843
