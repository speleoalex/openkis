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
        <frm_i18n>name</frm_i18n>
        <frm_help_i18n>E.g. Buranco dye tracing 2014. When empty it is built from the injection and detection points.</frm_help_i18n>
        <type>string</type>
        <view_tag>h2</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>code</name>
        <frm_i18n>campaign code</frm_i18n>
        <frm_help_i18n>Optional: label of the tracing campaign, shared by all its detection points.</frm_help_i18n>
        <type>uppercase</type>
        <frm_size>20</frm_size>
    </field>
    <field>
        <name>type</name>
        <frm_functionview>openkis_TracingViewValue</frm_functionview>
        <frm_i18n>type</frm_i18n>
        <type>radio</type>
        <frm_options>water,air</frm_options>
        <frm_options_i18n>water,air</frm_options_i18n>
        <frm_default>water</frm_default>
        <frm_required>1</frm_required>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>air_regime</name>
        <frm_functionview>openkis_TracingViewValue</frm_functionview>
        <frm_i18n>regime (air only)</frm_i18n>
        <frm_help_i18n>Air tracings only. When empty it is set from the release date: May to October summer, November to April winter.</frm_help_i18n>
        <type>radio</type>
        <frm_options>summer,winter</frm_options>
        <frm_options_i18n>summer (cold air),winter</frm_options_i18n>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>tracer</name>
        <frm_i18n>tracer</frm_i18n>
        <frm_help_i18n>E.g. fluorescein, rhodamine, tinopal, SF6, butane, smoke; give the quantity too.</frm_help_i18n>
        <type>string</type>
    </field>
    <field>
        <name>release_date</name>
        <frm_i18n>release date</frm_i18n>
        <frm_help_i18n>Release date and time: arrival times are counted from here.</frm_help_i18n>
        <type>datetime</type>
        <frm_dateformat>y-mm-dd 00:00</frm_dateformat>
        <view_dateformat>dd/mm/y 00:00</view_dateformat>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>cave_injection</name>
        <frm_functionview>openkis_TracingViewValue</frm_functionview>
        <frm_i18n>injection cave</frm_i18n>
        <type>string</type>
        <frm_type>selectfilter</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
        <frm_group_i18n>injection point</frm_group_i18n>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>latitude_txt</name>
        <frm_i18n>latitude</frm_i18n>
        <frm_help_i18n>When the injection point is not a cave entrance. When latitude and longitude are empty the coordinates and elevation of the injection cave are used.</frm_help_i18n>
        <type>varchar</type>
        <frm_type>latlon</frm_type>
    </field>
    <field>
        <name>longitude_txt</name>
        <frm_i18n>longitude</frm_i18n>
        <type>varchar</type>
        <frm_type>latlon</frm_type>
    </field>
    <field>
        <name>coordinates_type</name>
        <frm_i18n>coordinates type</frm_i18n>
        <frm_help_i18n>Geographic when given as 44° 12' 32'' or 44.23456, kilometric (projected) when given as 189992.</frm_help_i18n>
        <type>string</type>
        <foreignkey>ctl_coordinatestypes</foreignkey>
        <fk_link_field>coordinates_type</fk_link_field>
        <fk_show_field>coordinates_type</fk_show_field>
    </field>
    <field>
        <name>elevation</name>
        <frm_i18n>elevation</frm_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> m s.l.m</frm_suffix>
        <frm_size>8</frm_size>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>cave_detection</name>
        <frm_functionview>openkis_TracingViewValue</frm_functionview>
        <frm_i18n>detection cave</frm_i18n>
        <frm_help_i18n>Fill in either the detection cave or the detection spring. For several detection points enter one tracing each, with the same campaign code.</frm_help_i18n>
        <type>string</type>
        <frm_type>selectfilter</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
        <frm_group_i18n>detection point</frm_group_i18n>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>spring_detection</name>
        <frm_functionview>openkis_TracingViewValue</frm_functionview>
        <frm_i18n>detection spring</frm_i18n>
        <type>string</type>
        <frm_type>selectfilter</frm_type>
        <foreignkey>ctl_springs</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
        <frm_endgroup></frm_endgroup>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>sensor_type</name>
        <frm_i18n>sensor type</frm_i18n>
        <frm_help_i18n>E.g. activated charcoal bags, field fluorometer, automatic sampler, gas logger, anemometer, thermometer.</frm_help_i18n>
        <type>string</type>
    </field>
    <field>
        <name>path_length</name>
        <frm_i18n>polygonal distance</frm_i18n>
        <frm_help_i18n>Length of the path along the survey polygonal from the injection point to the detection point, when the two are connected by a survey.</frm_help_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> m</frm_suffix>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>result</name>
        <frm_functionview>openkis_TracingViewValue</frm_functionview>
        <frm_i18n>result</frm_i18n>
        <frm_help_i18n>Only the positive tracings are drawn on the map.</frm_help_i18n>
        <type>radio</type>
        <frm_options>positive,negative,uncertain</frm_options>
        <frm_options_i18n>positive,negative,uncertain</frm_options_i18n>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>result_notes</name>
        <frm_i18n>result details</frm_i18n>
        <frm_help_i18n>E.g. arrival time, concentration peak, apparent velocity.</frm_help_i18n>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>file</name>
        <frm_i18n>tracing file</frm_i18n>
        <frm_help_i18n>Tracing data or report. To upload several files, put them in a compressed archive (zip).</frm_help_i18n>
        <type>file</type>
        <view_tag>center</view_tag>
    </field>
    <field>
        <name>notes</name>
        <frm_i18n>notes</frm_i18n>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>latitude</name>
        <type>float</type>
        <frm_show>0</frm_show>
        <view_show>0</view_show>
    </field>
    <field>
        <name>longitude</name>
        <type>float</type>
        <frm_show>0</frm_show>
        <view_show>0</view_show>
    </field>
    <field>
        <name>coordnatesupdated</name>
        <frm_show>0</frm_show>
        <view_show>0</view_show>
    </field>
    <field>
        <!-- also keeps FNDBVIEW::Init() from rewriting this file (and dropping its header) when the history is on -->
        <name>recorddeleted</name>
        <type>string</type>
        <frm_show>0</frm_show>
        <view_show>0</view_show>
    </field>
    <field>
        <name>groupview</name>
        <frm_i18n>visible only to the groups</frm_i18n>
        <frm_help_i18n>Computed from the linked caves: a tracing that touches a restricted cave has the same visibility as the cave.</frm_help_i18n>
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
