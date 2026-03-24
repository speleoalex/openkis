<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>
	<field>
		<name>unirecid</name>
		<primarykey>1</primarykey>
		<extra>autoincrement</extra>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>txtid</name>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>title</name>
		<frm_en>Title</frm_en>
		<frm_it>Titolo</frm_it>
		<frm_i18n>title</frm_i18n>
		<frm_multilanguages>auto</frm_multilanguages>
	</field>
	<field>
		<name>argument</name>
		<frm_en>Argument</frm_it>
		<frm_it>Argomento</frm_it>
		<frm_i18n>argument</frm_i18n>
		<foreignkey>news_arguments</foreignkey>
		<fk_link_field>unirecid</fk_link_field>
		<fk_show_field>title</fk_show_field>
		<frm_show_image>icon</frm_show_image>
		<frm_required>1</frm_required>
	</field>
	<field>
		<name>status</name>
		<frm_en>Status</frm_en>
		<frm_it>Stato</frm_it>
		<frm_i18n>status</frm_i18n>
        <frm_type>radio</frm_type>
        <frm_options>1,0</frm_options>
		<frm_options_i18n>published,not published</frm_options_i18n>
	</field>
	<field>
		<name>summary</name>
		<frm_en>Summary</frm_en>
		<frm_it>Riassunto</frm_it>
		<frm_i18n>summary</frm_i18n>
		<frm_cols>auto</frm_cols>
		<frm_rows>6</frm_rows>
		<type>text</type>
		<frm_type>html</frm_type>
		<frm_multilanguages>auto</frm_multilanguages>
		<frm_required>1</frm_required>
	</field>
	<field>
		<name>body</name>
		<frm_en>Message body</frm_en>
		<frm_it>Corpo messaggio</frm_it>
		<frm_i18n>body</frm_i18n>
		<frm_cols>auto</frm_cols>
		<frm_rows>10</frm_rows>
		<type>text</type>
		<frm_type>html</frm_type>
		<frm_multilanguages>auto</frm_multilanguages>
	</field>
	<field>
		<name>photo1</name>
		<frm_en>News image</frm_en>
		<frm_it>Immagine notizia</frm_it>
		<frm_i18n>news image</frm_i18n>
		<frm_showinlist>1</frm_showinlist>
		<thumb_listheight>64</thumb_listheight>
		<type>image</type>
		<thumbsize>250</thumbsize>
		<view_tag>center</view_tag>
	</field>
	<field>
		<name>username</name>
		<frm_en>Author</frm_en>
		<frm_it>Autore messaggio</frm_it
		<frm_i18n>author</frm_i18n>>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>tags</name>
		<frm_en>Tags</frm_en>
		<frm_it>Tags</frm_it>
		<frm_i18n>tags</frm_i18n>
		<frm_type>string</frm_type>
	</field>
	<field>
		<name>date</name>
		<frm_en>Date</frm_en>
		<frm_it>Data</frm_it>
		<frm_i18n>date</frm_i18n>
		<frm_type>datetime</frm_type>
		<frm_dateformat>y-mm-dd 00:00:00</frm_dateformat>
	</field>
	<field>
		<name>startdate</name>
		<frm_en>Publication start date</frm_en>
		<frm_it>Data inizio pubblicazione</frm_it>
		<frm_i18n>publication start date</frm_i18n>
		<frm_type>datetime</frm_type>
		<frm_dateformat>y-mm-dd 00:00:00</frm_dateformat>
	</field>
	<field>
		<name>enddate</name>
		<frm_en>Publication end date</frm_en>
		<frm_it>Data fine pubblicazione</frm_it>
		<frm_i18n>publication end date</frm_i18n>
		<frm_type>datetime</frm_type>
		<frm_dateformat>y-mm-dd 00:00:00</frm_dateformat>
	</field>
	<field>
		<name>locktop</name>
		<frm_en>Lock top</frm_en>
		<frm_it>Blocca in alto</frm_it>
		<frm_i18n>lock top</frm_i18n>
		<frm_help_it>Blocca la notizia in alto alla pagina</frm_help_it>
		<frm_help_en>Lock the news in the top of page</frm_help_en>
		<frm_help_i18n>lock the news in the top of page</frm_help_i18n>
		<frm_type>check</frm_type>
	</field>
	<field>
		<name>guestnews</name>
		<frm_en>News proposal</frm_en>
		<frm_it>Segnalata</frm_it>
		<frm_fr>Signal&eacute;es</frm_fr>
		<frm_i18n>news proposal</frm_i18n>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>idimport</name>
		<frm_show>0</frm_show>
	</field>
<field>
		<name>title_en</name>
		<type>varchar</type>
		<frm_multilanguage>1</frm_multilanguage>
	</field>
<field>
		<name>title_it</name>
		<type>varchar</type>
		<frm_multilanguage>1</frm_multilanguage>
	</field>
<field>
		<name>summary_en</name>
		<type>text</type>
		<frm_multilanguage>1</frm_multilanguage>
	</field>
<field>
		<name>summary_it</name>
		<type>text</type>
		<frm_multilanguage>1</frm_multilanguage>
	</field>
<field>
		<name>body_en</name>
		<type>text</type>
		<frm_multilanguage>1</frm_multilanguage>
	</field>
<field>
		<name>body_it</name>
		<type>text</type>
		<frm_multilanguage>1</frm_multilanguage>
	</field>
</tables>
