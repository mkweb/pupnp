<?php
namespace at\mkweb\upnp;

use at\mkweb\upnp\exception\UPnPLogicException;

use at\mkweb\upnp\xmlparser\RootXMLParser;
use at\mkweb\upnp\xmlparser\ServiceXMLParser;

use \ReflectionClass;

class Device {

    public $id;
    public $name;

    public $date;
    public $location;
    public $server;
    public $st;
    public $usn;

    public $root;
    public $services;
	
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

    public function getId() {

        return $this->id;
    }

    public function getName() {

        return $this->name;
    }

    public function getServices() {

        return array_keys($this->services);
    }

    public function getClient($service) {

        return new Client($this, $service);
    }

    public function getData() {

        return $this->root->getData();
    }

    public function getIcons() {

        return $this->root->getIcons();
    }

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
