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
if (!function_exists ('add_action')): 
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
endif;

global $wpdb, $_POST, $wp_admin_dir, $mg_plugin_dir, $current_user, $userdata, $table_name1, $table_name2, $table_name3, $table_name4;

$mg_rating         = isset($_POST['mg_rating'])?$_POST['mg_rating'] : 1;
$mg_for_translator = isset($_POST['mg_for_translator'])?$_POST['mg_for_translator'] : '';
$mg_for_mygengo    = isset($_POST['mg_for_mygengo'])?$_POST['mg_for_mygengo'] : '';
$mg_public         = isset($_POST['mg_public'])?$_POST['mg_public'] : 0;

$mg_keys = mygengo_getKeys();

get_currentuserinfo();

$job_id = isset($_REQUEST['job_id'])?$_REQUEST['job_id']:0;
if ($job_id == 0) {
	wp_die(__('Invalid job id'), __('Invalid job id'));
}

if (!get_transient('mygengo_job_id_' . $jobid)) {
	mygengo_refresh_job($job_id);
}
$job    = $wpdb->get_row("SELECT * FROM ".$table_name3." WHERE job_id = ".$job_id, OBJECT);

//update related callbacks and comments to 'read'
$wpdb->query("UPDATE ".$table_name3." SET job_callback = 0 WHERE job_id = ".$job_id);
$wpdb->query("UPDATE ".$table_name4." SET comment_callback = 0, comment_status = 'read' WHERE comment_job_id = ".$job_id);

require_once($mg_plugin_dir . '/php/init.php');

$job_client = myGengo_Api::factory('job', $mg_keys['api_key'], $mg_keys['private_key']);

$params = array();
$params['ts'] = gmdate('U');
$params['api_key'] = $mg_keys['api_key'];
ksort($params);
$query = http_build_query($params);
$params['api_sig'] = myGengo_Crypto::sign($query, $mg_keys['private_key']);
?>

<script>

function reject() {
	var content = '';
	content += '<div class="wrap" style="z-index:10001; border: solid 10px #404040; background-color:#ffffff; max-width: 600px;"><h2>MyGengo Translator</h2><form name="reject" action="" method="post" id="reject">';
	content += '<?php wp_nonce_field('mygengo_reject_form'); ?><input type="hidden" name="mg_reject_form" value="1" />';
	content += '<div id="rejectstuff" class="metabox-holder">';
	content += '<div id="reject-body"><div id="reject-body-content>"';
	content += '<div class="stuffbox"><div class="inside" style="text-align:left;">';
	content += '<p>';
	content += '<?php _e('Remember that you can request corrections of this translation instead of rejecting it.'); ?> <br/>';
	content += '<?php _e('Click the X below to close this form, and then click the "Request corrections" button.'); ?>';
        content += '</p>';
<?php
	$instructions = get_option("mygengo_rejection_instructions");
	if (!is_null($instructions) && strlen(trim($instructions))>0) {
?>
	content += '<p><strong>';
	content += '<?php _e('Rejection instructions.'); ?> </strong><br/>';
	content += '<?php echo $instructions; ?>';
        content += '</p>';
<?php
	}
?>
	content += '<p>';
	content += '<?php _e('Would you like to cancel the translation?'); ?> <br/>';
	content += '<input type="radio" name="mg_follow_up" value="cancel" /> Yes &nbsp;<input type="radio" name="mg_follow_up" value="requeue" checked /> No &nbsp;';
        content += '</p>';
	content += '<p>';
	content += '<?php _e('Rejection reason'); ?>:<br/>';
	content += '<input type="radio" name="mg_reason" value="quality" checked /> <?php _e('Poor quality of translation'); ?> <br/>';
	content += '<input type="radio" name="mg_reason" value="incomplete" /> <?php _e('Missing or incomplete translation'); ?> <br/>';
	content += '<input type="radio" name="mg_reason" value="other" /> <?php _e('Other (please describe below)'); ?>';
        content += '</p>';
	content += '<p>';
	content += '<?php _e('Feedback for original translator'); ?>: <br/>';
	content += '<textarea rows="1" cols="40" name="mg_comment" style="height: 4em; margin: 0px; width: 98%;"></textarea>';
        content += '</p>';
	content += '<p>';
	content += '<?php _e('Please enter the text below (to confirm you are human)'); ?>: <br/>';
	content += '<img src="<?php echo $job->job_captcha_url; ?>" alt="captcha"/><input type="text" name="mg_captcha" />';
        content += '</p>';
	content += '<p style="text-align: right;">';
	content += '<input name="mg_submit" type="submit" class="button-primary" id="mg_submit" style="display: inline;" value="<?php _e('Reject translation'); ?>"/>';
        content += '</p>';
	content += '</div></div>';
	content += '</div></div>';
	content += '</div></form>'
	content += '<script type="text/javascript" language="javascript"><!--';
	content += 'var frmvalidator  = new Validator("reject");';
	content += 'frmvalidator.addValidation("mg_comment","req","<?php _e('The comment cannot be empty!'); ?>");';
	content += 'frmvalidator.addValidation("mg_captcha","req","<?php _e('The captcha entry cannot be empty!'); ?>");';
	content += '//--><'+'/script></div>';
	return displayForm(content);
}

function requestCorrections() {
	var content = '';
	content += '<div class="wrap" style="z-index:10001; border: solid 10px #404040; background-color:#ffffff; max-width: 600px;"><h2>MyGengo Translator</h2><form name="request" action="" method="post" id="request">';
	content += '<?php wp_nonce_field('mygengo_request_form'); ?><input type="hidden" name="mg_request_form" value="1" />';
	content += '<div id="requeststuff" class="metabox-holder">';
	content += '<div id="request-body"><div id="request-body-content>"';
	content += '<div class="stuffbox"><div class="inside" style="text-align:left;">';
	content += '<p>';
	content += '<?php _e('Remember to use one of the language defined in the job (source or target) when writing a comment.'); ?> <br/>';
	content += '<?php _e('If you have doubts about the request, please visit the '); ?>';
	content += '<a href="http://mygengo.com/help/faqs">FAQ</a>';
        content += '</p>';
<?php
	$instructions = get_option("mygengo_correction_instructions");
	if (!is_null($instructions) && strlen(trim($instructions))>0) {
?>
	content += '<p><strong>';
	content += '<?php _e('Correction instructions.'); ?> </strong><br/>';
	content += '<?php echo $instructions; ?>';
        content += '</p>';
<?php
	}
?>
	content += '<p>';
	content += '<p>';
	content += '<?php _e('Use this space to make a formal correction request'); ?><br/>';
	content += '<textarea rows="1" cols="40" name="mg_comment" style="height: 4em; margin: 0px; width: 98%;"></textarea>';
        content += '</p>';
	content += '<p style="text-align: right;">';
	content += '<input name="mg_submit" type="submit" class="button-primary" id="mg_submit" style="display: inline;" value="<?php _e('Request corrections from translator'); ?>"/>';
        content += '</p>';
	content += '</div></div>';
	content += '</div></div>';
	content += '</div></form>'
	content += '<script type="text/javascript" language="javascript"><!--';
	content += 'var frmvalidator  = new Validator("request");';
	content += 'frmvalidator.addValidation("mg_comment","req","<?php _e('The comment cannot be empty!'); ?>");';
	content += '//--><'+'/script></div>';
	return displayForm(content);
}

function displayForm(content){
	var thediv=document.getElementById('displaybox');
	if(thediv.style.display == "none"){
		thediv.style.display = "";
		thediv.innerHTML = "<table width='100%' height='100%'><tr><td align='center' valign='middle' width='100%' height='100%'>"+content+"<div style='width:100%; position: relative; left: 305px; top: -15px;'><a href='#' onclick='return displayForm();'><img src='<?php echo $mg_plugin_url;?>images/close.png' alt='close'></a></div></td></tr></table>";
	}else{
		thediv.style.display = "none";
		thediv.innerHTML = '';
	}
	return false;
}
</script>

<div class="wrap">
<h2><?php _e('MyGengo Translator'); ?></h2>
<?php if ( $notice ) : ?>
<div id="notice" class="error"><p><?php echo $notice ?></p></div>
<?php endif; ?>
<?php if ( $message ) : ?>
<div id="message" class="updated"><p><?php echo $message; ?></p></div>
<?php endif; ?>

<form name="review" action="<?php echo mygengo_getLink($wp_admin_url.'/admin.php',array('mg_add_comment' => '0'),true);?>" method="post" id="review">
<?php wp_nonce_field('mygengo_review_form'); ?>
<input type="hidden" id="mg_job_id" name="mg_job_id" value="<?php echo $job->job_id; ?>" />
<input type="hidden" id="mg_tgt_language" name="mg_tgt_language" value="<?php echo $job->job_lc_tgt; ?>" />
<input type="hidden" id="mg_user_id" name="mg_user_id" value="<?php echo $current_user->ID; ?>" />
<input type="hidden" id="mg_review_form" name="mg_review_form" value="1" />

<div id="poststuff" class="metabox-holder has-right-sidebar">
	<div id="side-info-column" class="inner-sidebar">
		<div id="side-sortables" class="meta-box-sortables">
			<div id="ordersubmitdiv" class="postbox ">
				<div class="handlediv" title="Click to toggle"><br />
				</div>
				<h3 class="hndle"><span><?php _e('Job Information'); ?></span></h3>
				<div class="inside" style="margin: 0px;padding: 0px;">
					<div class="submitbox" id="submitorder">
						<div id="minor-publishing">
							<div style="display:none;">
								<input type="submit" name="save" value="<?php _e('Save'); ?>">
							</div>
							<div id="minor-publishing-actions"> 
								<div id="preview-action">
									<?php _e('Job status'); ?>: <?php echo $job->job_status; ?>
								</div> 
								<div class="clear"></div> 
							</div> 
							<div id="misc-publishing-actions">
								<div class="misc-pub-section misc-pub-section-last">
<?php

if ($job->job_status == 'approved') {
?>
									<?php _e('Publish translation as'); ?>: <br/>
									<label><input type="radio" name="mg_publish_as" value="0" checked /><span class="checkbox-title"><?php _e('New post'); ?></span></label> <br/>
									<label><input type="radio" name="mg_publish_as" value="1" /><span class="checkbox-title"><?php _e('New page'); ?></span></label> <br/> 
<?php
	$sources  = mygengo_get_textsources();
	foreach($sources as $ts) {
		if ($ts->accept($job->job_post_type)) {
			echo $ts->getPublishableFormField($job->job_id);
		}
	}
}
?>
								<div class="clear"></div> 
								</div>
							</div>
						</div>
						<div id="major-publishing-actions">
							<div id="delete-action"> 
							</div> 
							<div id="publishing-action">
<?php
if ($job->job_status == 'approved') {
?>
<input name="mg_publish" type="submit" class="button-primary" id="mg_publish" style="display: inline;" value="<?php _e('Publish');?>">
<?php
} elseif ($job->job_status == 'reviewable') {
?>
<input name="mg_approve" type="submit" class="button-primary" id="mg_approve" style="display: inline;" value="<?php _e('Approve'); ?>"><br/>
<input name="mg_reject" type="button" class="button" id="mg_reject" style="display: inline;" value="<?php _e('Reject'); ?>" onclick="return reject();">
<input name="mg_request" type="button" class="button" id="mg_request" style="display: inline;" value="<?php _e('Request Corrections'); ?>" onclick="return requestCorrections();">
<?php
} elseif ($job->job_status == 'available') {
?>
<input name="mg_cancel" type="submit" class="button-primary" id="mg_cancel" style="display: inline;" value="<?php _e('Cancel'); ?>">
<?php
} elseif ($job->job_status == 'unpaid') {
?>
<input name="mg_pay" type="submit" class="button-primary" id="mg_pay" style="display: inline;" value="<?php _e('Pay'); ?>">
<?php
} 
?>
							</div>
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="post-body">
		<div id="post-body-content">

			<div id="orderdiv" class="stuffbox">
				<h3><?php _e('Job Details'); ?></h3>
				<div class="inside">
					<p>
						<strong><?php _e('Job'); ?> <?php echo $job->job_id; ?>: <?php echo $job->job_slug; ?></strong>
					<h3><?php _e('Original Text'); ?> (<?php echo $wpdb->get_var("SELECT language_name FROM ".$table_name1." WHERE language_code='".$job->job_lc_src."'"); ?>):</h3>
					<div>
<?php
		$body_src = mygengo_reverse_escape($job->job_body_src);
		if (has_action('mygengo_echo_translation'.$job->job_post_type)) {
			do_action('mygengo_echo_translation'.$job->job_post_type, $body_src);
		} else {
			do_action('mygengo_echo_translation', $body_src);
		}
?>
					</div>
				</div> 
			</div>
<?php
	if ($job->job_status == 'reviewable') {
		$job_client->previewJob($job->job_id);
		$response = $job_client->getResponseBody();
		if (false === $response) {
			$image_src = $mg_plugin_url . 'images/not_available.jpg';
		} else {
			$filename = $mg_plugin_dir . 'images/generated/' . $job->job_id . '.jpg';
			$fp = fopen($filename, 'w');
			fwrite($fp, $response);
			fclose($fp);
			$image_src = $mg_plugin_url . 'images/generated/' . $job->job_id . '.jpg';
		}
?>
			<div class="stuffbox">
				<h3><?php _e('Translation Feedback'); ?></h3>
				<div class="inside">
					<p>
						<img src="<?php echo $image_src;?>" alt="review" />
					</p>
					<p>
						<?php _e('Please rate this translation'); ?>:<br/>
						<?php _e('Bad'); ?> <?php echo mygengo_rating(5, 'mg_rating', $mg_rating); ?> <?php _e('Great'); ?>
					</p>
					<p>
						<?php _e('Feedback for translator'); ?>:
						<textarea rows="1" cols="40" name="mg_for_translator" style="height: 4em; margin: 0px; width: 98%;"><?php echo $mg_for_translator;?></textarea>
					</p>
					<p>
						<?php _e('Feedback for myGengo'); ?>: <br/>
						<textarea rows="1" cols="40" name="mg_for_mygengo" style="height: 4em; margin: 0px; width: 98%;"><?php echo $mg_for_mygengo;?></textarea>
					</p>
					<p>
						<?php _e('Can myGengo use this translation publicly in its examples?'); ?> <br/>
						<label for="mg_public" class="selectit"><input id="mg_public" name="mg_public" type="checkbox" value="Y" i<?php if ($mg_public) { ?>checked<?php } ?>> <?php _e('Yes, you can use this translation as a public example of myGengo\'s service'); ?>.</label>
					</p>
				</div> 
			</div>
<?php
	} 
	if (trim($job->job_body_tgt) != '') {
?>
			<div class="stuffbox">
				<h3><?php _e('Translation'); ?> (<?php echo $wpdb->get_var("SELECT language_name FROM ".$table_name1." WHERE language_code='".$job->job_lc_tgt."'"); ?>): </h3>
				<div class="inside">
					<p>
<?php 
		$body_tgt = mygengo_reverse_escape($job->job_body_tgt);
		if (has_action('mygengo_echo_translation'.$job->job_post_type)) {
			do_action('mygengo_echo_translation'.$job->job_post_type, $body_tgt);
		} else {
			do_action('mygengo_echo_translation', $body_tgt);
		}
?>
					</p>
<?php
		if ($job->job_status != 'approved') {
?>
					<p style="color:red;"><?php _e('Note: This translation is temporal and may not reflect the quality of the final version'); ?>.</p>
<?php
		}
?>
				</div>
			</div>

<?php
	}
?>
			<div id="normal-sortables" class="meta-box-sortables ui-sortable">
<?php
	job_comment_meta_box($job);
?>
			</div>
		</div>
	</div>
</div>
</form>

<?php
if ($job->job_status == 'approved') {
?>

<script type="text/javascript" language="javascript">
<!--

function get_radio_value(radios) {
	for (var i=0; i < radios.length; i++) {
		if (radios[i].checked) {
			return radios[i].value;
		}
	}
	return -1;
}

function DoCustomValidation() {
	var frm = document.forms["review"];
	if(get_radio_value(frm.mg_publish_as) == 2 && frm.mg_post.value == '') {
		sfm_show_error_msg('You must select a post!', frm.mg_post);
		return false;
	}
	if(get_radio_value(frm.mg_publish_as) == 3 && frm.mg_page.value == '') {
		sfm_show_error_msg('You must select a page!', frm.mg_page);
		return false;
	}

	return true;
}

var frmvalidator  = new Validator("review");
frmvalidator.setAddnlValidationFunction("DoCustomValidation");
//-->
</script>

<?php
}
?>

</div>
<?php
/**
 * A container for the comments box displayed at the bottom of each job
 *
 * @since 1.0
 *
 * @param an object $job representing an entry of a job submitted to myGengo
 */
function job_comment_meta_box($job) {
	global $wpdb, $wp_admin_url;
	$table_name4 = $wpdb->prefix . "gengo_comments";

	$total = $wpdb->get_var("SELECT count(1) FROM ".$table_name4." WHERE comment_job_id = ".$job->job_id."");
?>
<div id="commentsdiv" class="postbox">
<div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle"><span><?php _e('Comments and Activity'); ?></span></h3>
<div class="inside">
<?php
	if ( 1 > $total ) {
		echo '<p>' . __('No comments yet.') . '</p>';
	} else {
?>
<table class="widefat comments-box fixed" cellspacing="0">
<thead><tr>
	<?php print_column_headers('comments'); ?>
</tr></thead>
<tbody id="the-comment-list" class="list:comment">
<?php

	$comments = $wpdb->get_results("SELECT * FROM ".$table_name4." WHERE comment_job_id = ".$job->job_id);
	foreach ($comments as $comment) {
?>
	<tr id='comment-<?php echo $comment->comment_id;?>'>
<?php
        $columns = get_column_headers('edit-comments');
        $hidden = get_hidden_columns('edit-comments');
        foreach ( $columns as $column_name => $column_display_name ) {
                $class = "class=\"$column_name column-$column_name\"";

                $style = '';
                if ( in_array($column_name, $hidden) )
                        $style = ' style="display:none;"';

                $attributes = "$class$style";

                switch ($column_name) {
                        case 'cb':
				echo "<td></td>";
                                break;
                        case 'comment':
                                echo "<td $attributes>";
                                echo '<div id="submitted-on">';
				echo date("F j, Y, g:i a", $comment->comment_ctime);
                                echo '</div>';
                                echo $comment->comment_body;
                                echo '</td>';
                                break;
                        case 'author':
                                echo "<td $attributes><strong>"; 
				echo $comment->comment_author;
				echo '</strong><br />';
                                echo '</td>';
                                break;
                        case 'date':
                                echo "<td $attributes>" . date("F j, Y, g:i a", $comment->comment_ctime) . '</td>';
                                break;
                        case 'response':
				echo "<td></td>";
                                break;
		}
	}
?>
	</tr>
<?php
	}
?>
</tbody>
</table>
<?php
	}
	if(!($job->job_status == 'approved' || $job->job_status == 'cancelled')) {
?>
<p><?php _e('Add a comment for the translator'); ?>:<br />
<textarea rows="1" cols="40" name="mg_body_comment" style="height: 4em; margin: 0px; width: 98%;"></textarea>
<?php _e('Requesting personal contact details from translators is prohibited'); ?>.
</p>
<input name="mg_comment" type="button" class="button" id="mg_comment" style="display: inline;" value="<?php _e('Add Comment'); ?>" onclick="document.forms.review.action='<?php echo mygengo_getLink($wp_admin_url.'/admin.php',array('mg_add_comment' => '1'),true);?>';document.forms.review.submit();">
<?php
	}
?>
</div>
</div>
</div>
<div id="displaybox" style="display: none;z-index: 10000; background: rgba(64, 64, 64, 0.5); filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#7f404040, endColorstr=#7f404040); position:fixed; top:0px; left:0px; width:100%; height:100%; text-align:center; vertical-align:middle;"></div>
<?php
}
?>
