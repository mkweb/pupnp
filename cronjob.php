<?php
error_reporting(0);
ini_set('display_errors', false);

use at\mkweb\upnp\backend\UPnP;

function pr($value) {
    echo '<pre>';
    print_r($value);
    echo '</pre>';
}

require_once('src/at/mkweb/upnp/init.php');

try {
    // Discover devices
    UPnP::findDevices(); 

    // Subscribe or renew subscription to AVTransport
    $devices = UPnP::getDevices('AVTransport');

    echo "Starting subscriptions\n";
    foreach($devices as $uid => $device) {

        echo "Checking subscription for " . $uid . "\n";

        $device = UPnP::getDevice($uid);

        $current = $device->getSubscriptions();

        if(count($current) > 0) {

            foreach($current as $uid) {

                echo "Renew\n";
                $device->unSubscribe($uid);
                $device->subscribe();
            }
        } else {

            echo "Create\n";
            $device->subscribe('AVTransport');
        }
    }
} catch (\Exception $e) {

    echo get_class($e) . ': ' . $e->getMessage();
}
