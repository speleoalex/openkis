<?php
$config=FN_LoadConfig();
if($config['tablename'] )
{
    FN_XmltableEditor($config['tablename']);
}
?>
