<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<fn_sections>
	<type>dbview</type>
	<parent>menu_natural</parent>
	<position>56</position>
	<title>Temperatures and measurements</title>
	<description>Underground temperature measurements and monitorings (UCC project), water analyses</description>
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
	<title_it>Temperature e misurazioni</title_it>
	<description_it>Misure e monitoraggi della temperatura sotterranea (progetto UCC), analisi delle acque</description_it>
	<title_en>Temperatures and measurements</title_en>
	<description_en>Underground temperature measurements and monitorings (UCC project), water analyses</description_en>
</fn_sections>

<fncf_dbview>
	<databasename>fndatabase</databasename>
	<recordsperpage>24</recordsperpage>
	<groupinsert>w_caves</groupinsert>
	<groupadmin>w_caves</groupadmin>
	<groupview></groupview>
	<mailalert></mailalert>
	<viewonlycreator>0</viewonlycreator>
	<generate_googlesitemap>0</generate_googlesitemap>
	<tables>ctl_temperatures</tables>
	<search_orders>sort_date,name,cave,temperature,t_mean,recordupdate</search_orders>
	<defaultorder>recordupdate</defaultorder>
	<enable_comments_notify>0</enable_comments_notify>
	<search_min></search_min>
	<titlefield>name</titlefield>
	<descriptionfield>notes</descriptionfield>
	<search_options>type,medium</search_options>
	<navigate_groups>type</navigate_groups>
	<search_partfields>name,cave,spring,position,parameters,bibliography</search_partfields>
	<search_fields></search_fields>
	<appendquery></appendquery>
	<image_titlefield>photo1</image_titlefield>
	<image_size>100</image_size>
	<image_size_h>300</image_size_h>
	<table_rules></table_rules>
	<enable_permissions_each_records>1</enable_permissions_each_records>
	<enable_permissions_edit_each_records>0</enable_permissions_edit_each_records>
	<permissions_records_groups></permissions_records_groups>
	<permissions_records_edit_groups>w_caves</permissions_records_edit_groups>
	<enable_history>1</enable_history>
	<enable_export>1</enable_export>
	<enable_delete>1</enable_delete>
	<hide_on_delete>0</hide_on_delete>
	<default_show_groups>0</default_show_groups>
	<enable_statistics>0</enable_statistics>
	<search_query_native_mysql>0</search_query_native_mysql>
	<enablecomments>0</enablecomments>
	<enableranks>0</enableranks>
	<default_view_mode>list</default_view_mode>
	<enable_rss>0</enable_rss>
	<enable_offlineform>0</enable_offlineform>
</fncf_dbview>
