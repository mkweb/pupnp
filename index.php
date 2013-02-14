<?php
session_start();

$template = 'index';

if(isset($_GET['page']) && file_exists(dirname(__FILE__) . '/templates/' . $_GET['page'] . '.php')) {

    $template = $_GET['page'];
}

$flash = '';
if(isset($_SESSION['flash'])) {

    $flash = '<div class="flash">' . $_SESSION['flash'] . '</div>';
    unset($_SESSION['flash']);
}

require_once(dirname(__FILE__) . '/templates/' . $template . '.php');
