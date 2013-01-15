<?php

$values = array(
    'title'     => _('Title'),
    'artist'    => _('Artist'),
    'album'     => _('Album'),
    'genre'     => _('Genre'),
    'originalTrackNumber' => _('Track#'),
    'date'      => _('Date'),
    'author'    => _('Author'),
    'actor'     => _('Actor'),
    'longDescription' => _('Description')
);
?>
<h2><?= $item->title ?></h2>

<? if(isset($image)): ?>
    <img src="backend.php?image=<?= urlencode($image) ?>&w=200" style="float: left; margin-right: 10px; margin-bottom: 10px;" />
<? endif ?>

<table>
<? foreach($values as $key => $value): ?>
<? if(isset($item->$key) && trim($item->$key != '')): ?>
<tr valign="top">
    <td><?= $value ?>:</td><td><?= $item->$key ?></td>
</tr>
<? endif ?>
<? endforeach ?>
</table>

<br class="clear" />
