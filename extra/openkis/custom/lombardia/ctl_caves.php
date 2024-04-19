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
        <name>code_fslo</name>
        <frm_it>Codice FSLo</frm_it>
        <type>string</type>
        <frm_size>8</frm_size>
        <frm_help_it>Codice univoco della federazione Speleologica Lombarda. È composto LO+CODICE PROVINCIA+NUMERO+NUMERO INGRESSO AGGIUNTIVO</frm_help_it>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
    </field>


    <field>
        <name>name</name>
        <frm_it>Nome</frm_it>
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
        <name>caveslinks</name>
        <type>string</type>
        <frm_it>Grotte collegate</frm_it>
        <frm_help_it>Inserire i numeri di catasto separati da virgola degli ingressi e delle grotte collegate</frm_help_it>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>provincia,code,name</fk_show_field>
    </field>
    <field>
        <name>alias</name>
        <type>string</type>
        <frm_help_it>Se la cavità è stata messa a catasto con più numeri indicarli separati da virgola (es. PI1,PI12)</frm_help_it>
    </field>
    <field>
        <name>firstreference</name>
        <frm_it>rilevatori</frm_it>
        <type>uppercase</type>
        <frm_show>1</frm_show>
        <view_show>1</view_show>
        <frm_help_it>Inserire Cognome e nome di chi ha inserito a catasto la grotta,vale chi ha fornito i dati della scheda e NON chi la ha inserita sul gestionale.</frm_help_it>
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
        <frm_default>LOMBARDIA</frm_default>
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
        <frm_endgroup>location</frm_endgroup>
    </field>
    <field>
        <name>mount</name>
        <frm_it>Monte</frm_it>
        <type>uppercase</type>
        <frm_showinlist>0</frm_showinlist>
    </field>
    <field>
        <name>velley</name>
        <frm_it>Valle</frm_it>
        <type>uppercase</type>
        <frm_showinlist>0</frm_showinlist>
    </field>
    <field>
        <name>areas</name>
        <frm_it>Area carsica</frm_it>
        <type>string</type>
        <frm_type>multicave</frm_type>
        <foreignkey>ctl_areas</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>name</fk_show_field>
    </field>
    <field>
        <name>geologicalformation</name>
        <type>string</type>
        <frm_it>Formazione geologica</frm_it>
        <foreignkey>ctl_geologicalformations</foreignkey>
        <fk_link_field>code</fk_link_field>
        <fk_show_field>code,name</fk_show_field>
    </field>
    <field>
        <name>age</name>
        <type>string</type>
    </field>

    <field>
        <name>lithology</name>
        <type>string</type>
        <frm_it>Litologia</frm_it>
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
        <frm_help_it>somma delle lunghezze di tutte le gallerie,calcolate in pianta,
            cioè proiettate su un piano orizzontale</frm_help_it>
        <frm_suffix> m</frm_suffix>
        <frm_size>8</frm_size>
    </field>
    <field>
        <name>lenght_extension</name>
        <type>string</type>
        <frm_type>os_lenght</frm_type>
        <frm_help_it>Per estensione si intende la massima lunghezza planimetrica della cavità,non
            necessariamente a partire dall'ingresso</frm_help_it>
        <frm_it>Estensione</frm_it>
        <frm_suffix> m</frm_suffix>
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
        <frm_help_it>Inserisci qui la longitude. Se questa &grave; riferita a Monte Mario non dimenticarti di specificarlo,ad esempio inserendo 2&#176;12'11,5'' W di M.Mario</frm_help_it>
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
        <frm_i18n>original coordinates type</frm_i18n>
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
        <name>location_evaluation_options</name>
        <type>string</type>
        <frm_type>stringselect</frm_type>
        <foreignkey>ctl_caves</foreignkey>
        <fk_link_field>location_evaluation_options</fk_link_field>
        <fk_show_field>location_evaluation_options</fk_show_field>         
    </field>
    <field>
        <name>location_evaluation</name>
        <type>string</type>
        <frm_it>Valutazione dato</frm_it>
        <frm_help>descrivere l'affidabilit&agrave; del dato inserito,per esempio coordinate GPS del 12 febbraio 2011 con precisione 4m,oppure dato preso sui carta,oppure dato preso a occhio sulla carta</frm_help>
        <frm_size>60</frm_size>
        <frm_endgroup>entrance location</frm_endgroup>
        <view_endgroup>entrance location</view_endgroup>
    </field>
    <field>
        <name>archeological</name>
        <frm_group_i18n>references to special censuses</frm_group_i18n>
        <frm_it>Cavit&agrave; archeologica</frm_it>
        <type>radio</type>
        <frm_options>Y,N</frm_options>
        <frm_options_i18n>yes,no</frm_options_i18n>
    </field>
    <field>
        <name>marine</name>
        <frm_it>Cavit&agrave; marina</frm_it>
        <type>radio</type>
        <frm_options>Y,N</frm_options>
        <frm_options_i18n>yes,no</frm_options_i18n>
    </field>
    <field>
        <name>lake</name>
        <frm_it>Cavit&agrave; lacustre</frm_it>
        <type>radio</type>
        <frm_options>Y,N</frm_options>
        <frm_options_i18n>yes,no</frm_options_i18n>
    </field>
    <field>
        <name>environmentalrisk</name>
        <frm_it>Rischio ambientale</frm_it>
        <type>radio</type>
        <frm_options>Y,N</frm_options>
        <frm_options_i18n>yes,no</frm_options_i18n>
    </field>
    <field>
        <name>tourist</name>
        <frm_it>Grotta turistica</frm_it>
        <type>radio</type>
        <frm_options>Y,</frm_options>
        <frm_options_i18n>yes,no</frm_options_i18n>
    </field>
    <field>
        <name>closed</name>
        <frm_it>Cavit&agrave; chiusa</frm_it>
        <type>radio</type>
        <frm_options>Y,</frm_options>
        <frm_options_i18n>yes,no</frm_options_i18n>
    </field>
    <field>
        <name>closed_notes</name>
        <frm_it>Note accesso alla grotta</frm_it>
        <type>text</type>
    </field>
    <field>
        <name>destroyed</name>
        <frm_it>Cavit&agrave; distrutta</frm_it>
        <type>radio</type>
        <frm_options>Y,,P</frm_options>
        <frm_options_it>Si,No,Parzialmente</frm_options_it>
        <frm_endgroup>references to special censuses</frm_endgroup>
    </field>
    <field>
        <name>check</name>
        <frm_it>Posizione verificata sul campo da curatore</frm_it>
        <frm_help>Attenzione !! Il campo deve essere compilato dopo che la grotta è stata ritrovata successivamente la messa a catasto</frm_help>
        <frm_type>check</frm_type>
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
        <name>hydrology</name>
        <frm_it>Idrologia</frm_it>
        <type>string</type>
        <frm_type>multicheck</frm_type>
        <frm_options>temporary flooding,absorbent,emitting,permanent absorbent,temporary absorbent,permanent issuing,temporary issuing,lakes,permanent lakes,temporary lakes,slight flows,dry,siphons,permanent siphons,temporary siphons,only dripping,torrents,permanent torrents,temporary torrents,temporary ice,permanent ice,snow wells</frm_options>
        <frm_options_it>allagamenti temporanei,assorbente,emittente,assorbente permanente,assorbente temporanea,emittente permanente,emittente temporanea,laghi,laghi permanenti,laghi temporanei,lievi scorrimenti,secca,sifoni,sifoni permanenti,sifoni temporanei,solo stillicidio,torrenti,torrenti permanenti,torrenti temporanei,ghiaccio temporaneo,ghiaccio permanente,pozzi a neve</frm_options_it>
    </field>
    <field>
        <name>trend</name>
        <frm_it>Andamento</frm_it>
        <frm_help_it>es. Orizzontale,in leggera discesa,verticale</frm_help_it>
        <type>uppercase</type>
    </field>
    <field>
        <name>practicability</name>
        <frm_it>Percorribilit&agrave;</frm_it>
        <frm_help_it>es. Facile</frm_help_it>
        <type>uppercase</type>
    </field>
    <field>
        <name>practicability_text</name>
        <type>text</type>>
        <frm_it>Note Percorribilit&agrave;</frm_it>
    </field>
    
    <field>
        <name>associations</name>
        <frm_it>Gruppi</frm_it>
        <frm_help>Gruppi o persone che hanno effettuato attivit&agrave; rilevanti o esplorative sulla cacit&agrave;</frm_help>
        <type>text</type>
        <frm_type>multiselect</frm_type>

        <frm_cols>80</frm_cols>
        <foreignkey>ctl_associations</foreignkey>
        <fk_link_field>name</fk_link_field>
        <fk_show_field>acronym,name</fk_show_field>
    </field>
    <field>
        <name>DATAGG</name>
        <frm_it>Data agg. catastale</frm_it>
        <frm_help>Data dell'ultimo aggiornamento catastale ufficiale convenzionato</frm_help>
        <type>string</type>
        <type>datetime</type>
        <frm_dateformat>y-mm-dd</frm_dateformat>
        <view_dateformat>dd/mm/y</view_dateformat>
    </field>      
    <field>
        <name>chronology</name>
        <frm_it>Cronologia catastale</frm_it>
        <frm_help>Cronologia delle versioni,esempio:
            1990 - Mario Rossi - primo accatastatore
            2002 - Paolo Bianchi - aggiornamento per nuovo ramo esplorato,aggiunta descrizione
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
        <name>wells</name>
        <frm_it>Sequenza pozzi</frm_it>
        <frm_help_it>Es. 10,5,50,20</frm_help_it>
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
        <name>meteorology</name>
        <frm_it>Meteorologia</frm_it>
        <frm_type>multicheck</frm_type>
        <frm_options>blow_during_heat,blow_during_cold,suck_during_heat,suck_during_cold,none,blow_always,suck_always,none_in_heat,none_in_cold</frm_options>
        <frm_options_it>soffia con il caldo,soffia con il freddo,aspira con il caldo,aspira con il freddo,nessuna circolazione,soffia sempre,aspira sempre,nessuna circolazione in estate,nessuna circolazione in inverno</frm_options_it>
        <type>string</type>
        <frm_show>1</frm_show>
    </field>
    <field>
        <name>meteorology_text</name>
        <frm_it>Meteorologia</frm_it>
        <type>text</type>
        <frm_show>1</frm_show>
        <frm_help_it>Descrizione delle circolazioni d'aria e di acqua della grotta</frm_help_it>
        <frm_rows>auto</frm_rows>
        <frm_cols>80</frm_cols>
    </field>     
    <field>
        <name>geology</name>
        <frm_it>Geologia</frm_it>
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
    <innertable>
        <tablename>ctl_surveys</tablename>
        <frm_i18n>surveys</frm_i18n>
        <frm_it>Rilievi cavit&agrave;</frm_it>
        <linkfield>code,codecave</linkfield>
        <innertablefields>id,name,photo1,filekml,date,author</innertablefields>
        <view></view>
    </innertable>
    <innertable>
        <tablename>ctl_photos</tablename>
        <frm_it>Foto della cavit&agrave;</frm_it>
        <linkfield>code,codecave</linkfield>
        <innertablefields>id,name,photo</innertablefields>
        <view></view>
    </innertable>
    <innertable>
        <tablename>ctl_faunacave</tablename>
        <frm_it>Rilevamenti faunistici</frm_it>
        <linkfield>code,codecave</linkfield>
        <innertablefields>name,photo1,date</innertablefields>
        <view></view>
    </innertable>
    <innertable>
        <tablename>ctl_attachments</tablename>
        <frm_it>Allegati</frm_it>
        <linkfield>code,codecave</linkfield>
        <view></view>
    </innertable>
    <field>
        <name>changelog</name>
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
        <name>authorfauna</name>
        <frm_it>Autori testi Fauna</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>authorhistory</name>
        <frm_it>Autori testi Storia</frm_it>
        <view_hiddentitle>1</view_hiddentitle>
    </field>
    <field>
        <name>import_rawdata</name>
        <frm_it>Dati pre importazione</frm_it>
        <type>text</type>
    </field>
    <field>
        <name>recordinsert</name>
        <view_group>data</view_group>
        <view_group_i18n>Dati compilazione</view_group_i18n>
        <frm_it>Data importazione</frm_it>
        <type>datetime</type>
        <view_dateformat>dd/mm/y</view_dateformat>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
    </field>
    <field>
        <name>username</name>
        <type>string</type>
        <frm_it>Utente che ha inserito i dati</frm_it>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
    </field>
    <field>
        <name>userupdate</name>
        <type>varchar</type>
        <frm_it>Utente che ha aggiornato i dati</frm_it>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
    </field>
    <field>
        <name>recordupdate</name>
        <type>datetime</type>
        <view_dateformat>dd/mm/y</view_dateformat>
        <frm_it>Ultima modifica dei dati</frm_it>
        <frm_show>0</frm_show>
        <view_show>1</view_show>
        <view_endgroup>data</view_endgroup>
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
        <name>recorddeleted</name>
        <type>bool</type>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>to_check</name>
        <default>1</default>
        <frm_default>y</frm_default>
        <type>varchar</type>
        <frm_type>check</frm_type>
        <frm_show>1</frm_show>
        <frm_i18n>data to be verified</frm_i18n>
        <frm_options>Y,N</frm_options>
        <frm_options_i18n>yes,no</frm_options_i18n>
    </field>    
    <driver>mysql</driver>
</tables>