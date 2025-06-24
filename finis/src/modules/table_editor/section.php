<?php
$config=FN_LoadConfig();
if($config['tablename'] )
{
    FN_XMETATableEditor($config['tablename']);
}
?>
