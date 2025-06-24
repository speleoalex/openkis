<?php
/**
 * @package Flatnux_module_news
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 * not modify this file
 * all variables are overwritten by values in the database
 * in misc/fndatabase/sections_news/settings.php
 * this file contains only the default values
 */
//[it]Nome tabella notizie
//[en]Table name news
//[i18n]table name news
$config['tablename']="news";
//[it]News per pagina
//[en]News per page
//[i18n]News per page
$config['newspp'] = 10;
//[it] Abilita i commenti sulle news {1=si,0=no}
//[i18n]enable comments {1=yes,0=no}
$config['enablecomments'] = 1;
//[it]un utente non registrato puo' inserire i commenti {1=si,0=no}
//[i18n]an anonymous user can insert comments {1=yes,0=no}
$config['guestcomment'] = 0;
//[it]Un utente puo' segnalare le news {1=si,0=no}
//[i18n]a user can report news {1=yes,0=no}
$config['signews'] = 1;
//[it]Un utente non registrato puo' segnalare le news {1=si,0=no}
//[i18n]an anonymous user can report news{1=YES,0=NO}
$config['guestnews'] = 0;
//[it]abilita la visualizzazione della notizia precedente e successiva {1=si,0=no}
//[i18n]enable view previous and next news {1=yes,0=no}
$config['show_prev_next_news'] = 1;
//[it]Visualizza le news dello stesso argomento alla fine della news {1=si,0=no}
//[i18n]show the news the same subject at the end{1=yes,0=no}
$config['show_same_argument_news'] = 0;
//[it]Visualizza l' avatar delle news nella pagina principale  {1=si,0=no}
//[i18n]show news image {1=yes,0=no}
$config['show_news_icon'] = 1;
//[it]Editor per inserire le news{0=Disabilitato,=Default,include/htmleditors}
//[en]News Editor{0=Disable,=Default,include/htmleditors}
//[i18n]news Editor{0=Disable,=Default,include/htmleditors}
$config['htmleditornews'] = "";
//[it]Gruppo di utenti abilitato a pubblicare le notizie
//[en]Qualified group of users to publish the news
//[i18n]qualified group of users to publish the news
$config['group_news'] = "news";
//[it]Abilita bottoni social network {1=si,0=no}
//[en]Enable social network buttons {1=yes,0=no}
//[i18n]enable social network buttons {1=yes,0=no}
$config['enable_socialnetworks'] = 0;
//[i18n]enable Facebook {1=yes,0=no}
$config['enable_facebook'] = 0;
//[it]Genera una google sitemap con i records presenti {1=si,0=no}
//[en]Generate google sitemap {1=yes,0=no}
//[i18n]generate google sitemap {1=yes,0=no}
$config['generate_googlesitemap']=1;
//[it]Abilita le notifiche sui commenti {1=si,0=no}
//[en]Enable notifications on comments {1=yes,0=no}
//[i18n]Enable notifications on comments {1=yes,0=no}
$config['enable_comments_notify']=0;
//[i18n]show rss icon {1=yes,0=no}
$config['show_rss_icon']=1;
//[i18n]show news tags {1=yes,0=no}
$config['show_tags']=1;
//[i18n]facebook appid
$config['fb_appid']="63008488243";
//[i18n]enable cache {1=yes,0=no}
$config['use_cache']=false;

