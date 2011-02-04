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
<?php
/*
Plugin Name: MyGengo Translator
Plugin URI: http://www.mygengo.com
Description: Adds machine and professional translation to WordPress-based blogs
Version: 1.0
Author: Gonzalo Huerta-Canepa
Author URI: http://gonzalo.huerta.cl
License: GPL2
*/
?>
<?
/**
 *
 * myGengo plugin
 *
 * @package myGengo
 */
?>
<?php
	if (!function_exists ('add_action')): 
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
	endif;
?>
<?

require_once(dirname(__FILE__) . '/mygengo-common.php');

/*
Wordpress hooks
*/
//Plugin activation
register_activation_hook  (__FILE__, 'mygengo_activate');
register_deactivation_hook(__FILE__, 'mygengo_deactivate');
add_action('activate_myplugin.php', 'mygengo_install');
//For all sections
add_action('init', 'mygengo_register_javascripts');
add_action('wp_print_scripts', 'mygengo_load_javascripts');
//Administration section
add_action('admin_menu', 'mygengo_add_panel');
add_action('admin_menu', 'mygengo_add_account_panel');
add_action('admin_menu', 'mygengo_add_jobs_panel');
add_action('admin_menu', 'mygengo_add_admin_panel');
add_action('admin_menu', 'mygengo_create_meta_box');
add_action('wp_dashboard_setup', 'mygengo_add_dashboard_widgets' );
//Post-related actions
add_action('save_post',                  'mygengo_save_postdata'); 
add_action('restrict_manage_posts',      'mygengo_restrict_manage');  
add_filter('get_previous_post_join',     'mygengo_adjacent_posts_join');
add_filter('get_next_post_join',         'mygengo_adjacent_posts_join');
add_filter('get_previous_post_where',    'mygengo_adjacent_posts_where');
add_filter('get_next_post_where',        'mygengo_adjacent_posts_where');
//Page-related actions
add_action('save_page',                  'mygengo_save_postdata'); 
add_action('restrict_manage_pages',      'mygengo_restrict_manage');
//Comment-related actions
add_action('edit_comment', 'mygengo_save_commentdata'); 
//Display action -- selects only posts and pages written in the selected language
add_action('pre_get_posts', 'mygengo_selected_language_filter');
//User-related actions
add_action('show_user_profile',        'mygengo_show_extra_profile_fields' );
add_action('edit_user_profile',        'mygengo_show_extra_profile_fields' );
add_action('personal_options_update',  'mygengo_save_extra_profile_fields' );
add_action('edit_user_profile_update', 'mygengo_save_extra_profile_fields' );

function mygengo_activate() {
	global $wpdb;
	global $mg_plugin_dir, $table_name1, $table_name2, $table_name3, $table_name4, $table_name5;

        require_once ($mg_plugin_dir . '/php/init.php');
        $config  = myGengo_Config::getInstance();

	if ( ! empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty($wpdb->collate) )
		$charset_collate .= " COLLATE $wpdb->collate";

	$sync_needed = false;
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name1'") != $table_name1) {
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

		$sql = "CREATE TABLE " . $table_name1 . " (
			language_id int(11) AUTO_INCREMENT,
			language_code varchar(5) CHARACTER SET UTF8,
			language_name varchar(255) CHARACTER SET UTF8,
			language_localized_name varchar(255) CHARACTER SET UTF8,
			language_unit_type varchar(10) CHARACTER SET UTF8,
			UNIQUE KEY language_id (language_id),
			UNIQUE KEY language_code (language_code)
		)$charset_collate;";
		dbDelta($sql);

		$sync_needed = true;
	}

        if($wpdb->get_var("SHOW TABLES LIKE '$table_name2'") != $table_name2) {
                require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

                $sql = "CREATE TABLE " . $table_name2 . " (
                        pair_id int(11) AUTO_INCREMENT,
                        pair_source varchar(3),
                        pair_target varchar(3),
                        pair_tier varchar(20),
                        pair_unit_credit double,
                        UNIQUE KEY pair_id (pair_id)
                )$charset_collate;";
                dbDelta($sql);

                $sync_needed = true;
        }

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name3'") != $table_name3) {
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

		$sql = "CREATE TABLE " . $table_name3 . " (
			job_id int(11),
			job_group_id int(11) NOT NULL DEFAULT 0,
			job_slug varchar(255) CHARACTER SET UTF8,
			job_body_src LONGTEXT CHARACTER SET UTF8,
			job_body_tgt LONGTEXT CHARACTER SET UTF8,
			job_lc_src varchar(3) CHARACTER SET UTF8,
			job_lc_tgt varchar(3) CHARACTER SET UTF8,
			job_tier varchar(20) CHARACTER SET UTF8,
			job_unit_count int(5),
			job_credits double,
			job_status varchar(20) CHARACTER SET UTF8,
			job_ctime long,
			job_modified long,
			job_callback int(1) NOT NULL DEFAULT 0,
			job_captcha_url varchar(255) CHARACTER SET UTF8,
			job_user_id bigint(20) unsigned,
			job_post_id bigint(20) unsigned,
			job_post_type varchar(255) CHARACTER SET UTF8 NOT NULL DEFAULT '',
			UNIQUE KEY job_id (job_id)
		)$charset_collate;";
		dbDelta($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name4'") != $table_name4) {
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

		$sql = "CREATE TABLE " . $table_name4 . " (
			comment_id int(11) AUTO_INCREMENT,
			comment_job_id int(11),
			comment_body LONGTEXT CHARACTER SET UTF8,
			comment_author varchar(255) CHARACTER SET UTF8,
			comment_status varchar(20) CHARACTER SET UTF8,
			comment_callback int(1) NOT NULL DEFAULT 0,
			comment_ctime long,
			UNIQUE KEY comment_id (comment_id)
		)$charset_collate;";

		dbDelta($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name5'") != $table_name5) {
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

		$sql = "CREATE TABLE " . $table_name5 . " (
			ts_id int(11) AUTO_INCREMENT,
			ts_classname varchar(255) CHARACTER SET UTF8,
			ts_post_type varchar(255) CHARACTER SET UTF8 NOT NULL DEFAULT '',
			UNIQUE KEY ts_id (ts_id)
		)$charset_collate AUTO_INCREMENT = 10;";
		dbDelta($sql);
	}

	if ($sync_needed) {
		mygengo_sync_languages();
	}

	if(get_option("mygengo_use_browser") == "") {
		update_option("mygengo_use_browser", "0");
	}

	if(get_option("mygengo_use_admin_key") == "") {
		update_option("mygengo_use_admin_key", "0");
	}

	if(get_option("mygengo_format") == "") {
		$format = $config->get('format', 'json');
		update_option("mygengo_format", $format);
	}


	if(get_option("mygengo_baseurl") == "") {
		$baseurl = $config->get('baseurl', 'http://api.sandbox.mygengo.com/v1/');
		update_option("mygengo_baseurl", $baseurl);
	} else {
		$config->set('baseurl', get_option("mygengo_baseurl"));
	}

	define('WP_DEBUG',$config->get('debug', false));

	do_action('mygengo_init');
}

function mygengo_install () {
  echo "Remember to add your myGengo keys before using the plugin!";
}

function mygengo_deactivate() {
        global $wpdb;
        global $mg_plugin_dir, $table_name1, $table_name2, $table_name3, $table_name4, $table_name5;

	$mygengo_user_id = get_option("mygengo_translator_id");

	delete_option("mygengo_primary_language");
	delete_option("mygengo_translator_id");
	delete_option("mygengo_translator_active");
        delete_option("mygengo_primary_language");
        delete_option("mygengo_use_browser");
        delete_option("mygengo_use_mygengouser");
        delete_option("mygengo_use_admin_key");
        delete_option("mygengo_api_key");    
        delete_option("mygengo_private_key");
        delete_option("mygengo_baseurl");
        delete_option("mygengo_add_footer");
        delete_option("mygengo_footer"); 
        delete_option("mygengo_correction_instructions");
        delete_option("mygengo_rejection_instructions");

        if($wpdb->get_var("SHOW TABLES LIKE '$table_name1'") == $table_name1) {
                $sql = "DROP TABLE " . $table_name1 . ";";
                $wpdb->query($sql);
                $sql = "DROP TABLE " . $table_name2 . ";";
                $wpdb->query($sql);
                $sql = "DROP TABLE " . $table_name3 . ";";
                $wpdb->query($sql);
                $sql = "DROP TABLE " . $table_name4 . ";";
                $wpdb->query($sql);
                $sql = "DROP TABLE " . $table_name5 . ";";
                $wpdb->query($sql);
        }

	do_action('mygengo_reset');
}

////////////////////////////////////////////////////////////////////////////////
//////////                                                            //////////
//////////                         GLOBAL VARS                        //////////
//////////                                                            //////////
////////////////////////////////////////////////////////////////////////////////
global $job_headers, $job_hidden_headers, $new_meta_boxes;

$job_headers = array( 'job_id' => 'Job ID', 'job_ctime' => 'Ordered on', 'job_status' => 'Status', 'lang_src' => 'Source', 'lang_tgt' => 'Target', 'job_unit_count' => 'Unit Count', 'job_tier' => 'Tier', 'comments' => '<div class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>');
$job_hidden_headers = array();

$new_meta_boxes =
	array("post_language" => array(
		"name" => "post_language",
		"std" => "",
		"title" => __('Written in'),
		"description" => "")
		,
             "post_parent" => array(
		"name" => "post_parent",
		"std" => "",
		"title" => __('Post Parent'),
		"description" => "")
	);

////////////////////////////////////////////////////////////////////////////////
//////////                                                            //////////
//////////                           PANELS                           //////////
//////////                                                            //////////
////////////////////////////////////////////////////////////////////////////////

/*** ADD SECTION ***/
function mygengo_add_panel() {
	global $mg_plugin_url;
	if (current_user_can('edit_posts') && function_exists('add_menu_page')) {
		add_menu_page( __('MyGengo Translator'), 'MyGengo', 'edit_posts', basename(__FILE__), NULL, $mg_plugin_url . 'images/mygengo.png', 28 );
	}
}

function mygengo_add_admin_panel() {
	if (current_user_can('manage_options') && function_exists('add_submenu_page')) {
		$page = add_submenu_page(basename(__FILE__), __('Settings'), __('Settings'), 'manage_options', basename(__FILE__) . 'account', 'mygengo_admin_panel');
		add_action('admin_print_scripts-' . $page, 'mygengo_load_javascripts');
	}
}

function mygengo_add_account_panel() {
	if (function_exists('add_submenu_page')) {
		$page = add_submenu_page(basename(__FILE__), __('Personal Settings'), __('Personal Settings'), 'edit_posts', basename(__FILE__), 'mygengo_personal_panel');
		add_action('admin_print_scripts-' . $page, 'mygengo_load_javascripts');
	}
}

function mygengo_add_jobs_panel() {
	global $job_headers;

	if (function_exists('add_submenu_page')) { 
		$mg_keys = mygengo_getKeys();
		if (count($mg_keys) == 2) {
			$page = add_submenu_page(basename(__FILE__), __('Jobs'), __('Jobs'), 'edit_posts', basename(__FILE__) . 'jobs', 'mygengo_jobs_panel');
			add_action('admin_print_scripts-' . $page, 'mygengo_load_javascripts');
			register_column_headers(basename(__FILE__) . 'jobs', $job_headers);
			$page = add_submenu_page(basename(__FILE__), __('Add new job'), __('Add new job'), 'edit_posts', basename(__FILE__) . 'order', 'mygengo_add_job_panel');
			add_action('admin_print_scripts-' . $page, 'mygengo_load_javascripts');
			add_action('admin_init', 'mygengo_manage_forms');
		}
	}
}

function mygengo_add_job_panel() {
	$mg_keys = mygengo_getKeys();
	if (count($mg_keys) != 2) {
		mygengo_getaccount_panel();
        } else {
		include_once(dirname(__FILE__) . '/mygengo-order.php');
	}
}

/*** DISPLAY SECTION ***/

function mygengo_personal_panel() {
	if (!current_user_can('edit_posts'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	global $wpdb, $_POST, $current_user, $userdata;
        global $mg_plugin_dir, $wp_admin_url;

        require_once ($mg_plugin_dir . '/php/init.php');

	if($_POST['save_changes']) {
		$userid = $current_user->ID;
		update_usermeta($userid, "mygengo_primary_language", $_POST['mg_primary_language']);
		update_usermeta($userid, "mygengo_api_key",          $_POST['mg_api_key']);
		update_usermeta($userid, "mygengo_private_key",      $_POST['mg_private_key']);
		update_usermeta($userid, "mygengo_add_footer",       $_POST['mg_add_footer']);
		update_usermeta($userid, "mygengo_footer",           $_POST['mg_footer']);
	}

	if ($_POST["sync_languages"]) {
		mygengo_sync_languages();
	}

	include_once(dirname(__FILE__) . '/mygengo-personal.php');
}

function mygengo_admin_panel() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	global $wpdb, $_POST;
        global $mg_plugin_dir, $wp_admin_url;

        require_once ($mg_plugin_dir . '/php/init.php');
        $config  = myGengo_Config::getInstance();

	if($_POST['save_changes']) {
		update_option("mygengo_primary_language", $_POST['mg_primary_language']);
		update_option("mygengo_use_browser",      $_POST['mg_use_browser']);
		update_option("mygengo_use_mygengouser",  $_POST['mg_use_mygengouser']);
		update_option("mygengo_use_admin_key",    $_POST['mg_use_admin_key']);
		update_option("mygengo_api_key",          $_POST['mg_api_key']);
		update_option("mygengo_private_key",      $_POST['mg_private_key']);
		update_option("mygengo_baseurl",          $_POST['mg_baseurl']);
		update_option("mygengo_add_footer",       $_POST['mg_add_footer']);
		update_option("mygengo_footer",           $_POST['mg_footer']);

		update_option("mygengo_correction_instructions", $_POST['mg_correction_instructions']);
		update_option("mygengo_rejection_instructions",  $_POST['mg_rejection_instructions']);

                $config->set('baseurl', get_option("mygengo_baseurl"));
	}

	if ($_POST["sync_languages"]) {
		mygengo_sync_languages();
	}

        if($_POST['mg_use_mygengouser'] && get_option("mygengo_translator_active") == '') {
                $user_id = add_user();
		if (is_wp_error($user_id)) {
			$grant_access_notification = '<div id="message" class="updated fade"><p>' . _e('Creation of user mygengotranslator failed!') . $user_id->get_error_message() . '</p></div>';
		} else {
	                $user_pass = $_POST['pass1'];
        	        wp_new_user_notification($user_id, $user_pass);

	                update_option("mygengo_translator_id", $user_id);
        	        update_option("mygengo_translator_active", "1");
			$user = new WP_User(intval(get_option("mygengo_translator_id")));
			$user->set_role("editor");

        	        $grant_access_notification = '<div id="message" class="updated fade"><p>' . _e('User mygengotranslator added!') . '</p></div>';
		}
        }

	include_once(dirname(__FILE__) . '/mygengo-account.php');
}

function mygengo_account_panel() {
        if (!current_user_can('edit_posts'))  {
                wp_die( __('You do not have sufficient permissions to access this page.') );
        }
	include_once(dirname(__FILE__) . '/mygengo-stats.php');
}

function mygengo_getaccount_panel() {
        if (!current_user_can('edit_posts'))  {
                wp_die( __('You do not have sufficient permissions to access this page.') );
        }
	include_once(dirname(__FILE__) . '/mygengo-getaccount.php');
}

function mygengo_jobs_panel() {
        if (!current_user_can('edit_posts'))  {
                wp_die( __('You do not have sufficient permissions to access this page.') );
        }

	$mg_keys = mygengo_getKeys();
	if (count($mg_keys) != 2) {
                wp_die( __('You do not have sufficient permissions to access this page.') );
        }

        global $wpdb;
        global $mg_plugin_dir, $mg_plugin_url, $job_headers, $wp_admin_url;

	$action = $_REQUEST["action"];

	if ($action == 'view') {
		include_once(dirname(__FILE__). '/mygengo-review.php');
	} else {
		include_once(dirname(__FILE__) . '/mygengo-jobs.php');
	}
}

function mygengo_register_javascripts() {
	global $mg_plugin_url;
	wp_register_script('gen_validator',   $mg_plugin_url . 'js/gen_validatorv31.js');
	wp_register_script('mygengo_scripts', $mg_plugin_url . 'js/mygengo-script.js');
	wp_register_script('mygengo_ajax',    $mg_plugin_url . 'js/mygengo-ajax.js.php');
	wp_register_script('mygengo_base64',    $mg_plugin_url . 'js/webtoolkit.base64.js');
}
function mygengo_load_javascripts() {
	wp_enqueue_script('gen_validator');
	wp_enqueue_script('mygengo_scripts');
	wp_enqueue_script('mygengo_ajax');
	wp_enqueue_script('mygengo_base64');
}

////////////////////////////////////////////////////////////////////////////////
//////////                                                            //////////
//////////                      FORM PROCESSING                       //////////
//////////                                                            //////////
////////////////////////////////////////////////////////////////////////////////
function mygengo_manage_forms() {
	global $_REQUEST, $_POST, $wpdb;
	global $wp_admin_url, $mg_plugin_dir, $mg_plugin_url;

	require_once ($mg_plugin_dir . '/php/init.php');

	$table_name1 = $wpdb->prefix . "gengo_languages";
	$table_name2 = $wpdb->prefix . "gengo_pairs";
	$table_name3 = $wpdb->prefix . "gengo_jobs";
	$table_name4 = $wpdb->prefix . "gengo_comments";

	$mg_keys = mygengo_getKeys();

	if (isset($_POST['mg_order_form'])) {
		check_admin_referer('mygengo_order_form');

		$job_lc_src       = $_POST['mg_source_language'];
		$job_lc_tgt       = $_POST['mg_target_language'];
		$job_tier         = $_POST['mg_tier'];
		$job_unit_count   = $_POST['mg_unit_count'];
		$job_credits      = $_POST['mg_price'];
		$job_status       = 'submitted';
		$job_ctime        = time();
		$job_modified     = time();
		$job_user_id      = $_POST['mg_user_id'];
		$job_post_id      = (isset($_POST['mg_post_id']))?$_POST['mg_post_id']:0;
		$job_post_type    = (isset($_POST['mg_post_type']))?$_POST['mg_post_type']:'';
		$job_auto_approve = (isset($_POST['auto_approve']))?'1':'0';
		$job_callback_url = $mg_plugin_url.'mygengo-callback.php';
		
		$titotal = intval($_POST['mg_text_amount']);
		$jobstopost = array();
		for($ti=0; $ti<$titotal; $ti++) {
			$job_id           = $ti;
			$job_slug         = $_POST['mg_body_id_'.$job_id];
			$job_body_src     = $_POST['mg_body_src_'.$job_id];
			$job_body_comment = $_POST['mg_body_comment_'.$job_id];

			$job = array(
				'type'         => 'text',
				'slug'         => $job_slug,
				'body_src'     => $job_body_src,
				'lc_src'       => $job_lc_src,
				'lc_tgt'       => $job_lc_tgt,
				'tier'         => $job_tier,
				'callback_url' => $job_callback_url,
				'auto_approve' => $job_auto_approve,
				'custom_data'  => 'submitted through wordpress!'
			);

			if (trim($job_body_comment) != '') {
				$job['comment'] = $job_body_comment;
			}

			$jobstopost['job_'.$job_id] = $job;
		}

		if ($_POST['mg_quote']) {
			$service = myGengo_Api::factory('service', $mg_keys['api_key'], $mg_keys['private_key']);
			$service->getQuote($jobstopost, 'json');
			$json = $service->getResponseBody();
			$jsonobject = json_decode($json);
			$jsonerror = mygengo_check_error($jsonobject);
			if ($jsonerror) {
				wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
			}
			//check correct submission
			$jsonresponse = $jsonobject->{'response'};
			$jobs_qt = $jobs_uc = 0;
			for($ti=0; $ti<$titotal; $ti++) {
				$jsonjob   = $jsonresponse->{'jobs'}->{'job_'.$ti};
				$jsoncount = $jsonjob->{'unit_count'};
				$jsonquote = $jsonjob->{'credits'};
				$jobs_qt += $jsonquote;
				$jobs_uc += $jsoncount;
				$_POST['job_'.$ti.'_uc'] = $jsoncount;
				$_POST['job_'.$ti.'_qt'] = $jsonquote;
			}
			$_POST['jobs_uc'] = $jobs_uc;
			$_POST['jobs_qt'] = $jobs_qt;
		} else {
			$jobs    = myGengo_Api::factory('jobs', $mg_keys['api_key'], $mg_keys['private_key']);
			$job_as_group = ($titotal > 1 && isset($_POST['as_group']))?1:0;
			$job_process  = intval($_POST['mg_process']);

			$jobs->postJobs($jobstopost, $job_as_group, $job_process, 'json');
			$json = $jobs->getResponseBody();
			$jsonobject = json_decode($json);
			$jsonerror = mygengo_check_error($jsonobject);
			if ($jsonerror) {
				wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
			}

			//check correct submission
			$jsonresponse = $jsonobject->{'response'};
			$job_group_id = 0;
			$jsongroupid  = $jsonresponse->{'group_id'};
			if ($jsongroupid) {
				$job_group_id = intval($jsongroupid);
			}
			for($ti=0; $ti<$titotal; $ti++) {
				$jsonjob        = $jsonresponse->{'jobs'}[$ti]->{'job_'.$ti};
				$job_ctime      = $jsonjob->{'ctime'};
				$job_modified   = $jsonjob->{'ctime'};
				$job_id         = $jsonjob->{'job_id'};
				$job_slug       = $jsonjob->{'slug'};
				$job_body_src   = $jsonjob->{'body_src'};
				$job_body_tgt   = $jsonjob->{'body_tgt'};
				if (is_null($job_body_tgt)) { $job_body_tgt = 'NULL'; } else {$job_body_tgt   = "'".addslashes($job_body_tgt)."'";}
				$job_unit_count = $jsonjob->{'unit_count'};
				$job_credits    = $jsonjob->{'credits'};

				//insert in sql db
				$cnt = $wpdb->get_var("SELECT COUNT(1) FROM ".$table_name3." WHERE job_id = ".$job_id);
				if (!$cnt) {
					$sql = "INSERT INTO ".$table_name3." (job_id,job_slug,job_body_src,job_body_tgt,job_lc_src,job_lc_tgt,job_tier,job_unit_count,job_credits,job_status,job_ctime,job_modified,job_user_id,job_post_id,job_post_type, job_group_id) VALUES (".$job_id.",'".addslashes($job_slug)."','".addslashes($job_body_src)."',".$job_body_tgt.",'".$job_lc_src."','".$job_lc_tgt."','".$job_tier."',".$job_unit_count.",".$job_credits.",'".$job_status."',".$job_ctime.",".$job_modified.",".$job_user_id.",".$job_post_id.",'".$job_post_type."',".$job_group_id.")";
					if ($wpdb->query($sql) === FALSE) {
						wp_die( 'Error inserting new job! ID: ' . $job_id . ' query: ' . $sql, 'Error in order' );
					}
				}
			}
			wp_redirect($wp_admin_url . '/admin.php?page=mygengo.phpjobs');
		}
	} elseif (isset($_POST['mg_reject_form'])) {
		check_admin_referer('mygengo_reject_form');
		$job_id = $_POST['mg_job_id'];
		$job_client = myGengo_Api::factory('job', $mg_keys['api_key'], $mg_keys['private_key']);
		$data = array('action' => 'reject', 'reason' => $_POST['mg_reason'], 'follow_up' => $_POST['mg_follow_up'], 'comment' => $_POST['mg_comment'], 'captcha' => $_POST['mg_captcha']);
		$job_client->reject($job_id, $data);
		$json = $job_client->getResponseBody();
		$jsonobject = json_decode($json);
		$jsonerror = mygengo_check_error($jsonobject);
		if ($jsonerror) {
			wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
		}

		wp_redirect($wp_admin_url . '/admin.php?page=mygengo.phpjobs');
	} elseif (isset($_POST['mg_request_form'])) {
		check_admin_referer('mygengo_request_form');
		$job_id = $_POST['mg_job_id'];
		$job_client = myGengo_Api::factory('job', $mg_keys['api_key'], $mg_keys['private_key']);
		$job_client->revise($job_id, $_POST['mg_comment']);
		$json = $job_client->getResponseBody();
		$jsonobject = json_decode($json);
		$jsonerror = mygengo_check_error($jsonobject);
		if ($jsonerror) {
			wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
		}

		wp_redirect($wp_admin_url . '/admin.php?page=mygengo.phpjobs');
	} elseif (isset($_POST['mg_review_form'])) {
		check_admin_referer('mygengo_review_form');
		$job_id = $_POST['mg_job_id'];
		$job_client = myGengo_Api::factory('job', $mg_keys['api_key'], $mg_keys['private_key']);
		if ($_REQUEST['mg_add_comment'] == '1') {
			$job_comment = $_POST['mg_body_comment'];
			$job_client->postComment($job_id, $job_comment, 'json');
			$json = $job_client->getResponseBody();
			$jsonobject = json_decode($json);
			$jsonerror = mygengo_check_error($jsonobject);
			if ($jsonerror) {
				wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
			}

			$sql = "INSERT INTO ".$table_name4." (comment_job_id, comment_body, comment_author, comment_status, comment_ctime) VALUES (".$job_id.",'".addslashes($job_comment)."','customer', 'read', ".time().")";
			if ($wpdb->query($sql) === FALSE) {
				wp_die( 'Error inserting new comment!', 'Error in comment' );
			}
		} elseif (isset($_POST['mg_pay'])) {
			$job_client->purchase($job_id);
			$json = $job_client->getResponseBody();
			$jsonobject = json_decode($json);
			$jsonerror = mygengo_check_error($jsonobject);
			if ($jsonerror) {
				wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
			}

			wp_redirect($wp_admin_url . '/admin.php?page=mygengo.phpjobs');
		} elseif (isset($_POST['mg_cancel'])) {
			$job_client->cancel($job_id, 'json');
			$json = $job_client->getResponseBody();
			$jsonobject = json_decode($json);
			$jsonerror = mygengo_check_error($jsonobject);
			if ($jsonerror) {
				wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
			}

			wp_redirect($wp_admin_url . '/admin.php?page=mygengo.phpjobs');
		} elseif (isset($_POST['mg_approve'])) {
			$data = array('action' => 'approve', 'rating' => intval($_POST['mg_rating']));
			if ($_POST['mg_for_translator'] != '') {
				$data['for_translator'] = $_POST['mg_for_translator'];
			}
			if ($_POST['mg_for_mygengo'] != '') {
				$data['for_mygengo'] = $_POST['mg_for_mygengo'];
			}
			if (isset($_POST['mg_public'])) {
				$data['public'] = 1;
			}
			$job_client->approve($job_id, $data);
			$json = $job_client->getResponseBody();
			$jsonobject = json_decode($json);
			$jsonerror = mygengo_check_error($jsonobject);
			if ($jsonerror) {
				wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
			}

			wp_redirect($wp_admin_url . '/admin.php?page=mygengo.phpjobs');
		} elseif (current_user_can('edit_posts')) {
			$requestvars     = $_POST;
			$publishing_opts = split("-", $requestvars['mg_publish_as']);
			$publish_as      = intval($publishing_opts[0]);
			$publish_type    = (count($publishing_opts)==2)?$publishing_opts[1]:'';
			switch($publish_as) {
				case 0:
					$post_parent = 0;
					$post_type   = 'post';
					break;
				case 1:
					$post_parent = 0;
					$post_type   = 'page';
					break;
				default:
					break;
			}
			switch ($publish_as) {
				case 0:
				case 1:
					$job_body    = $wpdb->get_var("SELECT job_body_tgt FROM ".$table_name3." WHERE job_id = ".$job_id);
					$post_cats   = array();
					$userid      = $requestvars['mg_user_id'];
					$post_author = (get_option('mygengo_use_mygengouser'))?get_option('mygengo_translator_id'):$userid;
					if (count(mygengo_getKeys(2)) == 2 && get_the_author_meta('mygengo_add_footer', $userid)) {
						$post_footer   = '<div>'.get_the_author_meta('mygengo_footer').'</div>';
					} elseif (count(mygengo_getKeys(2)) != 2 && get_option('mygengo_add_footer')) {
						$post_footer   = '<div>'.get_option('mygengo_footer').'</div>';
					} else {
						$post_footer   = '';
					}
					$post_language = $requestvars['mg_tgt_language'];
					$post_sections = mygengo_parse_content($job_body);

					// Create post object
					$mg_post = array(
						'post_status'   => 'draft', 
						'post_type'     => $post_type,
						'post_title'    => $post_sections['post_title'],
						'post_excerpt'  => $post_sections['post_excerpt'],
						'post_content'  => $post_sections['post_content'].$post_footer,
						'post_author'   => $post_author,
						'post_category' => $post_cats,
						'post_parent'   => $post_parent
					);
		
					// Insert the post into the database
					$post_id = wp_insert_post( $mg_post, true );
					if (is_wp_error($post_id)) {
						wp_die( 'Error: could not create a post based on this content. Reason: ' . $post_id->get_error_message(), 'Error while creating the new post!' );
					} else {
						add_post_meta($post_id, '_post_language_value', $post_language, true);
						add_post_meta($post_id, '_post_parent_value', $post_parent, true);
						wp_redirect($wp_admin_url . '/post.php?post=' . $post_id .'&action=edit');
					}
					break;
				default:
					$sources  = mygengo_get_textsources();
					foreach($sources as $ts) {
						if ($ts->getInternalId() == $publish_as) {
							$ts->publishAs($job_id, $requestvars);
							break;
						}
					}
					break;
			}
		}
	}

}

////////////////////////////////////////////////////////////////////////////////
//////////                                                            //////////
//////////                          DASHBOARD                         //////////
//////////                                                            //////////
////////////////////////////////////////////////////////////////////////////////

function mygengo_dashboard_widget_function() {
	global $wpdb, $current_user, $userdata, $wp_admin_url, $table_name3, $table_name4;
	$filter = ' AND j.job_user_id IN (-1';
	$mg_public_keys  = mygengo_getkeys(0);	
	$mg_private_keys = mygengo_getkeys(2);	
	if (count($mg_private_keys)==2) {
		$filter .= ',' . $current_user->ID;
	} 
	if (count($mg_public_keys)==2) {
		$filter .= ',0';
	}
	$filter .= ')';

?>
<div id="the-comment-list" class="list:comment"> 
<?php
	$sql = "SELECT j.job_id, j.job_slug, j.job_status FROM ".$table_name3." j WHERE j.job_callback = 1".$filter;
	$callback_jobs = $wpdb->get_results($sql);

	if ($callback_jobs) {
		$idx = 0;
		foreach($callback_jobs as $job) {
			$idx++;
			$style = ($style == 'even')? 'odd' : 'even';
?>
	<div id="comment-<?php echo $idx; ?>" class="comment <?php echo $style; ?> <?php echo $job->job_status; ?>"> 
		<div class="dashboard-comment-wrap"> 
			<h4 class="comment-meta"> 
				<?php _e('Job'); ?> <a href='<?php echo $wp_admin_url; ?>/admin.php?page=mygengo.phpjobs&action=view&job_id=<?php echo $job->job_id; ?>'><?php echo mygengo_reverse_escape($job->job_slug); ?></a> <?php _e('has status'); ?> <strong><?php _e($job->job_status); ?></strong>
			</h4> 
		</div> 
	</div> 
<?php
		}
	} else {
?>
	<div><?php _e('No status updated'); ?></div>
<?php
	}
?>
	<div><hr style='width:100%;'/></div>
<?php

	$sql = "SELECT c.comment_job_id, c.comment_author, c.comment_body, j.job_slug FROM ".$table_name3." j, ".$table_name4." c WHERE c.comment_callback = 1 AND c.comment_job_id = j.job_id".$filter;
	$callback_comments = $wpdb->get_results($sql);

	if ($callback_comments) {
	foreach($callback_comments as $comment) {
		$idx++;
		$style = ($style == 'even')? 'odd' : 'even';
?>
	<div id="comment-<?php echo $idx; ?>" class="comment <?php echo $style; ?>"> 
		<div class="dashboard-comment-wrap"> 
			<h4 class="comment-meta"> 
				<?php _e('From'); ?> <cite class="comment-author"><?php echo $comment->comment_author; ?></cite> on <a href='<?php echo $wp_admin_url; ?>/admin.php?page=mygengo.phpjobs&action=view&job_id=<?php echo $comment->comment_job_id; ?>'><?php echo mygengo_reverse_escape($comment->job_slug); ?></a>
			</h4> 
			<blockquote><p><?php echo mygengo_reverse_escape($comment->comment_body); ?></p></blockquote> 
		</div> 
	</div> 
<?php
		}
	} else {
?>
	<div><?php _e('No comments updated'); ?></div>
<?php
	}
?>
	</div>
<?php
} 

function mygengo_add_dashboard_widgets() {
	wp_add_dashboard_widget('mygengo_dashboard_widget', 'myGengo Dashboard', 'mygengo_dashboard_widget_function');	
} 

////////////////////////////////////////////////////////////////////////////////
//////////                                                            //////////
//////////                          METADATA                          //////////
//////////                                                            //////////
////////////////////////////////////////////////////////////////////////////////

/**
 * This section is based on code from the 'translate my blog' plugin, but modified
 * to fit this plugin
 * translatemyblog link: http://translatemyblog.com/
 */

function mygengo_comment_meta_boxes() {
	global $comment, $wpdb, $table_name1, $table_name2;

	$meta_box_value = get_comment_meta($comment->comment_ID, '_comment_language_value', true);
	if($meta_box_value == "")
		$meta_box_value = '';

	echo'<input type="hidden" name="comment_language_value_noncename" id="comment_language_value_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';

	echo __('Written in')."&nbsp";

	$default_language = $meta_box_value;
	if($default_language == "") {
		$default_language = mygengo_get_primarylanguage();
	}
	echo '<select name="comment_language_value">';
	echo '<option value="">' . __('[Select]') . '</option>';
	echo mygengo_generate_select_from_sqlquery("SELECT language_code, language_name FROM ".$table_name1." ORDER BY language_name", "language_code", "language_name", $default_language);
	echo '</select>';

	echo'<p><label for="comment_language_value">'.__('The language in which the comment was written').'</label></p>';
	mygengo_new_comment_translatebutton();
}

function mygengo_new_meta_boxes() {
	global $post, $new_meta_boxes, $wpdb, $table_name1, $table_name2;

	foreach($new_meta_boxes as $meta_box) {
		$meta_box_value = get_post_meta($post->ID, '_'.$meta_box['name'].'_value', true);

		if($meta_box_value == "")
			$meta_box_value = $meta_box['std'];

		echo'<input type="hidden" name="'.$meta_box['name'].'_noncename" id="'.$meta_box['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';

		echo $meta_box['title']."&nbsp";

		if($meta_box['name'] == "post_language") {
			$default_language = $meta_box_value;
			if($default_language == "") {
				$default_language = mygengo_get_primarylanguage();
			}
			echo '<select name="'.$meta_box['name'].'_value">';
			echo '<option value="">' . __('[Select]') . '</option>';
			echo mygengo_generate_select_from_sqlquery("SELECT language_code, language_name FROM ".$table_name1." ORDER BY language_name", "language_code", "language_name", $default_language);
			echo '</select>';
		}

		else if($meta_box['name'] == "post_parent") {
			$default_parent = $meta_box_value;
			if($_GET['post_parent'] != "") {
				echo '<select name="'.$meta_box['name'].'_value">';
				echo '<option value="">' . __('[Select]') . '</option>';
				if (mygengo_get_post_type($post->ID) == 'page') {
					echo mygengo_generate_select_from_pages($_GET['post_parent']);
				} else {
					echo mygengo_generate_select_from_posts($_GET['post_parent']);
				}
			}
			else {
				echo '<select name="'.$meta_box['name'].'_value">';
				echo '<option value="">[Select]</option>';
				if (mygengo_get_post_type($post->ID) == 'page') {
					echo mygengo_generate_select_from_pages($default_parent);
				} else {
					echo mygengo_generate_select_from_posts($default_parent);
				}
			}
			echo '</select>';
		}

		else {
			echo'<input type="text" name="'.$meta_box['name'].'_value" value="'.$meta_box_value.'" size="55" /><br />';
		}

		echo'<p><label for="'.$meta_box['name'].'_value">'.$meta_box['description'].'</label></p>';
	}
	mygengo_new_post_translatebutton();
}

function mygengo_create_meta_box() {
	if ( function_exists('add_meta_box') ) {
		add_meta_box('new-meta-boxes', __('MyGengo Translator'), 'mygengo_new_meta_boxes', 'post', 'normal', 'high');
		add_meta_box('new-meta-boxes', __('MyGengo Translator'), 'mygengo_new_meta_boxes', 'page', 'normal', 'high');
		add_meta_box('comment-meta-boxes', __('MyGengo Translator'), 'mygengo_comment_meta_boxes', 'comment', 'normal', 'high');
	}
}


function  mygengo_save_postdata( $post_id ) {
	global $post, $new_meta_boxes;

	foreach($new_meta_boxes as $meta_box) {
		// Verify
		if ( !wp_verify_nonce( $_POST[$meta_box['name'].'_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}

		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ))
				return $post_id;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ))
				return $post_id;
		}

		$data = $_POST[$meta_box['name'].'_value'];

		if(get_post_meta($post_id, '_'.$meta_box['name'].'_value') == "")
			add_post_meta($post_id, '_'.$meta_box['name'].'_value', $data, true);
		elseif($data != get_post_meta($post_id, '_'.$meta_box['name'].'_value', true))
			update_post_meta($post_id, '_'.$meta_box['name'].'_value', $data);
		elseif($data == "")
			delete_post_meta($post_id, '_'.$meta_box['name'].'_value', get_post_meta($post_id, '_'.$meta_box['name'].'_value', true));
	}
}	


function  mygengo_save_commentdata( $comment_id ) {
	global $comment, $new_meta_boxes;

	if ( !wp_verify_nonce( $_POST['comment_language_value_noncename'], plugin_basename(__FILE__) )) {
		return $comment_id;
	}

//	if ( !current_user_can( 'edit_comment', $comment_id ))
//		return $comment_id;

	$data = $_POST['comment_language_value'];
	$previous = get_comment_meta($comment_id, '_comment_language_value', true);
	if($previous == "")
		add_comment_meta($comment_id, '_comment_language_value', $data, true);
	elseif($data != $previous)
		update_comment_meta($comment_id, '_comment_language_value', $data);
	elseif($data == "")
		delete_comment_meta($comment_id, '_comment_language_value', $previous);
}	

function mygengo_restrict_manage() {  
?>  
	<form name="mygengo_filterform" id="mygengo_filterform" action="" method="get">  
		<fieldset>  
			<select name='post_language' id='post_language' class='postform'>
				<option value="">View all languages&nbsp&nbsp;</option>
				<?php echo mygengo_generate_select_from_sqlquery("SELECT language_code, language_name FROM languages ORDER BY language_name", "language_code", "language_name", ""); ?>
			</select>  
			<input type="submit" name="submit" value="<?php _e('Filter') ?>" class="button" />  
		</fieldset>  
	</form>  
<?php  
}

if($_GET['post_language'] != "") {

	add_filter('request', 'mygengo_request' );

	function mygengo_request($qvars) {
		$qvars['meta_key'] = 'post_language_value';
		$qvars['meta_value'] = $_GET['post_language'];
		return $qvars;
	}
}

add_shortcode('mygengo_st', 'mygengo_sc_show_translations', 12);  

function mygengo_sc_show_translations( $atts, $content = null ) {
   extract( shortcode_atts( array(
      'post_type'  => 'post',
      'post_id'    => '0',
      'element_id' => '',
      'include_text' => '0',
      ), $atts ) );
 
   return mygengo_translations_viewer(esc_attr($post_id), esc_attr($post_type), esc_attr($element_id), intval(esc_attr($include_text)));
}

function mygengo_translations_viewer($post_id, $post_type='post', $element_id='', $include_text=0) {
	global $wpdb, $table_name3, $wp_admin_url;

	$trdivs = '';
	$navbar = '';
	$mg_keys = mygengo_getKeys();
	if (count($mg_keys) == 2) {
		if ($include_text) {
			$navbar = '<a href="javascript:void(0);" onclick=\'document.location="'.$wp_admin_url.'/admin.php?page=mygengo.phporder&mg_post_id='.$post_id.'&mg_post_type='.$post_type.'&mg_ttt="+Base64.encode(document.getElementById("'.$element_id.'").innerHTML)\'>'.__('Add a translation').'</a>&nbsp;&nbsp;';
		} else {
			$navbar = '<a href="'.$wp_admin_url.'/admin.php?page=mygengo.phporder&mg_post_id='.$post_id.'&mg_post_type='.$post_type.'">'.__('Add a translation').'</a>&nbsp;&nbsp;';
		}
	}

	$query = "SELECT job_lc_tgt, job_body_tgt FROM {$table_name3} WHERE job_post_id = {$post_id} AND job_post_type = '{$post_type}' AND job_status = 'approved'";
	$results = $wpdb->get_results($query);
	if ($results) {
		$navbar .= "<a href='javascript:void(0);' onclick='show_translation(\"{$element_id}\", \"{$element_id}\")'>".__('Show original text')."</a> &nbsp;";
		foreach($results as $result) {
			$trdivs .= "<div id='mygengo_{$post_type}_{$post_id}_{$result->job_lc_tgt}' class='mygengo_{$post_type}' style='display:none; visibility: hidden;'>".str_replace(array('[[[',']]]'), array('<','>'), $result->job_body_tgt)."</div> ";
			$navbar .= "<a href='javascript:void(0);' onclick='show_translation(\"{$element_id}\", \"mygengo_{$post_type}_{$post_id}_{$result->job_lc_tgt}\")'>".mygengo_get_language_image_src($result->job_lc_tgt)."</a> &nbsp;&nbsp;";
		}
	}
	return $trdivs . '<div class="mg_trans_navbar">'.$navbar.'</div>';
}

add_shortcode('mygengo_t4e', 'mygengo_sc_translations_editor', 12);  

function mygengo_sc_translations_editor( $atts, $content = null ) {
   extract( shortcode_atts( array(
      'post_type'  => 'post',
      'post_id'    => '0',
      'element_id' => '',
      ), $atts ) );
 
   return mygengo_translations_4editor(esc_attr($post_id), esc_attr($post_type), esc_attr($element_id));
}

function mygengo_translations_4editor($post_id, $post_type='post', $element_id='') {
	$mg_keys = mygengo_getKeys();
	if (count($mg_keys) == 0) return;

	global $wpdb, $table_name3, $wp_admin_url;

	$query = "SELECT job_lc_tgt, job_body_tgt FROM {$table_name3} WHERE job_post_id = {$post_id} AND job_post_type = '{$post_type}' AND job_status = 'approved'";
	$results = $wpdb->get_results($query);

	$trdivs = '';
	$navbar = '<a href="javascript:void(0);" onclick=\'document.location="'.$wp_admin_url.'/admin.php?page=mygengo.phporder&mg_post_id='.$post_id.'&mg_post_type='.$post_type.'&mg_ttt="+Base64.encode(document.getElementById("'.$element_id.'").innerHTML)\'>'.__('Add a translation').'</a>&nbsp;&nbsp;';
	if ($results) {
		foreach($results as $result) {
			$trdivs .= "<div id='mygengo_{$post_type}_{$post_id}_{$result->job_lc_tgt}' style='display:none; visibility: hidden;'>".str_replace(array('[[[',']]]'), array('<','>'), $result->job_body_tgt)."</div> ";
			$navbar .= "<a href='javascript:void(0);' onclick='add_translation_text(\"{$element_id}\", \"mygengo_{$post_type}_{$post_id}_{$result->job_lc_tgt}\")'>".mygengo_get_language_image_src($result->job_lc_tgt)."</a> &nbsp;&nbsp;";
		}
	}
	return $trdivs . '<div class="mg_trans_navbar">'.$navbar.'</div>';
}

function mygengo_display_translations($post_id) {
	global $wpdb;

	$query = "SELECT ID, ".$wpdb->prefix."postmeta.meta_key, ".$wpdb->prefix."postmeta.meta_value FROM ".$wpdb->prefix."posts INNER JOIN ".$wpdb->prefix."postmeta ON (".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id) WHERE 1=1 AND ".$wpdb->prefix."posts.post_type = 'post' AND (".$wpdb->prefix."posts.post_status = 'publish') AND ".$wpdb->prefix."postmeta.meta_key = '_post_parent_value' AND ".$wpdb->prefix."postmeta.meta_value = ".$post_id." ORDER BY ".$wpdb->prefix."postmeta.meta_value";

	$results = $wpdb->get_results($query);

	if ($results) {
		_e('Available in:');
		foreach($results as $result) {
			echo "<a href='".get_permalink($result->ID)."'>".mygengo_get_language_image_src(mygengo_get_post_language_value($result->ID))."</a> ";
		}
	}
}


function mygengo_display_parent_link($post_id) {
	global $wpdb;
	
	$query = "SELECT ID, ".$wpdb->prefix."postmeta.meta_key, ".$wpdb->prefix."postmeta.meta_value FROM ".$wpdb->prefix."posts INNER JOIN ".$wpdb->prefix."postmeta ON (".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id) WHERE 1=1 AND ".$wpdb->prefix."posts.post_type = 'post' AND (".$wpdb->prefix."posts.post_status = 'publish' OR ".$wpdb->prefix."posts.post_status = 'future' OR ".$wpdb->prefix."posts.post_status = 'draft' OR ".$wpdb->prefix."posts.post_status = 'pending' OR ".$wpdb->prefix."posts.post_status = 'private') AND ".$wpdb->prefix."postmeta.meta_key = '_post_parent_value' AND ID = ".$post_id." ORDER BY ".$wpdb->prefix."postmeta.meta_value LIMIT 1";

	$results = $wpdb->get_results($query);

	foreach($results as $result) {
		echo "&laquo; <a href='".get_permalink($result->meta_value)."'>".mygengo_get_language_image_src(mygengo_get_post_language_value($result->meta_value))." Return to Original Post.</a> ";
	}
}


function mygengo_widget_init() {
	$my_post_id = "";

	if ( !function_exists('register_sidebar_widget'))
		return;

	function mygengo_widget_translation_listings($args) {
		global $wp_query;
		global $id;
		global $my_post_id;

		if(!($wp_query->is_single || $wp_query->is_page)) {
			return;
		}

		if(!$id) {
			return;	
		}

		$my_post_id = $id;
		
		if(mygengo_get_primarylanguage() == mygengo_get_post_language_value($my_post_id)) {
			//the language of this post is the primary language, so use this post as the parent post in query below
			$parent_post_id = $my_post_id;
		}
		else {
			//the language of this post is not the primary language, so find the parent for this post, and use it in the query below
			$parent_post_id = get_post_meta($my_post_id, "_post_parent_value", true);
		}

		extract($args);
		$title = __('Translations');

		global $wpdb;

		$query = "SELECT DISTINCT ID, post_title FROM ".$wpdb->prefix."posts INNER JOIN ".$wpdb->prefix."postmeta ON (".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id) WHERE (1=1 AND ".$wpdb->prefix."posts.ID != ".$my_post_id." AND ".$wpdb->prefix."posts.post_type IN ('post','page') AND (".$wpdb->prefix."posts.post_status = 'publish') AND ".$wpdb->prefix."postmeta.meta_key = '_post_parent_value' AND ".$wpdb->prefix."postmeta.meta_value = ".$parent_post_id.")";

		if($parent_post_id != $my_post_id) {
			$query .= " OR (".$wpdb->prefix."posts.ID = ".$parent_post_id.")";
		}
		
		$query .= " ORDER BY ".$wpdb->prefix."postmeta.meta_value";



		$results = $wpdb->get_results($query);

		if(count($results) < 1) {
			return;
		}

		$use_browser = intval(get_option("mygengo_use_browser"));

		echo $before_widget;
		echo $before_title . $title . $after_title;
		if (current_user_can('edit_posts')) {
			echo '<a href="'.mygengo_menu_page_url('mygengo.phporder', false).'&post_id='.$my_post_id.'">Add a translation</a><br/>';
		}
		echo "<ul>";
		foreach($results as $result) {
			if(!$use_browser && (mygengo_get_browser_language() == mygengo_get_post_language_value($result->ID))) {
				echo "<li><b><a href='".get_permalink($result->ID)."'>".mygengo_get_language_image_src(mygengo_get_post_language_value($result->ID))." ".$result->post_title."</a></b></li>";
			}
			else {
				echo "<li><a href='".get_permalink($result->ID)."'>".mygengo_get_language_image_src(mygengo_get_post_language_value($result->ID))." ".$result->post_title."</a></li>";
			}
		}
		echo "</ul>";
		echo $after_widget;
	}


	function mygengo_widget_other_translation_listings($args) {
		
		global $wp_query;
		global $id;
		global $my_post_id;

		if(!($wp_query->is_single || $wp_query->is_page)) {
			return;
		}

		if(!$my_post_id) {
			return;	
		}

		if((intval(mygengo_get_primarylanguage()) == intval(mygengo_get_post_language_value($my_post_id))) || mygengo_get_post_language_value($my_post_id) == "") {
			return;
		}

		extract($args);
		$title = __('Translations') . " (".mygengo_get_language_name(mygengo_get_post_language_value($my_post_id)).")";

		global $wpdb;

		$query = "SELECT ID, ".$wpdb->prefix."postmeta.meta_key, ".$wpdb->prefix."postmeta.meta_value, post_title FROM ".$wpdb->prefix."posts INNER JOIN ".$wpdb->prefix."postmeta ON (".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id) WHERE 1=1 AND ".$wpdb->prefix."posts.ID != ".$my_post_id." AND ".$wpdb->prefix."posts.post_type IN ('post','page') AND (".$wpdb->prefix."posts.post_status = 'publish') AND ".$wpdb->prefix."postmeta.meta_key = '_post_language_value' AND ".$wpdb->prefix."postmeta.meta_value = '".mygengo_get_post_language_value($my_post_id)."' ORDER BY RAND() LIMIT 10";

		$results = $wpdb->get_results($query);

		if(count($results) < 1) {
			return;
		}

		echo $before_widget;
		echo $before_title . $title . $after_title;
		echo "<ul>";
		foreach($results as $result) {
			echo "<li><a href='".get_permalink($result->ID)."'>".mygengo_get_language_image_src(mygengo_get_post_language_value($result->ID))." ".$result->post_title."</a></li>";
		}
		echo "</ul>";
		echo $after_widget;
	}


	function mygengo_widget_translation_control() {

	}

	function mygengo_widget_other_translation_control() {

	}


	$widget_ops = array('classname' => 'widget_translations', 'description' => __("Translations of current post, from myGengo. Only visible when a translation exists.") );
	wp_register_sidebar_widget('mygengo_translation-posts', __('Translations'), 'mygengo_widget_translation_listings', $widget_ops);
	register_widget_control('mygengo_widget_control1', 'mygengo_widget_translation_control');


	$widget_ops = array('classname' => 'widget_other_translations', 'description' => __( "Other translations in this language. Only visible on translation pages themselves.") );
	wp_register_sidebar_widget('mygengo_translation-other-posts', __('Translations in [language]'), 'mygengo_widget_other_translation_listings', $widget_ops);
	register_widget_control('mygengo_widget_control2', 'mygengo_widget_other_translation_control');
}


add_action('widgets_init', 'mygengo_widget_init');


function mygengo_adjacent_posts_join($join) {
	global $wpdb;
	return $join." INNER JOIN ".$wpdb->prefix."postmeta ON (p.ID = ".$wpdb->prefix."postmeta.post_id)";
}


function mygengo_adjacent_posts_where($where) {

	global $id, $wpdb;

	return $where." AND ".$wpdb->prefix."postmeta.meta_key = '_post_language_value' AND ".$wpdb->prefix."postmeta.meta_value = ".mygengo_get_post_language_value($id);
}


function mygengo_add_language_join($from) {
	return $from;
}


function mygengo_add_language_where($where) {
	global $wpdb;
	global $wp_query;

        if(intval(get_option("mygengo_use_browser")) && !is_single() && !is_admin() && !is_page()) {

           if(mygengo_get_preferred_language() != "" && mygengo_get_preferred_language() != mygengo_get_primarylanguage()) {

			   $post_ID_in_1 = "(SELECT DISTINCT ID FROM ".$wpdb->prefix."posts INNER JOIN ".$wpdb->prefix."postmeta ON (".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id) WHERE 1=1 AND ".$wpdb->prefix."postmeta.meta_key = '_post_language_value' AND ".$wpdb->prefix."postmeta.meta_value = '".mygengo_get_preferred_language()."')";

			   $post_ID_in_2 = "SELECT DISTINCT ".$wpdb->prefix."postmeta.meta_value FROM ".$wpdb->prefix."posts INNER JOIN ".$wpdb->prefix."postmeta ON (".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id) WHERE 1=1 AND ".$wpdb->prefix."postmeta.meta_key = '_post_parent_value' AND ID IN (SELECT DISTINCT ID FROM ".$wpdb->prefix."posts INNER JOIN ".$wpdb->prefix."postmeta ON (".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id) WHERE 1=1 AND ".$wpdb->prefix."postmeta.meta_key = '_post_language_value' AND ".$wpdb->prefix."postmeta.meta_value = '".mygengo_get_preferred_language()."')";

			   $post_ID_in_3 = "SELECT DISTINCT ID FROM ".$wpdb->prefix."posts INNER JOIN ".$wpdb->prefix."postmeta ON (".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id) WHERE 1=1 AND ".$wpdb->prefix."postmeta.meta_key = '_post_language_value' AND ".$wpdb->prefix."postmeta.meta_value != '".get_option("mygengo_primary_language"."'");

			  $where_addition = "";
              
			  $where_addition = "AND (".$wpdb->prefix."posts.ID IN (".$post_ID_in_1.") OR (".$wpdb->prefix."posts.ID NOT IN (".$post_ID_in_2.") AND ".$wpdb->prefix."posts.ID NOT IN (".$post_ID_in_3.")))";

			  return $where." ".$where_addition;

           }
           else {

              return $where." AND (($wpdb->posts.ID NOT IN (SELECT DISTINCT ID FROM ".$wpdb->prefix."posts INNER JOIN ".$wpdb->prefix."postmeta ON (".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id) WHERE 1=1 AND ".$wpdb->prefix."postmeta.meta_key = '_post_language_value' AND ".$wpdb->prefix."postmeta.meta_value != '".mygengo_get_primarylanguage()."')))";
           }
	}
        else {

           return $where;
        }


}

function mygengo_selected_language_filter($notused) {
	add_filter('posts_join', 'mygengo_add_language_join');
	add_filter('posts_where', 'mygengo_add_language_where');
}


?>
