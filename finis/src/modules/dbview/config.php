<?php
/**
 * @package Finis_module_navigator
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
//[it]Nome del database xml
//[i18n]xml database name
$config['databasename'] = "fndatabase";
//[it]Records per pagina
//[i18n]Records per page
$config['recordsperpage'] = 12;
//[it]Limita inserimento al gruppo {fn_groups}
//[i18n]limit the insert of content to the group  {fn_groups}
$config['groupinsert'] = "";
//[it]Gruppo amministratori {fn_groups}
//[i18n]group administrators  {fn_groups}
$config['groupadmin'] = "";
//[it]Limita la visualizzazione dei contenuti al gruppo {fn_groups}
//[i18n]limit the display of content to the group  {fn_groups}
$config['groupview'] = "";
//[it]Mail di notifica nuovi contenuti
//[i18n]e-mail notification of insert
$config['mailalert'] = "";
//[it]Visualizza solo i propri record {1=SI,0=NO}
//[i18n]only displays your entries {1=yes,0=no}
$config['viewonlycreator'] = 0;
//[it]Genera una google sitemap con i records presenti {1=si,0=no}
//[i18n]generate google sitemap {1=yes,0=no}
$config['generate_googlesitemap'] = "1";
//[it]Nome della tabella
//[en]Table name
$config['tables'] = "fn_files";
//[it]Ordini
//[i18n]orders
$config['search_orders'] = "title,recordinsert,recordupdate";
//[it]Ordine predefinito per la visualizzazione records
//[i18n]default order to display records
$config['defaultorder'] = "recordupdate";
//[i18n]enable notifications on comments {1=yes,0=no}
$config['enable_comments_notify'] = 0;
//[i18n]fields to look for higher values of
$config['search_min'] = "";
//[i18n]field to be used as the title
$config['titlefield'] = "title";
//[i18n]field to be used as the description
$config['descriptionfield'] = "description";
//[i18n]fixed fields of research
$config['search_options'] = "";
//[i18n]fields to navigate as groups
$config['navigate_groups'] = "username";
//[i18n]fields of research
$config['search_partfields'] = "";
//[i18n]fields of research with exact match
$config['search_fields'] = "username";
//[i18n]append this query
$config['appendquery'] = "";
//[i18n]image field to display in list
$config['image_titlefield'] = "photo1";
//[i18n]image size width
$config['image_size'] = "400";
//[i18n]image size height
$config['image_size_h'] = "300";
//[i18n]table that contains the search rules
$config['table_rules'] = "";
//[i18n]allow permissions to display for each record {1=yes,0=no}
$config['enable_permissions_each_records'] = "0";
//[i18n]allow permissions to edit for each record {1=yes,0=no}
$config['enable_permissions_edit_each_records'] = "0";
//[i18n]permissions groups
$config['permissions_records_groups'] = "dbview_rw,dbview_ro";
//[i18n]permissions edit groups
$config['permissions_records_edit_groups'] = "dbview_rw,dbview_ro";
//[i18n]enables data history {1=yes,0=no}
$config['enable_history'] = "0";
//[i18n]enables export {1=yes,0=no}
$config['enable_export'] = "0";
//$appendquery = "";
//[i18n]enables delete {1=yes,0=no}
$config['enable_delete'] = "0";
//[i18n]hide record on delete {1=yes,0=no}
$config['hide_on_delete'] = "1";
//[i18n]default navigation {1=groups,0=default}
$config['default_show_groups'] = 0;
//[i18n]enables the viewing statistics {1=yes,0=no}
$config['enable_statistics'] = 0;
//[i18n]uses native mysql functions to perform a search {1=yes,0=no}
$config['search_query_native_mysql'] = 0;
//[i18n]enable offline form {1=yes,0=no}
$config['enable_offlineform'] = 1;
//[i18n]folder containing documents
$config['documents_folder']="";


?>