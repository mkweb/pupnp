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

require_once('src/at/mkweb/upnp/init.php');

if(AuthManager::authEnabled()) {

    AuthManager::authenticate();
}

$config = Config::getAll();

$errors = array();
if(isset($_POST['save'])) {

    $new = $_POST['config'];

    $errors = Config::validate($new);

    print_r($errors);
    print_r($_POST);

    if(count($errors) == 0) {

        Config::change($new);

        $_SESSION['flash'] = _('Sucessfully saved.');

        header('Location: ?page=config');
        exit;
    }
}
?>
<html>
<head>
    <title>pUPnP Device Tester</title>

    <link rel="stylesheet" type="text/css" href="res/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="resources.php?css=style.css" />
            
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="res/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="res/js/pupnp.js"></script>
    <script type="text/javascript">
    $(document).ready(function () {

        $("[rel=tooltip]").tooltip({
            placement : 'right'
        });
    });
    </script>
</head>
<body>

<? require_once(dirname(__FILE__) . '/navigation.php') ?>

<div id="wrapper-all">

    <? if(isset($error)) echo '<div id="error">' . $error . '</div>'; ?>

    <div class="span12">
        <h2><?= _('Configuration') ?></h2>

        <?= $flash ?>

        <form action="" method="post">
            <table class="table table-striped table-config">
            <? foreach($config as $key => $data): ?>

                <? if(isset($data->hidden)): continue; endif ?>

                <tr valign="top">
                    <td><label for="<?= $key ?>"<?= (!isset($data->null) ? ' class="bold"' : '') ?>><?= $data->name ?></label></td>
                    <td>
                        <? switch($data->type): 

                            case 'string': ?>

                                <input type="text" name="config[<?= $key ?>]" id="<?= $key ?>"<?= (isset($errors[$key]) ? ' class="error"' : '') ?> value="<?= (isset($_POST['config'][$key]) ? $_POST['config'][$key] : $data->current) ?>" />
                                <?= (isset($errors[$key]) ? '<div class="error small">' . $errors[$key] . '</div>' : '') ?>
                                <? break ?>

                            <? case 'enum': ?>

                                <ul class="optgroup">
                                    <? foreach($data->values as $k => $v): ?>
                                    <li>
                                        <? $current = (isset($_POST['config'][$key]) ? $_POST['config'][$key] : $data->current); ?>
                                        <input type="radio" name="config[<?= $key ?>]" id="<?= $key ?>_<?= $k ?>" value="<?= $k ?>"<?= ($current == $k ? ' checked="checked"' : '') ?><?= (isset($errors[$key]) ? ' class="error"' : '') ?>  /> 
                                        <label for="<?= $key ?>_<?= $k ?>"><?= $v ?></label>
                                    </li>
                                    <? endforeach ?>
                                </ul>
                                <?= (isset($errors[$key]) ? '<div class="error small clear">' . $errors[$key] . '</div>' : '') ?>
                                <? break ?>

                            <? case 'bool': ?>

                                <? $current = (isset($_POST['config'][$key]) ? $_POST['config'][$key] : $data->current); ?>
                                <input type="hidden" name="config[<?= $key ?>]" value="off" />
                                <input type="checkbox" name="config[<?= $key ?>]" id="<?= $key ?>"<?= ($current == 1 ? ' checked="checked"' : '') ?><?= (isset($errors[$key]) ? ' class="error"' : '') ?> />
                                <?= (isset($errors[$key]) ? '<div class="error small clear">' . $errors[$key] . '</div>' : '') ?>
                                <? break ?>

                        <? endswitch ?>
                    </td>
                    <td>
                        <? if(isset($data->desc)): ?>
                            <a href="javascript://" rel="tooltip" title="<?= htmlspecialchars($data->desc) ?>"><img src="res/images/icons/info.png" /></a>
                        <? endif ?>
                    </td>
                </tr>

            <? endforeach ?>

            <tr>
                <td colspan="3" align="right">
                    <input type="submit" name="save" class="btn btn-inverse" value="<?= _('Save') ?>" style="float: right;" />
                </td>
            </tr>
            </table>
        </form>
    </div>
</div>
</body>
</html>
