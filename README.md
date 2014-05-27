PHP/JS MediaPlayer for UPnP Devices.
===================================

**WARNING: This tool is under strong development, please be aware about!**

It's possible to accelerate UPnP Device discovery by creating a cronjob e.g. "*/1 * * * *   www-data    cd /var/www/pupnp; php discover.php"

Requirements
------------
* php5
* php5-gd
* php5-curl

Installation
------------
* git clone https://github.com/mkweb/pupnp.git
* cd pupnp && chmod -R +w logs cache conf

Usage
-----
* Point your Browser to http://[IP-Address]/pupnp

Tested Browsers
---------------
* Firefox 17.0.1
* Chromium 23.0.1271.97

Tested UPnP Devices (yet)
-------------------------
* Hama IR2000
* XBMC (Raspbmc - V12.0-RC2)

Known issues
------------
* Userlogins gets not encrypted or hashed yet

Todos
-----
* Playlist support
* _Sometimes_ there is an error when trying to stream any url with special chars
