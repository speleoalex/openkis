<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>
    <field>
        <name>id</name>
        <frm_required>1</frm_required>
        <primarykey>1</primarykey>
        <size>128</size>
        <frm_show>0</frm_show>
        <frm_i18n>unique name</frm_i18n>
        <frm_help_i18n>unique name of the page, used to identify the page in a unique way</frm_help_i18n>
    </field>
    <field>
        <name>type</name>
        <frm_i18n>page type</frm_i18n>
        <frm_type>select</frm_type>
        <frm_show>1</frm_show>
        <foreignkey>fn_sectionstypes</foreignkey>
        <fk_link_field>name</fk_link_field>
        <fk_show_field>title</fk_show_field>
        <frm_help_i18n>causes the page to load one of the installed modules, for example, a login page will load the functionality to log in users</frm_help_i18n>
    </field>
    <field>
        <name>parent</name>
        <foreignkey>fn_sections</foreignkey>
        <fk_link_field>id</fk_link_field>
        <fk_show_field>id</fk_show_field>
        <frm_help_i18n>parent page in the site map</frm_help_i18n>
    </field>
    <field>
        <name>position</name>
    </field>
    <field>
        <name>title</name>
        <frm_i18n>title</frm_i18n>
        <type>varchar</type>
        <frm_multilanguages>auto</frm_multilanguages>
    </field>
    <field>
        <name>description</name>
        <frm_i18n>description</frm_i18n>
        <type>varchar</type>
        <frm_multilanguages>auto</frm_multilanguages>
    </field>
    <field>
        <name>startdate</name>
        <frm_i18n>publication start date</frm_i18n>
        <frm_en>Publication start date</frm_en>
        <frm_it>Data inizio pubblicazione</frm_it>
        <frm_type>datetime</frm_type>
        <frm_dateformat>y-mm-dd</frm_dateformat>
    </field>
    <field>
        <name>enddate</name>
        <frm_i18n>publication end date</frm_i18n>
        <frm_type>datetime</frm_type>
        <frm_dateformat>y-mm-dd</frm_dateformat>
    </field>
    <field>
        <name>status</name>
        <frm_i18n>publication status</frm_i18n>
        <frm_type>radio</frm_type>
        <frm_options>1,0</frm_options>
        <frm_options_i18n>published,not published</frm_options_i18n>
    </field>
    <field>
        <name>hidden</name>
        <frm_type>check</frm_type>
        <frm_i18n>page is hidden in menus</frm_i18n>
        <frm_help_i18n>if selected the page does not appear in the menus but will still be accessible via direct link</frm_help_i18n>
    </field>
    <field>
        <name>accesskey</name>
        <frm_i18n>accesskey</frm_i18n>
        <type>varchar</type>
        <size>1</size>
        <frm_size>2</frm_size>
    </field>
    <field>
        <name>keywords</name>
        <frm_i18n>keywords</frm_i18n>
        <type>varchar</type>
        <frm_help_i18n>comma-separated page keywords to optimize indexing in search engines</frm_help_i18n>
    </field>
    <field>
        <name>sectionpath</name>
        <frm_type>varchar</frm_type>
        <frm_show>0</frm_show>
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
        <frm_group>blocks</frm_group>
        <frm_group_i18n>blocks</frm_group_i18n>
        <frm_i18n>view blocks</frm_i18n>
        <frm_type>radio</frm_type>
        <frm_options>,show,hide</frm_options>
        <frm_options_i18n>view all,displays only selected,hide selected</frm_options_i18n>
    </field>
    <field>
        <name>blocks</name>
        <frm_i18n>selected blocks</frm_i18n>
        <foreignkey>fn_blocks</foreignkey>
        <fk_link_field>id</fk_link_field>
        <fk_show_field>title</fk_show_field>
        <frm_type>multicheck</frm_type>
        <frm_endgroup></frm_endgroup>
    </field>    
    <filename>sections</filename>

</tables>
