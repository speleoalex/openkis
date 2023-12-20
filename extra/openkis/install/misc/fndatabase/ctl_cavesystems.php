<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>

    <field>
        <name>id</name>
        <extra>autoincrement</extra>
        <primarykey>1</primarykey>
        <unique>1</unique>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>name</name>
        <frm_it>Nome</frm_it>
        <frm_en>Name</frm_en>
        <type>string</type>
        <frm_required>1</frm_required>
        <view_tag>h2</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>code</name>
        <frm_i18n>code</frm_i18n>
        <view_show>0</view_show>
        <type>uppercase</type>
        <frm_required>1</frm_required>
    </field>      
    <field>
        <name>areas</name>
        <frm_it>Area carsica</frm_it>
        <type>string</type>
        <frm_type>multiselect</frm_type>
        <foreignkey>ctl_areas</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>		
    </field>
    <field>
        <name>lenght_total</name>
        <view_group>sviluppo</view_group>
        <view_group_i18n>Dimensioni</view_group_i18n>        
        <type>string</type>
        <frm_help_it>somma delle lunghezze di tutte le gallerie calcolate nelle tre
            dimensioni; si sommano perciò anche le lunghezze dei pozzi e dei tratti inclinati</frm_help_it>        
        <frm_type>os_lenght</frm_type>
        <frm_it>Sviluppo reale</frm_it>		
        <frm_showinlist>1</frm_showinlist>
        <frm_group>Dimensioni cavit&agrave;</frm_group>
        <frm_suffix> m</frm_suffix>
        <frm_size>8</frm_size>        
    </field>
    <field>
        <name>depth_total</name>
        <view_endgroup>sviluppo</view_endgroup>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_it>Dislivello totale</frm_it>				
        <frm_suffix> m</frm_suffix>	
        <frm_endgroup></frm_endgroup>
        <frm_size>8</frm_size>
        <frm_showinlist>1</frm_showinlist>
    </field>

    <field>
        <name>caves</name>
        <type>string</type>
        <frm_it>Grotte collegate</frm_it>
        <frm_help_it>Mettere i numeri di catasto separati da virgola degli ingressi e delle grotte collegate</frm_help_it>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>provincia,code,name</fk_show_field>
        <view_show>0</view_show>
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
        <name>recordinsert</name>
        <frm_i18n>insertion date</frm_i18n>
        <type>string</type>
        <frm_show>0</frm_show>ù
        <view_show>1</view_show>
    </field>
    <field>
        <name>recordupdate</name>
        <frm_i18n>date updated</frm_i18n>
        <type>string</type>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
    </field>
    <field>
        <name>view</name>
        <frm_i18n>number of views</frm_i18n>
        <type>string</type>
        <foreignkey>ctl_grottesicure_stat</foreignkey>
        <fk_link_field>unirecid</fk_link_field>
        <fk_show_field>view</fk_show_field>
        <frm_show>0</frm_show>
        <frm_showinlist>1</frm_showinlist>
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
    <field>
        <name>photo1</name>
        <frm_it>Immagine ingresso</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
        <type>image</type>
        <thumbsize>250</thumbsize>
    </field>
    <field>
        <name>authorphoto1</name>
        <frm_it>Autori immagine ingresso</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <innertable>
        <tablename>ctl_surveys</tablename>
        <frm_it>Rilievi</frm_it>
        <linkfield>code,codesystem</linkfield>
        <innertablefields>id,name,photo1,filekml,date,author</innertablefields>
        <view></view>
    </innertable>
    <sqltable>ctl_cavesystems</sqltable>
    <driver>mysql</driver>
</tables>
