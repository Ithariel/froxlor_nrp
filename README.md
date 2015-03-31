# NginX Reverse Proxy for Froxlor

This script allows a Froxlor setup to use Nginx as reverse proxy.

## Quick install

1. Install NginX
2. Remove default site config
3. Move scripts/ directory into your nginx directory
4. Move the proxy.conf into your nginx include directory (conf.d on most distris)
5. Move the npr_cron into your cron directory (/etc/cron.d/)
6. Configure the init.php File

Now change your Default Ports for HTTP to 8888 and HTTPS so 8843. After this run the Cronjob it self:

```bash
php /etc/nginx/scripts/init.php
```

