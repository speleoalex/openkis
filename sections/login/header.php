<?php
global $_FN;
if (!empty($_FN['login_error']) && $_FN['login_error'] == 'user is not active')
{
    FN_Redirect("index.php?mod=registrazione_in_attesa");
}

?>