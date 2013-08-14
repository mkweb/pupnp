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
* pUPnP Playlist class
*
* @package at.mkweb.upnp.backend
* @author Mario Klug <mario.klug@mk-web.at>
*/
class Playlist {

    private static $logfile = 'Playlist';

    /**
    * Device UID
    * @var str
    */
    private $deviceId;

    /**
    * Current active itemId
    * @var str
    */
    private $currentPlaying;

    /**
    * Playlist items
    * @var array
    */
    private $items = array();

    public function __construct($deviceId) {

        $this->deviceId = $deviceId;

        $file = $this->getFile();

        if(file_exists($file)) {

            $data = unserialize(file_get_contents($file));

            $this->currentPlaying = $data['current'];
            $this->items = $data['items'];
        }
    }

    /**
    * Returns all items in playlist
    *
    * @access public
    *
    * @return array
    */
    public function getAll() {

        $items = array();
        foreach($this->items as $uid => $item) {
    
            foreach($item as $key => $value) {

                $items[$uid][$key] = utf8_encode($value);
            }
        }

        return array(
            'current' => $this->currentPlaying,
            'items' => $items
        );
    }

    /**
    * Returns selected items if it exists
    *
    * @access public
    *
    * @param str    ItemID
    *
    * @return array
    */
    public function getItem($itemId) {

        $item = null;
        if(isset($this->items[$itemId])) {

            $item = $this->items[$itemId];
        }

        return $item;
    }

    /**
    * Adds an item, creates a random hash and update playlist file
    *
    * @access public
    *
    * @param mixed $item
    *
    * @return str   Generated hash
    */
    public function addItem($item) {

        Logger::debug('Starting ' . __METHOD__ . ' with item: ' . print_r($item, true), self::$logfile);

        $id = md5(microtime() . serialize($item));

        $item['id'] = $id;
        $this->items[$id] = $item;

        $this->save();

        Logger::debug("Returning ID: " . $id, self::$logfile);

        return $id;
    }

    /**
    * Removes an item from playlist
    *
    * @access public
    *
    * @param str $itemId
    */
    public function removeItem($itemId) {

        Logger::debug(__METHOD__, self::$logfile);

        if(isset($this->items[$itemId])) {

            Logger::debug('Item: ' . print_r($this->items[$itemId], true), self::$logfile);
            unset($this->items[$itemId]);
        } else {

            Logger::warn('Unable to find ' . $itemId, self::$logfile);
        }

        $this->save();
    }

    /**
    * Set item as currently playing
    *
    * @access public
    *
    * @param str $itemId
    */
    public function setPlaying($itemId) {

        Logger::debug(__METHOD__, self::$logfile);

        if(isset($this->items[$itemId])) {

            Logger::debug('Item: ' . print_r($this->items[$itemId], true), self::$logfile);
            $this->currentPlaying = $itemId;
        } else {

            Logger::debug('Unable to find ' . $itemId, self::$logfile);
        }

        $this->save();
    }

    /**
    * Unset currently playing
    *
    * @access public
    *
    * @param str $itemId
    */
    public function stop() {

        $this->currentPlaying = null;

        $this->save();
    }

    /**
    * Get next item if possible
    *
    * @access public
    *
    * @param str $itemId
    */
    public function next() {

        Logger::debug(__METHOD__, self::$logfile);

        if($this->currentPlaying == null) {

            Logger::debug('Playlist is stopped - ignore next();', self::$logfile);
            return;
        }

        $found = false;

        $next = null;
        foreach($this->items as $id => $item) {

            if($found) {

                $next = $id;
                break;
            }

            if($id == $this->currentPlaying) {

                $found = true;
            } 
        }

        if(!$found && count($this->items) > 0) {

            $tmp = $this->items;
            $item = array_shift($tmp);

            $next = $item['id'];
        }

        if(!is_null($next)) {

            $this->currentPlaying = $next;

            Logger::debug('Found next: ' . print_r($item, true), self::$logfile);
            $handler = new AjaxHandler();
            $handler->startPlayFromPlaylist($this->deviceId, array('id' => $next));

            $this->setPlaying($next);
        } else {

            Logger::debug('No next item found', self::$logfile);
            $this->stop();
        }

        $this->save();

        return !is_null($next);
    }

    /**
    * Return expected cache file location
    *
    * @access private
    *
    * @return str
    */
    private function getFile() {

        $file = 'cache' . DIRECTORY_SEPARATOR . 'playlist_' . $this->deviceId . '.serialized';

        return $file;
    }

    private function save() {

        $file = $this->getFile();

        $data = array(
            'current' => $this->currentPlaying,
            'items' => $this->items
        );

        Logger::debug('Saving to ' . $file, self::$logfile);

        $res = file_put_contents($file, serialize($data));

        if(!$res) {

            Logger::error('Not able to write ' . $file, self::$logfile);
        }
    }
}
