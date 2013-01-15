<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

use at\mkweb\upnp\UPnP;
use at\mkweb\upnp\exception\UPnPException;

require_once('src/at/mkweb/upnp/autoload.php');
at\mkweb\upnp\Autoloader::register();

require_once('class.AjaxHandler.php');

function pr($value) {
    echo '<pre>';
    print_r($value);
    echo '</pre>';
}
if(isset($_GET['image'])) {

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
}

$action = $_GET['action']; unset($_GET['action']);

try {
	$handler = new AjaxHandler();
	$handler->call($action, $_GET);

} catch (UPnPException $e) {

	$handler = new AjaxHandler();

	$handler->respond(array(
		'error' => $e->getMessage()
	));
}
exit;
try {

#    UPnP::findDevices(); exit;
#    pr(UPnP::getDevices());exit;
    $device = UPnP::getDevice('uuid:27035582-6095-cb8a-92a0-65591a70317e');

    $client = $device->getClient('ContentDirectory');

    $client->Browse(array('ObjectID' => 'XCEL.0.pDh23BgNQ4qhmeBLV16ZXcfEyKc', 'BrowseFlag' => 'BrowseDirectChildren'));

} catch (\Exception $e) {

    echo get_class($e) . ': ' . $e->getMessage();
}
