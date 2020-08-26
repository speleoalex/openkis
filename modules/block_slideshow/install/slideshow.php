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
        <name>image</name>
        <frm_i18n>image</frm_i18n>
        <type>image</type>
        <frm_type>image</frm_type>
        <thumbsize>64</thumbsize>
    </field>
    <field>
        <name>title</name>
        <frm_i18n>title</frm_i18n>
    </field>
    <field>
        <name>status</name>
        <frm_type>radio</frm_type>
        <frm_options>1,0</frm_options>
        <frm_options_i18n>published,not published</frm_options_i18n>
    </field>
    <field>
        <name>description</name>
        <frm_i18n>description</frm_i18n>
    </field>
</tables>