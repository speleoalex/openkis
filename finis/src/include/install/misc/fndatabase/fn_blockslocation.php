<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>
	<field>
		<name>id</name>
		<frm_required>1</frm_required>
		<primarykey>1</primarykey>
		<size>128</size>
	</field>
	<field>
		<name>title</name>
		<type>varchar</type>
		<frm_multilanguages>auto</frm_multilanguages>
	</field>
	<field>
		<name>position</name>
	</field>
	<field>
		<name>where</name>
	</field>
	<field>
		<name>startdate</name>
		<frm_en>Publication start date</frm_en>
		<frm_it>Data inizio pubblicazione</frm_it>
		<frm_type>datetime</frm_type>
		<frm_dateformat>y-mm-dd</frm_dateformat>
	</field>
	<field>
		<name>enddate</name>
		<frm_en>Publication end date</frm_en>
		<frm_it>Data fine pubblicazione</frm_it>
		<frm_type>datetime</frm_type>
		<frm_dateformat>y-mm-dd</frm_dateformat>
	</field>
	<field>
		<name>status</name>
		<frm_en>Status</frm_en>
		<frm_it>Stato</frm_it>
        <frm_type>radio</frm_type>
        <frm_options>1,0</frm_options>
        <frm_options_it>Pubblicata,Non pubblicata</frm_options_it>
        <frm_options_en>Active,Not active</frm_options_en>
	</field>
	<filename>blocks</filename>
</tables>

