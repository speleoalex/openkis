<?php exit(0);?>
<tables>			
    <field>
        <name>id</name>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
        <type>string</type>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>codecave</name>
        <frm_it>Codice Grotta</frm_it>
        <type>string</type>
    </field>
    <field>
        <name>name</name>
        <frm_it>Nome rilievo</frm_it>
    </field>
    <field>
        <name>photo1</name>
        <frm_it>Immagine</frm_it>
        <type>image</type>
        <thumbsize>408</thumbsize>
        <view_tag>center</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
        <googlemap>1</googlemap>
    </field>
    <field>
        <name>file</name>
        <frm_it>File sorgente</frm_it>
        <type>file</type>
        <view_tag>center</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
        <googlemap>1</googlemap>
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
        <name>filepdf</name>
        <frm_it>File pdf</frm_it>
        <type>file</type>
        <view_tag>center</view_tag>
        <view_hiddentitle>1</view_hiddentitle>
        <googlemap>1</googlemap>
    </field>	
    <field>
        <name>name</name>
        <frm_it>Nome rilievo</frm_it>
    </field>
    <field>
        <name>date</name>
        <frm_it>Data rilievo</frm_it>
        <type>datetime</type>
        <frm_dateformat>y-mm-dd</frm_dateformat>
    </field>
    <field>
        <name>author</name>
        <frm_it>Autore rilievo</frm_it>		
    </field>
    <field>
        <name>makers</name>
        <frm_it>Rilevatori</frm_it>
        <type>text</type>
        <frm_cols>80</frm_cols>
        <frm_rows>auto</frm_rows>
    </field>
    <field>
        <name>license</name>
        <frm_it>Licenza rilievo</frm_it>
        <foreignkey>ctl_licenses</foreignkey>
        <fk_link_field>name</fk_link_field>
        <fk_show_field>name</fk_show_field>		
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>description</name>
        <type>text</type>
        <frm_it>Descrizione rilievo</frm_it>
        <frm_rows>auto</frm_rows>
        <frm_cols>40</frm_cols>
    </field>
    <field>
        <name>accuracy</name>
        <type>string</type>
        <frm_it>Precisione rilievo</frm_it>
    </field>

    <field>
        <name>groupview</name>
        <frm_i18n>limits the display of the content in these groups</frm_i18n>
        <foreignkey>fn_groups</foreignkey>
        <fk_link_field>groupname</fk_link_field>
        <fk_show_field>groupname</fk_show_field>
        <frm_type>multicheck</frm_type>
        <type>string</type>
    </field>
    <field>
        <name>recordinsert</name>
        <type>datetime</type>
        <frm_it>Inserito</frm_it>
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
        <name>recordupdate</name>
        <type>datetime</type>
        <frm_it>Ultima modifica</frm_it>
        <frm_show>0</frm_show>
        <frm_showinlist>1</frm_showinlist>
    </field>	
    <driver>mysql</driver>
</tables>
