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
 * Add new order file. 
 * This page contains the form to add new orders to the current myGengo account.
 * Some considerations about this page:
 * - It uses the textsources registered by the plugin or the user functions (@see mygengo_register_textsource)
 * - Currently the option that defines the 'as group' myGengo property is not working
 * - Currently the option the pays the job later is not working neither
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

global $wpdb, $current_user, $userdata, $mg_plugin_dir, $mg_plugin_url, $table_name1, $table_name2, $table_name3, $table_name4, $table_name5;

require_once($mg_plugin_dir . '/php/init.php');

$mg_post_type = isset($_REQUEST['mg_post_type'])?$_REQUEST['mg_post_type']:'';
$sources  = mygengo_get_textsources();
$source   = null;

if (!empty($mg_post_type)) {
	foreach($sources as $ts) {
		if ($ts->accept($mg_post_type)) {
			$source = $ts;
			break;
		}
	}
	if (is_null($source)) { $source = new DummyTextSource(); }
	$mg_text_to_translate = $source->getTextToTranslate($_REQUEST);
	$mg_primary_language  = $source->getPrimaryLanguage();
	$mg_word_count        = 0; //$ts->getWordcount();
} else {
	$mg_text_to_translate = array(""=>"");
	$mg_primary_language  = mygengo_get_primarylanguage();
	$mg_word_count        = 0;
}

if (!isset($_POST['mg_text_amount'])) {
	$mg_secondary_language  = '';
	$mg_selected_tier       = '';
} else {
	$mg_primary_language    = $_POST['mg_source_language'];
	$mg_secondary_language  = $_POST['mg_target_language'];
	$mg_selected_tier       = $_POST['mg_tier'];
}

$mg_keys = mygengo_getKeys();

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

$mg_quote      = '-';
$mg_word_count = '-';
if (isset($_POST['jobs_uc'])) {
	$mg_quote      = $_POST['jobs_qt'];
	$mg_word_count = $_POST['jobs_uc'];
}
?>

<script type="text/javascript">

function processTargetLanguages() {
	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
		document.getElementById("mg_target_language").innerHTML=xmlhttp.responseText;
	}
}

function fillTargetLanguages() {
	disableAllActions();
	sourceLanguage = document.order.mg_source_language.value;
	getTargetLanguages(sourceLanguage, processTargetLanguages);
}

function processTiers() {
	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
		document.getElementById("mg_tier").innerHTML=xmlhttp.responseText;
		setTimeout('fillEstimate', 200);
<?php
	if (isset($_POST['jobs_uc'])) {
?> 
		enableOrder();
		updateTier();
<?php
	}
?>
	}
}

function fillTiers() {
	disableAllActions();
	sourceLanguage = document.order.mg_source_language.value;
	targetLanguage = document.order.mg_target_language.value;
	getTiers(sourceLanguage, targetLanguage, '<?php echo $mg_selected_tier?>', processTiers);
}

function processEstimate() {
	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
		document.getElementById("mg_estimated_time").value=xmlhttp.responseText;
	}
}

function updateTier() {
	var tier = document.getElementById('mg_tier').value;
	var machinetier  = document.getElementById('mg_tier_machine');
	var standardtier = document.getElementById('mg_tier_standard');
	var protier      = document.getElementById('mg_tier_pro');
	var ultratier    = document.getElementById('mg_tier_ultra');
	if (tier == 'machine') {
		var show = ['mg_tier_machine'];
		var hide = ['mg_tier_standard', 'mg_tier_pro', 'mg_tier_ultra'];
		showHide(show, hide, "table-cell");
	} else if (tier == 'standard') {
		var show = ['mg_tier_standard'];
		var hide = ['mg_tier_machine', 'mg_tier_pro', 'mg_tier_ultra'];
		showHide(show, hide, "table-cell");
	} else if (tier == 'pro') {
		var show = ['mg_tier_pro'];
		var hide = ['mg_tier_machine', 'mg_tier_standard', 'mg_tier_ultra'];
		showHide(show, hide, "table-cell");
	} else if (tier == 'ultra') {
		var show = ['mg_tier_ultra'];
		var hide = ['mg_tier_machine', 'mg_tier_standard', 'mg_tier_pro'];
		showHide(show, hide, "table-cell");
	} else {
		var show = [];
		var hide = ['mg_tier_machine', 'mg_tier_standard', 'mg_tier_pro', 'mg_tier_ultra'];
		showHide(show, hide, "table-cell");
		disableAllActions();
	}
}

function fillEstimate() {
	sourceLanguage = document.getElementById('mg_source_language').value;
	targetLanguage = document.getElementById('mg_target_language').value;
	tier	       = document.getElementById('mg_tier').value;
	unit_count     = document.getElementById('mg_unit_count').value;
	getEstimate(sourceLanguage, targetLanguage, tier, unit_count, processEstimate);
}

function disableAllActions() {
	var show = [];
	var hide = ['unpaid','publish','topup','getquote'];
	showHide(show, hide, "inline");
}

function enableQuote() {
	var show = ['getquote'];
	var hide = ['unpaid','publish','topup'];
	showHide(show, hide, "inline");
}

function enableOrder() {
	var price   = parseFloat(document.getElementById('mg_price').value);
	var credits = parseFloat(document.getElementById('mg_credits').value);

	if (price > credits) {
		var show = ['topup'];
		var hide = ['unpaid','publish','getquote'];
		showHide(show, hide, "inline");
	} else {
		var show = ['publish'];
		var hide = ['unpaid','topup','getquote'];
		showHide(show, hide, "inline");
	}
}
<?php
	if (isset($_POST['jobs_uc'])) {
?> 
function init() {
	fillTiers();
}
window.onload = init; 
<?php
	}
?>
</script>

<div class="wrap">
<h2>MyGengo Translator</h2>
<?php if ( $notice ) : ?>
<div id="notice" class="error"><p><?php echo $notice ?></p></div>
<?php endif; ?>
<?php if ( $message ) : ?>
<div id="message" class="updated"><p><?php echo $message; ?></p></div>
<?php endif; ?>
<form name="order" action="" method="post" id="order">
<?php wp_nonce_field('mygengo_order_form'); ?>
<input type="hidden" id="mg_user_id" name="mg_user_id" value="<?php echo $current_user->ID; ?>" />
<input type="hidden" id="mg_order_form" name="mg_order_form" value="1" />
<input type="hidden" id="mg_process" name="mg_process" value="1" />
<input type="hidden" id="mg_quote"   name="mg_quote"   value="0" />

<div id="poststuff" class="metabox-holder has-right-sidebar">
	<div id="side-info-column" class="inner-sidebar">
		<div id="side-sortables" class="meta-box-sortables">
			<div id="ordersubmitdiv" class="postbox ">
				<div class="handlediv" title="Click to toggle"><br />
				</div>
				<h3 class="hndle"><span><?php _e('Order'); ?></span></h3>
				<div class="inside" style="margin: 0px;padding: 0px;">
					<div class="submitbox" id="submitorder">
						<div id="minor-publishing">
							<div style="display:none;">
								<input type="submit" name="save" value="Save">
							</div>
							<div id="minor-publishing-actions"> 
								<div id="preview-action">
									<?php _e('Balance'); ?>: <input type="text" id="mg_credits" name="mg_credits" style="width: 10px; border:none" value="<?php echo $mg_credits; ?>" readonly /><br />
									<?php _e('Estimated cost'); ?>:  $ <input type="text" id="mg_price" name="mg_price" style="width: 10px; border:none" value="<?php echo $mg_quote; ?>" readonly /><br/>
									<?php _e('Units'); ?>: <input type="text" id="mg_unit_count" name="mg_unit_count" style="width: 10px; border:none" value="<?php echo $mg_word_count; ?>" readonly /> <br/>
									<?php _e('Estimated time'); ?>: <input type="text" id="mg_estimated_time" name="mg_estimated_time" style="width: 10px; border:none" value="-" size="3" readonly /> <?php _e('hours'); ?>
								</div> 
								<div class="clear"></div> 
							</div> 
							<div id="misc-publishing-actions">
								<div class="misc-pub-section misc-pub-section-last">
<label for="as_group" class="selectit"><input id="as_group" name="as_group" type="checkbox" value="Y" checked> <?php _e('Assign whole translation job to one person'); ?></label><br/>
<label for="auto_approve" class="selectit"><input id="auto_approve" name="auto_approve" type="checkbox" value="Y"> <?php _e('Auto-approve jobs'); ?></label>
								</div>
							</div>
						</div>
						<div id="major-publishing-actions">
							<div id="delete-action"> 
							</div> 
							<div id="publishing-action">
<img id="submitting_image" src="<?php echo $mg_plugin_url.'images/loading.gif';?>" alt="loading..." style="visibility:hidden; display: none;" />
<input name="addcredits" type="button" class="button-primary" id="topup"   style="visibility:hidden; display: none;" value="<?php _e('You need more credits'); ?>!" onclick="document.location='http://www.mygengo.com';">
<input name="unpaid"  type="button" class="button-secondary" id="unpaid" style="visibility:hidden; display: none;" value="<?php _e('Order & pay later'); ?>" onclick="document.order.mg_process.value = '0'; document.order.submit();">
<input name="getquote"  type="button" class="button-secondary" id="getquote" style="visibility:hidden; display: none;" value="<?php _e('Get Quote'); ?>" onclick="document.order.mg_quote.value = '1'; document.order.submit();">
<input name="publish" type="submit" class="button-primary" id="publish" style="visibility:hidden; display: none;" value="<?php _e('Order & pay now'); ?>">
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
				<h3><?php _e('Order Information'); ?></h3>
				<div class="inside">
					<table class="form-table">
						<tr>
							<td colspan="2">
<?php
	if (empty($mg_post_type)) {
		foreach($sources as $ts) {
			echo $ts->getAssignFormField();
		}
	} else {
		echo $source->getAssignedTo();
	}
?>
							</td>
						</tr>
						<tr>
							<td style="width:50%;"><?php _e('Source Language'); ?></td>
							<td style="width:50%;"><?php _e('Target Language'); ?></td>
						</tr>
						<tr>
							<td>
								<select name="mg_source_language" id="mg_source_language"  onchange="fillTargetLanguages(this.value)">
									<option value="">[<?php _e('Select'); ?>]</option>
									<?php echo mygengo_generate_select_from_sqlquery("SELECT language_code, language_name FROM ".$table_name1." ORDER BY language_name", "language_code", "language_name", $mg_primary_language); ?>
								</select>
							</td>
							<td>	
								<select name="mg_target_language" id="mg_target_language" onchange="getUnitType(this.value); fillTiers()">
									<option value="">[<?php _e('Select'); ?>]</option>
<?php 
	if ($mg_primary_language != '') {
		$selected_code = $mg_primary_language;
		echo mygengo_generate_select_from_sqlquery("SELECT DISTINCT l.language_code, l.language_name FROM ".$table_name1." l, ".$table_name2." p WHERE p.pair_source = '".$selected_code."' AND p.pair_target = l.language_code ORDER BY language_name, language_code", "language_code", "language_name", $mg_secondary_language); 
	}
?>
								</select>
							</td>
						</tr>
						<tr>
							<td colspan="2"><?php _e('Tier'); ?>:
								<select name="mg_tier" id="mg_tier" onchange="enableQuote();updateTier();">
									<option value="">[<?php _e('Select'); ?>]</option>
								</select>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<div id="mg_tier_machine" style="visibility:hidden; display: none; font-size: larger; vertical-align: middle;">
									<?php _e('Only for preview your text. Quality not ensured'); ?>
								</div>
								<div id="mg_tier_standard" style="visibility:hidden; display: none; font-size: larger; vertical-align: middle;">
									<img src="<?php echo $mg_plugin_url.'images/mg/mygengo_standard_172x44_s1.png';?>" alt="standard" /> <?php _e('Fast human translation by a native speaker. For everyday use.'); ?>
								</div>
								<div id="mg_tier_pro" style="visibility:hidden; display: none;font-size: larger; vertical-align: middle;">
									<img src="<?php echo $mg_plugin_url.'images/mg/mygengo_pro_172x44_s1.png';?>" alt="pro" /> <?php _e('Professional translation. For publishing and formal use.'); ?>
								</div>
								<div id="mg_tier_ultra" style="visibility:hidden; display: none;font-size: larger; vertical-align: middle;">
									<img src="<?php echo $mg_plugin_url.'images/mg/mygengo_ultra_172x44_s1.png';?>" alt="standard" /> <?php _e('Pro service + extra proofreading. For ultimate quality.'); ?>
								</div>
							</td>
						</tr>
					</table>
				</div> 
			</div>
<?php
	if (!isset($_POST['mg_text_amount'])) {
		$ttt_idx = 0;
		foreach($mg_text_to_translate as $ttt_title => $ttt_text) {
?>
			<div class="stuffbox">
				<h3><?php _e('Text to translate'); ?></h3>
				<div class="inside">
					<p><?php _e('Title'); ?>: <input type="text" id="mg_body_id_<?php echo $ttt_idx; ?>" name="mg_body_id_<?php echo $ttt_idx; ?>" style="margin: 0px; width: 200px;" value="<?php echo $ttt_title;?>"></p>
					<p><?php _e('Your text'); ?>:<br />
					<textarea rows="1" cols="40" id="mg_body_src_<?php echo $ttt_idx; ?>" name="mg_body_src_<?php echo $ttt_idx; ?>" style="height: 8em; margin: 0px; width: 98%;" onchange="enableQuote()"><?php echo $ttt_text;?></textarea></p>
					<p><?php _e('Add a comment for the translator (optional)'); ?>:<br />
					<textarea rows="1" cols="40" name="mg_body_comment_<?php echo $ttt_idx; ?>" style="height: 4em; margin: 0px; width: 98%;"></textarea></p>
				</div> 
			</div>
<?php
			$ttt_idx++;
		}
	} else {
		$ttt_idx = $_POST['mg_text_amount'];
		for($i=0; $i<$ttt_idx; $i++) {
?>
			<div class="stuffbox">
				<h3><?php _e('Text to translate'); ?>&nbsp;&nbsp;<?php if ($_POST['job_'.$i.'_qt']) { _e('Quote'); echo ':' . $_POST['job_'.$i.'_qt']; } ?>&nbsp;&nbsp;<?php if ($_POST['job_'.$i.'_uc']) { _e('Unit count'); echo ':' . $_POST['job_'.$i.'_uc']; } ?></h3>
				<div class="inside">
					<p><?php _e('Title'); ?>: <input type="text" id="mg_body_id_<?php echo $i; ?>" name="mg_body_id_<?php echo $i; ?>" style="margin: 0px; width: 200px;" value="<?php echo mygengo_reverse_escape($_POST['mg_body_id_' . $i]);?>"></p>
					<p><?php _e('Your text'); ?>:<br />
					<textarea rows="1" cols="40" id="mg_body_src_<?php echo $i; ?>" name="mg_body_src_<?php echo $i; ?>" style="height: 8em; margin: 0px; width: 98%;" onchange="enableQuote()"><?php echo mygengo_reverse_escape($_POST['mg_body_src_' . $i]);?></textarea></p>
					<p><?php _e('Add a comment for the translator (optional)'); ?>:<br />
					<textarea rows="1" cols="40" name="mg_body_comment_<?php echo $i; ?>" style="height: 4em; margin: 0px; width: 98%;"><?php echo mygengo_reverse_escape($_POST['mg_body_comment_' . $i]);?></textarea></p>
				</div> 
			</div>
<?php
		}
	}
?>
			<input type="hidden" name="mg_text_amount" value="<?php echo $ttt_idx; ?>" />
		</div>
	</div>
</div>
</form>
<script type="text/javascript" language="javascript">
<!--
//	setSelectedValue('mg_target_language', '<?php echo $mg_secondary_language; ?>');
//	setSelectedValue('mg_tier', '<?php echo $mg_selected_tier?>');

	var frmvalidator  = new Validator("order");
	frmvalidator.addValidation("mg_body_id_0","req","<?php _e('Please enter a title for your job!'); ?>");
	frmvalidator.addValidation("mg_body_src_0","req","<?php _e('The content cannot be empty!'); ?>");
	frmvalidator.addValidation("mg_source_language","dontselect=0");
	frmvalidator.addValidation("mg_target_language","dontselect=0");
	frmvalidator.addValidation("mg_tier","dontselect=0");
//-->
</script>
</div>
<?php

?>
