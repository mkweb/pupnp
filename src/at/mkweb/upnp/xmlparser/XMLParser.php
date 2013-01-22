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

use at\mkweb\upnp\execption\UPnPException;

use \DOMDocument;

/**
* Helper class to parse UPnP relevant XMLs
* 
* @author Mario Klug <mario.klug@mk-web.at>
*/
class XMLParser {

    /**
    * Device hostname or IP
    *
    * @access protected
    * @var string
    */
    protected $host;

    /**
    * Device port
    *
    * @access protected
    * @var string
    */
    protected $port;

    /**
    * Supported UPnP Version (min)
    *
    * @access protected
    * @var int
    */
    protected $specVersion_min = 0;

    /**
    * Supported UPnP Version (max)
    *
    * @access protected
    * @var int
    */
    protected $specVersion_max = 0;

    /**
    * Constructor
    * Read host and port if path is set
    *
    * @access public
    * 
    * @param $path  XML-Url
    */
    public function __construct($path = null) {

        if(!is_null($path)) {

            $tmp = parse_url($path);

            $this->host = $tmp['host'];
            $this->port = $tmp['port'];
        }
    }

    /**
    * Check if this node is a DOMText-Node or if it only contains 1 DOMText node
    *
    * @access protected
    * 
    * @param \DOMElement $node
    *
    * @return boolean
    */
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

    /**
    * Search for min and max supported UPnP version
    *
    * @access protected
    * 
    * @param \DOMDocument $doc
    * @param \DOMElement $tag
    */
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

    /**
    * Transforms relative paths to absolute ones
    *
    * @access protected
    * 
    * @param string $path
    *
    * @return string
    */
    protected function relativeToAbsolutePath($path) {

        if(strtolower(substr($path, 0, 4)) == 'http') {

            return $path;
        }

        return sprintf('http://%s:%d/%s', $this->host, $this->port, ltrim($path, '/'));
    }
}
