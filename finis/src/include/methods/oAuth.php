<?php
/*

https://www.sparkilla.com/meteofunghi/index.php
https://www.sparkilla.com/meteofunghi/index.php?fnapp=oAuth
https://www.sparkilla.com/meteofunghi/?fnloginprovider=1
https://www.sparkilla.com/meteofunghi/?fnloginprovider=google

https://console.cloud.google.com/apis/dashboard?hl=it
*/
FN_ManageLoginWithOpenAuthentication();
if ($_FN['user']) {
    FN_Redirect($_FN['siteurl'] . $_FN['selfscript']);
}
$providerId = FN_GetParam("provider", $_GET);
if ($providerId == "") {
    FN_Logout();
    $providers = FN_GetOpenAuthProviders();
    foreach ($providers as $provider) {
        $url = "{$_FN['siteurl']}{$_FN['selfscript']}?fnapp=oAuth&provider={$provider['id']}";
        echo "<div><a href=\"" . "{$url}" . "\">{$provider['name']}</a></div>";
    }
    die();
}
