<?php
namespace at\mkweb\upnp\xmlparser;

use at\mkweb\upnp\execption\UPnPException;

use \DOMDocument;

class XMLParser {

    protected $host;
    protected $port;

    protected $specVersion_min = 0;
    protected $specVersion_max = 0;

    public function __construct($path = null) {

        if(!is_null($path)) {

            $tmp = parse_url($path);

            $this->host = $tmp['host'];
            $this->port = $tmp['port'];
        }
    }

    protected function isText($node) {

        if($node->hasChildNodes()) {

            $cnt = 0;
            foreach($node->childNodes as $tmp) $cnt ++;

            if($cnt == 1 && get_class($node->childNodes->item(0)) == 'DOMText') {

                return true;
            }
        }

        return false;
    }

    protected function parseSpecVersion(DOMDocument &$doc, $tag) {

        if($tag->hasChildNodes()) {

            foreach($tag->childNodes as $node) {

                switch($node->tagName) {

                    case 'major':   $this->specVersion_max = $node->textContent;     break;
                    case 'minor':   $this->specVersion_min = $node->textContent;     break;
                }
            }
        }
    }

    protected function relativeToAbsolutePath($path) {

        if(strtolower(substr($path, 0, 4)) == 'http') {

            return $path;
        }

        return sprintf('http://%s:%d/%s', $this->host, $this->port, ltrim($path, '/'));
    }
}
