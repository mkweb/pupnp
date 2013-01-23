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
 * General Public License along with Mupen64PlusAE. If not, see <http://www.gnu.org/licenses/>.
 */
namespace at\mkweb\upnp\backend;

/**
* UPnP main class
*
* @package at.mkweb.upnp
* @author Mario Klug <mario.klug@mk-web.at>
*/
class UPnP {

    /**
    * Get all known devices.
    * If devices are cached it will return cached devicelist, either it will perform an UPnP
    * multicast lookup.
    *
    * @static
    * @access public
    *
    * @param string $service      Only return devices which provide given services
    *
    * @return array                 Array of devices
    */
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

    /**
    * Returns device from cache
    *
    * @static
    * @access public
    * 
    * @param string UID     Device UID
    *
    * @return at.mkweb.upnp.backend.Device
    */
    public static function getDevice($uid) {

        $device = new Device();
        $device->loadFromCache($uid);

        return $device;
    }

    /**
    * Find devices by UPnP multicast message and stores them to cache
    * 
    * @static
    * @access public
    *
    * @return array     Parsed device list
    */
    public static function findDevices() {

        $discover = self::discover();

        $cache = array();

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

    /**
    * Write devicelist to cache file [cachdir]/devices.serialized
    *
    * @param array $data
    * 
    * @throws at.mkweb.upnp.exception.UPnPLogicException    Thrown if cache dir is not found or not writeable
    */
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

    /**
    * Performs a standardized UPnP multicast request to 239.255.255.250:1900
    * and listens $timeout seconds for responses
    *
    * Thanks to artheus (https://github.com/artheus/PHP-UPnP/blob/master/phpupnp.class.php)
    *
    * @static
    * @access public
    *
    * @param int $timeout       Timeout to wait for responses
    *
    * @return array             Response
    */
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

		$response = array();

		do {
			$buf = null;
			@socket_recvfrom( $sock, $buf, 1024, MSG_WAITALL, $from, $port );
			if(!is_null($buf))$response[] = self::discoveryReponse2Array($buf);

		} while(!is_null($buf));

        return $response;
    }

    /**
    * Transforms discovery response string to key/value array
    *
    * @static
    * @access private
    *
    * @param string $res    discovery response
    *
    * @return \stdObj
    */
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

    /**
    * Returns path to cache directory
    *
    * @static
    * @access private
    *
    * @return string 
    */
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
