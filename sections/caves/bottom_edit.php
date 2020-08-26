<?php
$id=FN_GetParam("id",$_GET,"html");
$html.= "<hr /><a class=\"btn btn-primary\" href=\"{$_FN['siteurl']}openkis_photos.php?id={$id}&t=ctl_{$_FN['mod']}\">Inserimento rapido foto</a><hr />";

?>
