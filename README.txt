### Requirements.
* Local Linux machine, with CURL & PHP5+
* A domain name whose DNS are managed by Cloudflare.com

### Instructions.
1. Get your public IP [click here](https://ipinfo.io/ip) for instance
2. Setup a subdomain with Cloudflare DNS pointing to this IP (create a A record for this subdomain on your domain zone)
3. Clone the repo into your local machine
4. Open dummy-config.php, modify with your data, and save it into private-config.php
5. Set up a crontab */5 * * * /PATH_TO/cronjob.php

### How it works
Every 5 minutes the script queries ipinfo.io/ip (or an address of yours if you want copy the file ip.php to your site location) and compares it with the content of the file "myIp", if it changed, it updates the clouflare DNS for the subdomain and replace the value in "myIp". The file "myIp" is created at the first run.
