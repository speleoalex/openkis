<?php

FN_ManageLoginWithOpenAuthentication();
if ($_FN['user'])
{
    FN_Redirect($_FN['siteurl']);
}
$providerId = FN_GetParam("provider", $_GET);
if ($providerId == "")
{
    FN_Logout();
    $providers = FN_GetOpenAuthProviders();
    foreach ($providers as $provider)
    {
        echo "<div><a href=\"" . FN_RewriteLink("index.php?fnapp=oAuth&provider={$provider['id']}") . "\">{$provider['name']}</a></div>";
    }
    die();
}