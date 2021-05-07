<?php exit(0); ?>
<tables>			
    <field>
        <name>id</name>
        <type>string</type>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>title</name>
        <frm_it>Titolo</frm_it>
        <type>string</type>
        <view_tag>h2</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>magazine</name>
        <type>string</type>
        <frm_showinlist>1</frm_showinlist>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>photo1</name>
        <frm_it>Immagine Copertina</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
        <type>image</type>
        <thumbsize>250</thumbsize>
    </field>
    <field>
        <name>authors</name>
        <frm_it>Autori</frm_it>
        <type>string</type>
        <frm_showinlist>1</frm_showinlist>
        <frm_cols>80</frm_cols>
    </field>
    <field>        
        <name>editor</name>
        <name>Editore</name>
        <type>string</type>
    </field>
    <field>
        <name>country</name>
        <frm_it>Nazione</frm_it>
        <frm_it>Nazione pubblicazione</frm_it>
        <type>string</type>
        <frm_showinlist>1</frm_showinlist>
    </field>

    <field>
        <name>city</name>
        <frm_it>Citta pubblicazione</frm_it>
        <type>string</type>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>province</name>
        <frm_it>Provincia pubblicazione</frm_it>
        <type>string</type>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>year</name>
        <frm_it>Anno pubblicazione</frm_it>
        <type>string</type>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>number</name>
        <type>string</type>
    </field>

    <field>
        <name>pages</name>
        <frm_it>Pagine</frm_it>
        <type>string</type>
    </field>

    <field>
        <name>zone</name>
        <frm_it>Zona</frm_it>
        <frm_it>Zona grotte trattate</frm_it>
        <type>string</type>
    </field>
    <field>
        <name>abstract</name>
        <frm_it>Riassunto</frm_it>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>50</frm_cols>
    </field>
    <field>
        <name>codeartificials</name>
        <frm_it>Cavità artificiali</frm_it>
        <type>text</type>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>codecaves</name>
        <frm_it>Grotte</frm_it>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>provincia,code,name</fk_show_field>
        <frm_help_it> per esempio:LI928,LI930,LI975,LI976,LI1619,</frm_help_it>
        <frm_cols>80</frm_cols>
    </field>


    <field>
        <name>surveys</name>
        <frm_it>Rilievi</frm_it>
        <type>text</type>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>provincia,code,name</fk_show_field>
        <frm_help_it> per esempio:LI928,LI930,LI975,LI976,LI1619,</frm_help_it>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>modified_caves</name>
        <frm_i18n>modified caves</frm_i18n>
        <type>text</type>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>provincia,code,name</fk_show_field>
        <frm_help_it> per esempio:LI928,LI930,LI975,LI976,LI1619,</frm_help_it>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>modified_surveys</name>
        <frm_it>Rilievi_modificati</frm_it>
        <type>text</type>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>provincia,code,name</fk_show_field>
        <frm_help_it> per esempio:LI928,LI930,LI975,LI976,LI1619,</frm_help_it>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>photos</name>
        <frm_i18n>photos</frm_i18n>
        <frm_it>Foto</frm_it>
        <type>string</type>
        <frm_cols>80</frm_cols>
        <frm_help_it> per esempio:928,930,975,976,1619,</frm_help_it>
    </field>
    <field>
        <name>drawings</name>
        <frm_it>Disegni</frm_it>
        <type>text</type>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>fossils</name>
        <frm_it>Fossili</frm_it>
        <frm_it>Numeri grotte che fanno riferimento a fossili (Speleopaleo)</frm_it>
        <type>text</type>
        <frm_cols>80</frm_cols>
        <frm_help_it>Le grotte inserite qui compariranno nel DB speleopaleo es.928,930,975,976,1619</frm_help_it>
    </field>
    <field>
        <name>topic</name>
        <frm_it>Argomento</frm_it>
        <type>string</type>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>fauna</name>
        <frm_it>Fauna trattata</frm_it>
        <type>text</type>
        <frm_cols>80</frm_cols>
        <frm_type>multicave</frm_type>
        <frm_separator>|</frm_separator>        
        <foreignkey>ctl_fauna</foreignkey>
        <fk_link_field>scientific_name</fk_link_field>
        <fk_show_field>scientific_name</fk_show_field>
        <frm_help_it> per esempio:Plecotus auritus|Rhinolophus ferrumequinum|</frm_help_it>
    </field>
    <field>
        <name>file1</name>
        <frm_group>Allegati</frm_group>
        <frm_it>ESTRATTO ARTICOLO</frm_it>
        <type>file</type>
        <frm_type>file</frm_type>
        <view_tag>fieldset</view_tag>
    </field>	
    <field>
        <name>file2</name>
        <frm_it>ESTRATTO ARTICOLO 2</frm_it>
        <type>file</type>
        <frm_type>file</frm_type>
        <view_tag>fieldset</view_tag>
    </field>	
    <field>
        <name>file3</name>
        <frm_it>ESTRATTO ARTICOLO 3</frm_it>
        <type>file</type>
        <frm_type>file</frm_type>
        <view_tag>fieldset</view_tag>
        <frm_endgroup></frm_endgroup>
    </field>	
    <field>
        <name>license</name>
        <frm_i18n>license</frm_i18n>
        <foreignkey>ctl_licenses</foreignkey>
        <fk_link_field>name</fk_link_field>
        <fk_show_field>name</fk_show_field>		
        <frm_showinlist>1</frm_showinlist>
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
        <view_show>1</view_show>
    </field>
    <field>
        <name>recordinsert</name>
        <type>datetime</type>
        <view_dateformat>dd/mm/y 00:00:00</view_dateformat>
        <frm_it>Insetito</frm_it>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
    </field>	
    <field>
        <name>recordupdate</name>
        <type>datetime</type>
        <view_dateformat>dd/mm/y 00:00:00</view_dateformat>
        <frm_it>Ultima modifica</frm_it>
        <frm_show>0</frm_show>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <driver>mysql</driver>
    <sqltable>ctl_bibliography</sqltable>
</tables>
