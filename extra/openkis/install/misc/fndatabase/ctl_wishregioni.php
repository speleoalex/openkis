<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0); ?>
<tables>
    <field>
        <name>id</name>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>regione</name>
        <type>string</type>
        <frm_required>1</frm_required>
        <frm_type>select</frm_type>
        <frm_options>Abruzzo,Basilicata,Calabria,Campania,Emilia Romagna,Friuli Venezia Giulia,Lazio,Liguria,Lombardia,Marche,Molise,Piemonte,Puglia,Sardegna,Sicilia,Toscana,Trentino Alto Adige,Umbria,Valle d'Aosta,Veneto</frm_options>
        <unique>1</unique>
    </field>
    <field>
        <name>title</name>
        <type>string</type>
        <frm_it>Nome della federazione o ente che fornisce i dati</frm_it>
    </field>
    <field>
        <name>photo1</name>
        <frm_it>Logo federazione</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
        <type>image</type>
        <thumbsize>250</thumbsize>
    </field>
    <field>
        <name>names</name>
        <frm_it>Curatori</frm_it>
        <type>text</type>
        <frm_help>Nomi curatori</frm_help>
    </field>
    <field>
        <name>email</name>
        <frm_it>e-mail</frm_it>
        <type>string</type>
        <frm_help>indirizzo email</frm_help>
    </field>

    <field>
        <name>portal</name>
        <frm_it>URL portale</frm_it>
        <frm_help>Inserire l'url del portale</frm_help>
        <frm_type>link</frm_type>

    </field>
    <field>
        <name>portal</name>
        <frm_it>URL portale</frm_it>
        <frm_help>Inserire l'url del portale gestito dalla federazione</frm_help>
        <frm_type>link</frm_type>

    </field>
    <field>
        <name>portal_regione</name>
        <frm_it>URL portale regionale</frm_it>
        <frm_help>Inserire l'url del portale della regione (se diverso da quello della federazione)</frm_help>
        <frm_type>link</frm_type>

    </field>
    <field>
        <name>url_data_caves</name>
        <frm_it>URL dati grotte</frm_it>
        <frm_type>link</frm_type>
        <frm_viewonlyadmin>1</frm_viewonlyadmin>
        <frm_allowupdate>onlyadmin</frm_allowupdate>
    </field>
    <field>
        <name>url_data_springs</name>
        <frm_it>URL dati sorgenti carsiche</frm_it>
        <frm_type>link</frm_type>
        <frm_viewonlyadmin>1</frm_viewonlyadmin>
        <frm_allowupdate>onlyadmin</frm_allowupdate>
    </field>
    <field>
        <name>url_data_areas</name>
        <frm_it>URL dati aree speleologiche</frm_it>
        <frm_type>link</frm_type>
        <frm_viewonlyadmin>1</frm_viewonlyadmin>
        <frm_allowupdate>onlyadmin</frm_allowupdate>
    </field>
    <field>
        <name>file_caves</name>
        <frm_i18n>File grezzo dati grotte</frm_i18n>
        <type>file</type>
        <frm_help>In mancanza di dati disponibili su web è possibile caricare qui il file contenente i dati</frm_help>
        <frm_viewonlyadmin>1</frm_viewonlyadmin>
    </field>
    <field>
        <name>convenzione</name>
        <frm_i18n>Convenzione con Wish</frm_i18n>
        <type>file</type>
        <frm_viewonlyadmin>1</frm_viewonlyadmin>
    </field>
    <!--  system fields -->
    <field>
        <name>recordinsert</name>
        <frm_i18n>insertion date</frm_i18n>
        <type>string</type>
        <frm_show>0</frm_show>ù
        <view_show>0</view_show>
    </field>
    <field>
        <name>recordupdate</name>
        <frm_i18n>date updated</frm_i18n>
        <type>string</type>
        <frm_show>0</frm_show>
        <view_show>0</view_show>
    </field>
    <field>
        <name>view</name>
        <frm_i18n>number of views</frm_i18n>
        <type>string</type>
        <foreignkey>ctl_wishregioni_stat</foreignkey>
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
        <frm_viewonlyadmin>1</frm_viewonlyadmin>
        <frm_allowupdate>onlyadmin</frm_allowupdate>
    </field>
    <field>
        <name>visibility</name>
        <frm_i18n>Dati pubblici su wish</frm_i18n>
        <frm_type>multicheck</frm_type>
        <frm_options>nome,località,coordinate,rilievi</frm_options>
        <frm_viewonlyadmin>1</frm_viewonlyadmin>

    </field>
    <field>
        <name>driver</name>
        <frm_i18n>Algoritmo di importazione</frm_i18n>
        <frm_type>select</frm_type>
        <frm_options>openkis,lombardia,lazio,toscana,puglia,trentino,friuli,lazio,campania,abruzzo,sicilia,sardegna,emilia_romagna,veneto,umbria,piemonte,liguria,basilicata</frm_options>
        <frm_viewonlyadmin>1</frm_viewonlyadmin>
        <frm_allowupdate>onlyadmin</frm_allowupdate>
    </field>
    <field>
        <name>username</name>
        <type>string</type>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>status</name>
        <frm_it>Aderisce a WISH</frm_it>
        <frm_type>radio</frm_type>
        <frm_viewonlyadmin>1</frm_viewonlyadmin>
        <frm_options>1,</frm_options>
        <frm_options_it>SI,NO</frm_options_it>
        <frm_help>Aderisce al progetto WISH esportando i dati delle grotte presenti a sul proprio database e consentendone la visualizzazione sul portale condiviso.
            Sulla scheda sarà presente un link al portale di origine</frm_help>
    </field>
    <field>
        <name>random_error_coords</name>
        <frm_type>check</frm_type>
        <frm_i18n>Errore randomico su coordinate</frm_i18n>
        <default>0</default>
        <frm_default>0</frm_default>
        <frm_help>Errore randomico su coordinate in metri</frm_help>        
    </field>     
    <field>
        <name>no_position</name>
        <type>bool</type>
        <frm_type>check</frm_type>
        <frm_i18n>Posizione non fornita</frm_i18n>
        <default>0</default>
        <frm_default>0</frm_default>
    </field>           
    <field>
        <name>note</name>
        <type>text</type>
    </field>
</tables>
