<?php
/**
 * classe  XMETAField
 * classe che descrive un singolo field della tabella
 */
//#[AllowDynamicProperties]
class XMETAField extends stdClass
{
    var $name = null;
    var $extra = null;
    var $primarykey = null;
    var $frm_required = null;
    var $frm_show = null;

    var $size = null;
    var $title = null;
    var $readonly = null;
    var $foreignkey = null; //foreignkey
    var $_defaultvalue;
    var $type = null;
    var $proprieties = null;
    function __construct($descriptionfile, $fieldname)
    {
        $this->proprieties = array();
        //---proprieta' relative al database
        $this->proprieties['type']= "varchar";

        $this->type = "varchar";
        $this->name = "";
        $this->extra = "";
        $this->primarykey = "";
        $this->size = "";
        if (!is_array($descriptionfile))
            $obj = xmetadb_readDatabase($descriptionfile, "field");
        else
            $obj = $descriptionfile;
        $fields = null;
        foreach ($obj as $ob) {
            if (isset($ob['name']) && $ob['name'] == $fieldname) {
                $fields = $ob;
                break;
            }
        }
        if ($fields != null) {
            //$this->proprieties = $fields;
            foreach ($fields as $key => $value) {
                $this->{$key} = $value;
            }
        }
        if ($this->title == null) {
            $this->title = $this->name; // se e' null prende il nome del campo
        }
        if ($this->type == "string") {
            $this->type = "varchar";
        }
        if ($this->type == "varchar" && $this->size == "") {
            $this->size = 255;
        }
    }
}
