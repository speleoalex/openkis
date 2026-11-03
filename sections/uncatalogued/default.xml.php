<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<fn_sections>
	<type>dbview</type>
	<parent>menu_natural</parent>
	<position>45</position>
	<title>Cavit&agrave; non a catasto</title>
	<description></description>
	<startdate></startdate>
	<enddate></enddate>
	<status>1</status>
	<hidden>0</hidden>
	<accesskey></accesskey>
	<keywords></keywords>
	<sectionpath>sections</sectionpath>
	<level></level>
	<group_view>r_caves,w_caves,r_uncatalogued,w_uncatalogued</group_view>
	<group_edit></group_edit>
	<blocksmode></blocksmode>
	<blocks></blocks>
	<title_it>Cavit&agrave; non a catasto</title_it>
	<description_it></description_it>
	<title_en>Uncatalogued cavities</title_en>
	<description_en></description_en>
</fn_sections>

<fncf_dbview>
	<databasename>fndatabase</databasename>
	<recordsperpage>12</recordsperpage>
	<groupinsert>w_caves,w_uncatalogued</groupinsert>
	<groupadmin>w_caves,w_uncatalogued</groupadmin>
	<groupview>r_caves,r_uncatalogued</groupview>
	<mailalert></mailalert>
	<viewonlycreator>0</viewonlycreator>
	<generate_googlesitemap>0</generate_googlesitemap>
	<tables>ctl_uncatalogued</tables>
	<search_orders>name,recordupdate,code,lenght_total,depth_total</search_orders>
	<defaultorder>recordupdate</defaultorder>
	<enable_comments_notify>0</enable_comments_notify>
	<search_min>lenght_total,depth_total</search_min>
	<titlefield>code,name</titlefield>
	<descriptionfield>description</descriptionfield>
	<search_options></search_options>
	<navigate_groups>provincia,comune,areas</navigate_groups>
	<search_partfields>name,synonyms</search_partfields>
	<search_fields>code</search_fields>
	<appendquery></appendquery>
	<image_titlefield>photo1</image_titlefield>
	<image_size>100</image_size>
	<image_size_h>300</image_size_h>
	<table_rules></table_rules>
	<enable_permissions_each_records>0</enable_permissions_each_records>
	<enable_permissions_edit_each_records>0</enable_permissions_edit_each_records>
	<permissions_records_groups></permissions_records_groups>
	<permissions_records_edit_groups></permissions_records_edit_groups>
	<enable_history>0</enable_history>
	<enable_export>1</enable_export>
	<enable_delete>1</enable_delete>
	<hide_on_delete>1</hide_on_delete>
	<default_show_groups>1</default_show_groups>
	<enable_statistics>0</enable_statistics>
	<search_query_native_mysql>0</search_query_native_mysql>
	<enablecomments>0</enablecomments>
	<enableranks>0</enableranks>
	<default_view_mode>icon</default_view_mode>
	<enable_rss>1</enable_rss>
	<enable_offlineform>0</enable_offlineform>
</fncf_dbview>