<?xml version = "1.0" encoding = "UTF-8" ?>
<?php exit(0);?>
<tables>
    <field>
        <name>id</name>
        <frm_required>1</frm_required>
        <primarykey>1</primarykey>
        <size>128</size>
    </field>
    <field>
        <name>type</name>
        <frm_i18n>block type</frm_i18n>
        <frm_type>select</frm_type>
        <frm_show>1</frm_show>
        <foreignkey>fn_sectionstypes</foreignkey>
        <fk_link_field>name</fk_link_field>
        <fk_show_field>title</fk_show_field>
    </field>
    <field>
        <name>title</name>
        <frm_i18n>title</frm_i18n>
        <type>varchar</type>
        <frm_multilanguages>auto</frm_multilanguages>
    </field>
    <field>
        <name>position</name>
    </field>
    <field>
        <name>where</name>
        <frm_type>radio</frm_type>
        <frm_required>1</frm_required>
        <frm_options>top,bottom,left,right</frm_options>
    </field>
    <field>
        <name>hidetitle</name>
        <frm_i18n>hide title</frm_i18n>
        <frm_type>radio</frm_type>
        <frm_options>1,</frm_options>
        <frm_options_i18n>yes,no</frm_options_i18n>
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
        <frm_options_i18n>published,not published</frm_options_i18n>
    </field>
    <field>
        <name>level</name>
        <frm_type>select</frm_type>
        <frm_i18n>user level for viewing</frm_i18n>
        <frm_group>permissions</frm_group>
        <frm_group_i18n>permissions</frm_group_i18n>
        <frm_options>,0,1,2,3,4,5,6,7,8,9,10</frm_options>
        <frm_options_i18n>visible to everyone,only registered users,users with at least level 1,users with at least level 2,users with at least level 3,users with at least level 4,users with at least level 5,users with at least level 6,users with at least level 7,users with at least level 8,users with at least level 9,visible only by administrators</frm_options_i18n>                
    </field>
    <field>
        <name>group_view</name>
        <foreignkey>fn_groups</foreignkey>
        <fk_link_field>groupname</fk_link_field>
        <fk_show_field>groupname</fk_show_field>
        <frm_type>multicheck</frm_type>
        <frm_i18n>allow viewing only to these user groups</frm_i18n>
        <frm_help_i18n>if no group is selected, the content will be visible to everyone</frm_help_i18n>
    </field>
    <field>
        <name>group_edit</name>
        <foreignkey>fn_groups</foreignkey>
        <fk_link_field>groupname</fk_link_field>
        <fk_show_field>groupname</fk_show_field>
        <frm_type>multicheck</frm_type>
        <frm_i18n>allow modify to the following user groups</frm_i18n>
        <frm_help_i18n>if no group is selected, then only administrators can edit content</frm_help_i18n>
        <frm_endgroup>permissions</frm_endgroup>
    </field>
    <field>
        <name>blocksmode</name>
        <frm_group>sections</frm_group>
        <frm_group_i18n>pages</frm_group_i18n>
        <frm_i18n>view</frm_i18n>
        <frm_type>radio</frm_type>
        <frm_options>,show,hide</frm_options>
        <frm_options_i18n>displays anywhere,displays only selected,hide only selected</frm_options_i18n>
    </field>
    <field>
        <name>sections</name>
        <frm_i18n>selected pages</frm_i18n>
        <foreignkey>fn_sections</foreignkey>
        <fk_link_field>id</fk_link_field>
        <fk_show_field>title</fk_show_field>
        <frm_type>multicheck</frm_type>
        <frm_endgroup></frm_endgroup>
    </field>  
    <filename>blocks</filename>
</tables>

