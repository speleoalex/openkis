<?php exit(0);
/*
 * Caves and artificial cavities named by each bibliography item, one row per
 * code: the lookup table of the bibliography box on the cave and artificial
 * cavity pages (extra/openkis_bibliography.inc.php). Rebuilt by OpenKIS from
 * ctl_bibliography.codecaves / codeartificials whenever the bibliography
 * changes: never edit it by hand. The row with an empty linkfield records
 * which version of the bibliography it was built from.
 */
?>
<tables>
    <field>
        <name>id</name>
        <type>int</type>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
    </field>
    <field>
        <name>bibliography_id</name>
        <type>string</type>
    </field>
    <field>
        <name>linkfield</name>
        <type>string</type>
    </field>
    <field>
        <name>code</name>
        <type>string</type>
    </field>
    <driver>mysql</driver>
    <sqltable>ctl_bibliography_links</sqltable>
</tables>
