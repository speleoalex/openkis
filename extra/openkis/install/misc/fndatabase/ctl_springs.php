<?php exit(0);?>

<tables>
    <field>
        <name>id</name>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>code</name>
        <frm_it>Numero</frm_it>
        <type>string</type>
        <frm_size>8</frm_size>
        <frm_help_i18n>leave blank for self assignment</frm_help_i18n>
        <unique></unique>
    </field>
    <field>
        <name>codecave</name>
        <frm_it>Numero grotta</frm_it>
        <type>string</type>
        <view_show>1</view_show>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
        <frm_showinlist>1</frm_showinlist>
    </field>	
    <field>
        <name>name</name>
        <frm_it>Nome</frm_it>
        <type>uppercase</type>
        <view_tag>h2</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
        <frm_size>80</frm_size>
        <frm_help_it>Nome principale della cavit&agrave;</frm_help_it>
    </field>		
    <field>
        <name>synonyms</name>
        <frm_it>Altre denominazioni</frm_it>
        <type>uppercase</type>
        <view_tag>em</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
        <frm_size>80</frm_size>
        <frm_help_it>Inserire i sinonimi della grotta separati da ';' ad esempio 'GROTTA DELLE FATE; GROTTA DE FAJE'</frm_help_it>
    </field>
    <field>
        <name>country</name>
        <type>select</type>
        <frm_default>ITALY</frm_default>        
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>regione</name>
        <type>select</type>
        <frm_type>regione</frm_type>
        <frm_it>Regione</frm_it>
    </field>
    <field>
        <name>provincia</name>
        <type>string</type>
        <frm_it>Provincia</frm_it>
        <frm_type>provincia</frm_type>
    </field>    
    <field>
        <name>comune</name>
        <type>string</type>
        <frm_it>Comune</frm_it>
        <frm_type>comune</frm_type>
    </field>    
    <field>
        <name>localita</name>
        <frm_it>Localit&agrave;</frm_it>
        <type>uppercase</type>
    </field>  

    <field>
        <name>latitude_txt</name>
        <view_group>location</view_group>
        <frm_group_i18n>entrance location</frm_group_i18n>
        <frm_it>Latitudine</frm_it>
        <frm_en>Latitude</frm_en>
        <frm_es>Latitud</frm_es>
        <frm_fr>Latitude</frm_fr>
        <frm_help_it>Inserisci qui la latitude. es. 44&#176; 12'18,2'' N </frm_help_it>
        <frm_help_en>You insert the latitude here. ex. 44&#176; 12'18,2'' N </frm_help_en>
        <type>varchar</type>
        <frm_type>latlon</frm_type>
    </field>
    <field>
        <name>longitude_txt</name>
        <frm_it>Longitudine</frm_it>
        <frm_en>Longitude</frm_en>
        <frm_es>Longitud</frm_es>
        <frm_fr>Longitude</frm_fr>
        <frm_help_it>Inserisci qui la longitude. Se questa &grave; riferita a Monte Mario non dimenticarti di specificarlo, ad esempio inserendo 2&#176;12'11,5'' W di M.Mario</frm_help_it>
        <type>varchar</type>
        <frm_type>latlon</frm_type>
    </field>
    <field>
        <name>coordinates_type</name>
        <type>string</type>
        <frm_it>Tipo di coordinate</frm_it>
        <frm_help_it>geografiche quando sono del tipo : 44°12'32'' oppure 44.23456 mentre 189992 sono Chilometriche.</frm_help_it>
        <foreignkey>ctl_coordinatestypes</foreignkey>
        <fk_link_field>coordinates_type</fk_link_field>
        <fk_show_field>coordinates_type</fk_show_field>
    </field>
    <field>
        <name>original_coordinates_type</name>
    </field>
    <field>
        <name>elevation</name>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_it>Quota altimetrica</frm_it>
        <frm_suffix> m s.l.m</frm_suffix>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>elevation_map</name>
        <type>string</type>
        <frm_it>Quota cartografica</frm_it>
        <frm_type>os_lenght</frm_type>		
        <frm_suffix> m s.l.m</frm_suffix>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>elevation_gps</name>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_it>Quota GPS</frm_it>	
        <frm_help>ATTENZIONE: la quota data dai gps barometrici NON è la quota GPS ma la quota altimetrica.
            la quota GPS è quella data dall'elissoide a cui fa riferimento il datum wgs84
        </frm_help>
        <frm_suffix> m s.l.m</frm_suffix>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>map_denomination</name>
        <frm_it>Denominazione carta</frm_it>	
        <type>string</type>
    </field>    
    <field>
        <name>map_edition</name>
        <frm_it>Edizione carta</frm_it>	
        <type>string</type>
        <frm_help>Inserire l'edizione della carta</frm_help>
        <frm_endgroup></frm_endgroup>
        <view_endgroup></view_endgroup>
    </field>
    <field>
        <name>flow_max</name>
        <type>string</type>
        <frm_it>Portata massima</frm_it>
    </field>
    <field>
        <name>flow_min</name>
        <type>string</type>
        <frm_it>Portata minima</frm_it>
    </field>
    <field>
        <name>flow_average</name>
        <type>string</type>
        <frm_it>Portata media</frm_it>
    </field>

    <field>
        <name>use</name>
        <type>select</type>
        <frm_options>free,captured</frm_options>
        <frm_options_i18n>free spring,captured spring</frm_options_i18n>
    </field>
    <field>
        <name>utilization</name>
        <frm_i18n>use of water</frm_i18n>
        <type>select</type>
        <frm_options>drinking,agricoltural,industrial</frm_options>
        <frm_options_i18n>drinking,agricoltural,industrial</frm_options_i18n>
    </field>        
    <field>
        <name>hydrology</name>
        <frm_it>Idrologia</frm_it>
    </field>
    <field>
        <name>hydrogeology</name>
        <type>uppercase</type>
        <frm_it>Idrogeologia</frm_it>
    </field>
    <field>
        <name>morphology</name>
        <type>uppercase</type>
        <frm_it>Idrogeologia</frm_it>
    </field>

    <field>
        <name>description</name>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>itinerary</name>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>photo1</name>
        <frm_it>Immagine</frm_it>
        <frm_en>Screenshot</frm_en>
        <view_hiddentitle>1</view_hiddentitle>
        <thumb_listheight>64</thumb_listheight>
        <type>image</type>
        <thumbsize>250</thumbsize>
        <view_tag>center</view_tag>
    </field>
    <field>
        <name>authordescription</name>
        <frm_it>Autori testi descrizione</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>authoritinerary</name>
        <frm_it>Autori testi Itinerario</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
    </field>

    <field>
        <name>latitude</name>
        <type>float</type>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>longitude</name>
        <type>float</type>
        <frm_show>0</frm_show>
    </field>  
    <field>
        <name>coordnatesupdated</name>
        <frm_show>0</frm_show>
    </field> 
    <field>
        <name>username</name>
        <type>string</type>
        <frm_it>Inserita da</frm_it>
        <frm_showinlist>1</frm_showinlist>
        <frm_show>0</frm_show>
    </field>

    <field>
        <name>userupdate</name>
        <type>string</type>
        <frm_it>Aggiornata da</frm_it>
        <frm_showinlist>1</frm_showinlist>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>recordinsert</name>
        <type>datetime</type>
        <view_dateformat>dd/mm/y 00:00:00</view_dateformat>
        <frm_it>Insetito</frm_it>
        <frm_show>0</frm_show>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>recordupdate</name>
        <type>datetime</type>
        <view_dateformat>dd/mm/y 00:00:00</view_dateformat>
        <frm_it>Ultima modifica</frm_it>
        <frm_show>0</frm_show>
        <frm_showinlist>1</frm_showinlist>
    </field>	
    <field>
        <name>import_rawdata</name>
        <frm_it>Dati pre importazione</frm_it>
        <type>text</type>        
    </field>    
    <driver>mysql</driver>
    <sqltable>ctl_springs</sqltable>
</tables>
