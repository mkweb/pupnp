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

/**
* Image proxy
* This file is used to request images from different network
*
* Images gets cached to accelerate view
*
* @author Mario Klug <mario.klug@mk-web.at>
*/
$url = $_GET['image'];

if(strstr($url, ' ') !== false) {

    $url = str_replace(array('%', ' '), array('%25', '%20'), $url);
}

$hash = md5(serialize($_GET));

$cacheFile = 'cache/albumImages/' . $hash . '.jpg';
if(file_exists($cacheFile)) {

    header('Content-Type: image/jpeg');
    readfile($cacheFile);
    exit;
}

$temp = tempnam('/tmp', 'upnp_image_');
$binary =  file_get_contents($url);

if(false === $binary) {

    $binary = file_get_contents('res/images/unknown.jpg');
}

file_put_contents($temp, $binary);

$mime = mime_content_type($temp);

$tmp = explode('/', $mime);
$type = array_shift($tmp);
$format = $tmp[0];

if($type == 'image') {

    $img = null;

    switch($format) {

        case 'png':
            $img = imagecreatefrompng($temp);
            break;

        case 'jpeg':
            $img = imagecreatefromjpeg($temp);
            break;
    }

    if($img != null) {

        if(isset($_GET['sq'])) {

            $width = $height = $_GET['sq'];

            $new_image = imagecreatetruecolor($width, $height);

            $red = imagecolorallocate($new_image, 255, 0, 0);
            $black = imagecolorallocate($new_image, 0, 0, 0);
            imagecolortransparent($new_image, $black);

            imagecopyresampled($new_image, $img, 0, 0, 0, 0, $width, $height, imagesx($img), imagesy($img));

            $img = $new_image;
        }

        if(isset($_GET['w'])) {

            list($w, $h, $type, $attr)= getimagesize($temp);

            $width = $_GET['w'];
            $height = ($width * $h) / $w;

            $new_image = imagecreatetruecolor($width, $height);

            $red = imagecolorallocate($new_image, 255, 0, 0);
            $black = imagecolorallocate($new_image, 0, 0, 0);
            imagecolortransparent($new_image, $black);

            imagecopyresampled($new_image, $img, 0, 0, 0, 0, $width, $height, imagesx($img), imagesy($img));

            $img = $new_image;
        }


        header('Content-Type: ' . $mime);

        imagealphablending($img, false);
        imagesavealpha($img, true);

        ob_start();
        imagepng($img);
        
        $content = ob_get_clean();
        file_put_contents($cacheFile, $content);

        echo $content;
    } else {

        echo "Unknown MIME-Type: " . $mime;
    }
}

unlink($temp);
exit;
