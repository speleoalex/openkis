<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<fn_sections>
	<type>dbview</type>
	<parent>menu_fauna</parent>
	<position>180</position>
	<title>Fauna cavernicola</title>
	<description>contiene l'elenco degli animali cavernicoli</description>
	<startdate></startdate>
	<enddate></enddate>
	<status>1</status>
	<hidden>0</hidden>
	<accesskey></accesskey>
	<keywords></keywords>
	<sectionpath>sections</sectionpath>
	<level></level>
	<group_view></group_view>
	<group_edit></group_edit>
	<blocksmode></blocksmode>
	<blocks></blocks>
	<title_it>Fauna cavernicola</title_it>
	<description_it>contiene l'elenco degli animali cavernicoli</description_it>
	<title_en></title_en>
	<description_en></description_en>
</fn_sections>

<fncf_dbview>
	<databasename>fndatabase</databasename>
	<recordsperpage>12</recordsperpage>
	<groupinsert>fauna</groupinsert>
	<groupadmin>fauna</groupadmin>
	<groupview></groupview>
	<mailalert></mailalert>
	<viewonlycreator>0</viewonlycreator>
	<generate_googlesitemap>0</generate_googlesitemap>
	<tables>ctl_fauna</tables>
	<search_orders>name,order,scientific_name,recordinsert,recordupdate</search_orders>
	<defaultorder>recordupdate</defaultorder>
	<enable_comments_notify>0</enable_comments_notify>
	<search_min></search_min>
	<titlefield>scientific_name</titlefield>
	<descriptionfield>description</descriptionfield>
	<search_options>type</search_options>
	<navigate_groups>type,class,order,family</navigate_groups>
	<search_partfields>name,scientific_name</search_partfields>
	<search_fields></search_fields>
	<appendquery></appendquery>
	<image_titlefield>photo1</image_titlefield>
	<image_size>100</image_size>
	<image_size_h>300</image_size_h>
	<table_rules></table_rules>
	<enable_permissions_each_records>1</enable_permissions_each_records>
	<enable_permissions_edit_each_records>1</enable_permissions_edit_each_records>
	<permissions_records_groups>r_fauna,w_fauna</permissions_records_groups>
	<permissions_records_edit_groups>w_fauna</permissions_records_edit_groups>
	<enable_history>0</enable_history>
	<enable_export>0</enable_export>
	<enable_delete>0</enable_delete>
	<hide_on_delete>0</hide_on_delete>
	<default_show_groups>1</default_show_groups>
	<enable_statistics>0</enable_statistics>
	<search_query_native_mysql>0</search_query_native_mysql>
	<enable_offlineform>1</enable_offlineform>
</fncf_dbview>