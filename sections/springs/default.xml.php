<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<fn_sections>
	<type>dbview</type>
	<parent>menu_natural</parent>
	<position>90</position>
	<title>Sorgenti carsiche</title>
	<description></description>
	<startdate></startdate>
	<enddate></enddate>
	<status>1</status>
	<hidden>0</hidden>
	<accesskey></accesskey>
	<keywords></keywords>
	<sectionpath>sections</sectionpath>
	<level></level>
	<group_view>r_caves,w_caves</group_view>
	<group_edit></group_edit>
	<blocksmode></blocksmode>
	<blocks></blocks>
	<title_it>Sorgenti carsiche</title_it>
	<description_it></description_it>
	<title_en>karst springs</title_en>
	<description_en></description_en>
	<link>index.php?mod=springs</link>
	<image>http://localhost/speleoalex/speleo/openkis_work/images/section.png</image>
</fn_sections>

<fncf_dbview>
	<databasename>fndatabase</databasename>
	<recordsperpage>12</recordsperpage>
	<groupinsert></groupinsert>
	<groupadmin></groupadmin>
	<groupview></groupview>
	<mailalert></mailalert>
	<viewonlycreator>0</viewonlycreator>
	<generate_googlesitemap>1</generate_googlesitemap>
	<tables>ctl_springs</tables>
	<search_orders>title,recordinsert,recordupdate</search_orders>
	<defaultorder>recordupdate</defaultorder>
	<enable_comments_notify>0</enable_comments_notify>
	<search_min></search_min>
	<titlefield>title</titlefield>
	<descriptionfield>description</descriptionfield>
	<search_options></search_options>
	<navigate_groups>username</navigate_groups>
	<search_partfields></search_partfields>
	<search_fields>username</search_fields>
	<appendquery></appendquery>
	<image_titlefield>photo1</image_titlefield>
	<image_size>400</image_size>
	<image_size_h>300</image_size_h>
	<table_rules></table_rules>
	<enable_permissions_each_records>0</enable_permissions_each_records>
	<enable_permissions_edit_each_records>0</enable_permissions_edit_each_records>
	<permissions_records_groups>r_caves,w_caves</permissions_records_groups>
	<permissions_records_edit_groups>w_caves</permissions_records_edit_groups>
	<enable_history>0</enable_history>
	<enable_export>0</enable_export>
	<enable_delete>1</enable_delete>
	<hide_on_delete>0</hide_on_delete>
	<default_show_groups>0</default_show_groups>
	<enable_statistics>0</enable_statistics>
	<search_query_native_mysql>0</search_query_native_mysql>
	<enable_offlineform>1</enable_offlineform>
</fncf_dbview>