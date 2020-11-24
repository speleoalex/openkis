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
        <name>code</name>
        <frm_it>Numero</frm_it>
        <type>string</type>
        <frm_size>8</frm_size>
        <frm_help_i18n>leave blank for self assignment</frm_help_i18n>
        <unique>1</unique>
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
        <frm_help_it>Inserire i sinonimi della cavità separati da ';' ad esempio 'GALLERIA ESPERANCE; GALLERIA DELLA MADONNINA'</frm_help_it>
    </field>
    <field>
        <name>caveslinks</name>
        <type>string</type>
        <frm_it>Cavità collegate</frm_it>
        <frm_help_it>Inserire i numeri di catasto separati da virgola delle cavità artificiali collegate</frm_help_it>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_artificials</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>provincia,code,name</fk_show_field>
    </field>    
<field>
        <name>caveslinks1</name>
        <type>string</type>
        <frm_it>Grotte collegate</frm_it>
        <frm_help_it>Inserire i numeri di catasto separati da virgola delle grotte collegate o intercettate</frm_help_it>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>provincia,code,name</fk_show_field>
    </field>
    <field>
        <name>firstreference</name>
        <frm_it>Primo censitore</frm_it>		
        <type>uppercase</type>
        <frm_show>1</frm_show>
        <view_show>1</view_show>
        <frm_help_it>Inserire Cognome e nome di chi ha inserito a catasto la cavità, vale chi ha fornito i dati della scheda e NON chi la ha inserita sul gestionale.</frm_help_it>
    </field>	
    <field>
        <frm_group>location</frm_group>
        <frm_group_i18n>location</frm_group_i18n>
        <name>country</name>
        <type>select</type>
        <frm_default>Italy</frm_default>        
        <frm_show>0</frm_show>
    </field>    
    <field>
        <name>regione</name>
        <type>select</type>
        <frm_type>regione</frm_type>
        <frm_it>Regione</frm_it>
        <frm_default>PIEMONTE</frm_default>                
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
        <name>address</name>
        <frm_it>Indirizzo</frm_it>
        <frm_en>Address</frm_en>
        <frm_es>Direcci&oacute;n</frm_es>
        <frm_fr>Adresse</frm_fr>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <googlemap>1</googlemap>
        <frm_endgroup>location</frm_endgroup>
    </field>
    <field>
        <frm_group>cadastre</frm_group>
        <frm_group_it>Catasto Fabbricati</frm_group_it>
        <name>sheet</name>
        <frm_it>Foglio</frm_it>
        <type>string</type>
    </field>
    <field>
        <name>parcel</name>
        <frm_it>Particella</frm_it>
        <type>string</type>
    </field>
    <field>
        <name>subalterno</name>
        <frm_it>Subalterno</frm_it>
        <type>string</type>
    </field>
    <field>
        <name>notes</name>
        <frm_it>Note</frm_it>
        <type>string</type>
    </field>

    <field>
        <frm_group>features</frm_group>
        <frm_group_it>Caratteristiche</frm_group_it>
        <name>year</name>
        <frm_it>Anno di costruzione</frm_it>
        <type>string</type>
    </field>
    <field>
        <name>epoch</name>
        <frm_it>Epoca</frm_it>
        <frm_type>select</frm_type>
        <frm_options>x,a,b,c,d,e,f,g</frm_options>
        <frm_options_it>non rilevabile,preistoria > 3.500 a.C.,protostorica 3.500 a.C. - 750 a.C.,età antica 750 a.C. - 476 d.C.,età medievale 476 d.C. - 1492,età moderna 1492 - 1789,f età contemporanea > 1789 - 1899,novecento</frm_options_it>
    </field>
    <field>
        <name>typology</name>
        <type>string</type>
        <frm_it>Tipologie</frm_it>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_art_types</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>name</fk_show_field>
    </field>
    <field>
        <name>typology_notes</name>
        <type>string</type>
        <frm_it>Note alla tipologia</frm_it>
    </field>
    <field>
        <name>category</name>
        <type>string</type>
        <frm_it>Categoria</frm_it>
        <foreignkey>ctl_art_categories</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>name,note</fk_show_field>
    </field>
    <field>
        <name>geology</name>
        <frm_it>Litologia ingresso</frm_it>
        <type>string</type>
	<frm_type>multicave</frm_type>
        <foreignkey>ctl_geology</foreignkey>
        <fk_link_field>geology</fk_link_field>
        <fk_show_field>geology</fk_show_field>
    </field>
    <field>
        <name>concrection</name>
        <frm_it>Concrezionamenti</frm_it>
        <frm_help_it>Se calcitici si segnalano solo se importanti; se di altri minerali o anomali (es. eccentriche) indicare un breve commento nelle note</frm_help_it>		
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <frm_options>absent,rare,remarkable</frm_options>
        <frm_options_it>Assenti,Rari,Notevoli</frm_options_it>
    </field>
    <field>
        <name>concrection_notes</name>
        <type>string</type>
        <frm_it>Note dei concrezionamenti</frm_it>
    </field>
    <field>
        <name>internal walls</name>
        <type>string</type>
        <frm_it>Murature interne</frm_it>
        <foreignkey>ctl_art_Internal_walls</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>name,note</fk_show_field>
    </field>
     <field>
        <name>walls</name>
        <frm_it>Materiale delle pareti</frm_it>
        <type>string</type>
	<frm_type>multicave</frm_type>
        <foreignkey>ctl_walls</foreignkey>
        <fk_link_field>walls</fk_link_field>
        <fk_show_field>walls</fk_show_field>
    </field>
    <field>
        <name>notes</name>
        <frm_it>Note</frm_it>
        <type>string</type>
    </field>
    <field>
        <name>mined_materials</name>
        <frm_it>Materiali estratti</frm_it>
        <frm_en>Mined materials</frm_en>
        <frm_help_it>Indicare solamente per miniere e cave</frm_help_it>
        <type>string</type>
	<frm_type>multicave</frm_type>
        <foreignkey>ctl_mined_materials</foreignkey>
        <fk_link_field>mined_materials</fk_link_field>
        <fk_show_field>mined_materials</fk_show_field>
    </field>
     <field>
        <name>current use</name>
        <frm_it>Utilizzazione attuale</frm_it>
        <frm_help_it>Nel caso di cessato utilizzo indicare: ABBANDONO</frm_help_it>
        <type>string</type>
    </field>
    <field>
        <name>degree_of_artificility</name>
        <frm_it>Grado di artificialità</frm_it>
        <frm_help_it>Nelle note indicare gli eventuali numeri di catasto di grotte naturali o CA collegate alla presente</frm_help_it>		
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <frm_options>100% circa,over 50%,minus 50%</frm_options>
        <frm_options_it>100% circa,oltre 50%,meno del 50%</frm_options_it>
    </field>
    <field>
        <name>artificiality_degree_notes</name>
        <type>string</type>
        <frm_it>note del grado di artificialità</frm_it>
    </field>
    <field>
        <name>lenght_total</name>
        <view_group>size</view_group>
        <view_group_i18n>size</view_group_i18n>        
        <frm_group>size</frm_group>
        <frm_group_i18n>size</frm_group_i18n>
        <type>string</type>
        <frm_help_it>somma delle lunghezze di tutte le gallerie calcolate nelle tre
            dimensioni; si sommano perciò anche le lunghezze dei pozzi e dei tratti inclinati</frm_help_it>        
        <frm_type>os_lenght</frm_type>
        <frm_it>Sviluppo reale</frm_it>		
        <frm_showinlist>1</frm_showinlist>
        <frm_suffix> m</frm_suffix>
        <frm_size>8</frm_size>        
    </field>
    <field>
        <name>lenght_planimetric</name>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_it>Sviluppo planimetrico</frm_it>		
        <frm_help_it>somma delle lunghezze di tutte le gallerie, calcolate in pianta,
            cioè proiettate su un piano orizzontale</frm_help_it>
        <frm_suffix> m</frm_suffix>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>lenght_extension</name>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_help_it>Per estensione si intende la massima lunghezza planimetrica della cavità, non
            necessariamente a partire dall'ingresso</frm_help_it>
        <frm_it>Estensione</frm_it>		
        <frm_suffix> m</frm_suffix>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>volume</name>
        <type>string</type>
        <frm_type>os_volume</frm_type>
        <frm_it>Volume</frm_it>		
        <frm_suffix> mc</frm_suffix>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>depth_positive</name>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_it>Dislivello positivo</frm_it>
        <frm_size>8</frm_size>
        <frm_suffix> m</frm_suffix>
    </field>
    <field>
        <name>depth_negative</name>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_it>Dislivello negativo</frm_it>
        <frm_size>8</frm_size>
        <frm_suffix> m</frm_suffix>	
    </field>
    <field>
        <name>depth_total</name>
        <view_endgroup>size</view_endgroup>
        <frm_endgroup>size</frm_endgroup>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_it>Dislivello totale</frm_it>				
        <frm_suffix> m</frm_suffix>	
        <frm_size>8</frm_size>
        <frm_showinlist>1</frm_showinlist>
    </field>
    <field>
        <name>latitude_txt</name>
        <view_group>entrance location</view_group>
        <frm_group_i18n>entrance location</frm_group_i18n>
        <frm_it>Latitudine</frm_it>
        <frm_en>Latitude</frm_en>
        <frm_es>Latitud</frm_es>
        <frm_fr>Latitude</frm_fr>
        <frm_help_it>Inserisci qui la latitude. es. 44&#176;12'18.2'' N </frm_help_it>
        <frm_help_en>You insert the latitude here. ex. 44&#176;12'18.2'' N </frm_help_en>
        <type>varchar</type>
        <frm_type>latlon</frm_type>
    </field>
    <field>
        <name>longitude_txt</name>
        <frm_it>Longitudine</frm_it>
        <frm_en>Longitude</frm_en>
        <frm_es>Longitud</frm_es>
        <frm_fr>Longitude</frm_fr>
        <frm_help_it>Inserisci qui la longitude. Se questa &grave; riferita a Monte Mario non dimenticarti di specificarlo, ad esempio inserendo 2&#176;12'11.5'' W di M.Mario</frm_help_it>
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
        <frm_it>Tipo di coordinate originale</frm_it>
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
    </field>
    <field>
        <name>location_evaluation</name>
        <type>string</type>
        <frm_it>Valutazione dato</frm_it>
        <frm_help>descrivere lo strumento e l'affidabilit&agrave; del dato inserito, per esempio coordinate GPS del 12 febbraio 2011 con precisione 4m, oppure dato preso sui carta, oppure dato preso a occhio sulla carta</frm_help>
        <frm_size>60</frm_size>
        <frm_endgroup>entrance location</frm_endgroup>
        <view_endgroup>entrance location</view_endgroup>
    </field>
    <field>
        <name>check</name>
        <frm_it>Posizione verificata sul campo da curatore</frm_it>
        <frm_help>Indicare il nome di chi ha registrato le coordinate. Attenzione !! Il campo deve essere compilato dopo che la cavità è stata ritrovata successivamente la messa a catasto</frm_help>
        <type>varchar</type>
        <frm_show>1</frm_show>
    </field>
    <field>
        <name>check_date</name>
        <frm_it>Data ultima verifica sul campo</frm_it>
        <type>string</type>
        <type>datetime</type>
        <frm_show>1</frm_show>
        <frm_dateformat>y-mm-dd</frm_dateformat>
        <view_dateformat>dd/mm/y 00:00:00</view_dateformat>
    </field>
    <field>
        <name>archeological</name>
        <view_group>Riferimenti a censimenti speciali</view_group>
        <frm_group>Riferimenti a censimenti speciali</frm_group>
        <frm_it>Cavit&agrave; archeologica</frm_it>
        <type>radio</type>
        <frm_options>S,N</frm_options>
        <frm_options_it>Si,No</frm_options_it>
    </field>

    <field>  
        <name>environmentalrisk</name>
        <frm_it>Inquinamento</frm_it>
        <type>radio</type>
        <frm_options>S,N</frm_options>
        <frm_options_it>Si,No</frm_options_it>
    </field>
    <field>
        <name>degree_of_pollution</name>
        <type>string</type>
        <frm_it>Grado di inquinamento</frm_it>
        <foreignkey>ctl_art_pollution</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>name</fk_show_field>
    </field>
   <field>
        <name>type_of_pollution</name>
        <frm_it>Tipo di inquinante</frm_it>		
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <frm_options>solid,liquid,mix,animal,antropic,inert,chemical,other</frm_options>
        <frm_options_it>Solido,Liquido,Misto,Animale,Antropico,Inerte,Chimico,Altro</frm_options_it>
    </field>
    <field>
        <name>type_of_pollution_notes</name>
        <type>string</type>
        <frm_it>Note del tipo di inquinante</frm_it>
    </field>
    <field>  
        <name>closed</name>
        <frm_it>Cavit&agrave; chiusa</frm_it>
        <type>radio</type>
        <frm_options>X,</frm_options>
        <frm_options_it>Si,No</frm_options_it>
    </field>
    <field>  
        <name>closed_notes</name>
        <frm_it>Note accesso alla cavit&agrave</frm_it>
        <type>text</type>
    </field>
 <field>
        <name>danger</name>
        <frm_it>Pericolosit&agrave;</frm_it>		
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <frm_options>no danger,collapses/landslide,flooding,dangerous pollution,toxic gasses</frm_options>
        <frm_options_it>Nessun pericolo,Crolli/frane,Allagamenti,Inquinanti pericolosi,Gas tossici</frm_options_it>
    </field>
    <field>  
        <name>danger_notes</name>
        <frm_it>Note dei pericoli</frm_it>
        <type>text</type>
    </field>
    <field>  
        <name>seen_danger_frequency</name>
        <frm_it>Frequenza del pericolo individuato</frm_it>
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <frm_options>always,seasonal,imprevedible,exceptional</frm_options>
        <frm_options_it>Sempre,Stagionale,Imprevedibile,Eccezionale</frm_options_it>
    </field>
    <field>
        <name>conservation</name>
        <frm_it>Stato di conservazione</frm_it>		
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <frm_options>excellent,good,mediocre,bad</frm_options>
        <frm_options_it>ottimo,buono,mediocre,pessimo</frm_options_it>
    </field>
    <field>
        <name>robustness</name>
        <frm_it>Staticità</frm_it>		
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <frm_options>excellent,bad,ruined</frm_options>
        <frm_options_it>ottima,pessima,diruta</frm_options_it>    
        <frm_help_it>Diruta: quasi tutta franata e impercorribile</frm_help_it>
    </field>
    <field>  
        <name>destroyed</name>
        <frm_it>Cavit&agrave; distrutta</frm_it>
        <type>radio</type>
        <frm_options>X,,P</frm_options>
        <frm_options_it>Si,No,Parzialmente</frm_options_it>
        <frm_endgroup></frm_endgroup>
        <view_endgroup>cens</view_endgroup>
    </field>        
 
    <field>
        <name>trend</name>
        <frm_it>Andamento</frm_it>
        <frm_help_it>es. Orizzontale, in leggera discesa, verticale</frm_help_it>
        <type>uppercase</type>
    </field>
    <field>
        <name>praticability</name>
        <type>text</type>
        <frm_it>Praticabilità interna</frm_it>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_praticability</foreignkey>
        <fk_link_field>id</fk_link_field>
        <fk_show_field>name,note</fk_show_field>
    </field>	
  		
    <field>
        <name>hydrology</name>
        <frm_it>Idrologia</frm_it>		
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <frm_options>water,emitting,absorbing,streams,lakes/ponds,submerged cavity,submerged continuations,flooded cavity</frm_options>
        <frm_options_it>Acqua,Emittente,Assorbente,Corsi d'acqua,Laghi/Pozze,Cavit&agrave sommersa,Prosecuzioni sommerse,Cavità allagata</frm_options_it>
    </field>
    <field>
        <name>associations</name>
        <frm_it>Gruppi</frm_it>
        <frm_help>Gruppi o persone che hanno effettuato attivit&agrave; rilevanti o esplorative sulla cavit&agrave;</frm_help>
        <type>text</type>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>chronology</name>
        <frm_it>Cronologia catastale</frm_it>		
        <frm_help>Cronologia delle versioni, esempio: 
            1990 - Mario Rossi - primo accatastatore
            2002 - Paolo Bianchi - aggiornamento per nuovo ramo esplorato, aggiunta descrizione
            2010 - Pinco Pallino - riposizionamento
            aggiungere in coda le modifiche</frm_help>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>notes</name>
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>60</frm_cols>
    </field>
    <field>
        <name>description</name>
        <frm_it>Descrizione</frm_it>
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
        <name>history</name>
        <frm_it>Storia</frm_it>		
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    <field>
        <name>fauna</name>
        <frm_it>Fauna</frm_it>		
        <type>text</type>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>
    
    <field>
        <frm_group>Protezione dei dati</frm_group>        
        <name>hidden</name>
        <frm_it>Livello di visibilit&agrave; dei dati</frm_it>
        <type>radio</type>
        <frm_options>0,1</frm_options>
        <frm_options_it>Dati visibili,Dati non visibili</frm_options_it>
        <frm_endgroup>Protezione dei dati</frm_endgroup>
    </field>   

    <field>
        <frm_group>Protezione dei dati</frm_group>
        <name>hidden</name>
        <frm_it>Livello di visibilit&agrave; dei dati</frm_it>
        <type>radio</type>
        <frm_options>0,1</frm_options>
        <frm_options_it>Dati visibili,Dati non visibili</frm_options_it>
        <frm_endgroup></frm_endgroup>
    </field>
    <field>
        <name>recordinsert</name>
        <type>datetime</type>
        <view_dateformat>dd/mm/y 00:00:00</view_dateformat>
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
        <view_dateformat>dd/mm/y</view_dateformat>
        <frm_it>Ultima modifica</frm_it>
        <frm_show>0</frm_show>
        <frm_showinlist>1</frm_showinlist>
    </field>	
    <innertable>
        <tablename>ctl_surveys_artificials</tablename>
        <frm_i18n>surveys</frm_i18n>
        <frm_it>Rilievi cavit&agrave;</frm_it>        
        <linkfield>code,codeartificial</linkfield>
        <innertablefields>id,name,photo1,filekml,date,author</innertablefields>
        <view></view>
    </innertable>    
    <innertable>
        <tablename>ctl_attachments_artificials</tablename>
        <frm_it>Allegati</frm_it>
        <linkfield>code,codeartificial</linkfield>
        <innertablefields>id,name</innertablefields>
    </innertable>
    <innertable>
        <tablename>ctl_photos_artificials</tablename>
        <frm_it>Foto della cavit&agrave;</frm_it>
        <linkfield>code,codeartificial</linkfield>
        <innertablefields>id,name,photo</innertablefields>
        <view></view>
    </innertable>
    <innertable>
        <tablename>ctl_faunacave</tablename>
        <frm_it>Rilevamenti faunistici</frm_it>
        <linkfield>code,codeartificial</linkfield>
        <innertablefields>name,photo1,date</innertablefields>
        <view></view>
    </innertable>
    <field>
        <name>noteversione</name>
        <frm_it>Note relative a questa versione della scheda</frm_it>
        <type>string</type>
        <frm_show>1</frm_show>
        <frm_size>80</frm_size>
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
    <field>
        <name>license</name>
        <frm_it>Licenza immagine ingresso</frm_it>
        <foreignkey>ctl_licenses</foreignkey>
        <fk_link_field>name</fk_link_field>
        <fk_show_field>name</fk_show_field>		
        <frm_showinlist>1</frm_showinlist>
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
        <name>groupinsert</name>
        <frm_i18n>limits the edit of the content in these groups</frm_i18n>
        <foreignkey>fn_groups</foreignkey>
        <fk_link_field>groupname</fk_link_field>
        <fk_show_field>groupname</fk_show_field>
        <frm_type>multicheck</frm_type>
        <type>string</type>
        <frm_setonlyadmin>1</frm_setonlyadmin>
        <frm_allowupdate>onlyadmin</frm_allowupdate>
    </field>
    <field>
        <name>coordnatesupdated</name>
        <frm_show>0</frm_show>
    </field> 
    <field>
        <name>recorddeleted</name>
        <type>bool</type>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>username</name>
        <type>string</type>
        <frm_show>0</frm_show>
    </field>
    <driver>mysql</driver>
    <sqltable>ctl_artificials</sqltable>
</tables>
