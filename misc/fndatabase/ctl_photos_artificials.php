<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>			
    <field>
        <name>id</name>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
        <type>string</type>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>codeartificial</name>
        <frm_i8n>code</frm_i8n>
        <type>string</type>
        <view_show>1</view_show>
        <foreignkey>ctl_artificials</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>name</name>
        <frm_it>Nome foto</frm_it>
    </field>
    <field>
        <name>photo</name>
        <frm_it>Foto</frm_it>
        <type>image</type>
        <thumbsize>408</thumbsize>
        <frm_incremental>1</frm_incremental>
        <view_tag>center</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
        <googlemap>1</googlemap>
    </field>
    <field>
        <name>date</name>
        <frm_it>Data foto</frm_it>
        <type>datetime</type>
        <frm_dateformat>y-mm-dd</frm_dateformat>
    </field>
    <field>
        <name>author</name>
        <frm_it>Autore foto</frm_it>		
    </field>
<field>
        <name>license</name>
        <frm_i18n>license</frm_i18n>
        <foreignkey>ctl_licenses</foreignkey>
        <fk_link_field>name</fk_link_field>
        <fk_show_field>name</fk_show_field>		
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>description</name>
        <type>text</type>
        <frm_i18n>description</frm_i18n>
        <frm_rows>auto</frm_rows>
        <frm_cols>40</frm_cols>
    </field>
        <field>
        <name>priority</name>
        <frm_i18n>display priority</frm_i18n>
        <view_show>0</view_show>
        <type>int</type>
    </field>
    <driver>mysql</driver>
</tables>
