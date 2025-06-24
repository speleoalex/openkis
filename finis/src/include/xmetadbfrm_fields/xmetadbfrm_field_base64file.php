<?php
class xmetadbfrm_field_base64file
{

    function __construct() {}

    function show($params)
    {
        $name = $params['name'];
        $html = "<script>
                handleFileUpload$name = function (input) {
    const file{$name} = input.files[0];
    if (file{$name}) {
        // Check if the file is an image
        if (file{$name}.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const base64 = e.target.result;
                uploadFile{$name} = base64;
                uploadFilename{$name} = input.value.split(\"\\\\\").pop().split('/').pop();
                console.log(e);
                document.getElementById('fileuploadthumb$name').innerHTML = `<img style='max-height:60px;max-width:40px' src='\${base64}' />`;
                document.getElementById('base64_fileuploadname$name').value = e.target.result;                
            };
            reader.readAsDataURL(file{$name});
        } else {
            const reader = new FileReader();
            reader.onload = function (e) {
                const base64 = e.target.result;
                uploadFile{$name} = base64;
                uploadFilename{$name} = input.value.split(\"\\\\\").pop().split('\\/').pop();
                console.log(e);
                document.getElementById('fileuploadthumb$name').innerHTML = `\${uploadFilename{$name}}`;
                document.getElementById('base64_fileuploadname$name').value = e.target.result;
            };
            reader.readAsDataURL(file{$name});
            

            
        }
        document.getElementById('fileuploadDelete$name').style = \"\";
    } else {

    }
}

handleFileUploadDelete$name = function () {
        // Reset if no file is selected
        document.getElementById('fileuploadthumb$name').innerHTML = '&#x1F4CE;';
        document.getElementById('fileUpload$name').value = '';
        document.getElementById('base64_fileuploadname$name').value = '';    
        uploadFile{$name} = null;
        uploadFilename{$name} = '';
        document.getElementById('fileuploadDelete$name').style = \"display:none\";
    }    
</script>";
        $toltips = "";
        $size = isset($params['frm_size']) ? $params['frm_size'] : 20;
        $oldvalues = $params['oldvalues'];
        $tablepath = $params['fieldform']->xmltable->FindFolderTable($oldvalues);
        $oldval = $params['value'];
        $primarykey = $params['oldvalues_primarikey'];
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        //die($attributes);
        $required = "";
        $style = "";
        if ($oldval == "") {
            $style = "display:none";
            $required = (isset($params['frm_required']) && $params['frm_required'] == 1) ? "required=\"required\"" : "";
        }
        $strdelete = "<a style=\"$style\" id=\"fileuploadDelete$name\" href=\"#\" onclick=\"handleFileUploadDelete$name();return false;\">Delete</a>";
        $strInput = "&#x1F4CE;";
        if (substr($oldval, 0, strlen("data:image/")) === "data:image/") {
            $strInput = "<img style='max-height:60px;max-width:40px' src='{$oldval}' />";
        }
        $html .= "<input value=\"$oldval\" id =\"base64_fileuploadname$name\" $required type=\"hidden\" name=\"" . $params['name'] . "\" type=\"text\" />";
        $html .= "<input style=\"display:none\" id=\"fileUpload$name\" onchange=\"handleFileUpload$name(this);\" $required $attributes $toltips size=\"$size\" type=\"file\" />\n";
        $html .= "<a id =\"fileuploadthumb$name\" href=\"#\"  onclick=\"document.getElementById('fileUpload$name').click();return false\" >$strInput</a>$strdelete<br />";
        return $html;
    }

    function view($params)
    {
        $htmlout = "";
        $value = $params['value'];
        $decoded = @base64_decode($value);
        if ($decoded)
            $value = $decoded;
        $id = md5($value);
        if (isset($_GET[$id])) {
            FN_SaveFile($value, "filecontents");
        }
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $sep = "&";
        if (false === strstr($_SERVER['REQUEST_URI'], "?")) {
            $sep = "?";
        }
        $bytes = strlen($value);
        if ($bytes)
        {
            $htmlout .= "\n<a $attributes title=\"Download\" href=\"{$_SERVER['REQUEST_URI']}$sep$id\"  >Download</a>";
        }
        return $htmlout;
    }
    function gridview($params)
    {
        return $this->view($params);
    }
}
