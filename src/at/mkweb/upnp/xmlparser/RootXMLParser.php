<?php
namespace at\mkweb\upnp\xmlparser;

use at\mkweb\upnp\exception\UPnPException;

use \DOMDocument;

class RootXMLParser extends XMLParser {

    private $toAbsolutePath = array('presentationURL');

    private $data = array();
    private $icons = array();
    private $services = array();

    public function __construct($path) {

        if(!is_null($path)) {

            $this->parse($path);
        }
    }

    public function getId() {

        return $this->getData('UDN');
    }

    public function getName() {

        return $this->getData('friendlyName');
    }

    public function getData($key = null) {

        if(is_null($key)) {

            return (Object) $this->data;
        }

        return (isset($this->data[$key]) ? $this->data[$key] : null);
    }

    public function getIcons() {

        return $this->icons;
    }

    public function getServices() {

        return $this->services;
    }

    public function parse($path) {

        parent::__construct($path);

        $xml = file_get_contents($path);

#        header('Content-Type: text/xml');
#        echo $xml; exit;

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
