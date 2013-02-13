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
use at\mkweb\upnp\Config;
use at\mkweb\upnp\frontend\AuthManager;
use at\mkweb\upnp\backend\UPnP;

require_once('src/at/mkweb/upnp/init.php');

if(AuthManager::authEnabled()) {

    AuthManager::authenticate();
}

/**
* This file is a quick'n'dirty debug interface, so please
* excuse that's uncommented and very badly written
*/

/**
* @doc http://recursive-design.com/blog/2007/04/05/format-xml-with-php/
*/
function formatXmlString($xml) {  
  
  // add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
  $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);
  
  // now indent the tags
  $token      = strtok($xml, "\n");
  $result     = ''; // holds formatted version as it is built
  $pad        = 0; // initial indent
  $matches    = array(); // returns from preg_matches()
  
  // scan each line and adjust indent based on opening/closing tags
  while ($token !== false) : 
  
    // test for the various tag states
    
    // 1. open and closing tags on same line - no change
    if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) : 
      $indent=0;
    // 2. closing tag - outdent now
    elseif (preg_match('/^<\/\w/', $token, $matches)) :
      $pad-=3;
    // 3. opening tag - don't pad this one, only subsequent tags
    elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
      $indent=2;
    // 4. no indentation needed
    else :
      $indent = 0; 
    endif;
    
    // pad the line with the required number of leading spaces
    $line    = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
    $result .= $line . "\n"; // add to the cumulative result, with linefeed
    $token   = strtok("\n"); // get the next token
    $pad    += $indent; // update the pad size for subsequent lines    
  endwhile; 
  
  return $result;
}

function buildInputRow(Array $field) {

    $name = $field['name'];
    $f = null;

    if(isset($_POST['params'][$name])) {

        $field['default'] = $_POST['params'][$name];
    }

    if(in_array($name, array('InstanceID', 'ConnectionID')) && !isset($field['default'])) {

        $field['default'] = 0;
    }

    switch($field['param']) {

        case 'str':
        case 'string':
        case 'ui4':
        case 'i4':

            $f = '<input type="text" id="' . $name . '" name="params[' . $name . ']" value="' . (isset($field['default']) ? $field['default'] : '') . '"' . ($readonly ? ' readonly="readonly"' : '') . ' />';
            break;

        case 'select':

            $f = '<select id="' . $name . '" name="params[' . $name . ']"' . ($readonly ? ' readonly="readonly"' : '') . '>';
            if($readonly) $f.= '<option></option>';
            foreach($field['options'] as $o) {

                $f.= '<option' . (isset($field['default']) && $field['default'] == $o ? ' selected="selected"' : '') . '>' . $o . '</option>';
            }
            $f.= '</select>';
            break;
    }

    $html = sprintf('<tr><td><label for="%s">%s</label></td><td>%s <small>(%s)</small></td></tr>', $name, $name, $f, $field['param']);

    return $html;
}

$devices = UPnP::getDevices();
$deviceList = array();

$device = null;
$services = array();

$client = null;
$actions = array();

$action = null;

foreach($devices as $uid => $data) {

    $device = UPnP::getDevice($uid);

    $icon = null;
    $name = $device->getName();
    $icons = $device->getIcons();

    if(count($icons) > 0) {

        $first = array_shift($icons);

        $icon = $first->url;
    }

    $deviceList[$uid] = (Object)array(
        'name' => $name,
        'icon' => $icon
    );
}

$device = null;
try {
    if(isset($_GET['d'])) {

        $d = $_GET['d'];

        $device = UPnP::getDevice($d);
        $services = $device->getServices();

        if(isset($_GET['s'])) {

            $serviceCode = $_GET['s'];

            $client = $device->getClient($serviceCode);
            $service = $device->getService($serviceCode);
            $actions = $service->getActions();

            if(isset($_GET['a'])) {

                $actionName = $_GET['a'];

                if(isset($actions[$actionName])) {

                    $action = $actions[$actionName];

                    foreach($action as &$direction) {

                        foreach($direction as &$method) {

                            $method['param'] = 'str';

                            if(isset($method['relatedStateVariable'])) {

                                $var = $service->getStateVar($method['relatedStateVariable']);

                                if(!is_null($var)) {

                                    $method['param'] = $var->dataType;

                                    if(isset($var->allowedValueList)) {

                                        $method['param'] = 'select';
                                        $method['options'] = $var->allowedValueList;
                                    }

                                    if(isset($var->defaultValue)) $method['default'] = $var->defaultValue;
                                    if(isset($var->allowedValueRange)) $method['default'] = $var->allowedValueRange;
                                }
                            }
                        }
                    }
                }

                if(isset($_POST['send'])) {

                    $params = $_POST['params'];

                    if(is_null($params)) $params = array();

                    $client = $device->getClient($serviceCode);
                    $response = $client->call($actionName, $params, false);
                }
            }
        }
    }
} catch (\Exception $e) {

    $error = get_class($e) . ': ' . $e->getMessage();
}
?>
<html>
<head>
    <title>pUPnP Device Tester</title>

    <link rel="stylesheet" type="text/css" href="res/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="resources.php?css=style.css" />
    <style type="text/css">
    #right {
        margin-left: 10px;
    }
    input[type=text] {
        border: 1px solid #666;
        padding: 4px;
    }
    ul#devicelist {
        list-style-type: none;
        margin: 0;
        padding: 0;
    }
    ul#devicelist > li {
        background-color: #F1F1F1;
        border: 1px solid #DEDEDE;
        border-radius: 2px;
        margin: 3px 4px 4px;
        padding: 10px 6px;
        box-shadow: 0 0 2px #000;
    }
    ul#devicelist > li:hover {
        box-shadow: 0 0 4px #000;
    }
    ul#devicelist a {
        text-decoration: none;
        color: #666;
    }
    ul#devicelist a.active {
        color: #000;
        font-weight: bold;
    }
    ul#devicelist > li > a {
        font-size: 26pt;
    }
    ul#devicelist > li > ul > li > a {
        font-size: 20pt;
    }
    .column {
        float: left !important;
    }
    .desc {
        display: block !important;
    }
    textarea {
        width: 99%;
    }
    </style>
</head>
<body>

<div class="navbar navbar-fixed-top"> 
  <div class="navbar-inner"> 
    <div class="container"> 
      <div class="nav-collapse"> 
        <ul class="nav"> 
            <li><a href="index.php"><?= _('Workspace') ?></a></li>
            <li><a href="devicetest.php" class="active"><?= _('Debugging') ?></a></li>
        </ul> 
     </div> 
   </div> 
  </div> 
</div> 

<div id="wrapper-all">

    <? if(isset($error)) echo '<div id="error">' . $error . '</div>'; ?>

    <div class="span5">
        <h2><?= _('Devices') ?></h2>

        <div class="desc">
            <ul id="devicelist">
            <? foreach($deviceList as $uid => $dev): ?>

                <li>
                    <a href="<?= basename(__FILE__) ?>?d=<?= $uid ?>"<?= (!is_null($device) && $device->getId() == $uid ? ' class="active"' : '') ?>>
                        <img src="resources.php?image=<?= urlencode($dev->icon) ?>&sq=30" />
                        <?= $dev->name ?>
                    </a>
                    <? if(!is_null($device) && $device->getId() == $uid): ?>
                    <ul>
                        <? foreach($services as $serv): ?>
                            <li>
                                <a href="<?= basename(__FILE__) ?>?d=<?= $uid ?>&s=<?= $serv ?>"<?= (!is_null($serviceCode) && $serviceCode == $serv ? ' class="active"' : '') ?>><?= $serv ?></a>
                                <? if(!is_null($serviceCode) && $serviceCode == $serv): ?>
                                <ul>
                                    <? foreach($actions as $key => $data): ?>
                                        <li>
                                            <a href="<?= basename(__FILE__) ?>?d=<?= $uid ?>&s=<?= $serv ?>&a=<?= $key ?>"<?= (!is_null($actionName) && $actionName == $key ? ' class="active"' : '') ?>><?= $key ?></a>
                                        </li>
                                    <? endforeach ?>
                                </ul>
                                <? endif ?>
                            </li>
                        <? endforeach ?>
                    </ul>
                    <? endif ?>
                </li>
            <? endforeach ?>
            </ul>
        </div>
        <br class="clear" />
    </div>

    <? if(!is_null($action)): ?>
        <div id="right" class="span7">

            <h2><?= $actionName ?></h2>

            <h2><?= _('Input') ?></h2>

            <div class="desc">
                <?= _('Parameters') ?>:<br /><br />
                <form action="" method="post">
                    <? if(!isset($action['in']) || count($action['in']) == 0): ?>
                        <?= _('No request parameters') ?><br />
                    <? else: ?>
                        <table>
                            <? foreach($action['in'] as $param): ?>
                                <?= buildInputRow($param) ?>
                            <? endforeach ?>
                        </table>
                    <? endif ?>
                    <br />
                    <input type="submit" name="send" value="<?= _('Invoke') ?>" />
                </form>
            </div>

            <? if(!is_null($response)): ?>
            <h2><?= _('Output') ?></h2>
            <textarea rows="12" cols="100" class="desc"><?= formatXmlString($response) ?></textarea>
            <? endif ?>

        </div>
    <? endif ?>

</div>

</body>
</html>
