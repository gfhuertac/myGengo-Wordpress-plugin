<?php
/*  Copyright 2010  Gonzalo Huerta-Canepa  (email : gonzalo@huerta.cl)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/      
?>
<?
/**
 *
 * PHP script that contains all the AJAX function used by the myGengo
 * plugin.
 *
 * @package myGengo
 */
?>
<?php

require_once(dirname(__FILE__) . '/mygengo-common.php');

$mg_keys = mygengo_getKeys();

$table_name1 = $wpdb->prefix . "gengo_languages";
$table_name2 = $wpdb->prefix . "gengo_pairs";
$table_name3 = $wpdb->prefix . "gengo_jobs";
$table_name4 = $wpdb->prefix . "gengo_comments";
$table_name5 = $wpdb->prefix . "gengo_sources";

$action      = isset($_REQUEST['action'])?$_REQUEST['action']:'order';
$post_id     = isset($_REQUEST['post_id'])?$_REQUEST['post_id']:0;
$lang_src_id = isset($_REQUEST['lang_src_id'])?$_REQUEST['lang_src_id']:0;
$lang_tgt_id = isset($_REQUEST['lang_tgt_id'])?$_REQUEST['lang_tgt_id']:0;
$tier        = isset($_REQUEST['tier'])?$_REQUEST['tier']:0;
$format      = isset($_REQUEST['format'])?$_REQUEST['format']:'json';

$mg_primary_language = mygengo_get_primarylanguage();

if ($action == 'unit_type') {
	$selected_code = $lang_src_id;
	echo $wpdb->get_var("SELECT language_unit_type FROM ".$table_name1." WHERE language_code = '".$selected_code."'");
} elseif ($action == 'target_langs') {
	$selected_code = $lang_src_id;
	if ($format == 'options') {
		echo mygengo_generate_select_from_sqlquery("SELECT DISTINCT l.language_code, l.language_name FROM ".$table_name1." l, ".$table_name2." p WHERE p.pair_source = '".$selected_code."' AND p.pair_target = l.language_code ORDER BY language_name, language_code", "language_code", "language_name", -1, '<option value="">[Select]</option>');	
	} else {
		echo mygengo_generate_json_from_sqlquery("SELECT DISTINCT l.language_code, l.language_name FROM ".$table_name1." l, ".$table_name2." p WHERE p.pair_source = '".$selected_code."' AND p.pair_target = l.language_code ORDER BY language_name, language_code");
	}
} elseif ($action == 'estimate') {
	$source_code = $lang_src_id;
	$target_code = $lang_tgt_id;
	$tier        = isset($_REQUEST['tier'])?$_REQUEST['tier']:'machine';
	$unit_count  = isset($_REQUEST['unit_count'])?$_REQUEST['unit_count']:0;
	$format      = isset($_REQUEST['format'])?$_REQUEST['post_id']:'text';
	$url = 'http://mygengo.com/translate/order/ajax_get_time_estimate/'. $source_code .'/'. $target_code .'/'. $tier .'/'. $unit_count;
	$data = load($url);
	if ($format == 'json') {
		echo $data;
	} else {
		$jsonobject = json_decode(load($url));
		$jsonerror = mygengo_check_error($jsonobject);
		if ($jsonerror) {
			echo 'N/A';
			exit();
		}
		echo ceil($jsonobject->{'msg'}/3600);
	}
} elseif ($action == 'tiers') {
	$source_code   = $lang_src_id;
	$target_code   = $lang_tgt_id;
	$selected_tier = $tier;
	$format      = isset($_REQUEST['format'])?$_REQUEST['post_id']:'options';
	if ($format == 'options') {
		echo mygengo_generate_select_from_sqlquery("SELECT DISTINCT pair_unit_credit, pair_tier FROM ".$table_name2." WHERE pair_source = '".$source_code."' AND pair_target = '".$target_code."' ORDER BY pair_unit_credit, pair_tier", "pair_tier", "pair_tier", $selected_tier, '<option value="">[Select]</option>');
	} else {
		echo mygengo_generate_json_from_sqlquery("SELECT DISTINCT pair_unit_credit, pair_tier FROM ".$table_name2." WHERE pair_source = '".$source_code."' AND pair_target = '".$target_code."' ORDER BY pair_unit_credit, pair_tier");
	}
} elseif ($action == 'jobs') {
        require_once ($mg_plugin_dir . '/php/init.php');

        $jobs    = myGengo_Api::factory('jobs', $mg_keys['api_key'], $mg_keys['private_key']);

	$params = array();
	$params['ts'] = gmdate('U');
	$params['api_key'] = $mg_keys['api_key'];
	$params['count'] = (isset($_REQUEST['mg_count']))?$_REQUEST['mg_count']:100;
	if ($_REQUEST['mg_status'] != '') {
		$params['status'] = $_REQUEST['mg_status'];
	}

	ksort($params);
	$query = http_build_query($params);
	$params['api_sig'] = myGengo_Crypto::sign($query, $mg_keys['private_key']);
	if ($_REQUEST['mg_group_id'] != '') {
		$jobs->getGroupedJobs($_REQUEST['mg_group_id'], 'json', $params);
	} else {
		$jobs->getJobs(null, 'json', $params);
	}
        $json = $jobs->getResponseBody();
        $jsonobject   = json_decode($json);
	$jsonerror = mygengo_check_error($jsonobject);
	if ($jsonerror) {
		echo $json;
		exit();
	}
	if ($_REQUEST['mg_group_id'] != '') {
		$jsonresponse = $jsonobject->{'response'}->{'jobs'};
	} else {
        	$jsonresponse = $jsonobject->{'response'};
	}
	echo json_encode($jsonresponse);
} elseif ($action == 'job') {
	$job_id     = $_REQUEST['job_id'];
	if (!get_transient('mygengo_job_id_' . $job_id)) {
		echo mygengo_refresh_job($job_id);
	}

	echo mygengo_generate_json_from_sqlquery("SELECT *,(SELECT language_name FROM ".$table_name1." WHERE language_code = job_lc_src) AS lang_src, (SELECT language_name FROM ".$table_name1." WHERE language_code = job_lc_tgt) AS lang_tgt FROM ".$table_name3." WHERE job_id = ".$job_id."");
	exit();
} elseif ($action == 'comments') {
	$job_id     = $_REQUEST['job_id'];
	require_once ($mg_plugin_dir . '/php/init.php');

	$job_client = myGengo_Api::factory('job', $mg_keys['api_key'], $mg_keys['private_key']);

	$params = array();
	$params['ts'] = gmdate('U');
	$params['api_key'] = $mg_keys['api_key'];
	ksort($params);
	$query = http_build_query($params);
	$params['api_sig'] = myGengo_Crypto::sign($query, $mg_keys['private_key']);

	$job_client->getComments($job_id, 'json', $params);
	$json         = $job_client->getResponseBody();
	$jsonobject   = json_decode($json);
	$jsonerror = mygengo_check_error($jsonobject);
	if ($jsonerror) {
		echo $json;
		exit();
	}
	$jsonresponse = $jsonobject->{'response'};
	$jsonjob      = $jsonresponse->{'thread'};

	$count  = 0;
	$unread = 0;
	foreach($jsonjob as $comment) {
		$comment_job_id = $job_id;
		$comment_body   = $comment->{'body'};
		$comment_author = $comment->{'author'};
		$comment_status = 'read';
		$comment_ctime  = $comment->{'ctime'};

		$exists = $wpdb->get_var("SELECT COUNT(*) FROM ".$table_name4." WHERE comment_job_id = ".$comment_job_id." AND comment_ctime = ".$comment_ctime);
		if (!$exists) {
			$sql = "INSERT INTO ".$table_name4." (comment_job_id, comment_body, comment_author, comment_status, comment_ctime) VALUES (".$comment_job_id.",'".addslashes($comment_body)."','".$comment_author."', 'unread', ".$comment_ctime.")";
			$wpdb->query($sql); 
			$unread++;
		}
		$count++;
	}

	echo json_encode(array ('job_id'=>$job_id,'comments'=>$count, 'unread'=>$unread));
	exit();
} elseif ($action == 'balance') {
        require_once ($mg_plugin_dir . '/php/init.php');
	$type = intval($_REQUEST['balance_type']);
	$mg_keys = mygengo_getKeys($type);
        $account = myGengo_Api::factory('account', $mg_keys['api_key'], $mg_keys['private_key']);

        $params = array();
        $params['ts'] = gmdate('U');
        $params['api_key'] = $mg_keys['api_key'];
        ksort($params);
        $query = http_build_query($params);
        $params['api_sig'] = myGengo_Crypto::sign($query, $mg_keys['private_key']);

	$account->getBalance('json', $params);
        $json = $account->getResponseBody();
        $jsonobject   = json_decode($json);
	$jsonerror = mygengo_check_error($jsonobject);
	if ($jsonerror) {
		wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
	}
        $jsonresponse = $jsonobject->{'response'};
	$mg_credits   = $jsonresponse->{'credits'};

	$account->getStats('json', $params);
        $json = $account->getResponseBody();
        $jsonobject       = json_decode($json);
	$jsonerror = mygengo_check_error($jsonobject);
	if ($jsonerror) {
		wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
	}
        $jsonresponse     = $jsonobject->{'response'};
	$mg_credits_spent = $jsonresponse->{'credits_spent'};
	$mg_user_since    = date("d-m-Y H:i",$jsonresponse->{'user_since'});

	echo json_encode(array('credits' => $mg_credits, 'credits_spent' => $mg_credits_spent, 'user_since' => $mg_user_since));
	exit();
}
?>
