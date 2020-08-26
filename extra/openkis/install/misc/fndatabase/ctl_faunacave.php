<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>
    <field>
        <name>id</name>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>name</name>
        <frm_it>Nome scientifico specie rilevata</frm_it>
        <frm_en>name</frm_en>
        <frm_type>stringselect</frm_type>
        <foreignkey>ctl_fauna</foreignkey>
        <fk_link_field>scientific_name</fk_link_field>
        <fk_show_field>scientific_name,name</fk_show_field>
        <frm_required>1</frm_required>
    </field>
    <field>
        <name>codecave</name>
        <frm_it>Numero grotta</frm_it>
        <type>string</type>
        <view_show>1</view_show>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>codeartificial</name>
        <frm_it>Id cavità artificiale</frm_it>
        <type>string</type>
        <view_show>1</view_show>
        <foreignkey>ctl_artificials</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
        <frm_showinlist>1</frm_showinlist>
    </field>

    <field>
        <name>ammount</name>
        <frm_it>Numero esemplari</frm_it>
    </field>	
    <field>
        <name>description</name>
        <frm_cols>auto</frm_cols>
        <frm_rows>auto</frm_rows>
        <frm_help_i18n>Insert description here</frm_help_i18n>
        <frm_i18n>description</frm_i18n>
        <type>text</type>
    </field>
    <field>
        <name>photo1</name>
        <frm_i18n>image</frm_i18n>
        <frm_showinlist>0</frm_showinlist>
        <thumb_listheight>64</thumb_listheight>
        <type>image</type>
        <thumbsize>250</thumbsize>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>authorphoto1</name>
        <frm_i18n>author</frm_i18n>
        <view_hiddentitle>1</view_hiddentitle>
    </field>    
    <field>
        <name>photo2</name>
        <frm_i18n>image</frm_i18n>
        <frm_showinlist>0</frm_showinlist>
        <thumb_listheight>64</thumb_listheight>
        <type>image</type>
        <thumbsize>250</thumbsize>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>authorphoto2</name>
        <frm_i18n>author</frm_i18n>
        <view_hiddentitle>1</view_hiddentitle>
    </field>    
    <field>
        <name>date</name>
        <frm_it>Data rilevamento</frm_it>
        <type>datetime</type>
        <frm_dateformat>dd/mm/y 00:00</frm_dateformat>
        <view_dateformat>dd/mm/y 00:00</view_dateformat>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>caver</name>
        <frm_it>Rilevato da</frm_it>
        <type>string</type>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <!--  system fields -->
    <field>
        <name>recordinsert</name>
        <frm_i18n>insertion date</frm_i18n>
        <type>datetime</type>
        <view_dateformat>dd/mm/y 00:00</view_dateformat>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
    </field>
    <field>
        <name>recordupdate</name>
        <frm_i18n>date updated</frm_i18n>
        <type>datetime</type>
        <view_dateformat>dd/mm/y 00:00</view_dateformat>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
    </field>
    <field>
        <name>groupview</name>
        <frm_i18n>limits the display of the content in these groups</frm_i18n>        
        <foreignkey>fn_groups</foreignkey>
        <fk_link_field>groupname</fk_link_field>
        <fk_show_field>groupname</fk_show_field>        
        <frm_type>multicheck</frm_type>
    </field>
    <field>
        <name>username</name>
        <type>string</type>
        <frm_show>0</frm_show>
    </field>
    <driver>mysql</driver>
</tables>
