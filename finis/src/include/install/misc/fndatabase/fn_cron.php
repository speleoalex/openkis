<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0); ?>
<tables>
    <field>
        <name>id</name>
        <type>int</type>
        <primarykey>1</primarykey>
        <extra>autoincrement</extra>
        <frm_show>0</frm_show>
    </field>
    <field>
        <name>operation</name>
        <type>varchar</type>
    </field>
    <field>
        <name>cron_lines</name>
        <frm_i18n>cron</frm_i18n>
        <type>text</type>
        <frm_type>fn_cron_text</frm_type>
    </field>
    <field>
      <name>last_execution</name>
      <frm_i18n>last_execution</frm_i18n>
      <type>datetime</type>
  </field>
</tables>