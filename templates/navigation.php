<?
$pages = array(
    'index' => _('Workspace'),
    'config' => _('Configuration'),
    'devicetest' => _('Debugging')
);
?>
<div class="navbar navbar-fixed-top"> 
  <div class="navbar-inner"> 
    <div class="container"> 
      <div class="nav-collapse"> 
        <ul class="nav"> 
            <? foreach($pages as $code => $name): ?>
                <li><a href="?page=<?= $code ?>"<?= (isset($template) && $template == $code ? ' class="active"' : '') ?>><?= $name ?></a></li>
            <? endforeach ?>
        </ul> 
        <div id="clock"></div>
     </div> 
   </div> 
  </div> 
</div> 
