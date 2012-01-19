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
 * Account file. 
 * This file shows the page that containes the myGengo general settings
 * for the blog.
 * This page is used for configuring the server to connect to, the default
 * language for the blog, among other settings.
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

	global $mg_plugin_url;

	$table_name1          = $wpdb->prefix . "gengo_languages";
	$table_name2          = $wpdb->prefix . "gengo_pairs";
	$mg_primary_language  = get_option("mygengo_primary_language");
	$mg_use_mygengouser   = intval(get_option("mygengo_use_mygengouser"));
	$mg_use_browser       = intval(get_option("mygengo_use_browser"));
	$mg_keys              = mygengo_getKeys();
	$mg_use_admin_key     = intval(get_option("mygengo_use_admin_key"));
	$mg_api_key           = get_option("mygengo_api_key");
	$mg_private_key       = get_option("mygengo_private_key");
	$mg_format            = get_option("mygengo_format");
	$mg_baseurl           = get_option("mygengo_baseurl");
	$mg_add_footer        = intval(get_option("mygengo_add_footer"));
	$mg_footer            = get_option("mygengo_footer");

	$mg_correction_instructions = get_option("mygengo_correction_instructions");
	$mg_rejection_instructions = get_option("mygengo_rejection_instructions");
?>
	<div class="wrap">

	<h2><?php _e('MyGengo Translator'); ?></h2>

<?php
	if($grant_access_notification != "") {
		echo $grant_access_notification;
	}
?>

<?php
	$mg_public_keys = mygengo_getKeys(0);
	if (count($mg_public_keys) == 2) {
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
	var fn = function() { getBalance(0, processBalance); }
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
	<form id="settings_form" method="post" action="">
<?php
	if(get_option("mygengo_translator_active") == "") {
		global $current_user;
		get_currentuserinfo();
?>
			<?php $pwd = wp_generate_password(); ?>
			<input name="user_login" type="hidden" value="mygengotranslator" />
			<input name="first_name" type="hidden" value="MyGengo" />
			<input name="last_name" type="hidden" value="Translator" />
			<input name="email" type="hidden" value="nothing@here.com" />
			<input name="url" type="hidden" value="mygengo.com" />
			<input name="pass1" type="hidden"  value="<?php echo $pwd; ?>" />
			<input name="pass2" type="hidden"  value="<?php echo $pwd; ?>" />
			<input name="role" type="hidden" value="editor" />
			<input type="hidden" name="create_translator_user2" value="true" />
<?php
	}
?>
	<table class="form-table">
	<tr>
		<td><?php _e('Primary Language'); ?></td>
		<td>
			<select name="mg_primary_language">
				<option value="">[<?php _e('Select'); ?>]</option>
				<?php echo mygengo_generate_select_from_sqlquery("SELECT language_code, language_name FROM ".$table_name1." ORDER BY language_name", "language_code", "language_name", $mg_primary_language); ?>
			</select>
<?php
	if (count($mg_keys) != 2) {
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
		<td><?php _e('When published, add the post/page as authored by a user called'); ?> "mygengotranslator"?</td>
		<td>
			no, <?php _e('use current user'); ?> <input type="radio" name="mg_use_mygengouser" value="0" <?php if(!$mg_use_mygengouser) {echo "checked='checked'";} ?>>&nbsp;&nbsp;yes <input type="radio" name="mg_use_mygengouser" value="1" <?php if($mg_use_mygengouser) {echo "checked='checked'";} ?>>
		</td>
	</tr>

	<tr>
		<td><?php _e('Automatically serve translation according to user\'s browser preferences?'); ?>
			<div style="font-size:smaller">*<?php _e('This will filter ALL the posts and pages written in a different language');?>.</div>
		</td>
		<td>
			no <input type="radio" name="mg_use_browser" value="0" <?php if(!$mg_use_browser) {echo "checked='checked'";} ?>>&nbsp;&nbsp;yes <input type="radio" name="mg_use_browser" value="1" <?php if($mg_use_browser) {echo "checked='checked'";} ?>>
		</td>
	</tr>

	<tr>
		<td><?php _e('Allow users to utilize common myGengo keys?'); ?><br/><span style="font-size: smaller;"><?php _e('Note: Do not enter your personal keys here');?>. Use <a href="<?php echo $wp_admin_url; ?>/admin.php?page=mygengo.php">the personal settings page</a> to store them.</span></td>
		<td>
			no <input type="radio" name="mg_use_admin_key" value="0" <?php if(!$mg_use_admin_key) {echo "checked='checked'";} ?>>&nbsp;&nbsp;yes <input type="radio" name="mg_use_admin_key" value="1" <?php if($mg_use_admin_key) {echo "checked='checked'";} ?>>
		</td>
	</tr>

	<?php if($mg_api_key) { ?>
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
	<?php } else { ?>
	<tr>
		<td></td>
		<td>
			<input type="hidden" name="mg_api_key" id="mg_api_key">
			<input type="hidden" name="mg_private_key" id="mg_private_key">
			<div id="signin_with_mygengo"></div>
		</td>
	</tr>
	<script type="text/javascript" src="http://ogneg.com/js/passport.min.js"></script>
	<script type="text/javascript">
		var passport = new MyGengoPassport({
			appName: 'myGengo-WordPress',
			button: 'signin_with_mygengo',
			buttonStyle: 'largeBlue',
			on_authentication: function(data) {
				document.getElementById('signin_with_mygengo').style.display = 'none';
				document.getElementById('mg_api_key').value = data.public_key;
				document.getElementById('mg_private_key').value = data.private_key;
				document.getElementById('settings_form').submit();
			}
		});
	</script>
	<?php } ?>

	<tr>
		<td><?php _e('Base Url'); ?></td>
		<td><input type="text" name="mg_baseurl" style="width:200px;" value="<?php echo $mg_baseurl; ?>" />
		</td>
	</tr>

	<tr>
		<td><?php _e('Format'); ?></td>
		<td><input type="text" name="mg_format" readonly value="<?php echo $mg_format; ?>" />
		</td>
	</tr>

	<tr>
		<td><?php _e('Add an acknowledgement to mygengo at the end of each translated post if using public keys?'); ?></td>
		<td>
			no <input type="radio" name="mg_add_footer" value="0" <?php if(!$mg_add_footer) {echo "checked='checked'";} ?>>&nbsp;&nbsp;yes <input type="radio" name="mg_add_footer" value="1" <?php if($mg_add_footer) {echo "checked='checked'";} ?>>
		</td>
	</tr>

	<tr>
		<td valign="top"><?php _e('MyGengo Acknowledgement'); ?></td>
		<td>
			<textarea id="mg_footer" name="mg_footer" cols="45" rows="4"><?php echo mygengo_reverse_escape($mg_footer); ?></textarea>
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
		</td>
	</tr>

	<tr>
		<td valign="top"><?php _e('myGengo\'s correction instructions'); ?></td>
		<td><textarea name="mg_correction_instructions" cols="45" rows="4"><?php echo $mg_correction_instructions; ?></textarea>
		</td>
	</tr>

	<tr>
		<td valign="top"><?php _e('myGengo\'s rejection instructions'); ?></td>
		<td><textarea name="mg_rejection_instructions" cols="45" rows="4"><?php echo $mg_rejection_instructions; ?></textarea>
		</td>
	</tr>

	</table>
	<p class="submit">
		<input type="submit" value="Save" />
		<input type="hidden" name="save_changes" value="true" />
	</p>
	</form>
	</div>
	</fieldset>
	</div>
<?php
?>
