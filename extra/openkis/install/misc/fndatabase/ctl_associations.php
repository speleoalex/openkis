<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>
    <!-- caving clubs and associations, linked from the "groups" field of the surveys (fk_link_field code) -->
    <field>
        <name>code</name>
        <frm_it>Sigla</frm_it>
        <frm_en>Code</frm_en>
        <frm_help_it>Codice breve del gruppo (es. GSS, SIAL): e' il valore salvato nei rilievi. Senza virgole e senza punti.</frm_help_it>
        <frm_help_en>Short code of the club (e.g. GSS, SIAL): it is the value stored in the surveys. No commas and no dots.</frm_help_en>
        <type>string</type>
        <frm_type>uppercase</frm_type>
        <frm_size>12</frm_size>
        <frm_required>1</frm_required>
        <primarykey>1</primarykey>
    </field>
    <field>
        <name>name</name>
        <frm_it>Nome del gruppo</frm_it>
        <frm_en>Club name</frm_en>
        <type>string</type>
        <frm_size>60</frm_size>
        <frm_required>1</frm_required>
    </field>
    <field>
        <name>logo</name>
        <frm_it>Logo</frm_it>
        <frm_en>Logo</frm_en>
        <type>image</type>
        <thumbsize>200</thumbsize>
        <thumb_listheight>48</thumb_listheight>
    </field>
    <field>
        <name>active</name>
        <frm_it>Attivo</frm_it>
        <frm_en>Active</frm_en>
        <frm_help_it>Il gruppo è ancora attivo</frm_help_it>
        <frm_help_en>The club is still active</frm_help_en>
        <type>check</type>
        <frm_type>yesno</frm_type>
    </field>
    <field>
        <name>journal</name>
        <frm_it>Bollettino/Rivista</frm_it>
        <frm_en>Journal</frm_en>
        <type>string</type>
        <frm_size>60</frm_size>
    </field>
    <field>
        <name>city</name>
        <frm_it>Sede</frm_it>
        <frm_en>Town</frm_en>
        <type>string</type>
    </field>
    <field>
        <name>provincia</name>
        <frm_it>Provincia</frm_it>
        <frm_en>Province</frm_en>
        <frm_help_it>Sigla della provincia (GE, IM, SP, SV, ...)</frm_help_it>
        <frm_help_en>Province code (GE, IM, SP, SV, ...)</frm_help_en>
        <type>string</type>
        <frm_type>uppercase</frm_type>
        <frm_size>2</frm_size>
    </field>
    <field>
        <name>address</name>
        <frm_it>Indirizzo sede</frm_it>
        <frm_en>Address</frm_en>
        <type>string</type>
        <frm_size>60</frm_size>
    </field>
    <field>
        <name>postaladdress</name>
        <frm_it>Indirizzo postale</frm_it>
        <frm_en>Postal address</frm_en>
        <type>string</type>
        <frm_size>60</frm_size>
    </field>
    <field>
        <name>telephone</name>
        <frm_it>Telefono</frm_it>
        <frm_en>Phone</frm_en>
        <type>string</type>
    </field>
    <field>
        <name>email</name>
        <frm_it>Email</frm_it>
        <frm_en>Email</frm_en>
        <type>string</type>
    </field>
    <field>
        <name>website</name>
        <frm_it>Sito web</frm_it>
        <frm_en>Website</frm_en>
        <type>string</type>
        <frm_type>link</frm_type>
    </field>
    <field>
        <name>timetable</name>
        <frm_it>Orari riunioni</frm_it>
        <frm_en>Meeting schedule</frm_en>
        <type>string</type>
        <frm_size>60</frm_size>
    </field>
    <field>
        <name>description</name>
        <frm_it>Descrizione</frm_it>
        <frm_en>Description</frm_en>
        <type>text</type>
        <frm_type>textopenspeleo</frm_type>
        <frm_rows>auto</frm_rows>
        <frm_cols>90</frm_cols>
    </field>
    <field>
        <name>activities</name>
        <frm_it>Attività</frm_it>
        <frm_en>Activities</frm_en>
        <type>text</type>
        <frm_type>textopenspeleo</frm_type>
        <frm_rows>auto</frm_rows>
        <frm_cols>90</frm_cols>
    </field>
    <field>
        <name>history</name>
        <frm_it>Storia</frm_it>
        <frm_en>History</frm_en>
        <type>text</type>
        <frm_type>textopenspeleo</frm_type>
        <frm_rows>auto</frm_rows>
        <frm_cols>90</frm_cols>
    </field>
    <field>
        <name>route</name>
        <frm_it>Come raggiungerci</frm_it>
        <frm_en>How to reach us</frm_en>
        <type>text</type>
        <frm_type>textopenspeleo</frm_type>
        <frm_rows>auto</frm_rows>
        <frm_cols>90</frm_cols>
    </field>
    <field>
        <frm_group>Posizione della sede</frm_group>
        <frm_group_en>Headquarters location</frm_group_en>
        <name>latitude</name>
        <frm_it>Latitudine</frm_it>
        <frm_en>Latitude</frm_en>
        <frm_help_it>WGS84, gradi decimali (es. 44.4075)</frm_help_it>
        <frm_help_en>WGS84, decimal degrees (e.g. 44.4075)</frm_help_en>
        <type>string</type>
        <frm_size>12</frm_size>
    </field>
    <field>
        <name>longitude</name>
        <frm_it>Longitudine</frm_it>
        <frm_en>Longitude</frm_en>
        <frm_help_it>WGS84, gradi decimali (es. 8.9341)</frm_help_it>
        <frm_help_en>WGS84, decimal degrees (e.g. 8.9341)</frm_help_en>
        <type>string</type>
        <frm_size>12</frm_size>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>photo1</name>
        <frm_it>Foto della sede</frm_it>
        <frm_en>Headquarters photo</frm_en>
        <type>image</type>
        <thumbsize>250</thumbsize>
    </field>
    <field>
        <name>notes</name>
        <frm_it>Note</frm_it>
        <frm_en>Notes</frm_en>
        <frm_help_it>Nomi precedenti, sezione CAI, fusioni, gruppo non piu' attivo...</frm_help_it>
        <frm_help_en>Former names, CAI section, mergers, club no longer active...</frm_help_en>
        <type>text</type>
        <frm_rows>3</frm_rows>
        <frm_cols>60</frm_cols>
    </field>
    <driver>mysql</driver>
</tables>
