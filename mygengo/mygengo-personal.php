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
 * Personal Account file. 
 * This file shows the page that containes the myGengo personal settings
 * for users in the blog.
 * This page is used for managing the user keys, allow her to select a default
 * language for the blog, edit an acknowledgement that can be added to translated
 * texts, etc.
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

	global $mg_plugin_url, $current_user, $userdata, $table_name1, $table_name2;

	$userid = $current_user->ID;
	$mg_primary_language  = get_the_author_meta("mygengo_primary_language", $userid);
	$mg_api_key           = get_the_author_meta("mygengo_api_key", $userid);
	$mg_private_key       = get_the_author_meta("mygengo_private_key", $userid);
	$mg_add_footer        = intval(get_the_author_meta("mygengo_add_footer", $userid));
	$mg_footer            = get_the_author_meta("mygengo_footer", $userid);
?>
	<div class="wrap">

	<h2><?php _e('MyGengo Translator'); ?></h2>

<?php
	$mg_private_keys = mygengo_getKeys(2);
	if (count($mg_private_keys) == 2) {
?>
<script type="text/javascript">
<!--
function processBalance() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {
		var profile = JSON.parse(xmlhttp.responseText);
		if (profile.opstat == 'error') {
			alert('Error code: ' + profile.err.code + ' ' + profile.err.msg);
			return;
		}
		document.getElementById('mg_account_balance').innerHTML = profile.credits;
		document.getElementById('mg_account_spent').innerHTML = profile.credits_spent;
		document.getElementById('mg_account_since').innerHTML = profile.user_since;
	}
}

window.onload = function() {
	var fn = function() { getBalance(2, processBalance); }
	setTimeout(fn, 100);
}

//-->
</script>

        <h3><?php _e('Facts and Figures'); ?></h3>
        <table class="form-table">
        <tr>
                <td><?php _e('Current balance'); ?>:</td>
		<td><span id="mg_account_balance"><img src="<?php echo $mg_plugin_url; ?>images/loading.gif" alt="[<?php _e('loading'); ?>]" /></span>&nbsp;&nbsp;<input type="button" onclick="document.location='http://mygengo.com';" value="Top Up" /></td>
	</tr>
        <tr>
                <td><?php _e('Credits spent'); ?>:</td>
		<td id="mg_account_spent"></td>
	</tr>
        <tr>
                <td><?php _e('User since'); ?>:</td>
		<td id="mg_account_since"></td>
	</tr>
	</table>
	</div>
<?php
	}
?>
	<form name="sync_form" id="sync_form" method="post" action="" style="display:inline;">
		<input type="hidden" name="sync_languages" value="true" />
	</form>
	<h3><?php _e('General Settings'); ?></h3>
	<form method="post" action="">
	<table class="form-table">
	<tr>
		<td><?php _e('Primary Language'); ?></td>
		<td>
			<select name="mg_primary_language">
				<option value="">[<?php _e('Select'); ?>]</option>
				<?php echo mygengo_generate_select_from_sqlquery("SELECT language_code, language_name FROM ".$table_name1." ORDER BY language_name", "language_code", "language_name", $mg_primary_language); ?>
			</select>
<?php
	if (count($mg_private_keys) != 2) {
?>
		<span style="color:red;"><?php _e('You have to update the API and private keys to use the plugin'); ?></span>
<?php
	} else {
?>
                <input type="button" onclick="document.sync_form.submit();" value="<?php _e('Sync Languages'); ?>" />
<?php
	}
?>
		</td>
	</tr>

	<tr>
		<td><?php _e('API Key'); ?></td>
		<td><input type="text" name="mg_api_key" style="width:400px;" value="<?php echo $mg_api_key; ?>" />
		</td>
	</tr>

	<tr>
		<td><?php _e('Private Key'); ?></td>
		<td><input type="text" name="mg_private_key" style="width:400px;" value="<?php echo $mg_private_key; ?>" />
		</td>
	</tr>

<?php
	if (count($mg_private_keys) != 2) {
?>
	<tr>
		<td colspan="2"><?php _e('Click the button if you do not have a myGengo account yet'); ?>&nbsp;&nbsp;<input type="button" class="button-primary" value="Create an account!" onclick="document.location='https://mygengo.com/auth/form/signup'"/>
		</td>
	</tr>
<?php
	}
?>
	<tr>
		<td><?php _e('Add an acknowledgement to mygengo at the end of each translated post?'); ?></td>
		<td>
			no <input type="radio" name="mg_add_footer" value="0" <?php if(!$mg_add_footer) {echo "checked='checked'";} ?>>&nbsp;&nbsp;yes <input type="radio" name="mg_add_footer" value="1" <?php if($mg_add_footer) {echo "checked='checked'";} ?>>
		</td>
	</tr>

	<tr>
		<td valign="top"><?php _e('MyGengo Acknowledgement'); ?></td>
		<td><?php mygengo_acknowledgement_editor($mg_footer); ?>
		</td>
	</tr>

	</table>
	<p class="submit">
		<input type="submit" class="button-primary" value="Save" />
		<input type="hidden" name="save_changes" value="true" />
	</p>
	</form>
	</div>
<?php
?>
