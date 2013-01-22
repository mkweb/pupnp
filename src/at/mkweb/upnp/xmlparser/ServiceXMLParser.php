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
namespace at\mkweb\upnp\xmlparser;

use at\mkweb\upnp\exception\UPnPException;

use \DOMDocument;

/**
* Parser and data provider class for UPnP Device service XML
*
* @author Mario Klug <mario.klug@mk-web.at>
*/
class ServiceXMLParser extends XMLParser {

    /**
    * URL to service description
    *
    * @access private
    * @var string
    */
    private $scpdUrl;

    /**
    * Control URL
    *
    * @access private
    * @var string
    */
    private $controlUrl;

    /**
    * URL for eventing
    *
    * @access private
    * @var string
    */
    private $eventSubUrl;

    /**
    * UPnP ServiceType
    *
    * @access private
    * @var string
    */
    private $type;

    /**
    * UPnP Service UID
    *
    * @access private
    * @var string
    */
    private $id;

    /**
    * Action storage
    *
    * @access private
    * @var array
    */
    private $actions;

    /**
    * StateVar storage
    *
    * @access private
    * @var array
    */
    private $stateVars;

    /**
    * Constructor
    * Triggers ServiceXMLParser::parse() for scpdUrl
    *
    * @access public
    * @param array $data    Service data from RootXMLParser
    */
    public function __construct($data) {

        $this->type = $data->serviceType;
        $this->id   = $data->serviceId;

        if(isset($data->controlURL))     $this->controlUrl   = $data->controlURL;
        if(isset($data->eventSubURL))    $this->eventSubUrl  = $data->eventSubURL;
        if(isset($data->SCPDURL))        $this->scpdUrl      = $data->SCPDURL;

        $this->parse($this->scpdUrl);
    }

    /**
    * Get service description URL
    *
    * @access public
    * 
    * @return string    if found or null
    */
    public function getScdpUrl() {

        return $this->scpdUrl;
    }

    /**
    * Get control URL
    *
    * @access public
    * 
    * @return string    if found or null
    */
    public function getControlUrl() {

        return $this->controlUrl;
    }

    /**
    * Get eventing URL
    *
    * @access public
    * 
    * @return string    if found or null
    */
    public function getEventSubUrl() {

        return $this->eventSubUrl;
    }

    /**
    * Get ServiceType
    *
    * @access public
    * 
    * @return string    if found or null
    */
    public function getType() {

        return $this->type;
    }

    /**
    * Get UID of this service
    *
    * @access public
    * 
    * @return string    if found or null
    */
    public function getId() {

        return $this->id;
    }

    /**
    * Get action list
    *
    * @access public
    * 
    * @return array
    */
    public function getActions() {

        return $this->actions;
    }

    /**
    * Get stateVars
    *
    * @access public
    *
    * @param string $name
    * 
    * @return array
    */
    public function getStateVar($name) {

        return (isset($this->stateVars[$name]) ? $this->stateVars[$name] : null);
    }

    /**
    * Parse given service XML
    * 
    * @access public
    * 
    * @param $path      URL to service XML
    * 
    * @throws at.mkweb.upnp.exception.UPnPException     if unable to load service XML
    */
    public function parse($path) {

        parent::__construct($path);

        $path = trim($path);

        $xml = file_get_contents($path);

        // Debugging:
        // header('Content-Type: text/xml');
        // echo $xml; exit;

        if(false == $xml) {

            throw new UPnPException('Unable to load service XML: ' . $path);
        }

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $root = $doc->childNodes->item(0);

        if($root->hasChildNodes()) {

            foreach($root->childNodes as $childNode) {

                switch($childNode->tagName) {

                    case 'specVersion':         $this->parseSpecVersion($doc, $childNode);  break;
                    case 'actionList':          $this->parseActionList($doc, $childNode);   break;
                    case 'serviceStateTable':   $this->parseStateVars($doc, $childNode);    break;
                }
            }
        }
    }

    /**
    * Parse service XML action structure
    *
    * @access private
    *
    * @param \DOMDocument $dom
    * @param \DOMElement $node
    */
    private function parseActionList(DOMDocument &$dom, $node) {

        if($node->hasChildNodes()) {

            foreach($node->childNodes as $childNode) {

                if(get_class($childNode) != 'DOMText' && $childNode->tagName == 'action' && $childNode->hasChildNodes()) {

                    $action = (Object) array(
                        'name' => null,
                        'arguments' => array(
                            'in' => array(),
                            'out' => array()
                        )
                    );

                    foreach($childNode->childNodes as $child) {

                        if($child->tagName == 'name') {

                            $action->name = $child->textContent;
                        } elseif($child->tagName == 'argumentList') {

                            foreach($child->childNodes as $argument) {

                                if($argument->tagName != 'argument') continue;

                                $data = array();

                                foreach($argument->childNodes as $argumentChild) {

                                    if(get_class($argumentChild) != 'DOMText') {

                                        $data[$argumentChild->tagName] = $argumentChild->textContent;
                                    }
                                }

                                $direction = $data['direction'];
                                unset($data['direction']);
        
                                $action->arguments[$direction][] = $data;
                            }
                        }
                    }

                    $this->actions[$action->name] = $action->arguments;
                }
            }
        }
    }
        
    /**
    * Parse service XML statevars
    *
    * @access private
    *
    * @param \DOMDocument $dom
    * @param \DOMElement $node
    */
    private function parseStateVars(DOMDocument &$dom, $node) {

        if($node->hasChildNodes()) {

            foreach($node->childNodes as $stateVar) {

                $variable = array();

                if($stateVar->hasChildNodes()) {

                    foreach($stateVar->childNodes as $childNode) {

                        if($this->isText($childNode)) {

                            $variable[$childNode->tagName] = $childNode->textContent;
                        } else {

                            if(get_class($childNode) != 'DOMText') {

                                switch($childNode->tagName) {

                                    case 'allowedValueList':

                                        $list = array();
                                        foreach($childNode->childNodes as $value) {

                                            if(get_class($value) != 'DOMText' && $this->isText($value)) {

                                                $list[] = $value->textContent;
                                            }
                                        }

                                        $variable['allowedValueList'] = $list;
                                        break;

                                    case 'allowedValueRange':

                                        $list = array();
                                        foreach($childNode->childNodes as $value) {

                                            if(get_class($value) != 'DOMText' && $this->isText($value)) {

                                                $list[$value->tagName] = $value->textContent;
                                            }
                                        }

                                        $variable['allowedValueRange'] = $list;
                                        break;

                                    case 'defaultValue':

                                        $variable['defaultValue'] = $childNode->textContent;
                                        break;

                                    default:

                                        if(!is_null($childNode->tagName)) {

                                            throw new UPnPException('Unknown tag in StateVar: ' . var_export($childNode->tagName, true));
                                        }
                                        break;
                                }
                            }
                        }
                    }
                }

                if(isset($variable['name'])) {

                    $this->stateVars[$variable['name']] = (Object) $variable;
                }
            }
        }
    }
}
