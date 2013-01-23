<?php
/**
 * pUPnP, an PHP UPnP MediaControl
 * 
 * Copyright (C) 2012 Mario Klug
 * 
 * This file is part of pUPnP.
 * 
 * pUPnP is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2 of the
 * License, or (at your option) any later version.
 * 
 * pUPnP is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * See the GNU General Public License for more details. You should have received a copy of the GNU
 * General Public License along with pUPnP. If not, see <http://www.gnu.org/licenses/>.
 */
namespace at\mkweb\upnp\backend;

use at\mkweb\upnp\exception\UPnPLogicException;

use at\mkweb\upnp\backend\xmlparser\RootXMLParser;
use at\mkweb\upnp\backend\xmlparser\ServiceXMLParser;

use \ReflectionClass;

/**
* UPnP Device representation
*
* @package at.mkweb.upnp
* @author Mario Klug <mario.klug@mk-web.at>
*/
class Device {

    /**
    * Device UID
    * @var str
    */
    public $id;

    /**
    * Device friendlyName
    * @var str
    */
    public $name;

    /**
    * Date of last discovery response
    * @var str
    */
    public $date;

    /**
    * Location of the root XML
    * @var str
    */
    public $location;
    
    /**
    * Server name
    * @var str
    */
    public $server;

    /**
    * ST value in UPnP discovery response
    * @var str
    */
    public $st;

    /**
    * USN value in UPnP discovery response
    * @var str
    */
    public $usn;

    /**
    * USN value in UPnP discovery response
    * @var str
    */
    public $root;

    /**
    * Root xml
    * @var at.mkweb.upnp.xmlparsers.RootXMLParser
    */
    public $services;

    /**
    * Typemapping for more simple services access
    * @var array
    */	
    private $typeMapping = array(
		'urn:schemas-upnp-org:service:AVTransport:1' 		=> 'AVTransport',
		'urn:schemas-upnp-org:service:AVTransport:2' 		=> 'AVTransport',
		'urn:schemas-upnp-org:service:ConnectionManager:1' 	=> 'ConnectionManager',
		'urn:schemas-upnp-org:service:ConnectionManager:2'	=> 'ConnectionManager',
		'urn:schemas-upnp-org:service:RenderingControl:1' 	=> 'RenderingControl',
		'urn:schemas-upnp-org:service:RenderingControl:2' 	=> 'RenderingControl',
		'urn:schemas-upnp-org:service:ContentDirectory:1'	=> 'ContentDirectory',
		'urn:schemas-upnp-org:service:ContentDirectory:2'	=> 'ContentDirectory',
		'urn:microsoft.com:service:X_MS_MediaReceiverRegistrar:1' => 'X_MS_MediaReceiverRegistrar',
		'urn:microsoft.com:service:X_MS_MediaReceiverRegistrar:2' => 'X_MS_MediaReceiverRegistrar'
	);

    /**
    * Returns uid for selected device
    * 
    * @access public
    *
    * @return str
    */
    public function getId() {

        return $this->id;
    }

    /**
    * Returns friendlyName for selected device
    * 
    * @access public
    *
    * @return str
    */
    public function getName() {

        return $this->name;
    }

    /**
    * Returns all available service names
    * 
    * @access public
    *
    * @return array
    */
    public function getServices() {

        return array_keys($this->services);
    }

    /**
    * Returns new HTTP-Client for selected service
    * 
    * @access public
    * 
    * @param string $service    Service Name
    *
    * @return at.mkweb.upnp.backend.Client
    */
    public function getClient($service) {

        return new Client($this, $service);
    }

    /**
    * Returns data from root-xml
    * 
    * @access public
    * 
    * @return array
    */
    public function getData() {

        return $this->root->getData();
    }

    /**
    * Returns icons list from root-xml
    * 
    * @access public
    * 
    * @return array
    */
    public function getIcons() {

        return $this->root->getIcons();
    }

    /**
    * Loading device from serialized cache file
    *
    * @access public
    *
    * @param string $uid    Device UID
    */ 
    public function loadFromCache($uid) {

        $cacheFile = self::getCacheDir() . DIRECTORY_SEPARATOR . $uid;

        if(file_exists($cacheFile)) {

            $data = unserialize(file_get_contents($cacheFile));

            $reflection = new ReflectionClass($data);

            $properties = $reflection->getProperties();

            foreach($properties as $prop) {

                $name = $prop->name;

                $this->$name = $data->$name;
            }
        }
    }

    /**
    * Init device by discovery response
    *
    * @access public
    *
    * @param \stdClass $response    Discovery response
    */
    public function initByDiscoveryReponse(\stdClass $response) {

        $this->date     = $response->DATE;
        $this->location = $response->LOCATION;
        $this->server   = $response->SERVER;
        $this->st       = $response->ST;
        $this->usn      = $response->USN;

        $this->root     = new RootXMLParser($this->location);
        $services       = $this->root->getServices();

        foreach($services as $data) {

            $service = new ServiceXMLParser($data);

            $type = $service->getType();

            if(isset($this->typeMapping[$service->getType()])) {

                $type = $this->typeMapping[$service->getType()];
            }

            $this->services[$type] = $service;
        }

        $this->id = $this->root->getId();
        $this->name = $this->root->getName();
    }

    /**
    * Save current device to serialized cache file
    *
    * @access public
    */
    public function saveToCache() {

        $content = serialize($this);

        $cacheDir = self::getCacheDir();

        if(!file_exists($cacheDir)) {

            mkdir($cacheDir, 0777);
        }

        $file = $cacheDir . DIRECTORY_SEPARATOR . $this->id;

        $fh = fopen($file, 'w');
        fputs($fh, $content);
        fclose($fh);
    }

    /**
    * Returns path to current cache dir
    *
    * @static
    * @access public
    *
    * @throws at.mkweb.upnp.exception.UPnPLogicException    If cache dir not found or not writeable
    * @return string                                        Path of cache dir
    */
    private static function getCacheDir() {

        $tmp = explode('\\', __NAMESPACE__);
        $cnt = count($tmp);

        $cacheDir = __FILE__;

        for($i = 0; $i < $cnt + 2; $i++) {

            $cacheDir = dirname($cacheDir);
        }

        $cacheDir .= DIRECTORY_SEPARATOR . 'cache';

        if(!file_exists($cacheDir)) {

            throw new UPnPLogicException('Cache dir ' . $cacheDir . ' not found.');
        }

        if(!is_writeable($cacheDir)) {

            throw new UPnPLogicException('Cache dir ' . $cacheDir . ' not writeable.');
        }

        $cacheDir .= DIRECTORY_SEPARATOR . 'devices';

        return $cacheDir;
    }
}
