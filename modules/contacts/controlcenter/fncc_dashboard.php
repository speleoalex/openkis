<?php

/**
 * @package Flatnux_module_contacts
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
$config=FN_LoadConfig("modules/contacts/config.php");
$tablename=empty($config['tablename']) ? "contact_message" : $config['tablename'];
$messages=FN_XMLQuery("SELECT * FROM $tablename ORDER BY id DESC LIMIT 1,5");
if (is_array($messages))
{
    echo "<b>".FN_Translate("list of messages")."</b><br />";
    foreach($messages as $message)
    {
        //echo $message['contact']." ";
        if (!empty($message['name']))
            echo $message['name']." ";
        if (!empty($message['subject']))
            echo $message['subject']." ";
        if (!empty($message['date']))
            echo FN_FormatDate ($message['date'])." ";
        
        echo "<a href=\"?op___xdb_{$tablename}=view&pk___xdb_{$tablename}={$message['id']}&mod={$_FN['mod']}&opt=fnc_ccnf_section_{$_FN['mod']}\">".FN_Translate("read message")."</a>";
        echo "<br />";
    }
}
else
    echo FN_Translate("no result");
?>