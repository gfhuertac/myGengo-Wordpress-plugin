<?php
	require_once(dirname(__FILE__) . '/../../../../wp-load.php');
	require_once(dirname(__FILE__) . '/../mygengo-common.php');
?>
<!--

function getBalance(balance_type, target)
{
	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	} else {// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=target;
	xmlhttp.open("GET","<?php echo $mg_plugin_url; ?>mygengo-ajax.php?action=balance&balance_type="+balance_type,true);
	xmlhttp.send();
}

function getUnitType(lang, target)
{
	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	} else {// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=target;
	xmlhttp.open("GET","<?php echo $mg_plugin_url; ?>mygengo-ajax.php?action=unit_type&lang_src_id="+lang,true);
	xmlhttp.send();
}

function getTargetLanguages(lang, target, format)
{
	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	} else {// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=target;
	if (format == null) {
		format = 'options';
	}
	xmlhttp.open("GET","<?php echo $mg_plugin_url; ?>mygengo-ajax.php?action=target_langs&lang_src_id="+lang+"&format="+format,true);
	xmlhttp.send();
}

function getTiers(langfrom, langto, tier, target, format)
{
        if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp=new XMLHttpRequest();
        } else {// code for IE6, IE5
                xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }

        xmlhttp.onreadystatechange=target; 
	if (format == null) {
		format = 'options';
	}
        xmlhttp.open("GET","<?php echo $mg_plugin_url; ?>mygengo-ajax.php?action=tiers&lang_src_id="+langfrom+"&lang_tgt_id="+langto+"&tier="+tier,true);
        xmlhttp.send(); 
}

function getEstimate(langfrom, langto, tier, unitcount, target, format)
{
        if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp=new XMLHttpRequest();
        } else {// code for IE6, IE5
                xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }

        xmlhttp.onreadystatechange=target;
	if (format == null) {
		format = 'text';
	}
        xmlhttp.open("GET","<?php echo $mg_plugin_url; ?>mygengo-ajax.php?action=estimate&lang_src_id="+langfrom+"&lang_tgt_id="+langto+"&tier="+tier+"&unit_count="+unitcount,true);
        xmlhttp.send(); 
}

function getJobs(target, query, format)
{
        if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp=new XMLHttpRequest();
        } else {// code for IE6, IE5
                xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }

        xmlhttp.onreadystatechange=target;
        if (format == null) {
                format = 'json';
        }
	if (query == null) { 
		query = '';
	}
        xmlhttp.open("GET","<?php echo $mg_plugin_url; ?>mygengo-ajax.php?action=jobs&format="+format+query,true);
        xmlhttp.send(); 
}

function getJob(jobid, target, format)
{
        if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp=new XMLHttpRequest();
        } else {// code for IE6, IE5
                xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }

	xmlhttp.onreadystatechange=target;
        if (format == null) {
                format = 'json';
        }
        xmlhttp.open("GET","<?php echo $mg_plugin_url; ?>mygengo-ajax.php?action=job&job_id="+jobid+"&format="+format,true);
        xmlhttp.send();	
}

function getComments(jobid, target, format)
{
        if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp=new XMLHttpRequest();
        } else {// code for IE6, IE5
                xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }

        xmlhttp.onreadystatechange=target;
        if (format == null) {
                format = 'json';
        }
	xmlhttp.open("GET","<?php echo $mg_plugin_url; ?>mygengo-ajax.php?action=comments&job_id="+jobid+"&format="+format,true);
        xmlhttp.send(); 
}
//-->
