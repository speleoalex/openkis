<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0); ?>
<tables>

    <field>
        <name>id</name>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
        <type>string</type>
        <frm_show>0</frm_show>
    </field>

    <field>
        <name>name</name>
        <type>uppercase</type>
    </field>
    <field>
        <name>code</name>
        <type>uppercase</type>
    </field>  
    <field>
        <frm_group>localizzazione</frm_group>
        <name>regione</name>
        <type>string</type>
        <frm_it>Provincia</frm_it>
    </field>    
    <field>
        <name>provincia</name>
        <type>string</type>
        <frm_it>Provincia</frm_it>
    </field>    
    <field>
        <name>comune</name>
        <type>string</type>
        <frm_it>Provincia</frm_it>
        <frm_endgroup>localizzazione</frm_endgroup>
    </field>
    <field>
        <name>description</name>
        <frm_i18n>description</frm_i18n>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>associations</name>
        <frm_i18n>groups</frm_i18n>
        <frm_help>Gruppi o persone che hanno effettuato attivit&agrave; rilevanti o esplorative sulla cacit&agrave;</frm_help>
        <type>text</type>
        <frm_cols>80</frm_cols>
        <frm_rows>auto</frm_rows>
    </field>
    <field>
        <name>surface</name>
        <frm_it>Superficie interessata (ha)</frm_it>
        <type>string</type>
        <frm_showinlist>0</frm_showinlist>
        <view_endtag>0</view_endtag>
    </field>				

    <field>
        <name>cities</name>
        <frm_it>Comuni</frm_it>
        <frm_en>Cities</frm_en>
        <type>string</type>
    </field>
    <field>
        <name>mountaincommunity</name>
        <frm_it>Bacino imbrifero</frm_it>
        <frm_en>Mountain Community</frm_en>
        <frm_es>De la Comunidad de Montaña</frm_es>
        <frm_fr>Communaut&eacute; de montagne</frm_fr>
        <type>string</type>
    </field>
    <field>
        <name>watershed</name>
        <frm_it>Bacino imbrifero</frm_it>
        <frm_en>Mountain Community</frm_en>
        <frm_es>Watershed</frm_es>
        <frm_fr>Watershed</frm_fr>
        <type>string</type>
    </field>
    <field>
        <name>activequarries</name>
        <frm_it>Cave attive</frm_it>
        <frm_en>Active quarries</frm_en>
        <frm_es>Canteras activas</frm_es>
        <frm_fr>Actifs carri&egrave;res</frm_fr>
        <type>string</type>
    </field>
    <field>
        <name>inactivequarries</name>
        <frm_it>Cave inattive</frm_it>
        <frm_en>inactive quarries</frm_en>
        <frm_es>Canteras inactivas</frm_es>
        <frm_fr>inactifs carri&egrave;res</frm_fr>
        <type>string</type>
    </field>
    <field>
        <name>activelandfills</name>
        <frm_it>Discariche attive</frm_it>
        <frm_en>Active Landfills</frm_en>
        <frm_es>Vertederos activos</frm_es>
        <frm_fr>Actifs d&eacute;charges</frm_fr>
        <type>string</type>
    </field>
    <field>
        <name>inactivelandfills</name>
        <frm_it>Discariche inattive</frm_it>
        <frm_en>Inactive Landfills</frm_en>
        <frm_es>Vertederos inactivos</frm_es>
        <frm_fr>Inactifs d&eacute;charges</frm_fr>
        <type>string</type>
        <view_endgroup></view_endgroup>
    </field>
    <field>
        <name>lithological</name>
        <frm_it>Caratteristiche geolitologiche</frm_it>
        <frm_en>Lithological</frm_en>
        <frm_es>Caracter&iacute;sticas litol&oacute;gicas</frm_es>
        <frm_fr>Caract&eacute;ristiques lithologiques</frm_fr>
        <type>text</type>
        <view_titletag>h3</view_titletag>
        <frm_rows>auto</frm_rows>
        <frm_cols>60</frm_cols>
    </field>
    <field>
        <name>geomorphology</name>
        <frm_it>Caratteristiche geomorfologiche</frm_it>
        <frm_en>Geomorphology</frm_en>
        <frm_es>Geomorfolog&iacute;a</frm_es>
        <frm_fr>G&eacute;omorphologie</frm_fr>
        <type>text</type>
        <view_titletag>h3</view_titletag>
        <frm_rows>auto</frm_rows>
        <frm_cols>60</frm_cols>
    </field>
    <field>
        <name>hydrogeological</name>
        <frm_it>Caratteristiche idrogeologiche</frm_it>
        <frm_en></frm_en>
        <frm_es></frm_es>
        <frm_fr></frm_fr>
        <type>text</type>
        <view_titletag>h3</view_titletag>
        <frm_rows>auto</frm_rows>
        <frm_cols>60</frm_cols>
    </field>
    <field>
        <name>caving</name>
        <frm_it>Caratteristiche speleologiche</frm_it>
        <frm_en>Caving characteristics</frm_en>
        <type>text</type>
        <view_titletag>h3</view_titletag>
        <frm_rows>auto</frm_rows>
        <frm_cols>60</frm_cols>
    </field>
    <field>
        <name>landcover</name>
        <frm_it>Copertura vegetale e uso del suolo</frm_it>
        <frm_en>Vegetation cover and land use</frm_en>
        <frm_es>La cobertura vegetal y uso de la tierra</frm_es>
        <frm_fr>Couverture v&eacute;g&eacute;tale et l'utilisation des terres</frm_fr>
        <type>text</type>
        <view_titletag>h3</view_titletag>
        <frm_rows>auto</frm_rows>
        <frm_cols>60</frm_cols>
    </field>
    <field>
        <name>othercharacteristics</name>
        <frm_it>Altre caratteristiche</frm_it>
        <frm_en>Other features</frm_en>
        <frm_es>Otras caracter&iacute;sticas</frm_es>
        <frm_fr>Autres caract&eacute;ristiques</frm_fr>
        <type>text</type>
        <view_titletag>h3</view_titletag>
        <frm_rows>auto</frm_rows>
        <frm_cols>60</frm_cols>
    </field>
    <field>
        <name>aquifers</name>
        <frm_it>Acquiferi</frm_it>
        <frm_en>Acuíferos</frm_en>
        <frm_es>Acu&iacute;feros</frm_es>
        <frm_fr>Aquif&egrave;res</frm_fr>
        <frm_rows>10</frm_rows>
        <frm_cols>60</frm_cols>
        <type>text</type>
        <view_titletag>h3</view_titletag>
    </field>
    <field>
        <name>notes</name>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>60</frm_cols>
    </field>
    <field>
        <name>photo1</name>
        <frm_it>Immagine</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
        <type>image</type>
        <thumbsize>250</thumbsize>
    </field>
    <field>
        <name>authorphoto1</name>
        <frm_it>Autore immagine</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>authors</name>
        <frm_i18n>authors</frm_i18n>
        <view_hiddentitle>1</view_hiddentitle>
    </field>    
    <field>
        <name>bibliography</name>
        <frm_i18n>bibliography</frm_i18n>
        <type>text</type>
    </field>
    <field>
        <name>geographical_framework</name>
        <frm_i18n>geographical framework</frm_i18n>
        <type>text</type>
    </field>
    <field>
        <name>geological_framework</name>
        <frm_i18n>geological framework</frm_i18n>
        <type>text</type>
    </field>

    <field>
        <name>filekml</name>
        <frm_it>File kml</frm_it>
        <type>file</type>
        <view_tag>center</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
        <googlemap>1</googlemap>
    </field>
    <field>
        <name>filelox</name>
        <frm_it>File lox</frm_it>
        <type>file</type>
        <view_tag>center</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>userupdate</name>
        <type>string</type>
        <frm_it>Aggiornata da</frm_it>
        <frm_showinlist>1</frm_showinlist>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>recordupdate</name>
        <type>datetime</type>
        <frm_it>Ultima modifica</frm_it>
        <frm_show>0</frm_show>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <driver>mysql</driver>
</tables>
