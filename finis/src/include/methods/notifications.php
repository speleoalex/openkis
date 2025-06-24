<?php
/**
 * @package Finis
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2021
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
global $_FN;
$notifications = array();
$display = array();
if (!empty($_FN['user']))
{
    $id = FN_GetParam("display", $_GET, "int");
    $notifications = FN_GetNotificationsUndisplayed($_FN['user']);
    if ($id)
    {
        foreach ($notifications as $notification)
        {
            if ($notification['id'] == $id)
            {
                $display[] = $notification;
                FN_SetNotificationDisplayed($id);
                break;
            }
        }
    }
    else
    {
        $display = $notifications;
    }
}
header('Content-Type: application/json');
echo json_encode($notifications);