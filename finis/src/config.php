<?php
/**
 * not modify this file
 * all variables are overwritten by values in the database
 * in misc/fndatabase/fn_settings/settings.php
 * this file contains only the default values
 */
//[i18n]site name
$_FN['sitename']="Finis";
//[i18n]site title
$_FN['site_title']="Finis CMS";
//[i18n]site subtitle
$_FN['site_subtitle']="f.i.n.i.s.";
//[i18n]Keywords
$_FN['keywords']="";
//[i18n]Contents languages {+languages/*}
$_FN['languages']="en";
//[i18n]Default theme{./themes}
$_FN['theme']="default";
//[i18n]default control center theme{./controlcenter/themes}
$_FN['controlcenter_theme']="classic";
//[i18n]enable theme switch {1=YES,0=NO}
$_FN['switchtheme']=1;
//[i18n]site url (empty = autodetect)
$_FN['siteurl']="";
//[i18n]site email address
$_FN['site_email_address']="nobody@site_email_address.set"; //{required}
//[i18n]logs email address
$_FN['log_email_address']=""; //{required}
//[i18n]compress pages with gzip {1=YES,0=NO}
$_FN['enable_compress_gzip']=0;
//[i18n]default section{0=Disabilitato,./sections}
$_FN['home_section']="home";
//[i18n]show access key {1=YES,0=NO}
$_FN['showaccesskey']=0;
//[i18n]enable LOG email to administrator{1=YES,0=NO}
$_FN['enable_log_email']=0;
//[i18n]enable Apache mod_rewrite {1=YES,0=NO,2=FORCE}
$_FN['enable_mod_rewrite']=1;
//[i18n]path method {0=https://siteurl/folder/folder2/example.html,1=/folder/folder2/example.html}
$_FN['use_urlserverpath']=0;
//[i18n]links mode {{0=Disabilitato,./include/mod_rewrite}}
$_FN['links_mode']="html";
//[i18n]enable user registration {1=YES,0=NO}
$_FN['enable_registration']=1;
//[i18n]use email address as username {1=yes,0=NO}
$_FN['username_is_email']=0;
//[i18n]registration by mail confirmation {1=YES,0=NO}
$_FN['registration_by_email']=1;
//[i18n]allow users to stay connected {1=YES,0=NO}
$_FN['remember_login']=1;
//[i18n]enable captcha in login form {1=YES,0=NO}
$_FN['enable_captcha']=0;
//[i18n]html editor{0=Disable,include/htmleditors}
$_FN['htmleditor']="ckeditor4";
//[i18n]allows online administration {1=YES,0=NO}
$_FN['enable_online_administration']=1;
//[i18n]credits
$_FN['credits']="Powered by Finis";
//[i18n]site in maintenance {1=yes,0=no}
$_FN['maintenance']=0;
//[i18n]url update 
$_FN['url_update']='';
//[i18n]enable cache {1=yes,0=no}
$_FN['use_cache']=0;
//[i18n]timezone {""=get from server,timezone_identifiers_list()}
$_FN['timezone']="";
