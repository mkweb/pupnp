<?php
use at\mkweb\upnp\backend\UPnP;
use at\mkweb\upnp\backend\Device;
use at\mkweb\upnp\backend\Playlist;

use at\mkweb\upnp\Logger;

use \DOMDocument;

require_once('src/at/mkweb/upnp/init.php');

$sid = 'uuid:daff8928-07f5-64d2-d666-4cede51e62f4';
$instanceId = 0;
$transportState = 'STOPPED';

error_reporting(E_ALL);
ini_set('display_errors', true);

$request = '';

$headers = getallheaders();
$header = '';
foreach($headers as $key => $value) {

    $header .= $key . ": " . $value . "\r\n";
}

$request = file_get_contents('php://input');

Logger::debug($header . "\r\n\r\n" . $request . "\n\n", 'event');

$doc = new DomDocument();
$doc->loadXML($request);

$root = $doc->childNodes->item(0);

if($root->localName == 'propertyset') {

    $property = $root->childNodes->item(0);

    if($property->localName == 'property') {

        $data = $property->textContent;

        $doc = new DOMDocument();
        $doc->loadXML($data);

        $root = $doc->childNodes->item(0);

        if($root->localName == 'Event') {

            $instance = $root->childNodes->item(0);

            if($instance->localName == 'InstanceID') {

                $instanceId = 0;
                $transportState = null;

                if($instance->hasAttributes()) {

                    foreach($instance->attributes as $attr) {

                        if($attr->localName == 'val') {

                            $instanceId = $attr->textContent;
                        }
                    }
                }

                if($instance->hasChildNodes()) {

                    foreach($instance->childNodes as $cn) {

                        if($cn->localName == 'TransportState' && $cn->hasAttributes()) {

                            foreach($cn->attributes as $attr) {

                                if($attr->localName == 'val') {

                                    $transportState = $attr->textContent;
                                }
                            }
                        }
                    }
                }

                if($transportState != null) {

                    if(isset($headers['SID'])) {

                        $sid = $headers['SID'];
                        file_put_contents('/tmp/tmp.log', $sid . "\n", FILE_APPEND);

                        $subscriptions = Device::getAllSubscriptions();

                        $device = null;
                        foreach($subscriptions as $deviceId => $ids) {

                            foreach($ids as $id) {

                                if($id == $sid) {

                                    $device = UPnP::getDevice($deviceId);
                                    $device->receivedEvent($transportState);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
