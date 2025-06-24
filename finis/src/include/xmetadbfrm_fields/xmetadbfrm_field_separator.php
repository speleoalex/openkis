<?php
/**
 * fields
 *
 *
 */
class xmetadbfrm_field_separator
{

    function __construct()
    {
        
    }

    function show($params)
    {
        return "$strhiddenfield" . $params['title'];
    }

}

