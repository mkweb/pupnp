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

use at\mkweb\upnp\exception\UPnPException;
use at\mkweb\upnp\Logger;

use \DOMDocument;

/**
* pUPnP HTTP-Client
*
* @package at.mkweb.upnp.backend
* @author Mario Klug <mario.klug@mk-web.at>
*/
class Client {

    /**
    * Device object
    * @var at.mkweb.upnp.backend.Device
    */ 
    private $device;

    /**
    * Service
    * @var at.mkweb.upnp.xmlparser.ServiceXMLParser
    */ 
    private $service;

    /**
    * Name of logfile for this class
    * @var string
    */
    private static $logfile = 'Client';

    /**
    * Hide logs for debugging purposes
    * @var boolean
    */
    private $hideLogs = false;

    /**
    * Hide logs for these methods - for debugging purposes
    * @var array
    */
    private $hideLogsMethods = array('GetPositionInfo', 'GetTransportInfo');

    /**
    * Constructor - preparing Service
    *
    * @access public
    *
    * @param Device   $device
    * @param string   $service
    */
    public function __construct(Device $device, $service) {

        Logger::debug(__METHOD__ . '; Device: ' . $device->getId() . ' [' . $device->getName() . ']; Service: ' . $service, self::$logfile);

        $this->device = $device;

        $services = $this->device->services;

        if(!isset($services[$service])) {

            throw new UPnPException('Unknown service: ' . $service);
        }

        $this->service = $services[$service];
    }

    /**
    * Returns action name if it's a valid action for selected service, either null
    *
    * @access public
    *
    * @param string $name
    *
    * return mixed  Action name or null
    */
    public function getAction($name) {

        $actions = $this->getActions();

        return (isset($actions[$name]) ? $actions[$name] : null);
    }

    /**
    * Returns all actions for selected service
    *
    * @access public
    * 
    * @return array
    */
    public function getActions() {

        return $this->service->getActions();
    }

    /**
    * Sending HTTP-Request and returns parsed response
    *
    * @access public
    *
    * @param string $method     Method name
    * @param array $data        Key-Value array
    *
    * @return array             Parsed response
    */
    public function call($method, Array $data = array(), $formatResponse = true) {

        $this->hideLogs = $hideLogs = (in_array($method, $this->hideLogsMethods));
        if(!$hideLogs) Logger::debug(__METHOD__ . '; Method: ' . $method . '; Data: ' . print_r($data, true), self::$logfile);

        $request = $this->getRequest($method, $data);

		$urldata = parse_url($this->service->getControlUrl());

		$header = array(
			'HOST: ' . $urldata['host'] . ':' . $urldata['port'],
			'Content-LENGTH: ' . mb_strlen($request),
			'CONTENT-TYPE: text/xml;charset="utf-8"',
			'USER-AGENT: Linux/2.6.31-1.0 UPnP/1.0 pupnp/0.1',
			'SOAPACTION: "' . $this->service->getId() . ':1#' . $method . '"',
		);

        if(!$hideLogs) Logger::debug("Endpoint: " . $this->service->getControlUrl(), self::$logfile);
        if(!$hideLogs) Logger::debug("Header:\n" . join("\n", $header) . "\n", self::$logfile);
        if(!$hideLogs) Logger::debug("Request:\n" . $request . "\n", self::$logfile);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->service->getControlUrl());
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $result = curl_exec($ch);
		
        if(!$hideLogs) Logger::debug("Response:\n" . $result . "\n", self::$logfile);
	
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

        if(!$formatResponse) {

            return $result;
        }

        $responseCode = $this->getResponseCode($lastHeaders);
        if(!$hideLogs) Logger::debug('ResponseCode: ' . $responseCode, self::$logfile);

        if($responseCode == 500) {

            if(!$hideLogs) Logger::debug('HTTP-Code 500 - Create error response', self::$logfile);
            $response = $this->parseResponseError($result);
        } else {

            if(!$hideLogs) Logger::debug('HTTP-Code OK - Create response', self::$logfile);
            $response = $this->parseResponse($method, $result);
        }

        if(!$hideLogs) Logger::debug('Return: ' . print_r($response, true), self::$logfile);
        return $response;
    }

    /**
    * Subscribe to event notifies
    *
    * @access public
    *
    * @return string    Subscription ID
    */
    public function subscribe() {

        $url = $this->service->getEventSubUrl();
		$urldata = parse_url($url);

        $path = dirname($_SERVER['SCRIPT_FILENAME']);
        $path = trim(substr($path, strlen($_SERVER['DOCUMENT_ROOT'])), '/');
        $eventUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/' . $path . '/event.php';

        $header = array(
			'HOST: ' . $urldata['host'] . ':' . $urldata['port'],
			'USER-AGENT: Linux/2.6.31-1.0 UPnP/1.0 pupnp/0.1',
            'CALLBACK: <' . $eventUrl . '>',
            'NT: upnp:event',
            'TIMEOUT: 180',
        );

        Logger::debug('Subscribe to ' . $this->device->getId() . ' with: ' . "\n" . print_r($header, true), 'subscription');

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->service->getEventSubUrl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'SUBSCRIBE');

        $result = curl_exec($ch);

        $tmp = explode("\r\n", trim($result));

        $response = array();
        foreach($tmp as $line) {

            $tmp = explode(':', $line);

            $key = strtoupper(trim(array_shift($tmp)));
            $value = trim(join(':', $tmp));

            $response[$key] = $value;
        }

        if(isset($response['SID'])) {

            return $response['SID'];
        }

        return null;
    }

    /**
    * Unsubscribe from event notifies
    *
    * @access public
    *
    * @param string $sid    Subscription ID
    */
    public function unSubscribe($sid) {

        $url = $this->service->getEventSubUrl();
		$urldata = parse_url($url);

        $header = array(
			'HOST: ' . $urldata['host'] . ':' . $urldata['port'],
            'SID: ' . $sid,
        );

        Logger::debug('Unsubscribe from SID: ' . $sid . ' with: ' . "\n" . print_r($header, true), 'subscription');

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->service->getEventSubUrl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'UNSUBSCRIBE');

        $result = curl_exec($ch);
    }

    /**
    * Renew subscription for event notifies
    *
    * @access public
    *
    * @param string $sid    Subscription ID
    */
    public function renewSubscription($sid) {

        $url = $this->service->getEventSubUrl();
		$urldata = parse_url($url);

        $header = array(
			'HOST: ' . $urldata['host'] . ':' . $urldata['port'],
            'SID: ' . $sid,
            'TIMEOUT: 180'
        );

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->service->getEventSubUrl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'SUBSCRIBE');

        $result = curl_exec($ch);
        
        Logger::debug('Renew subscription for sid ' . $sid . ' with: ' . "\n" . print_r($header, true), 'subscription');
    }

    /**
    * Filters response HTTP-Code from response headers
    *
    * @access private
    * 
    * @param string $headers    HTTP response headers
    *
    * @return mixed             Response code (int) or null if not found
    */
    private function getResponseCode($headers) {

        $tmp = explode("\n", $headers);
        $firstLine = array_shift($tmp);

        if(substr($headers, 0, 8) == 'HTTP/1.1') {

            return substr($headers, 9, 3);
        }

        return null;
    }

    /**
    * Catchall function to enable direct method calls like $client->methodName();
    *
    * @access public
    *
    * @param string $method     Method name
    * @param array  $data       Key-value array
    *
    * @return array             Parsed response
    */
    public function __call($method, $data) {

        return $this->call($method, (isset($data[0]) ? $data[0] : array()));
    }

    /**
    * Prepares SOAP XML-Request
    *
    * @access public
    *
    * @param string $method     Method name
    * @param array  $userData   Key-value array
    *
    * @return string            Request XML
    */
    public function getRequest($method, Array $userData) {

        $action = $this->getAction($method);

        $params = $action['in'];

        if(is_array($params)) {

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
        } else {

            $data = $userData;
        }
            
        if(array_key_exists('StartingIndex', $data) && $data['StartingIndex'] == null) $data['StartingIndex'] = 0;
        if(array_key_exists('RequestedCount', $data) && $data['RequestedCount'] == null) $data['RequestedCount'] = 0;

        $xml = '<?xml version="1.0"?>';
        $xml.= '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
        $xml.= '<s:Body>';
        $xml.= '<u:' . $method . ' xmlns:u="' . $this->service->getType() . '">';

		foreach($data as $key => $value) {

            switch($key) {
    
                case 'CurrentURIMetaData':  $value = str_replace('&amp;', '&', $value);  break;
                case 'CurrentURI':          $value = htmlentities($value);     break;
            }

			$xml .= '<' . $key . '>' . $value . '</' . $key . '>';
		}

        $xml.= '</u:' . $method . '>';
        $xml.= '</s:Body>';
        $xml.= '</s:Envelope>';

		$xml = str_replace(array("\r", "\n", "\t"), "", $xml);

        return $xml;
    }

    /**
    * DOMDocument helper to return first non-DOMText child
    *
    * @access private 
    *
    * @param \DOMNodeList    $node   List of DOMNodes
    *
    * @return \DOMNode
    */
    private function getFirst($node) {

        $first = null;

        $i = 0;
        while($first == null || get_class($first) == 'DOMText') {

            $first = $node->childNodes->item($i);
            $i ++;
        }

        return $first;
    }

    /**
    * Transforms SOAPFault XML to Array
    *
    * @access private
    *
    * @param string $xml    Response XML
    *
    * @return array
    */
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

    /**
    * Transforms response XML to Array
    *
    * @access private
    *
    * @param string $method Method name
    * @param string $xml    Response XML
    *
    * @return array
    */
    private function parseResponse($method, $xml) {

        if($xml == '') {

            return array(); 
        }

        $hideLogs = $this->hideLogs;
        $original_xml = $xml;
        
        // Bad hack
        if(strstr($xml, 'parentID') !== false) {

            $xml = preg_replace('/ parentID="(.*)"/Uis', ' parentID="' . rand(1000, 9999) . '"', $xml);
        }

        $result = array();
        $action = $this->getAction($method);

        $params = $action['out'];

        if(!$hideLogs) Logger::debug('Expected response params: ' . print_r($params, true), self::$logfile);

        foreach($params as $param) {

            $name = $param['name'];

            $regex = sprintf('/<%s>(.*)<\/%s>/Uis', $name, $name);

            preg_match($regex, $xml, $tmp);

            if(count($tmp) == 2) {

                if(!$hideLogs) Logger::debug('Found ' . $name, self::$logfile);
                $result[$name] = $tmp[1];
            } else {

                if(!$hideLogs) Logger::warn('Unable to find ' . $name . ' in response', self::$logfile);
                throw new UPnPException('Missing response value: ' . $name);
            }
        }

        if($method == 'Browse' || $method == 'GetPositionInfo') {

            if(!$hideLogs) Logger::debug('Detected "Browse" or "GetPositionInfo" - begin parsing didl', self::$logfile);
            switch($method) {

                case 'Browse':          $tagname = 'Result'; break;
                case 'GetPositionInfo': $tagname = 'TrackMetaData'; break;
            }

            if(!$hideLogs) Logger::debug('Name of didl tag: "' . $tagname . '"', self::$logfile);

            $data = array();

            $xml = html_entity_decode($result[$tagname]);

            if(!$hideLogs) Logger::debug($xml, self::$logfile);

            if(strstr($xml, '&lt;') !== false) {

                if(!$hideLogs) Logger::debug('Didl contains "&lt;" -> htmlspecialchars_decode();', self::$logfile);
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

            if(!$hideLogs) Logger::debug($data, self::$logfile);

            if(!$hideLogs) Logger::debug('Move ' . $tagname . ' to ' . $tagname . '_XML in response', self::$logfile);
            if(!$hideLogs) Logger::debug('Add result as ' . $tagname . ' to response', self::$logfile);
            $result[$tagname . '_XML'] = $result[$tagname];
            $result[$tagname] = $data;
        }

        return $result;
    }
}
