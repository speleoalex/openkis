<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>
    <!--
    Underground temperature measurements, after the data sheets of the UCC
    project (Underground Climate Change, https://www.progettoucc.it): one
    record is either a spot measurement (one reading) or a monitoring (a
    series of readings of a data logger, summarised by its statistics).
    See docs/TEMPERATURES.md for the mapping with the UCC sheets.
    -->
    <field>
        <name>id</name>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>name</name>
        <frm_i18n>name</frm_i18n>
        <frm_help_i18n>When empty it is built from the cave, the position and the date.</frm_help_i18n>
        <type>string</type>
        <view_tag>h2</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>type</name>
        <frm_functionview>openkis_TemperatureViewValue</frm_functionview>
        <frm_i18n>type</frm_i18n>
        <frm_help_i18n>Spot: one reading with a thermometer. Monitoring: a series of readings of a data logger, summarised by its statistics. Water analysis: chemical and physical parameters of a water sample, with the report attached; give its date in the group of the spot measurement.</frm_help_i18n>
        <type>radio</type>
        <frm_options>spot,monitoring,analysis</frm_options>
        <frm_options_i18n>spot measurement,monitoring,water analysis</frm_options_i18n>
        <frm_default>spot</frm_default>
        <frm_required>1</frm_required>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>medium</name>
        <frm_functionview>openkis_TemperatureViewValue</frm_functionview>
        <frm_i18n>measured medium</frm_i18n>
        <frm_help_i18n>What the sensor measures: the air, the water or the rock (a sensor inside a hole in the wall).</frm_help_i18n>
        <type>radio</type>
        <frm_options>air,water,rock</frm_options>
        <frm_options_i18n>air,water,rock</frm_options_i18n>
        <frm_default>air</frm_default>
        <frm_required>1</frm_required>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>cave</name>
        <frm_functionview>openkis_TemperatureViewValue</frm_functionview>
        <frm_i18n>cave</frm_i18n>
        <frm_help_i18n>The cave of the measurement: its name, coordinates, municipality, karst area and entrance elevation are taken from the register. Leave it empty and fill in the coordinates for a site that is not in the register.</frm_help_i18n>
        <type>string</type>
        <frm_type>selectfilter</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
        <frm_group_i18n>measurement point</frm_group_i18n>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>spring</name>
        <frm_functionview>openkis_TemperatureViewValue</frm_functionview>
        <frm_i18n>spring</frm_i18n>
        <frm_help_i18n>For a measurement or an analysis at a spring of the register: leave the cave empty, the coordinates of the spring are used.</frm_help_i18n>
        <type>string</type>
        <frm_type>selectfilter</frm_type>
        <foreignkey>ctl_springs</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
    </field>
    <field>
        <name>position</name>
        <frm_i18n>position</frm_i18n>
        <frm_help_i18n>Where the measurement was taken: e.g. entrance, base of the first pitch, Sala Grande, siphon lake.</frm_help_i18n>
        <type>string</type>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>measure_elevation</name>
        <frm_i18n>elevation of the measurement point</frm_i18n>
        <frm_help_i18n>Elevation above sea level of the measurement point, also approximate (20 m of uncertainty are fine).</frm_help_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> m s.l.m.</frm_suffix>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>entrance_distance</name>
        <frm_i18n>distance from the entrance</frm_i18n>
        <frm_help_i18n>Distance along the cave from the nearest known entrance, also approximate (50 m of uncertainty are fine); 0 for a measurement at the entrance.</frm_help_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> m</frm_suffix>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>latitude_txt</name>
        <frm_i18n>latitude</frm_i18n>
        <frm_help_i18n>Only for a site that is not a cave of the register, or for a point far from the entrance of the cave. When latitude and longitude are empty the coordinates of the cave are used.</frm_help_i18n>
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
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>ventilation</name>
        <frm_functionview>openkis_TemperatureViewValue</frm_functionview>
        <frm_i18n>ventilation</frm_i18n>
        <frm_help_i18n>Air flow felt at the measurement point.</frm_help_i18n>
        <type>select</type>
        <frm_options>none,weak,medium,strong</frm_options>
        <frm_options_i18n>not perceptible,weak,medium,strong</frm_options_i18n>
        <frm_group_i18n>conditions at the measurement point</frm_group_i18n>
    </field>
    <field>
        <name>snow_ice</name>
        <frm_functionview>openkis_TemperatureViewValue</frm_functionview>
        <frm_i18n>snow or ice</frm_i18n>
        <frm_help_i18n>Snow or ice at the measurement point: present at the time of a spot measurement, seasonal or persistent for a monitoring.</frm_help_i18n>
        <type>select</type>
        <frm_options>absent,present,seasonal,persistent</frm_options>
        <frm_options_i18n>absent,present,seasonal,persistent</frm_options_i18n>
    </field>
    <field>
        <name>water</name>
        <frm_functionview>openkis_TemperatureViewValue</frm_functionview>
        <frm_i18n>water</frm_i18n>
        <frm_help_i18n>Water at the measurement point: wall films, dripping, stream, pool or lake.</frm_help_i18n>
        <type>select</type>
        <frm_options>absent,films,dripping,stream,pool</frm_options>
        <frm_options_i18n>absent,wall films,dripping,stream,pool or lake</frm_options_i18n>
    </field>
    <field>
        <name>instrument</name>
        <frm_i18n>instrument</frm_i18n>
        <frm_help_i18n>E.g. mercury thermometer, Assmann psychrometer, Tinytag TGP-4520 data logger with PB-5002 probe; give the serial number of a station.</frm_help_i18n>
        <type>string</type>
    </field>
    <field>
        <name>accuracy</name>
        <frm_i18n>accuracy</frm_i18n>
        <frm_help_i18n>Uncertainty of the instrument, e.g. 0.2 (the UCC stations require 0.2 or better).</frm_help_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> °C</frm_suffix>
        <frm_size>6</frm_size>
    </field>
    <field>
        <name>resolution</name>
        <frm_i18n>resolution</frm_i18n>
        <frm_help_i18n>Smallest change the instrument shows, e.g. 0.1 for a decimal thermometer, 0.01 for a data logger.</frm_help_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> °C</frm_suffix>
        <frm_size>6</frm_size>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>temperature</name>
        <frm_i18n>temperature</frm_i18n>
        <frm_help_i18n>Spot measurements and water analyses: the reading, with its significant decimals.</frm_help_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> °C</frm_suffix>
        <frm_size>6</frm_size>
        <frm_group_i18n>spot measurement</frm_group_i18n>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>temperature_external</name>
        <frm_i18n>outside temperature</frm_i18n>
        <frm_help_i18n>Air temperature outside the cave at the time of the measurement, when known.</frm_help_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> °C</frm_suffix>
        <frm_size>6</frm_size>
    </field>
    <field>
        <name>year</name>
        <frm_i18n>year</frm_i18n>
        <frm_help_i18n>Year, month and day are separate because old measurements often have an incomplete date: leave empty what is unknown.</frm_help_i18n>
        <type>int</type>
        <frm_size>4</frm_size>
    </field>
    <field>
        <name>month</name>
        <frm_i18n>month</frm_i18n>
        <type>int</type>
        <frm_size>2</frm_size>
    </field>
    <field>
        <name>day</name>
        <frm_i18n>day</frm_i18n>
        <type>int</type>
        <frm_size>2</frm_size>
    </field>
    <field>
        <name>hour</name>
        <frm_i18n>hour</frm_i18n>
        <frm_help_i18n>Time of the reading as hh:mm, or e.g. morning, evening.</frm_help_i18n>
        <type>string</type>
        <frm_size>8</frm_size>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>start_date</name>
        <frm_i18n>start of the monitoring</frm_i18n>
        <frm_help_i18n>Monitorings only: first reading of the series.</frm_help_i18n>
        <type>datetime</type>
        <frm_dateformat>y-mm-dd</frm_dateformat>
        <view_dateformat>dd/mm/y</view_dateformat>
        <frm_group_i18n>monitoring</frm_group_i18n>
    </field>
    <field>
        <name>end_date</name>
        <frm_i18n>end of the monitoring</frm_i18n>
        <frm_help_i18n>Last reading of the series; leave it empty while the monitoring is running.</frm_help_i18n>
        <type>datetime</type>
        <frm_dateformat>y-mm-dd</frm_dateformat>
        <view_dateformat>dd/mm/y</view_dateformat>
    </field>
    <field>
        <name>acq_interval</name>
        <frm_i18n>acquisition interval</frm_i18n>
        <frm_help_i18n>Minutes between two readings of the data logger (the UCC stations use 30).</frm_help_i18n>
        <type>int</type>
        <frm_suffix> min</frm_suffix>
        <frm_size>6</frm_size>
    </field>
    <field>
        <name>samples</name>
        <frm_i18n>number of readings</frm_i18n>
        <type>int</type>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>t_mean</name>
        <frm_i18n>mean temperature</frm_i18n>
        <frm_help_i18n>Statistics of the series; the mean is enough when the others are unknown.</frm_help_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> °C</frm_suffix>
        <frm_size>6</frm_size>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>t_min</name>
        <frm_i18n>minimum temperature</frm_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> °C</frm_suffix>
        <frm_size>6</frm_size>
    </field>
    <field>
        <name>t_max</name>
        <frm_i18n>maximum temperature</frm_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> °C</frm_suffix>
        <frm_size>6</frm_size>
    </field>
    <field>
        <name>t_median</name>
        <frm_i18n>median temperature</frm_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> °C</frm_suffix>
        <frm_size>6</frm_size>
    </field>
    <field>
        <name>t_mode</name>
        <frm_i18n>modal temperature</frm_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> °C</frm_suffix>
        <frm_size>6</frm_size>
    </field>
    <field>
        <name>t_stddev</name>
        <frm_i18n>standard deviation</frm_i18n>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_suffix> °C</frm_suffix>
        <frm_size>6</frm_size>
    </field>
    <field>
        <name>data_holder</name>
        <frm_i18n>data holder</frm_i18n>
        <frm_help_i18n>Who keeps the full series: a caving club, a university, an agency.</frm_help_i18n>
        <type>string</type>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>ph</name>
        <frm_i18n>acidity (pH)</frm_i18n>
        <frm_help_i18n>Water analyses only.</frm_help_i18n>
        <type>string</type>
        <frm_size>6</frm_size>
        <frm_group_i18n>water analysis</frm_group_i18n>
    </field>
    <field>
        <name>conductivity</name>
        <frm_i18n>electrical conductivity (µS/cm)</frm_i18n>
        <frm_help_i18n>At 20 °C.</frm_help_i18n>
        <type>string</type>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>hardness</name>
        <frm_i18n>total hardness (°F)</frm_i18n>
        <frm_help_i18n>In French degrees; give other units in the other parameters.</frm_help_i18n>
        <type>string</type>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>parameters</name>
        <frm_i18n>other parameters</frm_i18n>
        <frm_help_i18n>One per line with its unit, e.g. Ca 82 mg/l, Mg 6 mg/l, HCO3 250 mg/l, SO4 12 mg/l, NO3 3 mg/l, flow 2 l/s; and the laboratory.</frm_help_i18n>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>bibliography</name>
        <frm_i18n>bibliography</frm_i18n>
        <frm_help_i18n>Source of the data, at least author(s) and year; or the unpublished notes it comes from.</frm_help_i18n>
        <type>text</type>
        <frm_rows>2</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>bibliography_ids</name>
        <frm_i18n>bibliography sheets</frm_i18n>
        <frm_help_i18n>The publications of the section Bibliography the data come from: the sheet of each publication lists this measurement. When the field bibliography is empty, the UCC export takes the citation from these sheets.</frm_help_i18n>
        <type>string</type>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_bibliography</foreignkey>
        <fk_link_field>id</fk_link_field>
        <fk_show_field>year,authors,title</fk_show_field>
    </field>
    <field>
        <name>file</name>
        <frm_i18n>data file</frm_i18n>
        <frm_help_i18n>The readings of the data logger, the report or the report of the water analysis (also the pages of a publication). To upload several files, put them in a compressed archive (zip).</frm_help_i18n>
        <type>file</type>
        <view_tag>center</view_tag>
    </field>
    <field>
        <name>photo1</name>
        <frm_i18n>image</frm_i18n>
        <frm_help_i18n>A chart of the measurements (temperature curve, diagram of the water analyses) or a photo of the measurement point.</frm_help_i18n>
        <thumb_listheight>64</thumb_listheight>
        <type>image</type>
        <thumbsize>250</thumbsize>
    </field>
    <field>
        <name>photo1_caption</name>
        <frm_i18n>image caption</frm_i18n>
        <frm_help_i18n>What the image shows and where it comes from, e.g. the figure and the page of the publication.</frm_help_i18n>
        <type>string</type>
    </field>
    <field>
        <name>attachment1</name>
        <frm_i18n>attachment</frm_i18n>
        <frm_help_i18n>Other documents of the measurement: charts, tables, the pages of the publication.</frm_help_i18n>
        <type>file</type>
    </field>
    <field>
        <name>attachment2</name>
        <frm_i18n>second attachment</frm_i18n>
        <type>file</type>
    </field>
    <field>
        <name>ucc</name>
        <frm_i18n>in the UCC national database</frm_i18n>
        <frm_help_i18n>Tick when the record has been copied into the national sheet of the UCC project (Underground Climate Change).</frm_help_i18n>
        <type>check</type>
        <frm_checkon>1</frm_checkon>
    </field>
    <field>
        <name>notes</name>
        <frm_i18n>notes</frm_i18n>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>sort_date</name>
        <frm_functionview>openkis_TemperatureViewDate</frm_functionview>
        <frm_i18n>date</frm_i18n>
        <frm_help_i18n>Computed: the date of the spot measurement (year, month, day) or the start of the monitoring.</frm_help_i18n>
        <type>string</type>
        <frm_show>0</frm_show>
        <frm_showinlist>1</frm_showinlist>
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
        <frm_help_i18n>Computed from the cave: a measurement in a restricted cave has the same visibility as the cave.</frm_help_i18n>
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
