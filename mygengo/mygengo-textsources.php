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

require_once(dirname(__FILE__) . '/mygengo-common.php'); 

global $postts_id, $pagets_id, $commentts_id;

function mygengo_register_textsource($classname) {
	global $wpdb, $table_name5;

	$cnt = $wpdb->get_var("SELECT ts_id FROM {$table_name5} WHERE ts_classname = '{$classname}'");
	if (!$cnt) {
		$sql = "INSERT INTO {$table_name5} (ts_classname) VALUES ('{$classname}')";
		if ($wpdb->query($sql) === FALSE) {
			wp_die(__('Error: could not register new text source' . $sql), __('Error: could not register new text source'));
		}
		$cnt = $wpdb->get_var("SELECT ts_id FROM {$table_name5} WHERE ts_classname = '{$classname}'");
	}
	return $cnt;
}

function mygengo_get_textsources() {
	global $wpdb, $table_name5;

	$sources = $wpdb->get_results("SELECT ts_id, ts_classname FROM {$table_name5}");
	$ts = array();
	if ($sources) :
		foreach ($sources as $source) :
			eval("\$tsinstance = new {$source->ts_classname}({$source->ts_id});");
			$ts[] = $tsinstance;
		endforeach;
	endif;

	return $ts;
}

abstract class TextSource {  
	protected $internalId;  

	abstract public function accept($post_type);
	abstract public function retrieveFormElements();
	abstract public function getAssignedTo();
	abstract public function getTextToTranslate($requestvars);  
	abstract public function getPrimaryLanguage();
	abstract public function getWordcount($unit);
	abstract public function retrievePublishableAs($jobid);  
	abstract public function publishAs($jobid, $requestvars);  

	public function __construct($internalId) {  
		$this->internalId = $internalId;  
	} 

	public function getInternalId() {  
		return $this->internalId;  
	}  

	public function getAssignFormField() {
		$elements = $this->retrieveFormElements();
		if (!$elements) { return ''; }

		$html     = '<div class="mg_css_assign">' . $elements['label'];
		$q        = 'document.location=document.location+\'&mg_post_type='.$this->type.'\'';
		$names    = $elements['names'];
		foreach($elements['elements'] as $key => $element) {
			$q    .= '+getValue(\'' . $key . '\',\'' . $names[$key] . '\')';
			$html .= $element;
		}
		$html .= '<input type="hidden" name="mg_'.$this->type.'_type" id="mg_'.$this->type.'_type" value="'.$this->type.'" />';
		$q    .= '+getValue(\'mg_' . $this->type . '_type\',\'mg_post_type\')';
		$html .= '<input type="button" value="'. __('Assign').'" onclick="' . $q . ';" /></div>';
		return $html;
	}

	public function getPublishableFormField($jobid) {
		$html = '<div class="mg_css_assign">' . $elements['label'];
		$html .= $this->retrievePublishableAs($jobid);
		$html .= '</div>';
		return $html;
	}
}  

class DummyTextSource extends TextSource {  
	protected $primarylang = 'en';  
	protected $type        = 'post';  
	protected $post_id     = 0;  


	public function __construct($internalId) {  
		$this->internalId = $internalId;  
		$primarylang = mygengo_get_primarylanguage();
	} 

	public function getInternalId() {  
		return $this->internalId;  
	}  

	public function accept($post_type) { return true; }
	public function getAssignFormField() { return ''; }
	public function retrieveFormElements() { return ''; }

	public function getAssignedTo() {
		$this->post_id = $_REQUEST['mg_post_id'];
		$this->type = $_REQUEST['mg_post_type'];
		return  __('Assigned to ').$this->type.' ID '.$this->post_id. '<input type="hidden" id="mg_post_id" name="mg_post_id" value="' . $this->post_id . '" /><input type="hidden" id="mg_post_type" name="mg_post_type" value="' . $this->type . '" />';
	}  

	public function getTextToTranslate($requestvars) { 
		$this->post_id = $requestvars['mg_post_id'];
		$this->type = $requestvars['mg_post_type'];
		$texts = array();
		if (isset($requestvars['mg_ttt'])) {
			$texts[__('Your title')] = base64_decode($requestvars['mg_ttt']); 
		} else {
			$texts[__('Your title')] = '';
		}
		return $texts;
	}

	public function getPrimaryLanguage() {return $this->primarylang; }
	public function getWordcount($unit) { return 0; }
	public function retrievePublishableAs($jobid) { return ''; }
	public function publishAs($jobid, $requestvars) { return ''; }
	public function getPublishableFormField($jobid) { return ''; }
}  

abstract class BlogTextSource extends TextSource {  
	protected $primarylang = 'en';  
	protected $wordcount   = 0;  
	protected $type        = 'post';  
	protected $post_id     = 0;  

	public function __construct($internalId) {
		parent::__construct($internalId);
		$primarylang = mygengo_get_primarylanguage();
	}

	public function retrieveFormElements()  {
		eval("\$mg_select = mygengo_generate_select_from_{$this->type}s();");
		$mg_post_id = '<select name="mg_'.$this->type.'_id" id="mg_'.$this->type.'_id" style="width:300px;"><option value="0">[' . __('Select') . ']</option>' . $mg_select . '</select>';
		$mg_add_post_comments = '<label for="mg_add_'.$this->type.'_comments"><input type="checkbox" id="mg_add_'.$this->type.'_comments" name="mg_add_'.$this->type.'_comments" value="Y"/>' . __('Add comments') . '</label>';
	
		$elements = array('label'    => __('Insert job text from ') . __($this->type),
			  'elements' => array ('mg_'.$this->type.'_id' => $mg_post_id,  'mg_add_'.$this->type.'_comments' => $mg_add_post_comments),
			  'names'    => array ('mg_'.$this->type.'_id' => 'mg_post_id', 'mg_add_'.$this->type.'_comments' => 'add_comments')
			 );
		return $elements;
	}

	public function getAssignedTo() {
		return  __('Assigned to ').mygengo_get_post_type($this->post_id).' ID '.$this->post_id. '<input type="hidden" id="mg_post_id" name="mg_post_id" value="' . $this->post_id . '" /><input type="hidden" id="mg_post_type" name="mg_post_type" value="' . mygengo_get_post_type($this->post_id) . '" />';
	}  

	public function getTextToTranslate($requestvars) {
		$this->post_id = $post_id = isset($requestvars['mg_post_id'])?$requestvars['mg_post_id']:0;
		if ($post_id == 0) {
			return array();
		}

		$texts = array();
		$this->primarylang = mygengo_get_post_language_value($post_id);
		$this->wordcount   = mygengo_post_word_count($post_id);
		$job_title='';
		$job_text='';
		$job_comments = '';

		$post_data = mygengo_post($post_id);
		if ($post_data) {
			$job_title=$post_data['post_title'];
			foreach($post_data as $key=>$value) {
				$job_text .= "\r\n[[[".$key . "__]]]\r\n";
				$job_text .= $value . "\r\n";
			}
			$texts[$job_title] = $job_text;
			if (isset($requestvars['add_comments'])) {
				$comments = get_comments('post_id='.$post_id);
				if (count($comments) > 0) {
					foreach($comments as $comment) :
						$content       = str_replace(array('<','>'), array('[[[',']]]'), $comment->comment_content);
						$job_comments .= "\r\n[[[post_comment__]]]\r\n" . ($content). "\r\n";
						$this->wordcount += mygengo_comment_word_count($post_id, $content);
					endforeach;
					$texts[$job_title . ' comments'] = $job_comments;
				}
			}
		}
		return $texts;
	}

	public  function getPrimaryLanguage() {
		return $this->primarylang;
	}

	public  function getWordcount($unit) {
		return $this->wordcount;
	}

	public function retrievePublishableAs($jobid) {  
		global $wpdb;
		$table_name3 = $wpdb->prefix . 'gengo_jobs';
		$job_post_id = $wpdb->get_var("SELECT job_post_id FROM ".$table_name3." WHERE job_id = ".$jobid);
		if (!$job_post_id) { $job_post_id = 0; }

		eval("\$mg_select = mygengo_generate_select_from_{$this->type}s({$job_post_id}, '<option value=\"\">[Select]</option>');");
		$html  = __('Publish inside '.$this->type);
		$html .= '<select name="mg_'.$this->type.'">' . $mg_select . '</select><br/>';
		$html .= '<label><input type="radio" name="mg_publish_as" value="' . $this->internalId . '-translate" /><span class="checkbox-title">' . __('as translation') . '</span></label> <br/>';
		$html .= '<label><input type="radio" name="mg_publish_as" value="' . $this->internalId . '-comment" /><span class="checkbox-title">' . __('as comments') . '</span></label> <br/>';
		return $html;
	}  

	public function publishAs($jobid, $requestvars) { 
		list($id, $publish_as)  = split("-", $requestvars['mg_publish_as']);
		if (intval($id) != $this->getInternalId()) {
			wp_die( 'Error: accessing a text source with different id.', 'Error while creating the new post!' );
		}

		global $wpdb, $wp_admin_url;
		$table_name3 = $wpdb->prefix . 'gengo_jobs';
		$job_body    = $wpdb->get_var("SELECT job_body_tgt FROM ".$table_name3." WHERE job_id = ".$jobid);

		$post_parent = $requestvars['mg_'.$this->type];
		$post_type   = $this->type;
		$categories  = get_the_category($post_parent);
		$post_cats   = array();
		foreach($categories as $cat) {
			$post_cats[] = $cat->ID;
		}
 
		$userid       = $requestvars['mg_user_id'];
		$post_author  = (get_option('mygengo_use_mygengouser'))?get_option('mygengo_translator_id'):$userid;
		if (count(mygengo_getKeys(2)) == 2 && get_the_author_meta('mygengo_add_footer', $userid)) {
			$post_footer   = '<div>'.get_the_author_meta('mygengo_footer').'</div>';
		} elseif (count(mygengo_getKeys(2)) != 2 && get_option('mygengo_add_footer')) {
			$post_footer   = '<div>'.get_option('mygengo_footer').'</div>';
		} else {
			$post_footer   = '';
		}
		$post_language = $requestvars['mg_tgt_language'];

		if ($publish_as == 'translate') {
			$post_sections = mygengo_parse_content($job_body);
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

			$post_id = wp_insert_post( $mg_post, true );
			if (is_wp_error($post_id)) {
				wp_die( 'Error: could not create a post based on this content. Reason: ' . $post_id->get_error_message(), 'Error while creating the new post!' );
			} else {
				add_post_meta($post_id, '_post_language_value', $post_language, true);
				add_post_meta($post_id, '_post_parent_value', $post_parent, true);
				wp_redirect($wp_admin_url . '/post.php?post=' . $post_id .'&action=edit');
			}
		} elseif ($publish_as == 'comment') {
			$comments = mygengo_parse_comments($job_body);
			foreach($comments as $comment) {
				$time = current_time('mysql');
				$data = array(
					'comment_post_ID'   => $post_parent,
					'comment_content'   => $comment.$post_footer,
					'user_id'           => $post_author,
					'comment_author_IP' => '127.0.0.1',
					'comment_date'      => $time,
					'comment_approved'  => 0
				);

				$post_id = wp_insert_comment($data);
				if (is_wp_error($post_id)) {
					wp_die( 'Error: could not create a post based on this content. Reason: ' . $post_id->get_error_message(), 'Error while creating the new post!' );
				}
			}
			wp_redirect($wp_admin_url . '/post.php?post=' . $post_parent .'&action=edit');
		}
	}  

}

class PostTextSource extends BlogTextSource {  
	protected $type = 'post';

	public function __construct($internalId) {
		parent::__construct($internalId);
	}

	public function accept($post_type) {
		return (strcmp($post_type,$this->type)==0 || empty($post_type));
	}
}

class PageTextSource extends BlogTextSource {  
	protected $type = 'page';

	public function __construct($internalId) {
		parent::__construct($internalId);
	}

	public function accept($post_type) {
		return (strcmp($post_type,$this->type)==0 || empty($post_type));
	}
}

class CommentTextSource extends TextSource {  
	protected $primarylang = 'en';  
	protected $wordcount   = 0;  
	protected $type        = 'comment';  
	protected $post_type   = 'post';  
	protected $post_id     = 0;  
	protected $comment_id  = 0;  

	public function __construct($internalId) {
		parent::__construct($internalId);
		$primarylang = mygengo_get_primarylanguage();
	}

	public function accept($post_type) {
		return (strcmp($post_type,$this->type)==0 || empty($post_type));
	}

	public function retrieveFormElements()  {
		return FALSE;
	}

	public function getAssignedTo() {
		return  __('Assigned to comment') .' ID '.$this->comment_id. '<input type="hidden" id="mg_post_id" name="mg_post_id" value="' . $this->comment_id . '" /><input type="hidden" id="mg_post_type" name="mg_post_type" value="comment" />';
	}  

	public function getTextToTranslate($requestvars) {
		$this->post_id    = $post_id    = isset($requestvars['wp_post_id'])?$requestvars['wp_post_id']:0;
		$this->comment_id = $comment_id = isset($requestvars['mg_post_id'])?$requestvars['mg_post_id']:0;
		if (!$comment_id) {
			return array();
		} 

		$comment = get_comment($this->comment_id);
		if (!$post_id) {
			$this->post_id = $post_id = $comment->comment_post_ID;
		}
		$this->type = mygengo_get_post_type($post_id);

		$texts = array();
		$this->primarylang = mygengo_get_comment_language_value($post_id);
		$this->wordcount   = 0; //not used
		$job_title = get_post($post_id)->post_title;

		$content  = str_replace(array('<','>'), array('[[[',']]]'), $comment->comment_content);
		$job_text = "\r\n[[[post_comment__]]]\r\n" . ($content). "\r\n";
		$texts[$job_title . ' comment ' . $comment_id] = $job_text;

		return $texts;
	}

	public  function getPrimaryLanguage() {
		return $this->primarylang;
	}

	public  function getWordcount($unit) {
		return $this->wordcount;
	}

	public function retrievePublishableAs($jobid) {  
		global $wpdb;
		$table_name3 = $wpdb->prefix . 'gengo_jobs';
		$job_post_id = $wpdb->get_var("SELECT job_post_id FROM ".$table_name3." WHERE job_id = ".$jobid);
		if (!$job_post_id) { $job_post_id = 0; }

		$comment = get_comment($job_post_id);
		$this->post_id = $comment->comment_post_ID;

		eval("\$mg_select = mygengo_generate_select_from_{$this->post_type}s({$this->post_id}, '<option value=\"\">[Select]</option>');");
		$html .= '<label><input type="radio" name="mg_publish_as" value="' . $this->internalId . '-translate" /><span class="checkbox-title">' . __('Publish as comment for '.$this->post_type) .'</span></label>';
		$html .= '<select name="mg_'.$this->type.'">' . $mg_select . '</select><br/>';
		return $html;
	}  

	public function publishAs($jobid, $requestvars) { 
		list($id, $publish_as)  = split("-", $requestvars['mg_publish_as']);
		if (intval($id) != $this->getInternalId()) {
			wp_die( 'Error: accessing a text source with different id.', 'Error while creating the new comment!' );
		}

		global $wpdb, $wp_admin_url;
		$table_name3 = $wpdb->prefix . 'gengo_jobs';
		$job_body    = $wpdb->get_var("SELECT job_body_tgt FROM ".$table_name3." WHERE job_id = ".$jobid);

		$post_parent = $requestvars['mg_'.$this->type];
		$post_type   = $this->post_type;
 
		$userid       = $requestvars['mg_user_id'];
		$comment_author  = (get_option('mygengo_use_mygengouser'))?get_option('mygengo_translator_id'):$userid;
		if (count(mygengo_getKeys(2)) == 2 && get_the_author_meta('mygengo_add_footer', $userid)) {
			$comment_footer   = '<div>'.get_the_author_meta('mygengo_footer').'</div>';
		} elseif (count(mygengo_getKeys(2)) != 2 && get_option('mygengo_add_footer')) {
			$comment_footer   = '<div>'.get_option('mygengo_footer').'</div>';
		} else {
			$comment_footer   = '';
		}
		$comment_language = $requestvars['mg_tgt_language'];

		$comments = mygengo_parse_comments($job_body);
		foreach($comments as $comment) {
			$time = current_time('mysql');
			$data = array(
				'comment_post_ID'   => $post_parent,
				'comment_content'   => $comment.$comment_footer,
				'user_id'           => $comment_author,
				'comment_author_IP' => '127.0.0.1',
				'comment_date'      => $time,
				'comment_approved'  => 0
			);

			$comment_id = wp_insert_comment($data);
			if (is_wp_error($comment_id)) {
				wp_die( 'Error: could not create a comment based on this content. Reason: ' . $comment_id->get_error_message(), 'Error while creating the new comment!' );
			} else {
				add_comment_meta($comment_id, '_comment_language_value', $comment_language, true);
			}
		}
		wp_redirect($wp_admin_url . '/post.php?post=' . $post_parent .'&action=edit');
	}

}

function mygengo_columns($defaults) {
	$defaults['language'] = __('Written in');
	return $defaults;
}

function mygengo_custom_post_column($column_name, $post_id = -1) {
	global $postts_id, $pagets_id, $mg_plugin_url;
	if($column_name == "language") {
		echo mygengo_get_language_image_src(mygengo_get_post_language_value($post_id));
		echo ' <div><a href="'.mygengo_menu_page_url('mygengo.phporder', false).'&mg_post_type=post&mg_post_id='.$post_id.'">' . __('order a translation') . '</a></div>';
	}
}

function mygengo_custom_page_column($column_name, $post_id = -1) {
	global $postts_id, $pagets_id, $mg_plugin_url;
	if($column_name == "language") {
		echo mygengo_get_language_image_src(mygengo_get_post_language_value($post_id));
		echo ' <div><a href="'.mygengo_menu_page_url('mygengo.phporder', false).'&mg_post_type=page&mg_post_id='.$post_id.'">' . __('order a translation') . '</a></div>';
	}
}

function mygengo_custom_comment_column($column_name, $comment_id = -1) {
	global $commentts_id, $mg_plugin_url;
	if($column_name == "language") {
		echo mygengo_get_language_image_src(mygengo_get_comment_language_value($comment_id));
		echo ' <div><a href="'.mygengo_menu_page_url('mygengo.phporder', false).'&mg_post_type=comment&mg_post_id='.$comment_id.'&wp_post_id='.$post_id.'">' . __('order a translation') . '</a></div>';
	}
}

add_filter('manage_posts_columns',       'mygengo_columns');
add_filter('manage_pages_columns',       'mygengo_columns');
add_filter('manage_edit-comments_columns',  'mygengo_columns');
add_action('manage_posts_custom_column', 'mygengo_custom_post_column', 10, 2);
add_action('manage_pages_custom_column', 'mygengo_custom_page_column', 10, 2);
add_action('manage_comments_custom_column', 'mygengo_custom_comment_column', 10, 2);

add_filter('mygengo_parse_translation', 'mygengo_parse_content');
add_filter('mygengo_parse_translationpost', 'mygengo_parse_content');
add_filter('mygengo_parse_translationpage', 'mygengo_parse_content');
add_action('mygengo_echo_translation', 'mygengo_echo_translation_post');
add_action('mygengo_echo_translationpost', 'mygengo_echo_translation_post');
add_action('mygengo_echo_translationpage', 'mygengo_echo_translation_post');
function mygengo_echo_translation_post($body_src) {
	$post_sections = mygengo_parse_content($body_src);
	if ($post_sections['post_title'] != '') {
		echo '<p><strong>' . __('Title') . ':</strong> ' . nl2br($post_sections['post_title']) . '</p>'; 
	}
	echo '<p><strong>' .__('Content') . ':</strong><br/>' . nl2br($post_sections['post_content']) . '</p>';
	if (trim($post_sections['post_excerpt']) != '') {
		echo '<p><strong>' . __('Excerpt') . ':</strong><br/> ' . nl2br($post_sections['post_excerpt']) . '</p>'; 
	}
}

add_filter('mygengo_parse_translationcomment', 'mygengo_parse_comments');
add_action('mygengo_echo_translationcomment', 'mygengo_echo_translation_comment');
function mygengo_echo_translation_comment($body_src) {
	$comments      = mygengo_parse_comments($body_src);
	foreach($comments as $comment) {
		echo '<p><strong>' . __('Comment') . ':</strong><br/> ' . nl2br($comment) . '</p>';
	}
}

add_action('mygengo_init','mygengo_register_wpsources',5);
function mygengo_register_wpsources() {
	global $postts_id, $pagets_id, $commentts_id;
	$postts_id = mygengo_register_textsource('PostTextSource');
	$pagets_id = mygengo_register_textsource('PageTextSource');
	$commentts_id = mygengo_register_textsource('CommentTextSource');
}

function mygengo_new_post_translatebutton() {
	global $post, $wp_admin_url, $postts_id, $pagets_id;
	if ($post->post_type == 'post') {
		echo "<div id='translate_id'><input type='button' class='button-secondary' onclick='document.location=\"".$wp_admin_url."/admin.php?page=mygengo.phporder&mg_post_type=post&mg_post_id=".$post->ID."\"' value='".__('Translate')."' /></div><div class='clear'></div>";
	} elseif ($post->post_type == 'page') {
		echo "<div id='translate_id'><input type='button' class='button-secondary' onclick='document.location=\"".$wp_admin_url."/admin.php?page=mygengo.phporder&mg_post_type=page&mg_post_id=".$post->ID."\"' value='".__('Translate')."' /></div><div class='clear'></div>";
	}
}

function mygengo_new_comment_translatebutton() {
	global $comment, $wp_admin_url, $commentts_id;
	echo "<div id='translate_id'><input type='button' class='button-secondary' onclick='document.location=\"".$wp_admin_url."/admin.php?page=mygengo.phporder&mg_post_type=comment&mg_post_id=".$comment->comment_ID."&wp_post_id=".$comment->comment_post_ID."\"' value='".__('Translate')."' /></div><div class='clear'></div>";
}


?>