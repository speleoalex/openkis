/**
 * Finis ajax funcions
 * @package Finis
 * @autor Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
var ajaxOk = true;
var scripts = document.getElementsByTagName('script');
var fn_to_ajaxFilesPath = "";
for (var i in scripts)
{
    if (scripts[i].src != undefined && (scripts[i].src.search(/fn_to_ajax.js$/)>=0))
    {
       fn_to_ajaxFilesPath  = scripts[i].src.replace(/fn_to_ajax.js$/,'');
       break;
    }
}
//alert (fn_to_ajaxFilesPath);
/**
 * Example:
 * <a href=\"index.php?mod=pippo\" onclick="return fn_to_ajax(this,'section');">sample link</a>
 */
function fn_to_ajax(a,div)
{
    //scommentare per bypassare
    //return true;
    var url = a.href;
    var xsreq;
    if (ajaxOk)
    {
        ajaxOk = false;
        fn_loading(div);
    }
    else
    {
        //alert ("redirect");
        window.location = url;
        return false;
    }
    if (window.XMLHttpRequest) {
        xsreq = new XMLHttpRequest();
    }
    else if (window.ActiveXObject) {
        xsreq = new ActiveXObject("Microsoft.XMLHTTP");
    }
    if (xsreq) {

        xsreq.onreadystatechange = function() {
            try{
                fn_ajDone(url, div,xsreq);
            }catch(e){
                //alert(e);
                window.location = url;
            }
        };
        xsreq.open("POST", url, true);
        xsreq.send("fn_to_ajax_href="+a.href+"&fn_to_ajax_div="+div);
    }
    return false;
}
/**
 * sample: <button onclick="fn_call_ajax_silent('index.php','section')" >pippo</button>
 */
function fn_call_ajax_silent(url,div,method,params,callback)
{
    method = (typeof(method) !=='undefined' && method!==false) ? method : "post";    
    params = (typeof(params) !=='undefined' && params!==false) ? params : false;    
    return fn_call_ajax(url,div,true,method,params,callback);
}

/**
 *
 */
function fn_ajDone(url, divs, xsreq, callback)
{
	
    if (xsreq.readyState == 4)
    {
        var listdivs = divs.split(',');
        var div;
        var i;
        var output;
        var el;
        var nodes;
        for (var idiv in listdivs)
        {
			
            div =  listdivs[idiv];
            output = xsreq.responseText;
            el = document.createElement("div");
            el.innerHTML = output;
            nodes = el.getElementsByTagName('div');
            for(i=0;i<nodes.length; i++) {
                if (nodes[i].id == div)
                {
                    document.getElementById(div).innerHTML = nodes[i].innerHTML;
                    fn_execJS (document.getElementById(div));
                }
            }
            nodes = el.getElementsByTagName('span');
            for(i=0;i<nodes.length; i++)
            {
                if (nodes[i].id == div)
                {
                    document.getElementById(div).innerHTML = nodes[i].innerHTML;
                    fn_execJS (document.getElementById(div));
                }
            }
            nodes = el.getElementsByTagName('td');
            for(i=0;i<nodes.length; i++)
            {
                if (nodes[i].id == div)
                {
                    document.getElementById(div).innerHTML = nodes[i].innerHTML;
                    fn_execJS (document.getElementById(div));
                }
            }
            nodes = el.getElementsByTagName('tr');
            for(i=0;i<nodes.length; i++)
            {
                if (nodes[i].id == div)
                {
                    var eltr = document.getElementById(div);
                    eltr.innerHTML = nodes[i].innerHTML;
                    fn_execJS (document.getElementById(div));
                }
            }            
        }
        try{
            if(document.getElementById("fnajloading"))
            {
                var c = document.getElementById("fnajloading");
                document.getElementById("fnajloading").parentNode.removeChild(c);
            }
        }
        catch(e){
            alert(e);
        }
        ajaxOk = true;
        callback();
    }
}
/**
 * 
 */
function fn_getScrollY() {
    var scrOfX = 0, scrOfY = 0;
    if( typeof( window.pageYOffset ) == 'number' ) {
        //Netscape compliant
        scrOfY = window.pageYOffset;
        scrOfX = window.pageXOffset;
    } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
        //DOM compliant
        scrOfY = document.body.scrollTop;
        scrOfX = document.body.scrollLeft;
    } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
        //IE6 standards compliant mode
        scrOfY = document.documentElement.scrollTop;
        scrOfX = document.documentElement.scrollLeft;
    }
    return scrOfY;
//return [ scrOfX, scrOfY ];
}
/**
 * 
 */
function fn_getScrollX() {
    var scrOfX = 0, scrOfY = 0;
    if( typeof( window.pageYOffset ) == 'number' ) {
        //Netscape compliant
        scrOfY = window.pageYOffset;
        scrOfX = window.pageXOffset;
    } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
        //DOM compliant
        scrOfY = document.body.scrollTop;
        scrOfX = document.body.scrollLeft;
    } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
        //IE6 standards compliant mode
        scrOfY = document.documentElement.scrollTop;
        scrOfX = document.documentElement.scrollLeft;
    }
    return scrOfX;
}
/**
 * 
 */
function fn_loading(_div)
{
    var div;
    div = document.createElement('div');
    div.setAttribute('id', 'fnajloading');
    div.innerHTML='loading...';
    oHeight = document.getElementsByTagName('body')[0].clientHeight + fn_getScrollY();
    oWidth = document.getElementsByTagName('body')[0].clientWidth + fn_getScrollX();
    oHeight=oHeight+"px";
    oWidth=oWidth+"px";
    try{
        div.style.backgroundColor='#000000';
        div.style.color='#ffffff';
        div.style.display='block';
        div.style.zIndex=999;
        div.style.position='absolute';
        div.style.width=oWidth;
        div.style.height = oHeight;
        div.style.top='0px';
        div.style.left='0px';
        div.style.textAlign='center';
        div.style.opacity='0.5';
        div.style.filter='alpha(opacity=50)';
        div.style.overflow='hidden';
        div.style.transition = 'opacity 0.5s ease-in-out';
    }
    catch(e)
    {
		
    }
    div.innerHTML = "<div id=\"fnajloading\" style=\"z-index:999;color:#ffffff;margin-top:"+fn_getScrollY()+"px\" ><br />Loading...<br /><br /><img  src='"+fn_to_ajaxFilesPath+"../../images/loading.gif' /><br /><br /></div>";
    var listdivs = _div.split(',');
    try{
        if (!document.getElementById("fnajloading"))
        {
            document.getElementsByTagName('body')[0].appendChild(div);
        }
	
    }catch(e){}
}
/**
 * 
 */
function fn_execJS (node) {

    var st = node.getElementsByTagName('SCRIPT');
    var strExec;

    var bSaf = (navigator.userAgent.indexOf('Safari') != -1);
    var bOpera = (navigator.userAgent.indexOf('Opera') != -1);
    var bMoz = (navigator.appName == 'Netscape');

    for(var i=0;i<st.length; i++) {
        if (bSaf) {
            strExec = st[i].innerHTML;
        }
        else if (bOpera) {
            strExec = st[i].text;
        }
        else if (bMoz) {
            strExec = st[i].textContent;
        }
        else {
            strExec = st[i].text;
        }
        try {
            eval(strExec);
        } catch(e) {
        //alert(e);
        }
    }

}


/**
 * Function to handle form submission via AJAX
 */
function fn_FormToAjax(formElement, divToUpdate, method, makeJson, action, callback, loadingCallback) {
    method = (typeof(method) !== 'undefined' && method !== false) ? method : formElement.getAttribute("method");
    makeJson = (typeof(makeJson) !== 'undefined' && makeJson !== false) ? makeJson : false;
    loadingCallback = (typeof(loadingCallback) !== 'undefined' && loadingCallback !== false) ? loadingCallback : false;
    var url = (typeof(action) !== 'undefined' && action !== false) ? action : formElement.action;
   
    divToUpdate = (typeof(divToUpdate) !== 'undefined' && divToUpdate !== false) ? divToUpdate : "";
    callback = (typeof(callback) !== 'undefined' && callback !== false) ? callback : function() {};
    
    var params;
    if (makeJson) {
        params = fn_MakegetString(formElement, makeJson);
    } else {
        // Use FormData for file uploads
        params = new FormData(formElement);
    }
    
    fn_call_ajax(url, divToUpdate, loadingCallback, method, params, callback);
    return false;
}

/**
 * Function to perform AJAX call
 */
function fn_call_ajax(url, div, silent, method, params, callback) {
    method = (typeof(method) !== 'undefined' && method !== false) ? method : "post";
    method = method.toUpperCase();
    
    silent = (typeof(silent) !== 'undefined' && silent !== false) ? silent : false;
    callback = (typeof(callback) !== 'undefined' && callback !== false) ? callback : function() {};
    
    var xsreq;

    if (silent === false)
        fn_loading(div);

    if (typeof(silent) == "function") {
        silent();
    }

    if (window.XMLHttpRequest) {
        xsreq = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        xsreq = new ActiveXObject("Microsoft.XMLHTTP");
    }
    if (xsreq) {
        xsreq.onreadystatechange = function() {
            try {
                fn_ajDone(url, div, xsreq, callback);
            } catch(e) {
                window.location = url;
            }
        };
        
        xsreq.open(method, url, true);
        
        if (method == "POST" && !(params instanceof FormData)) {
            xsreq.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        }
        
        xsreq.send(params);
    }
    return false;
}




/**
 * 
 */
function _fn_FormToAjax(formElement,divToUpdate,method,makeJson,action,callback,loadingCallback)
{
    
    method = (typeof(method) !=='undefined' && method !==false) ? method : formElement.getAttribute("method");
    makeJson = (typeof(makeJson) !=='undefined' && makeJson !==false) ? makeJson : false;
    loadingCallback = (typeof(loadingCallback) !=='undefined' && loadingCallback !==false) ? loadingCallback : false;
    var url = (typeof(action) !=='undefined' && action !==false) ? action : formElement.action;
   
    divToUpdate = (typeof(divToUpdate) !=='undefined' && divToUpdate !==false ) ? divToUpdate : "";
    var params = fn_MakegetString(formElement,makeJson);
    callback = (typeof(callback) !== 'undefined' && callback !==false) ? callback : function() {};
    
    if (method == 'get')
    {
        if (url.toString().search(/\?/i) != false)
        {
            url = url+'&'+params;
        }
        else
        {
            url = url+'?'+params;
        }
    }
    fn_call_ajax(url,divToUpdate,loadingCallback,method,params,callback);
    return false;
}
/**
 *
 */
function _fn_call_ajax(url,div,silent,method,params,callback)
{
    method = (typeof(method) !=='undefined' && method!==false) ? method : "post";
    method = method.toUpperCase();
    
    silent = (typeof(silent) !=='undefined' && silent!==false) ? silent : false;
    params = (typeof(params) !=='undefined' && params!==false) ? params : false;
    callback = (typeof(callback) !== 'undefined' && callback!==false) ? callback : function() {};
    
    //alert("url="+url+"\nparams="+params);
    var xsreq;



    if (silent===false)
        fn_loading(div);

    if (typeof(silent)=="function")
    {
        silent();
    }


    if (window.XMLHttpRequest) {
        xsreq = new XMLHttpRequest();
    }
    else if (window.ActiveXObject) {
        xsreq = new ActiveXObject("Microsoft.XMLHTTP");
    }
    if (xsreq) {
        xsreq.onreadystatechange = function() {
            try{
                fn_ajDone(url, div, xsreq, callback);
            }catch(e){
                //alert(e);
                window.location = url;
            }
        };
        
        xsreq.open(method, url, true);
        
        if (method == "POST" )
        {
            xsreq.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xsreq.send(params);
        }
        else
            xsreq.send(params);
    }
    return false;
}
/**
 * 
 */
function fn_MakegetString(formElement,makeJson)
{
    makeJson = (typeof(makeJson) !=='undefined') ? makeJson : false;
    var string="";
    //compongo i dati da inviare
    var i=0;
    var sep = "";
    var json = "{";
    var jsep = "";
    var key = "";
    var value = "";
    var jvalue = "";
    var isChecked = true;
    //------inputs----------
    while(formElement.getElementsByTagName('input').item(i)!=null)
    {
        if(formElement.getElementsByTagName('input').item(i).getAttribute('name')!=null)
        {
            var type = formElement.getElementsByTagName('input').item(i).type.toString().toLowerCase();
            isChecked = true;
            if (  type == "checkbox" || type == "radio")
            {
                if (!formElement.getElementsByTagName('input').item(i).checked)
                {
                    isChecked = false;
                }
            }
            if (isChecked)
            {
                key = formElement.getElementsByTagName('input').item(i).getAttribute('name');
                value = formElement.getElementsByTagName('input').item(i).value;
                string +=sep+key+"="+value;
                sep = "&";
                json+=jsep+"\""+key+"\":\""+value.replace(/\\/g,"\\\\").replace(/"/g,"\\\"")+"\"";
                jsep = ",";
            }
        }       
        i++;
    }
    i=0;
    //------textareas----------
    while(formElement.getElementsByTagName('textarea').item(i)!=null)
    {
        if(formElement.getElementsByTagName('textarea').item(i).getAttribute('name')!=null)
        {
            key = formElement.getElementsByTagName('textarea').item(i).getAttribute('name')
            value = formElement.getElementsByTagName('textarea').item(i).value;
            string +=sep+key+"="+value;
            sep = "&";
            json+=jsep+"\""+key+"\":\""+value.replace(/\\/g,"\\\\").replace(/"/g,"\\\"")+"\"";
            jsep = ",";
        }
        i++;
    }
    //------combobox----------
    i=0;
    while(formElement.getElementsByTagName('select').item(i)!=null)
    {
        if(formElement.getElementsByTagName('select').item(i).getAttribute('name') != null)
        {
            key = formElement.getElementsByTagName('select').item(i).getAttribute('name');
            if(formElement.getElementsByTagName('select').item(i).selectedIndex>=0)
            {
                value = formElement.getElementsByTagName('select').item(i).options[formElement.getElementsByTagName('select').item(i).selectedIndex].value;
            }
            else
            {
                value = formElement.getElementsByTagName('select').item(i).value;
            }
            string +=sep+key+"="+value;
            sep = "&";
            json+=jsep+"\""+key+"\":\""+value.replace(/\\/g,"\\\\").replace(/"/g,"\\\"")+"\"";
            jsep = ",";
        }
        i++;
    }
    if (makeJson)
        string=json+"}";
    return string;
}