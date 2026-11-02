<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>
    <!-- links between the OpenKIS records and the Grottocenter entrances
         (section "grottocenter", extra/openkis/openkis_grottocenter.inc.php) -->
    <field>
        <name>id</name>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>section</name>
        <type>string</type>
        <frm_i18n>section</frm_i18n>
    </field>
    <field>
        <name>record_id</name>
        <type>string</type>
        <frm_i18n>record</frm_i18n>
    </field>
    <field>
        <name>status</name>
        <type>select</type>
        <frm_i18n>status</frm_i18n>
        <frm_options>proposed,nomatch,confirmed,missing</frm_options>
        <frm_options_i18n>proposed,no match,confirmed,not on Grottocenter</frm_options_i18n>
    </field>
    <field>
        <name>gc_entrance</name>
        <type>string</type>
        <frm_i18n>Grottocenter entrance</frm_i18n>
    </field>
    <field>
        <name>gc_cave</name>
        <type>string</type>
        <frm_i18n>Grottocenter cave</frm_i18n>
    </field>
    <field>
        <name>gc_name</name>
        <type>string</type>
        <frm_i18n>name on Grottocenter</frm_i18n>
    </field>
    <field>
        <name>distance</name>
        <type>string</type>
        <frm_i18n>distance</frm_i18n>
        <frm_suffix> m</frm_suffix>
    </field>
    <field>
        <name>name_score</name>
        <type>string</type>
        <frm_i18n>name similarity</frm_i18n>
        <frm_suffix> %</frm_suffix>
    </field>
    <field>
        <name>score</name>
        <type>string</type>
        <frm_i18n>score</frm_i18n>
        <frm_suffix> %</frm_suffix>
    </field>
    <field>
        <!-- best candidates of the last run, JSON -->
        <name>candidates</name>
        <type>text</type>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>username</name>
        <type>string</type>
        <frm_i18n>user</frm_i18n>
    </field>
    <field>
        <name>recordupdate</name>
        <type>datetime</type>
        <frm_i18n>last update</frm_i18n>
    </field>
</tables>
