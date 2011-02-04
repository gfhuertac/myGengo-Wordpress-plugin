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
 * Page to display a message if the user had not configured the myGengo keys yet
 * but she is trying to access any of the functionalities provided by this plugin.
 * Note: this page is not fully i18n compatible, since the text is long. Please
 * modify this page by hand to provide other languages.
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
        <div class="wrap">

        <h2>MyGengo: <?php _e('Simple Human Translation'); ?></h2>

	<p>
		myGengo is the revolutionary new way to order translations online. We get rid of the hassle, providing you with accurate, timely translations at an unbeatable price. <br/>
		We are backed by a global team of 1200+ qualified translators. Our interface is simple and easy to use. Just enter your text and go!
		<div style="width:100%; text-align:center; font-size:bigger;">
			If you already have an account, then add your myGengo keys in <a href="<?php echo $wp_admin_url; ?>/admin.php?page=mygengo.php">the personal settings page</a> <br/><br/>
			Or you can <input type="button" class="button-primary" value="<?php _e('Create an account!'); ?>" onclick="document.location='https://mygengo.com/auth/form/signup'"/>
		</div>
	</p>

<?php
?>
