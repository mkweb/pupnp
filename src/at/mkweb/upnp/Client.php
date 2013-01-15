<?php
namespace at\mkweb\upnp;

use at\mkweb\upnp\exception\UPnPException;

use \DOMDocument;

class Client {

    private $device;
    private $service;

    private $hideLogs = array('Browse', 'GetPositionInfo');

    public function __construct(Device $device, $service) {

        $this->device = $device;

        $services = $this->device->services;

        if(!isset($services[$service])) {

            throw new UPnPException('Unknown service: ' . $service);
        }

        $this->service = $services[$service];
    }

    public function getAction($name) {

        $actions = $this->getActions();

        return (isset($actions[$name]) ? $actions[$name] : null);
    }

    public function getActions() {

        return $this->service->getActions();
    }

    public function call($method, Array $data = array()) {

        $hideLogs = (in_array($method, $this->hideLogs));

        $request = $this->getRequest($method, $data);

		$urldata = parse_url($this->service->getControlUrl());

		$header = array(
			'HOST: ' . $urldata['host'] . ':' . $urldata['port'],
			'DATE: ' . date('r'),
			'USER-AGENT: Linux/2.6.31-1.0 UPnP/1.0 DLNADOC/1.50 INTEL_NMPR/2.0 LGE_DLNA_SDK/1.5.0',
			'friendlyName.dlna.org: LG DLNA DMP DEVICE',
			'SOAPAction: "' . $this->service->getId() . ':1#' . $method . '"',
			'Content-Length: ' . mb_strlen($request),
			'Content-Type: text/xml;charset="utf-8"',
            'Accept-Language: de-at;q=1, de;q=0.5',
            'Accept-Encoding: gzip',
            'Connection: close'
		);

        if(!$hideLogs) file_put_contents('logs/http.log', date('Y-m-d H:i:s') . ' - Request: ' . 'POST ' . $urldata['path'] . ' HTTP/1.1' . "\n" . join("\n", $header) . "\n\n" . $request . "\n\n", FILE_APPEND);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->service->getControlUrl());
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $result = curl_exec($ch);
		
        if(!$hideLogs) file_put_contents('logs/http.log', date('Y-m-d H:i:s') . ' - Response: ' . $result . "\n\n", FILE_APPEND);
	
        $headers = array();

        $tmp = explode("\r\n\r\n", $result);

		foreach($tmp as $key => $value) {

			if(substr($value, 0, 8) == 'HTTP/1.1') {

				$headers[] = $tmp[$key];
				unset($tmp[$key]);
			}
		}

        $lastHeaders = $headers[count($headers) - 1];

		$headers = join("\n\n", $headers);
		$result = join("\r\n", $tmp);

        $responseCode = $this->getResponseCode($lastHeaders);

        if($responseCode == 500) {

            $response = $this->parseResponseError($result);
        } else {

            $response = $this->parseResponse($method, $result);
        }

        return $response;
    }

    private function getResponseCode($headers) {

        $tmp = explode("\n", $headers);
        $firstLine = array_shift($tmp);

        if(substr($headers, 0, 8) == 'HTTP/1.1') {

            return substr($headers, 9, 3);
        }

        return null;
    }

    public function __call($method, $data) {

        return $this->call($method, (isset($data[0]) ? $data[0] : array()));
    }

    public function getRequest($method, Array $userData) {

        $action = $this->getAction($method);

        $params = $action['in'];

        $defaultData = array();
        foreach($params as $param) {

            $value = (isset($userData[$param['name']]) ? $userData[$param['name']] : null);

            if($param['name'] == 'InstanceID' && is_null($value)) $value = 0;

            $defaultData[$param['name']] = $value;

            if(isset($param['relatedStateVariable'])) {

                $stateVar = $this->service->getStateVar($param['relatedStateVariable']);

                if(isset($stateVar->dataType)) {

                    $error = false;

                    switch($stateVar->dataType) {

                        case 'string':

                            if(!is_null($value) && !is_string($value) && !is_int($value))  $error = true;
                            break;

                        case 'ui4':

                        
                            break;
                    }

                    if($error) {

                        throw new UPnPException('Invalid data type for field "' . $param['name'] . '". Allowed type is "' . $stateVar->dataType . '"');
                    }
                }

                if(isset($stateVar->allowedValueList) && !in_array($value, $stateVar->allowedValueList)) {

                    throw new UPnPException('Invalid value "' . $defaultData[$param['name']] . '" for field "' . $param['name'] . '". Allowed values are: ' . join(', ', $stateVar->allowedValueList));
                }
            }
        }

        $data = $defaultData;

        if(array_key_exists('StartingIndex', $data) && $data['StartingIndex'] == null) $data['StartingIndex'] = 0;
        if(array_key_exists('RequestedCount', $data) && $data['RequestedCount'] == null) $data['RequestedCount'] = 0;

        $xml = '<?xml version="1.0"?>';
        $xml.= '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
        $xml.= '<s:Body>';
        $xml.= '<u:' . $method . ' xmlns:u="' . $this->service->getType() . '">';

		foreach($data as $key => $value) {

            $value = str_replace('&', '&amp;', $value);

			$xml .= '<' . $key . '>' . $value . '</' . $key . '>';
		}

        $xml.= '</u:' . $method . '>';
        $xml.= '</s:Body>';
        $xml.= '</s:Envelope>';

		$xml = str_replace(array("\r", "\n", "\t"), "", $xml);

        $xml = utf8_decode($xml);

        return $xml;
    }

    private function getFirst($node) {

        $first = null;

        $i = 0;
        while($first == null || get_class($first) == 'DOMText') {

            $first = $node->childNodes->item($i);
            $i ++;
        }

        return $first;
    }

    private function parseResponseError($xml) {

        $result = array();

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $env = $this->getFirst($doc);
        $body = $this->getFirst($env);
        $fault = $this->getFirst($body);

        foreach($fault->childNodes as $childNode) {

            if(get_class($childNode) == 'DOMText') continue;

            if($childNode->tagName == 'faultCode') {

                $result['faultCode'] = $childNode->textContent;
                continue;
            }

            if($childNode->tagName == 'faultString') {

                $result['faultString'] = $childNode->textContent;
                continue;
            }

            if($childNode->tagName == 'detail') {

                $details = array();

                $firstChild = $this->getFirst($childNode);

                if($firstChild->tagName == 'UPnPError') {

                    foreach($firstChild->childNodes as $detailsChild) {

                        if(get_class($detailsChild) == 'DOMText') continue;

                        $details[$detailsChild->tagName] = $detailsChild->textContent;
                    }
                }

                $result['detail'] = $details;
                continue;
            }
        }

        return $result;
    }

    private function parseResponse($method, $xml) {

        $original_xml = $xml;

        // Bad hack
        if(strstr($xml, 'parentID') !== false) {

            $xml = preg_replace('/ parentID="(.*)"/Uis', '', $xml);
        }

        $result = array();
        $action = $this->getAction($method);

        $params = $action['out'];

        foreach($params as $param) {

            $name = $param['name'];

            $regex = sprintf('/<%s>(.*)<\/%s>/Uis', $name, $name);

            preg_match($regex, $xml, $tmp);

            if(count($tmp) == 2) {

                $result[$name] = $tmp[1];
            } else {

                throw new UPnPException('Missing response value: ' . $name);
            }
        }

        if($method == 'Browse' || $method == 'GetPositionInfo') {

            switch($method) {

                case 'Browse':          $tagname = 'Result'; break;
                case 'GetPositionInfo': $tagname = 'TrackMetaData'; break;
            }

            $data = array();

            $xml = html_entity_decode($result[$tagname]);

            if(strstr($xml, '&lt;') !== false) {

                $xml = htmlspecialchars_decode($xml);
            }

            $dom = new DOMDocument();
            $dom->loadXML($xml);

            $root = $dom->childNodes->item(0);

            foreach($root->childNodes as $node) {

                if(get_class($node) == 'DOMText') continue;

                $element = array();
                $element['type'] = $node->tagName;

                if($node->hasAttributes()) {

                    $element['attributes'] = array();

                    foreach($node->attributes as $attr) {

                        $element['attributes'][$attr->name] = $attr->textContent;
                    }
                }

                if($node->hasChildNodes()) {

                    $element['data'] = array();

                    $i = 0;
                    foreach($node->childNodes as $childNode) {

                        $tmp = array();

                        $tmp['value'] = $childNode->textContent;

                        if($childNode->hasAttributes()) {

                            foreach($childNode->attributes as $attr) {

                                $tmp['attributes'][$attr->name] = $attr->textContent;
                            }
                        }

                        $t = explode(':', $childNode->tagName);
                        $tagName = array_pop($t);
                        $element['data'][$tagName][] = $tmp;
                    }
                }

                $data[] = $element;
            }

            $result[$tagname . '_XML'] = $result[$tagname];
            $result[$tagname] = $data;
        }

        return $result;
    }
}
