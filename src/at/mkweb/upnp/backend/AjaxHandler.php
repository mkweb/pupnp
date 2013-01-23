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

use at\mkweb\upnp\Logger;

/**
* Wrapper for simple data access using Ajax (JavaScript)
*
* @author Mario Klug <mario.klug@mk-web.at>
*/
class AjaxHandler {

	private $callback = null;
    private static $logfile = 'AjaxHandler';

    public function __construct() {

        Logger::debug('construct()', self::$logfile);
    }

	public function call($method, $params) {

        Logger::debug(__METHOD__, self::$logfile);

		if(isset($params['callback'])) {

			$this->callback = $params['callback'];
			unset($params['callback']);
		}

		$device = (isset($params['device']) ? $params['device'] : null);

		if(method_exists($this, $method)) {

			if(!is_null($device)) {

				$this->$method($device, $params);
			} else {

				$this->$method($params);
			}
		}
	}

    public function StartPlay($device, Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

        $sourceDevice = UPnP::getDevice($data['source']);
        $sourceClient = $sourceDevice->getClient('ContentDirectory');

        $sourceData = array(
            'ObjectID' => $data['id'],
            'BrowseFlag' => 'BrowseMetadata'
        );

        $item = $sourceClient->Browse($sourceData);

        if(isset($item['Result']) && count($item['Result']) == 1) {

            $xml = $item['Result_XML'];

            $url = null;

            foreach($item['Result'][0]['data']['res'] as $res) {

                if(substr($res['attributes']['protocolInfo'], 0, 8) == 'http-get') {

                    $url = $res['value'];
                    break;
                }
            }

            if(!is_null($url)) {

                $data = array(
                    'CurrentURI' => $url,
                    'CurrentURIMetaData' => htmlspecialchars($xml) #utf8_encode(urldecode($xml))
                );

                $dstDevice = UPnP::getDevice($device);
                $client = $dstDevice->getClient('AVTransport');
                
                $client->call('Stop', array('Speed' => 1));

                $client->call('SetAVTransportURI', $data);

                $client->call('Play', array('Speed' => 1));
            }
        }
    }

    public function getFileInfoHtml($device, Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

        $objectId = $data['ObjectID'];
        $objectId = $objectId;

        $device = UPnP::getDevice($device);
        $client = $device->getClient('ContentDirectory');

        $data['BrowseFlag'] = 'BrowseMetadata';

        $result = $client->Browse($data);

        if(isset($result['Result'])) {

            $data = $this->prepareMetaData($result['Result']);

            $this->respondHtmlTemplate('fileinfo', $data);
        }
    }

    public function getCurrentInfoHtml($device, Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

        $device = UPnP::getDevice($device);
        $client = $device->getClient('AVTransport');

        $result = $client->GetPositionInfo();

        if(isset($result['TrackMetaData'])) {

            $data = $this->prepareMetaData($result['TrackMetaData']);

            $this->respondHtmlTemplate('fileinfo', $data);
        }
    }

	public function getDeviceData($device) {

        Logger::debug(__METHOD__, self::$logfile);

		$device = UPnP::getDevice($device);
		$data = $device->getData();

        $icons = $device->getIcons();

        if(count($icons) > 0) {

            $data->icons = $icons;
        }

		$this->respond($data);
	}

	public function getMetaData($device, Array $data, $return = false) {

        Logger::debug(__METHOD__, self::$logfile);

        $device = UPnP::getDevice($device);
        $client = $device->getClient('ContentDirectory');

        $data['BrowseFlag'] = 'BrowseMetadata';

		$data = $client->Browse($data);

        if(isset($data['Result'])) {

            $data['Result'] = $this->prepareMetaData($data['Result']);
        }

		if(!$return) {

			$this->respond($data);
		} else {

			return $data;
		}
	}

	public function getChilds($device, Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

        $device = UPnP::getDevice($device);
        $client = $device->getClient('ContentDirectory');

        $data['BrowseFlag'] = 'BrowseDirectChildren';

		$data = $client->Browse($data);

        if(isset($data['Result'])) {

            $data['Result'] = $this->prepareMetaData($data['Result']);
        }

		$this->respond($data);
	}

	public function getProtocolInfo($device) {

        Logger::debug(__METHOD__, self::$logfile);

		$device = UPnP::getDevice($device);
		$client = $device->getClient('ConnectionManager');

		$result = $client->call('GetProtocolInfo', array('InstanceID' => 0));

		$this->respond($result);
	}

	public function SetAVTransportURI($device, Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

		$device = UPnP::getDevice($device);
		$client = $device->getClient('AVTransport');

		$result = $client->call('SetAVTransportURI', $data);

		$this->respond($result);
	}

	public function Play($device) {

        Logger::debug(__METHOD__, self::$logfile);

		$device = UPnP::getDevice($device);
		$client = $device->getClient('AVTransport');

		$result = $client->call('Play', array('Speed' => 1));

		$this->respond($result);
	}

	public function Pause($device) {

        Logger::debug(__METHOD__, self::$logfile);

		$device = UPnP::getDevice($device);
		$client = $device->getClient('AVTransport');

		$result = $client->call('Pause', array());

		$this->respond($result);
	}

	public function Stop($device) {

        Logger::debug(__METHOD__, self::$logfile);

		$device = UPnP::getDevice($device);
		$client = $device->getClient('AVTransport');

		$result = $client->call('Stop', array());

		$this->respond($result);
	}

	public function Seek($device, Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

		$device = UPnP::getDevice($device);
		$client = $device->getClient('AVTransport');

		$result = $client->call('Seek', $data);

		$this->respond($result);
	}

	public function getPositionInfo($device, Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

		$device = UPnP::getDevice($device);
		$client = $device->getClient('AVTransport');

		$result = $client->call('GetPositionInfo', array('InstanceID' => 0));

        $result['TrackMetaData'] = $this->prepareMetaData($result['TrackMetaData']);
        $result['TrackMetaData'] = $result['TrackMetaData'][0];

		$this->respond($result);
	}

	public function getTransportInfo($device, Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

		$device = UPnP::getDevice($device);
		$client = $device->getClient('AVTransport');

		$result = $client->call('GetTransportInfo', array('InstanceID' => 0));

		$this->respond($result);
	}

	public function getMediaInfo($device, Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

		$device = UPnP::getDevice($device);
		$client = $device->getClient('AVTransport');

		$result = $client->call('GetMediaInfo', array('InstanceID' => 0));

		$this->respond($result);
	}

	public function getDevices($request) {

        Logger::debug(__METHOD__, self::$logfile);

		$service = (isset($request['service']) ? $request['service'] : null);

		$this->respond(UPnP::getDevices($service));
	}

    public function getFavorites() {

        Logger::debug(__METHOD__, self::$logfile);

        $username = null;
        if(isset($_SERVER['PHP_AUTH_USER'])) {

            $username = $_SERVER['PHP_AUTH_USER'];
        }

        $file = 'cache' . DIRECTORY_SEPARATOR . 'favorites' . (!is_null($username) ? '.' . $username : '') . '.serialized';

        $favorites = array();
        if(file_exists($file)) {

            $favorites = unserialize(file_get_contents($file));

            foreach($favorites as &$fav) {

                foreach($fav as $key => &$value) {

                    $value = utf8_encode($value);
                }
            }
        }

        $this->respond($favorites);
    }

    public function addFavorite(Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

        $deviceId = $data['deviceId'];
        $deviceName = $data['deviceName'];
        $objectId = $data['objectId'];

        $username = null;
        if(isset($_SERVER['PHP_AUTH_USER'])) {

            $username = $_SERVER['PHP_AUTH_USER'];
        }

        $file = 'cache' . DIRECTORY_SEPARATOR . 'favorites' . (!is_null($username) ? '.' . $username : '') . '.serialized';

        $favorites = array();
        if(file_exists($file)) {

            $favorites = unserialize(file_get_contents($file));
        }

        $favorites[$deviceId . '---' . $objectId] = $data;

        file_put_contents($file, serialize($favorites));
    }

    public function removeFavorite(Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

        $uid = $data['uid'];

        $username = null;
        if(isset($_SERVER['PHP_AUTH_USER'])) {

            $username = $_SERVER['PHP_AUTH_USER'];
        }

        $file = 'cache' . DIRECTORY_SEPARATOR . 'favorites' . (!is_null($username) ? '.' . $username : '') . '.serialized';

        if(file_exists($file)) {

            $favorites = unserialize(file_get_contents($file));

            if(isset($favorites[$uid])) {

                unset($favorites[$uid]);

                file_put_contents($file, serialize($favorites));
            }
        }
    }

	public function respond($data) {

        Logger::debug(__METHOD__, self::$logfile);

        if(isset($_GET['print'])) {

            pr($data);
            exit;
        }

		if(!is_null($this->callback)) {

			$data['callback'] = $this->callback;
		}

		echo json_encode($data);
		exit;
	}

    private function prepareMetaData(Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

        $newData = array();

        foreach($data as $item) {

            $obj = array();

            foreach($item as $key => $value) {

                if(!is_array($value)) {

                    $obj[$key] = $value;
                    continue;
                }

                if($key == 'attributes') {

                    foreach($value as $k => $v) {

                        $obj[$k] = $v;
                    }
                    continue;
                }

                if($key == 'data') {

                    foreach($value as $k => $v) {

                        if(is_array($v) && isset($v[0]['value'])) {

                            $obj[$k] = $v[0]['value'];

                            if(isset($v[0]['attributes'])) {

                                foreach($v[0]['attributes'] as $attr_k => $attr_v) {

                                    $obj[$k . '-' . $attr_k] = $attr_v;
                                }
                            }
                        }
                    }
                    continue;
                }
            }

            $newData[] = $obj;
        }

        foreach($newData as &$item) {

            if(isset($item['res-protocolInfo'])) {

                $tmp = explode(':', $item['res-protocolInfo']);
                $tmp = array_slice($tmp, 0, 3);

                $mime = join(':', $tmp) . ':*';

                $item['mimeType'] = $mime;
            }
        }

        return $newData;
    }

    private function respondHtmlTemplate($template, Array $data) {

        Logger::debug(__METHOD__, self::$logfile);

        if(isset($data['0'])) {

            $item = $data[0];

            foreach($item as $key => $value) {

                if(is_string($value) && is_utf8($value)) {

                    $item[$key] = utf8_decode($item[$key]);
                }

                if(is_string($item[$key]) && is_utf8($item[$key])) {

                    $item[$key] = utf8_decode($item[$key]);
                }
            }

            $image = null;
            if(isset($item['albumArtURI'])) {

                $image = $item['albumArtURI'];
            }

            $item = (Object) $item;

            $file = 'ajax-templates' . DIRECTORY_SEPARATOR . $template . '.php';
            if(file_exists($file)) {

                ob_start();
                require_once($file);
                $response = ob_get_clean();

                if(isset($_GET['print'])) {

                    echo $response; exit;
                }

                $response = rtrim(base64_encode($response), '=') . '==';

                echo $response;
            }
        }
        exit;
    }
}
