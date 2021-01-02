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
        <name>scientific_name</name>		
        <frm_it>Nome scientifico</frm_it>
        <frm_i18n>Scientific name</frm_i18n>
        <frm_required>1</frm_required>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>name</name>
        <frm_it>Nome comune</frm_it>
        <frm_i18n>common name</frm_i18n>
        <type>string</type>
        <frm_type>uppercase</frm_type>
        <frm_uppercase>uppercase</frm_uppercase>		
        <view_hiddentitle>1</view_hiddentitle>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>sinon</name>
        <frm_it>Sinonimi</frm_it>
        <frm_i18n>synonyms</frm_i18n>
        <type>string</type>
        <frm_type>uppercase</frm_type>
        <frm_uppercase>uppercase</frm_uppercase>		
        <view_hiddentitle>1</view_hiddentitle>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>phylum</name>
        <frm_group>taxonomy</frm_group>
        <frm_group_it>Tassonomia</frm_group_it>
        <frm_it>Phylum</frm_it>
        <frm_i18n>Phylum</frm_i18n>
        <frm_showinlist>1</frm_showinlist>
        <foreignkey>ctl_fauna</foreignkey>
        <fk_link_field>phylum</fk_link_field>
        <fk_show_field>phylum</fk_show_field>
        <frm_type>stringselect</frm_type>
        <frm_uppercase>uppercase</frm_uppercase>		
    </field>
    <field>
        <name>class</name>
        <frm_it>Classe</frm_it>
        <frm_i18n>Class</frm_i18n>
        <frm_showinlist>1</frm_showinlist>
        <frm_type>stringselect</frm_type>
        <frm_uppercase>uppercase</frm_uppercase>		
        <foreignkey>ctl_fauna</foreignkey>
        <fk_link_field>class</fk_link_field>
        <fk_show_field>class</fk_show_field>
    </field>
    <field>
        <name>order</name>		
        <frm_it>Ordine</frm_it>
        <frm_i18n>Order</frm_i18n>
        <frm_type>stringselect</frm_type>
        <frm_uppercase>uppercase</frm_uppercase>		
        <foreignkey>ctl_fauna</foreignkey>
        <fk_link_field>order</fk_link_field>
        <fk_show_field>order</fk_show_field>
    </field>
    <field>
        <name>family</name>		
        <frm_it>Famiglia</frm_it>
        <frm_i18n>Family</frm_i18n>
        <frm_type>stringselect</frm_type>
        <frm_uppercase>uppercase</frm_uppercase>
        <foreignkey>ctl_fauna</foreignkey>
        <fk_link_field>family</fk_link_field>
        <fk_show_field>family</fk_show_field>
        <frm_showinlist>1</frm_showinlist>	
    </field>
    <field>
        <name>genus</name>		
        <frm_it>Genere</frm_it>
        <frm_i18n>genere</frm_i18n>
        <frm_endgroup>tassonomia</frm_endgroup>		
    </field>
        <field>
        <name>type</name>
        <frm_it>Categoria</frm_it>
        <frm_i18n>category</frm_i18n>
        <type>string</type>
        <frm_type>radio</frm_type>
        <frm_options>TROGLOSSENO,TROGLOFILO,SUBTROGLOFILO,EUTROGLOFILO,TROGLOBIO,STIGOBIO,STIGOFILO,EUSTIGOFOLO,STIGOSSENO</frm_options>
        <frm_help_it>Troglosseni: specie che si trovano in ambiente ipogeo solo accidentalmente, come quelli che cadono all’interno dei pozzi verticali o fluitati dalle acque.
            Troglofili: specie che sono presenti con maggiore regolarità nell’ambiente ipogeo. Questa categoria comprende due distinti gruppi di animali.
            Subtroglofili: quelli che si trovano in grotta o in ambienti artificiali solo in alcuni periodi della loro vita e non presentano adattamenti all’ambiente sotterraneo.
            Eutroglofili: quegli animali che, pur manifestando una spiccata preferenza per l’ambiente ipogeo e possiedono inoltre alcuni particolari adattamenti morfologici e fisiologici, possono vivere e in alcuni casi riprodursi anche nell’ambiente epigeo.
            Troglobi: organismi considerati “cavernicoli obbligati” cioè perfettamente adattati alla vita ipogea e non più capaci di svincolarsene. I troglobi svolgono l’intero ciclo vitale all’interno delle grotte o più precisamente del reticolo sotterraneo e presentano in maniera evidente vistose modificazioni morfologiche e fisiologiche rispetto alle specie epigee.
            Gli organismi esclusivi delle acque sotterranee prendono invece il nome di stigobi (e, per analogia con i troglobi, vengono usati i termini stigofili – ed anche eustigofili – e stigosseni).
        </frm_help_it>
        <view_hiddentitle>1</view_hiddentitle>
        <frm_showinlist>1</frm_showinlist>
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
        <name>photo1</name>
        <frm_i18n>image</frm_i18n>
        <frm_showinlist>0</frm_showinlist>
        <view_hiddentitle>1</view_hiddentitle>
        <thumb_listheight>64</thumb_listheight>
        <type>image</type>
        <thumbsize>250</thumbsize>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>authorphoto1</name>
        <frm_it>Autori immagine 1</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>photo2</name>
        <frm_i18n>image</frm_i18n>
        <frm_showinlist>0</frm_showinlist>
        <view_hiddentitle>1</view_hiddentitle>
        <thumb_listheight>64</thumb_listheight>
        <type>image</type>
        <thumbsize>250</thumbsize>
        <view_hiddentitle>1</view_hiddentitle>
    </field>	
    <field>
        <name>authorphoto2</name>
        <frm_it>Autori immagine 2</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <!--  system fields -->
    <field>
        <name>username</name>
        <type>string</type>
        <frm_it>Inserita da</frm_it>
        <frm_showinlist>1</frm_showinlist>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
    </field>
    <field>
        <name>userupdate</name>
        <type>string</type>
        <frm_it>Aggiornata da</frm_it>
        <frm_showinlist>1</frm_showinlist>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
    </field>	
    <field>
        <name>recordinsert</name>
        <frm_i18n>insertion date</frm_i18n>
        <type>string</type>
        <frm_type>datetime</frm_type>
        <view_dateformat>dd/mm/y 00:00:00</view_dateformat>
        <frm_show>0</frm_show>ù
        <view_show>1</view_show>
    </field>
    <field>
        <name>recordupdate</name>
        <frm_i18n>date updated</frm_i18n>
        <type>string</type>
        <frm_type>datetime</frm_type>
        <view_dateformat>dd/mm/y 00:00:00</view_dateformat>
        <frm_showinlist>0</frm_showinlist>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
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
        <name>groupinsert</name>
        <frm_i18n>limits the edit of the content to these groups</frm_i18n>
        <foreignkey>fn_groups</foreignkey>
        <fk_link_field>groupname</fk_link_field>
        <fk_show_field>groupname</fk_show_field>
        <frm_type>multicheck</frm_type>
        <type>string</type>
        <frm_setonlyadmin>1</frm_setonlyadmin>
        <frm_allowupdate>onlyadmin</frm_allowupdate>
    </field>
    <driver>mysql</driver>
    <sqltable>ctl_fauna</sqltable>    
</tables>
