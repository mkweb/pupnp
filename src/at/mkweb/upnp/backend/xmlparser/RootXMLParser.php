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
namespace at\mkweb\upnp\backend\xmlparser;

use at\mkweb\upnp\exception\UPnPException;

use \DOMDocument;

/**
* Parser and data provider class for UPnP Device root XML
*
* @author Mario Klug <mario.klug@mk-web.at>
* @uses XMLParser
*/
class RootXMLParser extends XMLParser {

    /**
    * Values with these keys gets transformed 
    * from relative to absolute path if possible
    *
    * @access private
    * @var array
    */
    private $toAbsolutePath = array('presentationURL');

    /**
    * Main device data
    *
    * @access private
    * @var array
    */
    private $data = array();

    /**
    * Iconslist
    *
    * @access private
    * @var array
    */
    private $icons = array();

    /**
    * Available services
    *
    * @access private
    * @var array
    */
    private $services = array();

    /**
    * Constructor
    * If path equals null RootXMLParser::parse gets triggered
    *
    * @access public
    * @param string $path       Url to root XML
    */
    public function __construct($path = null) {

        if(!is_null($path)) {

            $this->parse($path);
        }
    }

    /**
    * Get UID of device
    *
    * @access public
    * 
    * @return string    if found or null
    */
    public function getId() {

        return $this->getData('UDN');
    }

    /**
    * Get friendlyName of device
    *
    * @access public
    * 
    * @return string    if found or null
    */
    public function getName() {

        return $this->getData('friendlyName');
    }

    /**
    * Get data parsed from root XML
    * If key is not set, all datas gets returned
    *
    * @accesss public
    *
    * @param string $key
    *
    * @return \stdObj
    */
    public function getData($key = null) {

        if(is_null($key)) {

            return (Object) $this->data;
        }

        return (isset($this->data[$key]) ? $this->data[$key] : null);
    }

    /**
    * Returns icon list if found
    *
    * @access public 
    *
    * @return array
    */
    public function getIcons() {

        return $this->icons;
    }

    /**
    * Returns service list if found
    *
    * @access public 
    *
    * @return array
    */
    public function getServices() {

        return $this->services;
    }

    /**
    * Parse given root XML
    * 
    * @access public
    * 
    * @param $path      URL to root XML
    * 
    * @throws at.mkweb.upnp.exception.UPnPException     if unable to load root XML
    */
    public function parse($path) {

        parent::__construct($path);

        $xml = file_get_contents($path);

        // Debugging
        // header('Content-Type: text/xml');
        // echo $xml; exit;

        if(false == $xml) {

            throw new UPnPException('Unable to load root XML: ' . $path);
        }

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $root = $doc->childNodes->item(0);

        if($root->hasChildNodes()) {

            foreach($root->childNodes as $rootChild) {

                if($rootChild->tagName == 'specVersion') {

                    $this->parseSpecVersion($doc, $rootChild);
                }

                if($rootChild->tagName == 'device') {

                    $this->parseDevice($doc, $rootChild);
                }
            }
        }
    }

    /**
    * Parse root XML device structure
    *
    * @access private
    *
    * @param \DOMDocument $doc
    * @param \DOMElement $tag
    *
    * @throws at.mkweb.upnp.exception.UPnPException     If an unknown tag appears
    */
    private function parseDevice(DOMDocument &$doc, $tag) {

        if($tag->hasChildNodes()) {

            foreach($tag->childNodes as $node) {

                if($this->isText($node)) {

                    $this->data[$node->tagName] = $node->textContent;
                } elseif($node->hasChildNodes()) {

                    switch($node->tagName) {

                        case 'iconList':    $this->parseIconList($doc, $node);      break;
                        case 'serviceList': $this->parseServiceList($doc, $node);   break;
                        case 'deviceList':                                          break;

                        default:
                            throw new UPnPException('Unkown tag in root XML: ' . $node->tagName);
                            break;
                    }
                }
            }
        }

        foreach($this->toAbsolutePath as $key) {

            if(isset($this->data[$key])) {

                $this->data[$key] = $this->relativeToAbsolutePath($this->data[$key]);
            }
        }

        unset($this->toAbsolutePath);
    }

    /**
    * Parse root XML icon structure
    *
    * @access private
    *
    * @param \DOMDocument $doc
    * @param \DOMElement $tag
    */
    private function parseIconList(DOMDocument &$doc, $tag) {

        if($tag->hasChildNodes()) {

            foreach($tag->childNodes as $childNode) {

                if($childNode->tagName == 'icon' && $childNode->hasChildNodes()) {

                    $icon = array();
                    foreach($childNode->childNodes as $node) {

                        if(get_class($node) != 'DOMText') {

                            $icon[$node->tagName] = $node->textContent;
                        }
                    }

                    $icon['url'] = $this->relativeToAbsolutePath($icon['url']);

                    $this->icons[] = (Object) $icon;
                }
            }
        }
    }

    /**
    * Parse root XML servicelist structure
    *
    * @access private
    *
    * @param \DOMDocument $doc
    * @param \DOMElement $tag
    */
    private function parseServiceList(DOMDocument &$doc, $tag) {

        if($tag->hasChildNodes()) {

            foreach($tag->childNodes as $node) {

                if($node->tagName == 'service' && $node->hasChildNodes()) {

                    $service = array();
                    foreach($node->childNodes as $childNode) {

                        if(get_class($childNode) != 'DOMText') {

                            $service[$childNode->tagName] = $childNode->textContent;
                        }
                    }

                    foreach($service as $key => $value) {

                        if(strtoupper(substr($key, -3)) == 'URL') {

                            $service[$key] = $this->relativeToAbsolutePath($value);
                        }
                    }

                    $this->services[$service['serviceType']] = (Object) $service;
                }
            }
        }
    }
}
