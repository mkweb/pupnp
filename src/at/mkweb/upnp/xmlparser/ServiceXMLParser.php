<?php
namespace at\mkweb\upnp\xmlparser;

use at\mkweb\upnp\exception\UPnPException;

use \DOMDocument;

class ServiceXMLParser extends XMLParser {

    private $scpdUrl;
    private $controlUrl;
    private $eventSubUrl;

    private $type;
    private $id;

    private $actions;
    private $stateVars;

    public function __construct($data) {

        $this->type = $data->serviceType;
        $this->id   = $data->serviceId;

        if(isset($data->controlURL))     $this->controlUrl   = $data->controlURL;
        if(isset($data->eventSubURL))    $this->eventSubUrl  = $data->eventSubURL;
        if(isset($data->SCPDURL))        $this->scpdUrl      = $data->SCPDURL;

        $this->parse($this->scpdUrl);
    }

    public function getScdpUrl() {

        return $this->scpdUrl;
    }

    public function getControlUrl() {

        return $this->controlUrl;
    }

    public function getEventSubUrl() {

        return $this->eventSubUrl;
    }

    public function getType() {

        return $this->type;
    }

    public function getId() {

        return $this->id;
    }

    public function getActions() {

        return $this->actions;
    }

    public function getStateVar($name) {

        return (isset($this->stateVars[$name]) ? $this->stateVars[$name] : null);
    }

    public function parse($path) {

        parent::__construct($path);

        $path = trim($path);

        $xml = file_get_contents($path);

        #if($this->debug()) {
        #   header('Content-Type: text/xml');
        #   echo $xml; exit;
        #}

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
        
    function debug() {

         return ($this->id == 'urn:upnp-org:serviceId:ContentDirectory');
    }

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
