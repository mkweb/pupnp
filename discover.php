<?php
error_reporting(E_ALL);
ini_set('display_errors', false);

use at\mkweb\upnp\UPnP;

function pr($value) {
    echo '<pre>';
    print_r($value);
    echo '</pre>';
}

require_once('src/at/mkweb/upnp/autoload.php');

at\mkweb\upnp\Autoloader::register();

try {
    UPnP::findDevices(); 

} catch (\Exception $e) {

    echo get_class($e) . ': ' . $e->getMessage();
}
