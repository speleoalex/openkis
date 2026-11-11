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
        <frm_i18n>title</frm_i18n>
        <frm_help_i18n>Short title of the finding, e.g. Neolithic burial, cave bear remains.</frm_help_i18n>
        <type>string</type>
        <frm_required>1</frm_required>
        <view_tag>h2</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>category</name>
        <frm_i18n>category</frm_i18n>
        <type>radio</type>
        <frm_options>paleontological,archaeological,anthropological,mixed</frm_options>
        <frm_options_i18n>paleontological,archaeological,anthropological (human remains),mixed</frm_options_i18n>
        <frm_required>1</frm_required>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>finding_type</name>
        <frm_i18n>type of finds</frm_i18n>
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <frm_options>faunal remains,human remains,lithic industry,pottery,bone artefacts,metal objects,coins,burial,structures,rock art,plant remains,other</frm_options>
        <frm_options_i18n>faunal remains,human remains,lithic industry,pottery,bone artefacts,metal objects,coins,burial,structures (hearths and walls),rock art and engravings,plant remains and charcoal,other</frm_options_i18n>
    </field>
    <field>
        <name>taxa</name>
        <frm_i18n>identified taxa</frm_i18n>
        <frm_help_i18n>Species or taxa of the remains, e.g. Ursus spelaeus, Capra ibex.</frm_help_i18n>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>codecave</name>
        <frm_i18n>cave</frm_i18n>
        <type>string</type>
        <frm_type>selectfilter</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
        <frm_group_i18n>place of the finding</frm_group_i18n>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>codeartificial</name>
        <frm_i18n>artificial cavity</frm_i18n>
        <type>string</type>
        <frm_type>selectfilter</frm_type>
        <foreignkey>ctl_artificials</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
    </field>
    <field>
        <name>position</name>
        <frm_i18n>position in the cavity</frm_i18n>
        <frm_help_i18n>Part of the cavity or stratigraphic level, e.g. inner chamber, layer III.</frm_help_i18n>
        <type>string</type>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>period</name>
        <frm_i18n>period</frm_i18n>
        <!-- from the oldest: the cards list the findings in this order -->
        <type>select</type>
        <frm_options>pre-quaternary,lower pleistocene,middle pleistocene,upper pleistocene,holocene,lower paleolithic,middle paleolithic,upper paleolithic,epipaleolithic and mesolithic,neolithic,copper age,bronze age,iron age,roman period,late antiquity and early middle ages,middle ages,modern period,contemporary period,undetermined</frm_options>
        <frm_options_i18n>pre-quaternary,lower pleistocene,middle pleistocene,upper pleistocene,holocene,lower paleolithic,middle paleolithic,upper paleolithic,epipaleolithic and mesolithic,neolithic,copper age,bronze age,iron age,roman period,late antiquity and early middle ages,middle ages,modern period,contemporary period,undetermined</frm_options_i18n>
        <frm_group_i18n>dating</frm_group_i18n>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>period_detail</name>
        <frm_i18n>culture or facies</frm_i18n>
        <frm_help_i18n>As written in the source, e.g. Mousterian, Square Mouth Pottery.</frm_help_i18n>
        <!-- text: a finding gathered from several sources lists the terms of each one -->
        <type>text</type>
        <frm_rows>2</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>dating_from</name>
        <frm_i18n>from year</frm_i18n>
        <frm_help_i18n>Optional, negative for years before Christ (e.g. -5800).</frm_help_i18n>
        <type>int</type>
        <frm_size>10</frm_size>
    </field>
    <field>
        <name>dating_to</name>
        <frm_i18n>to year</frm_i18n>
        <type>int</type>
        <frm_size>10</frm_size>
    </field>
    <field>
        <name>dating_method</name>
        <frm_i18n>dating method</frm_i18n>
        <type>select</type>
        <frm_options>typological,stratigraphic,radiocarbon,uranium-thorium,other</frm_options>
        <frm_options_i18n>typological,stratigraphic,radiocarbon (C14),uranium-thorium (U/Th),other</frm_options_i18n>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>description</name>
        <frm_i18n>description of the finds</frm_i18n>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>discovery_date</name>
        <frm_i18n>year of discovery or excavation</frm_i18n>
        <frm_help_i18n>A year or a span, e.g. 1885-87.</frm_help_i18n>
        <type>string</type>
        <frm_group_i18n>discovery and conservation</frm_group_i18n>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>discoverer</name>
        <frm_i18n>discoverer or excavator</frm_i18n>
        <type>string</type>
    </field>
    <field>
        <name>repository</name>
        <frm_i18n>place of conservation</frm_i18n>
        <frm_help_i18n>Museum, heritage office, private collection, lost.</frm_help_i18n>
        <type>string</type>
    </field>
    <field>
        <name>inventory</name>
        <frm_i18n>inventory numbers</frm_i18n>
        <type>string</type>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>bibliography_ids</name>
        <frm_i18n>bibliography sheets</frm_i18n>
        <frm_help_i18n>The publications of the section Bibliography the finding is taken from: the sheet of each publication lists this finding.</frm_help_i18n>
        <type>string</type>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_bibliography</foreignkey>
        <fk_link_field>id</fk_link_field>
        <fk_show_field>year,authors,title</fk_show_field>
        <frm_group_i18n>sources</frm_group_i18n>
    </field>
    <field>
        <name>citations</name>
        <frm_i18n>citations</frm_i18n>
        <frm_help_i18n>Later publications that cite the same finding: add them here instead of entering the finding again.</frm_help_i18n>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>reliability</name>
        <frm_i18n>reliability</frm_i18n>
        <frm_help_i18n>Of the attribution of the finding to the cavity and of its dating.</frm_help_i18n>
        <type>radio</type>
        <frm_options>certain,uncertain,to be verified</frm_options>
        <frm_options_i18n>certain,uncertain,to be verified</frm_options_i18n>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>photo1</name>
        <frm_i18n>image</frm_i18n>
        <thumb_listheight>64</thumb_listheight>
        <type>image</type>
        <thumbsize>250</thumbsize>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>authorphoto1</name>
        <frm_i18n>author</frm_i18n>
        <type>string</type>
    </field>
    <field>
        <name>photo2</name>
        <frm_i18n>image</frm_i18n>
        <thumb_listheight>64</thumb_listheight>
        <type>image</type>
        <thumbsize>250</thumbsize>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>authorphoto2</name>
        <frm_i18n>author</frm_i18n>
        <type>string</type>
    </field>
    <field>
        <name>notes</name>
        <frm_i18n>notes</frm_i18n>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>groupview</name>
        <frm_i18n>visible only to the groups</frm_i18n>
        <frm_help_i18n>Empty: the finding is public. A restricted finding shows only its category and period on the sheet of the cavity. Archaeological sites are exposed to illegal digging.</frm_help_i18n>
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <foreignkey>fn_groups</foreignkey>
        <fk_link_field>groupname</fk_link_field>
        <fk_show_field>groupname</fk_show_field>
    </field>
    <field>
        <!-- also keeps FNDBVIEW::Init() from rewriting this file (and dropping its header) when the history is on -->
        <name>recorddeleted</name>
        <type>string</type>
        <frm_show>0</frm_show>
        <view_show>0</view_show>
    </field>
    <field>
        <name>username</name>
        <frm_i18n>inserted by</frm_i18n>
        <type>string</type>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>userupdate</name>
        <frm_i18n>updated by</frm_i18n>
        <type>string</type>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>recordinsert</name>
        <frm_i18n>inserted</frm_i18n>
        <type>datetime</type>
        <view_dateformat>dd/mm/y 00:00:00</view_dateformat>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>recordupdate</name>
        <frm_i18n>last update</frm_i18n>
        <type>datetime</type>
        <view_dateformat>dd/mm/y 00:00:00</view_dateformat>
        <frm_show>0</frm_show>
    </field>
    <driver>mysql</driver>
</tables>
