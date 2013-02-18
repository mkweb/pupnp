<?php
namespace at\mkweb\upnp;

require_once('src/at/mkweb/upnp/autoload.php');
require_once('src/at/mkweb/upnp/Config.php');

define('DS', DIRECTORY_SEPARATOR);
define('BASE_PATH', '.');

$configFile = BASE_PATH . DS . 'conf' . DS . 'config.ini';

Autoloader::register();
Config::init($configFile);

if(isset($_SERVER['SERVER_ADDR'])) {

    Config::write('host_name', $_SERVER['SERVER_ADDR']);
}

function pr($value) {
    echo '<pre>';
    print_r($value);
    echo '</pre>';
}

function is_utf8($str){
  $strlen = strlen($str);
  for($i=0; $i<$strlen; $i++){
    $ord = ord($str[$i]);
    if($ord < 0x80) continue; // 0bbbbbbb
    elseif(($ord&0xE0)===0xC0 && $ord>0xC1) $n = 1; // 110bbbbb (exkl C0-C1)
    elseif(($ord&0xF0)===0xE0) $n = 2; // 1110bbbb
    elseif(($ord&0xF8)===0xF0 && $ord<0xF5) $n = 3; // 11110bbb (exkl F5-FF)
    else return false; // ungültiges UTF-8-Zeichen
    for($c=0; $c<$n; $c++) // $n Folgebytes? // 10bbbbbb
      if(++$i===$strlen || (ord($str[$i])&0xC0)!==0x80)
        return false; // ungültiges UTF-8-Zeichen
  }
  return true; // kein ungültiges UTF-8-Zeichen gefunden
}

