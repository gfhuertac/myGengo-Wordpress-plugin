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
 * Script that contains all the php functions needed by the components
 * of the myGengo plugin.
 *
 * @package myGengo
 */
?>
<?php

//require_once(dirname(__FILE__) . '/../../../wp-load.php');
require_once(dirname(__FILE__) . '/../../../wp-config.php');
require_once(dirname(__FILE__) . '/../../../wp-includes/wp-db.php');
require_once(dirname(__FILE__) . '/../../../wp-includes/pluggable.php');

global $wpdb;
global $wp_admin_url, $wp_admin_dir, $wp_content_url, $wp_content_dir, $wp_plugin_url, $wp_plugin_dir, $wpmu_plugin_url, $wpmu_plugin_dir, $mg_plugin_dir, $mg_plugin_url, $mg_status_list, $table_name1, $table_name2, $table_name3, $table_name4, $table_name5;

if ( ! function_exists( 'is_ssl' ) ) {
 function is_ssl() {
  if ( isset($_SERVER['HTTPS']) ) {
   if ( 'on' == strtolower($_SERVER['HTTPS']) )
    return true;
   if ( '1' == $_SERVER['HTTPS'] )
    return true;
  } elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
   return true;
  }
  return false;
 }
}

if ( version_compare( get_bloginfo( 'version' ) , '3.0' , '<' ) && is_ssl() ) {
 $wp_content_url = str_replace( 'http://' , 'https://' , get_option( 'siteurl' ) );
} else {
 $wp_content_url = get_option( 'siteurl' );
}
$wp_admin_url    = $wp_content_url . '/wp-admin';
$wp_admin_dir    = ABSPATH . 'wp-admin';
$wp_content_url .= '/wp-content';
$wp_content_dir  = ABSPATH . 'wp-content';
$wp_plugin_url   = $wp_content_url . '/plugins';
$wp_plugin_dir   = $wp_content_dir . '/plugins';
$wpmu_plugin_url = $wp_content_url . '/mu-plugins';
$wpmu_plugin_dir = $wp_content_dir . '/mu-plugins';

$mg_plugin_dir   = $wp_plugin_dir . '/' . str_replace(basename( __FILE__),'',plugin_basename(__FILE__));
$mg_plugin_url   = $wp_plugin_url . '/' . str_replace(basename( __FILE__),'',plugin_basename(__FILE__));

$mg_status_list = array('unpaid', 'available', 'pending', 'reviewable', 'approved', 'rejected', 'held', 'canceled');

$table_name1 = $wpdb->prefix . "gengo_languages";
$table_name2 = $wpdb->prefix . "gengo_pairs";
$table_name3 = $wpdb->prefix . "gengo_jobs";
$table_name4 = $wpdb->prefix . "gengo_comments";
$table_name5 = $wpdb->prefix . "gengo_sources";

/** 
 * A function used to check if a json response contains any error.
 * If it does contain an error, then an error with the message and the code is returned.
 * Otherwise, a boolean value of false is returned
 *
 * @since 1.0 
 * 
 * @param a string $response containing the json response from the server
 * @return false if no errors are found, an array with the description of the error otherwise
 */ 
function mygengo_check_error($response) {
	$jsonstatus = $response->{'opstat'};
	if ($jsonstatus == 'error') {
		$jsonerror = $response->{'err'};
		$jsoncode  = $jsonerror->{'code'};
		$jsonmsg   = $jsonerror->{'msg'};
		return array($jsoncode, $jsonmsg);
	}
	return false;
}

/** 
 * A reverse function for the addslashes one defined by PHP
 *
 * @since 1.0 
 * 
 * @param a string $str, in which special characters were escaped using the addslashes function
 * @return the reversed string
 */ 
function mygengo_reverse_escape($str) {
	$search=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	$replace=array("\\","\0","\n","\r","\x1a","'",'"');
	return str_replace($search,$replace,$str);
}

/** 
 * Retrieves the keys for the user to connect to myGengo server.
 * There are two possibilities:
 * - That the keys are the private keys for the user, or
 * - That the keys were defined by the administrator of the blog and she allowed others to use them
 *
 * @since 1.0 
 *  
 * @param an int $level that defines the type of key (0 = public keys only, 1 = any key, 2 = private key only)
 * @return the set of keys for the user
 */ 
function mygengo_getKeys($level = 1) {
	global $current_user, $userdata;

	$keys = array();

	$userid = $current_user->ID;
	if ($level > 0 && get_the_author_meta('mygengo_api_key', $userid) && get_the_author_meta('mygengo_private_key', $userid))  {
		$keys = array('api_key' => get_the_author_meta('mygengo_api_key', $userid),'private_key' => get_the_author_meta('mygengo_private_key', $userid));
	} elseif ($level < 2 && get_option('mygengo_use_admin_key')) {
		if(get_option('mygengo_api_key') && get_option('mygengo_private_key')) {
			$keys = array('api_key' => get_option('mygengo_api_key'), 'private_key' => get_option('mygengo_private_key'));
		}
	}

	return $keys;
}

/** 
 * Obtains the language defined by the user on her preferences. If the language is not
 * defined then the primary language of the blog is used
 *
 * @since 1.0 
 *  
 * @return the code of the primary language
 */ 
function mygengo_get_primarylanguage() {
	global $current_user, $userdata;

	$userid = $current_user->ID;
	if (get_the_author_meta('mygengo_primary_language', $userid)) {
		return get_the_author_meta('mygengo_primary_language', $userid);
	} else {
		return get_option('mygengo_primary_language');

	}
}

/** 
 * Obtains the language used in a post. If the post does not define a language, 
 * then the primary language of the user or the blog is used
 *
 * @since 1.0 
 *  
 * @return the code of the post language
 */ 
function mygengo_get_post_language_value($post_id) {
	$post_language_value = get_post_meta($post_id, "_post_language_value", true);
	
	if($post_language_value != "") {
		return $post_language_value;
	}
	else {
		return mygengo_get_primarylanguage();
	}

}

/** 
 * Obtains the language used in a comment. If the comment does not define a language, 
 * then the language used in the parent post is returned
 *
 * @since 1.0 
 *  
 * @return the code of the comment language
 */ 
function mygengo_get_comment_language_value($comment_id, $post_id=0) {
	$comment_language_value = get_comment_meta($comment_id, "_comment_language_value", true);
	
	if($comment_language_value != "") {
		return $comment_language_value;
	}
	else {
		if ($post_id <= 0) {
			$comment = get_comment($comment_id);
			$post_id = $comment->comment_post_ID;
		}
		return mygengo_get_post_language_value($post_id);
	}

}

/** 
 * Auxiliary function to escape JSON characters to be UTF-8 compatible.
 * Not used in this version.
 *
 * @since 1.0 
 *  
 * @param a string $data with the json data
 * @return a string with the escaped characters
 */
function mygengo_safeJSON_chars($data) { 
	$aux = str_split($data); 
	foreach($aux as $a) { 
		$a1 = urlencode($a); 
		$aa = explode("%", $a1); 
		foreach($aa as $v) { 
			if($v!="") { 
				if(hexdec($v)>127) { 
					$data = str_replace($a,"&#".hexdec($v).";",$data); 
				} 
			} 
		} 
	} 
	return $data; 
}

/** 
 * Synchronizes the languages between the DB and the myGengo server
 *
 * @since 1.0 
 *  
 */
function mygengo_sync_languages() {
	global $wpdb, $mg_plugin_dir, $table_name1, $table_name2;


	require_once ($mg_plugin_dir . '/php/init.php');

	$selected = "";
	if(mygengo_get_primarylanguage() != "") {
		$selected = $wpdb->get_var("SELECT language_code FROM $table_name1 WHERE language_code = '".mygengo_get_primarylanguage()."'");
	}

	$mg_keys = mygengo_getKeys();
	if (count($mg_keys) != 2) {
		return;
	}

	$service = myGengo_Api::factory('service', $mg_keys['api_key'], $mg_keys['private_key']);

	$params = array();
	$params['ts'] = gmdate('U');
	$params['api_key'] = $mg_keys['api_key'];
	ksort($params);
	$query = http_build_query($params);
	$params['api_sig'] = myGengo_Crypto::sign($query, $mg_keys['private_key']);

	$service->getLanguages('json', $params);
	$json = $service->getResponseBody();
	$jsonobject = json_decode($json);
	$jsonerror = mygengo_check_error($jsonobject);
	if ($jsonerror) {
		wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
	}
	$jsonlanguages = $jsonobject->{'response'};

	$wpdb->query("DELETE FROM " . $table_name1);
	foreach ($jsonlanguages as $language) {
		$wpdb->query("INSERT INTO $table_name1 (language_code, language_name, language_localized_name, language_unit_type) VALUES ('".$language->{'lc'}."','".$language->{'language'}."','".$language->{'localized_name'}."','".$language->{'unit_type'}."')");
	}

	if($selected == "") {
		update_option("mygengo_primary_language", 'en');
	} else {
		update_option("mygengo_primary_language", $selected);
	}

	$service->getLanguagePair('json', $params);
	$json          = $service->getResponseBody();
	$jsonobject    = json_decode($json);
	$jsonerror = mygengo_check_error($jsonobject);
	if ($jsonerror) {
		wp_die( 'Error: ' . $jsonerror[0] . ' - ' . $jsonerror[1], 'Error while communicating with myGengo server!' );
	}
	$jsonpairs     = $jsonobject->{'response'};

	$wpdb->query("DELETE FROM " . $table_name2);
	foreach ($jsonpairs  as $pair) {
		$wpdb->query("INSERT INTO $table_name2 (pair_source, pair_target, pair_tier, pair_unit_credit) VALUES ('".$pair->{'lc_src'}."', '".$pair->{'lc_tgt'}."', '".$pair->{'tier'}."', ".$pair->{'unit_price'}.")");
	}
}

/** 
 * Builds and return html code representing a rating system.
 * The amount of options is passed as parameter, as long with the name and a possible default value
 * It assumes that the numbers are integers and the interval between them is 1
 *
 * @since 1.0 
 *  
 * @param a int $amount, the number of options to be presented
 * @param a string $name containing the name of the radio buttons. Defaults to 'mg_rating'
 * @param a default value $selected to mark as selected. The default value is zero (0)
 * @return a string containing the HTML code to be used in a webpage
 */
function mygengo_rating($amount, $name='mg_rating', $selected=0) {
	$code = '';
	for($i=1; $i<=$amount; $i++) {
		$code .= '<input type="radio" name="'.$name.'" value="'.$i.'" ';
		if ($selected == $i) {
			$code .= 'checked ';
		}
		$code .= '/>';
	}
	return $code;
}

/** 
 * Returns the type of a post available at wordpress
 *
 * @since 1.0 
 *  
 * @param an int $post_id that contains the id of the post
 * @return a string that represents the type of the post (one of 'page' or 'post')
 */
function mygengo_get_post_type($post_id) {
	global $wpdb;
	$tp = $wpdb->prefix;

	$query = "SELECT post_type FROM {$tp}posts WHERE ID = {$post_id}";
	return $wpdb->get_var($query);
}

/** 
 * Returns a set of options for a select container.
 * The options are obtained from posts of type 'post' available at wordpress.
 *
 * @since 1.0 
 *  
 * @param a default value $default, used to mark one of the options as selected. Default to zero (0)
 * @param a string $first_option containing a header option. Default to the empty string ('')
 * @return the html code for the options to be used as innerHTML of the select
 */
function mygengo_generate_select_from_posts($default=0,$first_option='') {
	global $wpdb;

	$tp = $wpdb->prefix;

	$query = "SELECT ID, post_title
			FROM {$tp}posts, {$tp}term_relationships, {$tp}term_taxonomy
			WHERE {$tp}posts.ID = {$tp}term_relationships.object_id
			AND {$tp}term_relationships.term_taxonomy_id = {$tp}term_taxonomy.term_taxonomy_id
			AND {$tp}term_taxonomy.taxonomy = 'category' 
			AND post_status = 'publish' 
			AND post_type != 'page' 
			ORDER BY post_title ASC
		";
	$field1 = 'ID';
	$field2 = 'post_title';
	return mygengo_generate_select_from_sqlquery($query, $field1, $field2, $default, $first_option);
}

/** 
 * Returns a set of options for a select container.
 * The options are obtained from posts of type 'page' available at wordpress.
 *
 * @since 1.0 
 *  
 * @param a default value $default, used to mark one of the options as selected. Default to zero (0)
 * @param a string $first_option containing a header option. Default to the empty string ('')
 * @return the html code for the options to be used as innerHTML of the select
 */
function mygengo_generate_select_from_pages($default=0,$first_option='') {
	global $wpdb;

	$tp = $wpdb->prefix;

	$query = "SELECT ID, post_title
			FROM {$tp}posts
			WHERE post_status = 'publish' 
			AND post_type = 'page' 
			ORDER BY post_title ASC
		";
	$field1 = 'ID';
	$field2 = 'post_title';
	return mygengo_generate_select_from_sqlquery($query, $field1, $field2, $default, $first_option);
}

/** 
 * Returns a set of options for a select container.
 * The options are obtained from jobs sent to myGengo by the users in the blog
 *
 * @since 1.0 
 *  
 * @param a default value $default, used to mark one of the options as selected. Default to zero (0)
 * @param a string $first_option containing a header option. Default to the empty string ('')
 * @return the html code for the options to be used as innerHTML of the select
 */
function mygengo_generate_select_from_jobs($default=0,$first_option='') {
	global $wpdb;
	$tp = $wpdb->prefix;

	$query = "SELECT job_id, concat( job_slug, ' (', job_lc_src, '> ', job_lc_tgt, ')' ) AS job_descr
			FROM {$tp}gengo_jobs
			ORDER BY job_id DESC
		";
	$field1 = 'job_id';
	$field2 = 'job_descr';
	return mygengo_generate_select_from_sqlquery($query, $field1, $field2, $default, $first_option);
}

/** 
 * Returns the json representation of data from the wordpress database.
 * The data is obtained from a sql query passed as parameter. 
 *
 * @since 1.0 
 *  
 * @param a string $query containing the query to be executed
 * @return the json code for the elements returned by the query
 */ 
function mygengo_generate_json_from_sqlquery($query) {
	global $wpdb;
	$str_options = $first_option . '\n';

	$options = $wpdb->get_results($query);

	$data = array();
	foreach($options as $option) {
		$data[] = json_encode($option);
	}
	return json_encode($data);
}

/** 
 * Returns a set of options for a select container.
 * The options are obtained from a sql query passed as parameter. The fields that are
 * used as values and texts for the options are also defined in the parameters
 *
 * @since 1.0 
 *  
 * @param a string $query containing the query to be executed
 * @param a string $field1 representing the field from the query that will be used as the value for each option
 * @param a string $field2 representing the field from the query that will be used as the text for each option
 * @param a default value $default, used to mark one of the options as selected. Default to zero (0)
 * @param a string $first_option containing a header option. Default to the empty string ('')
 * @return the html code for the options to be used as innerHTML of the select
 */ 
function mygengo_generate_select_from_sqlquery($query, $field1, $field2, $default=0, $first_option='') {
        global $wpdb;
        $str_options = $first_option . '\n';

        $options = $wpdb->get_results($query);

        foreach($options as $option) {
                if($default == $option->$field1) {
                        $str_options .= '<option value="'.$option->$field1.'" selected="selected">'.$option->$field2.'</option>\n';
                }
                else {
                        $str_options .= '<option value="'.$option->$field1.'">'.$option->$field2.'</option>\n';
                }
        }

        return $str_options;
}

/** 
 * Returns a set of options for a select container.
 * The options are obtained from the set of status defined by myGengo
 * (Either "unpaid", "available", "pending", "reviewable", "revising", "held", "approved", or "cancelled".)
 *
 * @since 1.0 
 *  
 * @param a default value $default, used to mark one of the options as selected. Default to zero (0)
 * @param a string $first_option containing a header option. Default to the empty string ('')
 * @return the html code for the options to be used as innerHTML of the select
 */ 
function mygengo_generate_select_from_status($default=0, $first_option='') {
	global $wpdb, $mg_status_list;

	$str_options = $first_option . '\n';

	foreach($mg_status_list as $option) {
		if($default == $option) {
			$str_options .= '<option value="'.$option.'" selected="selected">'.$option.'</option>\n';
		}
		else {
			$str_options .= '<option value="'.$option.'">'.$option.'</option>\n';
		}
	}

	return $str_options;
}

/** 
 * Returns a set of options for a select container.
 * The options are obtained from groups of jobs sent together to the myGengo server
 *
 * @since 1.0 
 *  
 * @param a default value $default, used to mark one of the options as selected. Default to zero (0)
 * @param a string $first_option containing a header option. Default to the empty string ('')
 * @return the html code for the options to be used as innerHTML of the select
 */ 
function mygengo_generate_select_from_groups($default=0, $first_option='') {
	global $wpdb, $table_name3;

	$query = "SELECT DISTINCT(job_group_id), CONCAT('group ', job_group_id) as group_id
			FROM {$table_name3}
			WHERE job_group_id IS NOT NULL
			  AND job_group_id > 0
			ORDER BY job_group_id DESC
		";
	$field1 = 'job_group_id';
	$field2 = 'group_id';
	return mygengo_generate_select_from_sqlquery($query, $field1, $field2, $default, $first_option);
}

/** 
 * Gets the language name (not localized from the DB).
 *
 * @since 1.0 
 *  
 * @param a string $code that contains the ISO code for the language. Default to a null string.
 * @return the language name of the language defined in the parameter $code, or the name of the primary language for the user if $code is NULL
 */ 
function mygengo_get_language_name($code = NULL) {
	global $wpdb, $table_name1;

	if (!is_null($code)) {
		return $wpdb->get_var("SELECT language_name FROM ".$table_name1." WHERE language_code = '".$code."'");
	} else {
		return $wpdb->get_var("SELECT language_name FROM ".$table_name1." WHERE language_code = '".mygengo_get_primarylanguage()."'");
	}

}

/** 
 * Returns the url for a language image.
 *
 * @since 1.0 
 *  
 * @param a string $code that contains the ISO code for the language. Default to a null string.
 * @return the url of the image for the language
 */ 
function mygengo_get_language_image_src($code = NULL) {
	global $wpdb, $mg_plugin_url, $table_name1, $table_name2;

	if (!is_null($code)) {
		$language_code = $code;
	} else {
		$language_code = mygengo_get_primarylanguage();
	}
	$language_name = $wpdb->get_var("SELECT language_name FROM ".$table_name1." WHERE language_code = '".$language_code."'");

	return '<img src="'.$mg_plugin_url."/images/flag_icons/".$language_code.'.png" title="'.$language_name.'" alt="'.$language_name.'" />';
}

/** 
 * Obtains the language preffered by the user based on the browser. If the language is not
 * on the list of languages accepted by myGengo then the primary language of the user or the
 * blog is used
 *
 * @since 1.0 
 *  
 * @return the code of the preffered language
 */ 
function mygengo_get_preferred_language() {
	$use_browser = intval(get_option("mygengo_use_browser"));

	if($use_browser) {
		$accepted_languages = mygengo_get_languages('data');

		foreach($accepted_languages as $accepted_language) {
			$select_language = trim($accepted_language[1]);
			if($select_language != '') {
				return strval($select_language);
			}
		}
	}

	//if accepted languages are not in languages table, just return primary language
	$select_language = mygengo_get_primarylanguage();
	return strval($select_language);
}

/** 
 * Returns the language defined at the webbrowser used by the user
 *
 * @since 1.0 
 *  
 * @return the code of the browser language if present in the accepted languages from myGengo. The default language otherwsie.
 */ 
function mygengo_get_browser_language() {
	$accepted_languages = mygengo_get_languages('data');

	foreach($accepted_languages as $accepted_language) {
		$select_language = trim($accepted_language[1]);
		if($select_language != '') {
			return strval($select_language);
		}
	}
	
	return NULL;
}

/** 
 * Parses text into an associate array representing a post.
 * It splits the texts according to three sections, title, content and excerpt.
 * if the title boundary does not exists, the text is returned as a single 
 * content without title and excerpt.
 *
 * @since 1.0 
 *  
 * @param a string $content containing the text to be parsed
 * @return an associate array with keys post_title, post_content and post_excerpt
 */ 
function mygengo_parse_content($content) {
	$content = str_replace('[[[ ','[[[', $content); //to fix machine translation error
	$title_meta = '[[[post_title__]]]';
	$content_meta = '[[[post_content__]]]';
	$excerpt_meta = '[[[post_excerpt__]]]';

	$use_mb = false;

	$title_pos = ($use_mb)?mb_stripos($content, $title_meta,'UTF-8'):stripos($content, $title_meta);
	if ($title_pos === FALSE) {
		return array('post_title' => '', 'post_content' => str_replace(array('[[[',']]]'), array('<','>'), $content), 'post_excerpt' => '');
	}
	$title_pos   += strlen($title_meta);
	$content_pos = ($use_mb)?mb_stripos($content, $content_meta, 'UTF-8'):stripos($content, $content_meta);
	$ptitle = ($use_mb)?mb_substr($content, $title_pos, $content_pos - $title_pos, 'UTF-8'):substr($content, $title_pos, $content_pos - $title_pos);

	$content_pos += strlen($content_meta);
	$excerpt_pos = ($use_mb)?mb_stripos($content, $excerpt_meta, 'UTF-8'):stripos($content, $excerpt_meta);
	$pcontent = ($use_mb)?mb_substr($content, $content_pos, $excerpt_pos - $content_pos, 'UTF-8'):substr($content, $content_pos, $excerpt_pos - $content_pos);

	$excerpt_pos += strlen($excerpt_meta);
	$pexcerpt = ($use_mb)?mb_substr($content, $excerpt_pos, 'UTF-8'):substr($content, $excerpt_pos);

	return array('post_title' => str_replace(array('[[[',']]]'), array('<','>'), $ptitle), 'post_content' => str_replace(array('[[[',']]]'), array('<','>'), $pcontent), 'post_excerpt' => str_replace(array('[[[',']]]'), array('<','>'), $pexcerpt));
}

/** 
 * Parses text into an array of comments.
 * It splits the texts according to a predefined boundary. 
 * if the boundary does not exists, the text is returned as a single 
 * element inside an array.
 *
 * @since 1.0 
 *  
 * @param a string $content containing the text to be parsed
 * @return an array $comments of text
 */ 
function mygengo_parse_comments($content) {
	$content = str_replace('[[[ ','[[[', $content);
	$comment_meta = '[[[post_comment__]]]';
	$comments = array();
	$next_post = 0;
	$comment_pos = stripos($content, $comment_meta, $next_pos);
	if ($comment_pos === FALSE) {
		$comment_pos = stripos($content, $comment_meta, $next_pos);
		if ($comment_pos === FALSE) {
			return array(str_replace(array('[[[',']]]'), array('<','>'),$content));
		}
	}
	$comment_pos += strlen($comment_meta);
	$next_pos     = stripos($content, $comment_meta, $comment_pos);
	while($next_pos !== FALSE) {
		$comments[]   = str_replace(array('[[[',']]]'), array('<','>'),substr($content, $comment_pos, $next_pos - $comment_pos));
		$comment_pos  = $next_pos + strlen($comment_meta);
		$next_pos     = stripos($content, $comment_meta, $comment_pos);
	}
	$comments[] = str_replace(array('[[[',']]]'), array('<','>'), substr($content, $comment_pos));

	return $comments;
}

/** 
 * HTML representation of the editor to create acknowledgements that will be used at the bottom
 * of translated texts, if the user wants to.
 *
 * @since 1.0 
 *  
 * @param a string $current_ack containing the current acknowledgement. Defaults to the empty string ('').
 */ 
function mygengo_acknowledgement_editor($current_ack = '') {
	global $mg_plugin_url;
?>
			<textarea id="mg_footer" name="mg_footer" cols="45" rows="4"><?php echo mygengo_reverse_escape($current_ack); ?></textarea>
			<p><?php _e('You may use one of the following images'); ?>:
				<div>
					<img onclick="add_ack(document.getElementById('mg_footer'), this.src)" src="<?php echo $mg_plugin_url;?>images/mg/mygengo_100x36_white_s1.png" alt="" />
					<img onclick="add_ack(document.getElementById('mg_footer'), this.src)" src="<?php echo $mg_plugin_url;?>images/mg/mygengo_pb_232x36_white_s1.png" alt="" />
					<img onclick="add_ack(document.getElementById('mg_footer'), this.src)" src="<?php echo $mg_plugin_url;?>images/mg/mygengo_pb_150x54_white_s1.png" alt="" />  
				</div>
				<div>
					<img onclick="add_ack(document.getElementById('mg_footer'), this.src)" src="<?php echo $mg_plugin_url;?>images/mg/mygengo_pb_100x36_light_s1.png" alt="" />  
					<img onclick="add_ack(document.getElementById('mg_footer'), this.src)" src="<?php echo $mg_plugin_url;?>images/mg/mygengo_pb_232x36_light_s1.png" alt="" />
					<img onclick="add_ack(document.getElementById('mg_footer'), this.src)" src="<?php echo $mg_plugin_url;?>images/mg/mygengo_pb_150x54_light_s1.png" alt="" />
				</div>
				<div>
					<img onclick="add_ack(document.getElementById('mg_footer'), this.src)" src="<?php echo $mg_plugin_url;?>images/mg/mygengo_pb_100x36_blue_s1.png" alt="" />
					<img onclick="add_ack(document.getElementById('mg_footer'), this.src)" src="<?php echo $mg_plugin_url;?>images/mg/mygengo_pb_232x36_blue_s1.png" alt="" /> 
					<img onclick="add_ack(document.getElementById('mg_footer'), this.src)" src="<?php echo $mg_plugin_url;?>images/mg/mygengo_pb_150x54_blue_s1.png" alt="" />   
				</div>
			</p>
			<p class="form-allowed-tags"><?php _e('You may use these HTML tags and attributes'); ?>:  <code>&lt;a href=&quot;&quot; title=&quot;&quot;&gt; &lt;abbr title=&quot;&quot;&gt; &lt;acronym title=&quot;&quot;&gt; &lt;b&gt; &lt;blockquote cite=&quot;&quot;&gt; &lt;cite&gt; &lt;code&gt; &lt;del datetime=&quot;&quot;&gt; &lt;em&gt; &lt;i&gt; &lt;q cite=&quot;&quot;&gt; &lt;strike&gt; &lt;strong&gt; </code></p>
<?php
}

/** 
 * Counts the number of units in a post. The unit type is obtained from the language defined
 * in the post. If no language is defined, the default language is used.
 *
 * @since 1.0 
 *  
 * @param a int $post_id, the id of the post that hosts the comment. It must be greater than 0.
 * @return an int $count that contains the size of the post based on the unit type
 */ 
function mygengo_post_word_count($post_id = 0) {
	if ($post_id <= 0) {
		return 0;
	}

	global $wpdb;
	$table_name1 = $wpdb->prefix . "gengo_languages";
	
	$lang = get_post_meta($post_id, '_post_language', true);
	if ($lang == '') {
	       	$lang = mygengo_get_primarylanguage();
	}

	$unit_type = $wpdb->get_var("SELECT language_unit_type FROM ".$table_name1." WHERE language_code = '".$lang."'");
	if ($unit_type == '') {
		$unit_type = 'word';
	}

	$query = "SELECT post_title, post_content, post_excerpt FROM $wpdb->posts WHERE ID = $post_id";
	$words = $wpdb->get_row($query, ARRAY_N);

	return mygengo_word_count($words, $unit_type);
}

/** 
 * Counts the number of units in a comment. The unit type is obtained from the language defined
 * in the post that contains the comment. If no language is defined, the default language is used.
 *
 * @since 1.0 
 *  
 * @param a int $post_id, the id of the post that hosts the comment. It must be greater than 0.
 * @param a string $comment, the text to be translated. Default is an empty string ('')
 * @return an int $count that contains the size of the comment based on the unit type
 */ 
function mygengo_comment_word_count($post_id = 0, $comment = '') {
	if ($post_id <= 0) {
		return 0;
	}

	global $wpdb;
	$table_name1 = $wpdb->prefix . "gengo_languages";
	
	$lang = get_post_meta($post_id, '_post_language', true);
	if ($lang == '') {
	       	$lang = mygengo_get_primarylanguage();
	}

	$unit_type = $wpdb->get_var("SELECT language_unit_type FROM ".$table_name1." WHERE language_code = '".$lang."'");
	if ($unit_type == '') {
		$unit_type = 'word';
	}

	return mygengo_word_count($comment, $unit_type);
}

/** 
 * Counts the number of units in a text or an array of texts, based on the unit_type
 *
 * @since 1.0 
 *  
 * @param a string $text, the text to be translated. Default is an empty string ('')
 * @param a string $unit_type that identifies how the length should be counted. Defaults to 'word'
 * @return an int $count that contains the size of the text(s) to be translated based on the unit type
 */ 
function mygengo_word_count($words = '', $unit_type = 'word') {
	$totalcount = 0;
	if ($words) {
		if (is_array($words)) {
			foreach ($words as $word) {
				$totalcount += mygengo_count($word, $unit_type);
			}
		} else {
			$totalcount = mygengo_count($words, $unit_type);
		}
	}
	return $totalcount;
}

/** 
 * Counts the number of units in a text, based on the unit_type
 *
 * @since 1.0 
 *  
 * @param a string $text, the text to be translated. Default is an empty string ('')
 * @param a string $unit_type that identifies how the length should be counted. Defaults to 'word'
 * @return an int $count that contains the size of the text to be translated based on the unit type
 */ 
function mygengo_count($text = '', $unit_type = 'word') {
	$count = 0;
	$text = strip_tags($text);
	$text = explode(' ', $text);
	if ($unit_type == 'word') {
		$count = count($text);
	} else {
		foreach($text as $phrase) {
			$count += mb_strlen($phrase);
		}
	}
	return $count;
}

/** 
 * Reads a post from the database and filters the results to be compatible with the 
 * myGengo format for jobs (basically it replaces the start and end tags of html
 * by triple-brackets so the content is not translated)
 *
 * @since 1.0 
 *  
 * @param int $post_id the id of the post that will be retrieved from the DB
 * @return an array $rv containing the title, content and excerpt of the post
 */ 
function mygengo_post($post_id = 0) {
	global $wpdb;

	$query = "SELECT post_title, post_content, post_excerpt FROM $wpdb->posts WHERE ID = $post_id";
	$rv = $wpdb->get_row($query, ARRAY_A);
	foreach($rv as $key=> $value) {
		$value = str_replace(array('<','>'), array('[[[',']]]'), $value);
		$rv[$key] = $value;
	}
	return $rv;
}

/** 
 * Reads a job from the database and filters the results to be compatible with  
 * wordpress format for posts (basically it replaces the triple-brackets bach to html start and end tags)
 *
 * @since 1.0 
 *  
 * @param int $job_id the id of the job that will be retrieved from the DB
 * @param int $post_id the id of the post that was used as content for the job
 * @param int $post_type the type of the post that was used as content for the job
 * @return an array $rv containing the title, content and excerpt of the post
 */ 
function mygengo_job_status($job_id = 0, $post_id = 0, $post_type = 'post', $lc = null) {
	if ($job_id == 0 && $post_id == 0) {
		return '';
	}
	$where = '';
	if (!is_null($lc)) {
		$where = " AND job_lc_tgt = '{$lc}'";
	}

	global $wpdb, $table_name3;

	if ($job_id == 0) {
		$query = "SELECT job_lc_tgt, job_status FROM {$table_name3} WHERE job_id = {$job_id}".$where;
	} else {
		$query = "SELECT job_lc_tgt, job_status FROM {$table_name3} WHERE job_post_id = {$post_id} AND job_post_type = '{$post_type}'".$where;
	}
	$rv = $wpdb->get_results($query);
	foreach($rv as $row) {
		$value = str_replace(array('[[[',']]]'), array('<','>'), $rv[$row->job_body_tgt]);
		$rv[$row->job_lc_tgt] = $value;
	}
	return $rv;
}

/** 
 * Reads a job from the database and filters the results to be compatible with  
 * wordpress format for posts (basically it replaces the triple-brackets bach to html start and end tags)
 *
 * @since 1.0 
 *  
 * @param int $job_id the id of the job that will be retrieved from the DB
 * @param int $post_id the id of the post that was used as content for the job
 * @param int $post_type the type of the post that was used as content for the job
 * @return an array $rv containing the title, content and excerpt of the post
 */ 
function mygengo_job_text($job_id = 0, $post_id = 0, $post_type = 'post') {
	if ($job_id == 0 && $post_id == 0) {
		return '';
	}

	global $wpdb, $table_name3;

	if ($job_id == 0) {
		$query = "SELECT job_lc_tgt, job_body_tgt FROM {$table_name3} WHERE job_id = {$job_id}";
	} else {
		$query = "SELECT job_lc_tgt, job_body_tgt FROM {$table_name3} WHERE job_post_id = {$post_id} AND job_post_type = '{$post_type}'";
	}
	$rv = $wpdb->get_results($query);
	foreach($rv as $row) {
		$value = str_replace(array('[[[',']]]'), array('<','>'), $rv[$row->job_body_tgt]);
		$rv[$row->job_lc_tgt] = $value;
	}
	return $rv;
}

/** 
 * Refresh the contents of a job from the myGengo server into the database
 * It is used when the jobs are read at the listing page and then after 5 minutes had passed 
 *  
 * @since 1.0 
 *  
 * @param int $job_id the id of the job to refresh
 */ 
function mygengo_refresh_job($job_id) {
	global $wpdb, $mg_plugin_dir, $table_name3, $current_user, $userdata;

	require_once ($mg_plugin_dir . '/php/init.php');
        $mg_public_keys  = mygengo_getkeys(0);
        $mg_private_keys = mygengo_getkeys(2);
        if (count($mg_private_keys)==2) {
		$sql_userid = $current_user->ID;
		$mg_keys = $mg_private_keys;
        } elseif (count($mg_public_keys)==2) {
		$sql_userid = 0;
		$mg_keys = $mg_public_keys;
        }

	$job_client = myGengo_Api::factory('job', $mg_keys['api_key'], $mg_keys['private_key']);

	$params = array();
	$params['ts'] = gmdate('U');
	$params['api_key'] = $mg_keys['api_key'];
	ksort($params);
	$query = http_build_query($params);
	$params['api_sig'] = myGengo_Crypto::sign($query, $mg_keys['private_key']);

	$job_client->getJob($job_id, 'json', $params);
	$json         = $job_client->getResponseBody();
	$jsonobject   = json_decode($json);

	$jsonerror = mygengo_check_error($jsonobject);
	if ($jsonerror) {
		return $json;
		exit();
	}
	$jsonresponse = $jsonobject->{'response'};
	$jsonjob      = $jsonresponse->{'job'};

	$job_slug       = $jsonjob->{'slug'};
	$job_body_src   = $jsonjob->{'body_src'};
	$job_body_tgt   = $jsonjob->{'body_tgt'};
	if (is_null($jsonjob->{'body_tgt'})) {
		$job_body_tgt = 'NULL';
		$job_body_tgt_set = '';
	} else {
		$job_body_tgt = "'".addslashes($jsonjob->{'body_tgt'})."'";
		$job_body_tgt_set = 'job_body_tgt ='.$job_body_tgt.', ';
	}
	$job_captcha_url = $jsonjob->{'captcha_url'};
	if (is_null($job_captcha_url)) {
		$job_captcha_url = 'NULL';
		$job_captcha_url_set = '';
	} else {
		$job_captcha_url = "'".$jsonjob->{'captcha_url'}."'";
		$job_captcha_url_set = 'job_captcha_url ='.$job_captcha_url.', ';
	}

	$job_lc_src     = $jsonjob->{'lc_src'};
	$job_lc_tgt     = $jsonjob->{'lc_tgt'};
	$job_unit_count = $jsonjob->{'unit_count'};
	$job_tier       = $jsonjob->{'tier'};
	$job_credits    = $jsonjob->{'credits'};
	$job_status     = $jsonjob->{'status'};
	$job_eta        = $jsonjob->{'eta'};
	$job_ctime      = $jsonjob->{'ctime'};
	$job_modified   = $jsonjob->{'ctime'};
	$job_user_id    = $sql_userid;
	$job_post_id    = 0;

	$exists = $wpdb->get_var("SELECT COUNT(*) FROM ".$table_name3." WHERE job_id = ".$job_id."");
	if (is_null($exists) || !$exists) {
		$sql = "INSERT INTO ".$table_name3." (job_id,job_slug,job_body_src,job_body_tgt,job_lc_src,job_lc_tgt,job_tier,job_unit_count,job_credits,job_status,job_ctime,job_modified,job_captcha_url,job_user_id,job_post_id) VALUES (".$job_id.",'".addslashes($job_slug)."','".addslashes($job_body_src)."',".$job_body_tgt.",'".$job_lc_src."','".$job_lc_tgt."','".$job_tier."',".$job_unit_count.",".$job_credits.",'".$job_status."',".$job_ctime.",".$job_modified.",".$job_captcha_url.",".$job_user_id.",".$job_post_id.")";
	} else {
		$sql = "UPDATE ".$table_name3." SET " . $job_body_tgt_set . $job_captcha_url_set. "job_unit_count = ".$job_unit_count.", job_credits = ".$job_credits.", job_status = '".$job_status."' WHERE job_id = ".$job_id."";
	}
	if ($wpdb->query($sql) !== FALSE) {
		set_transient('mygengo_job_id_' . $job_id, true, 300);
	}
	return false;
}

/** 
 * Original code from wordpress core. Modified to suit the plugin.
 * Get the url to access a particular menu page based on the slug it was registered with. 
 *  
 * If the slug hasn't been registered properly no url will be returned 
 *  
 * @since 1.0 
 *  
 * @param string $menu_slug The slug name to refer to this menu by (should be unique for this menu) 
 * @param bool $echo Whether or not to echo the url - default is true 
 * @return string the url 
 */ 
function mygengo_menu_page_url($menu_slug, $echo = true, $relative = false) { 
	global $_parent_pages; 
		 
	if ( isset( $_parent_pages[$menu_slug] ) ) { 
		if ( $_parent_pages[$menu_slug] && $relative) { 
			$url = admin_url($_parent_pages[$menu_slug] . '?page=' . $menu_slug); 
		} else { 
			$url = admin_url('admin.php?page=' . $menu_slug); 
		} 
	} else { 
		$url = ''; 
	} 
	 
	$url = esc_url($url); 
	 
	if ( $echo ) 
		echo $url; 
	 
	return $url; 
} 

/**
 * Code from http://www.bin-co.com/php/scripts/misc/getlink/
 * Create a link by joining the given URL and the parameters given as the second argument.
 * Example : 
 *	    getLink("http://www.google.com/search",array("q"=>"binny","hello"=>"world","results"=>10));
 *		    will return
 *	    http://www.google.com/search?q=binny&amp;hello=world&amp;results=10
 *
 * @param string $url - The base url.
 * @param array $params - An array containing all the parameters and their values 
 * @return string the new url
 * 
 */
function mygengo_getLink($url,$params=array(),$use_existing_arguments=false) {
    global $_GET;
    if($use_existing_arguments) $params = $params + $_GET;
    if(!$params) return $url;
    $link = $url;
    if(strpos($link,'?') === false) $link .= '?'; //If there is no '?' add one at the end
    elseif(!preg_match('/(\?|\&(amp;)?)$/',$link)) $link .= '&amp;'; //If there is no '&' at the END, add one.
    
    $params_arr = array();
    foreach($params as $key=>$value) {
	if(gettype($value) == 'array') { //Handle array data properly
	    foreach($value as $val) {
		$params_arr[] = $key . '[]=' . urlencode($val);
	    }
	} else {
	    $params_arr[] = $key . '=' . urlencode($value);
	}
    }
    $link .= implode('&amp;',$params_arr);
    
    return $link;
} 

include_once('php_language_detection.php');
include_once('php_load.php');
include_once('mygengo-textsources.php');
?>
