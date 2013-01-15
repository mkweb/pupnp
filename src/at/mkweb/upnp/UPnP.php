<?php
namespace at\mkweb\upnp;

class UPnP {

    public static function getDevices($service = null) {

        $cacheFile = self::getCacheDir() . DIRECTORY_SEPARATOR . 'devices.serialized';

        if(file_exists($cacheFile)) {

            $devices = unserialize(file_get_contents($cacheFile));
        } else {

            $devices = self::findDevices();
        }

        if(!is_null($service)) {

            $tmp = $devices;
            $devices = array();

            foreach($tmp as $id => $device) {

                if(in_array($service, $device['services'])) {

                    $devices[$id] = $device;
                }
            }
        }

        return $devices;
    }

    public static function getDevice($uid) {

        $device = new Device();
        $device->loadFromCache($uid);

        return $device;
    }

    public static function findDevices() {

        $discover = self::discover();

        $cache = array();

#        $tmp = 'O:8:"stdClass":7:{s:8:"LOCATION";s:47:"http://192.168.2.112:1649/DeviceDescription.xml";s:13:"CACHE-CONTROL";s:12:"max-age=1800";s:6:"SERVER";s:38:"UPnP/1.0 DLNADOC/1.50 Platinum/0.6.9.1";s:3:"EXT";s:0:"";s:3:"USN";s:58:"uuid:6612698b-2926-8a06-5c54-49334756eb04::upnp:rootdevice";s:2:"ST";s:15:"upnp:rootdevice";s:4:"DATE";s:29:"Thu, 03 Jan 2013 23:01:00 GMT";}';
#        $discover = array(unserialize($tmp));

        pr("DISCOVER:");
        pr($discover);
        flush();
        foreach($discover as $response) {

            $device = new Device();
            $device->initByDiscoveryReponse($response);

            $device->saveToCache();

            $cache[$device->getId()] = array(
                'name' => $device->getName(),
                'services' => $device->getServices()
            );
        }

        self::saveCache($cache);

        pr("CACHE:");
        pr($cache);
        flush();

        return $cache;
    }

    public static function saveCache(Array $data) {

        $cacheDir = self::getCacheDir();

        if(!file_exists($cacheDir)) {

            throw new UPnPLogicException('Cache dir ' . $cacheDir . ' not found.');
        }

        if(!is_writeable($cacheDir)) {

            throw new UPnPLogicException('Cache dir ' . $cacheDir . ' not writeable.');
        }

        $file = $cacheDir . DIRECTORY_SEPARATOR . 'devices.serialized';

        $fh = fopen($file, 'w');
        fputs($fh, serialize($data));
        fclose($fh);
    }

    public static function discover($timeout = 2) {

		$msg  = 'M-SEARCH * HTTP/1.1' . "\r\n";
		$msg .= 'HOST: 239.255.255.250:1900' ."\r\n";
		$msg .= 'MAN: "ssdp:discover"' . "\r\n";
		$msg .= "MX: 3\r\n";
		$msg .= "ST: upnp:rootdevice\r\n";
		$msg .= "USER-AGENT: MacOSX/10.8.2 UPnP/1.1 PHP-UPnP/0.0.1a\r\n";
		$msg .= '' ."\r\n";

		$sock = socket_create(AF_INET, SOCK_DGRAM, 0);
		$opt_ret = socket_set_option($sock, 1, 6, TRUE);
		$send_ret = socket_sendto($sock, $msg, strlen($msg), 0, '239.255.255.250', 1900);

		socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));

		// RECIEVE RESPONSE
		$response = array();

		do {
			$buf = null;
			@socket_recvfrom( $sock, $buf, 1024, MSG_WAITALL, $from, $port );
			if(!is_null($buf))$response[] = self::discoveryReponse2Array($buf);

		} while(!is_null($buf));

        return $response;
    }

	private static function discoveryReponse2Array($res) {

		$result = array();

		$lines = explode("\n", trim($res));

		if(trim($lines[0]) == 'HTTP/1.1 200 OK') {

			array_shift($lines);
		}

		foreach($lines as $line) {

			$tmp = explode(':', trim($line));

			$key = strtoupper(array_shift($tmp));
			$value = (count($tmp) > 0 ? trim(join(':', $tmp)) : null);

			$result[$key] = $value;
		}

		return (Object) $result;	
	}

    private static function getCacheDir() {

        $tmp = explode('\\', __NAMESPACE__);
        $cnt = count($tmp);

        $cacheDir = __FILE__;

        for($i = 0; $i < $cnt + 2; $i++) {

            $cacheDir = dirname($cacheDir);
        }

        $cacheDir .= DIRECTORY_SEPARATOR . 'cache';

        return $cacheDir;
    }
}
