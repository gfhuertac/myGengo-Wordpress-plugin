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
 * PHP script that contains the callbacks performed by the myGengo server
 * in order to update the status of a job.
 * Currently it updates the state of jobs and comments for a job.
 * The updates are notified to the user through her dashboard.
 *
 * @package myGengo
 */
?>
<?php

require_once(dirname(__FILE__) . '/../../../wp-load.php');
require_once(dirname(__FILE__) . '/mygengo-common.php');

global $table_name1, $table_name2, $table_name3, $table_name4;

//$postvars = print_r($_POST, true);
syslog(LOG_INFO, 'callback called');
if (isset($_POST['job'])) {
	$json_data = $_POST['job'];
	$action    = 'job';
} else if (isset($_POST['comment'])) {
	$json_data = $_POST['comment'];
	$action    = 'comment';
} else {
	exit();
}

$data = json_decode($json_data);
syslog(LOG_INFO, $data);
if ($action == 'job') {
	$extra = '';
	$job = new stdClass();
	$job->job_id   = $data->job_id;
	$job->status   = $data->status;
	$job->modified = $data->ctime;
	if (isset($data->body_tgt)) {
		$job->body_tgt = $data->body_tgt;
		$extra         = ", job_body_tgt = '".$job->body_tgt."'";
	}

	$sql = "UPDATE ".$table_name3." SET job_status = '".$job->status."', job_modified = ".$job->modified.$extra." WHERE job_id = ".$job->job_id;
	if ($wpdb->query($sql)===FALSE) {
		syslog(LOG_INFO, 'Job update failed with sql ' + $sql);
	}
} elseif ($action == 'comment') {
	$comment = new stdClass();
	$comment->job_id = $data->job_id;
	$comment->body   = $data->body;
	$comment->status = 'unread';
	$comment->author = 'translator';
	$comment->ctime  = $data->ctime;
	$custom_data     = $data->custom_data;

	$sql = "INSERT INTO ".$table_name4." (comment_job_id,comment_body,comment_author,comment_status,comment_ctime) VALUES (".$comment->job_id.",'".$comment->body."','".$comment->author."','".$comment->status."',".$comment->ctime.")";
	if ($wpdb->query($sql)===FALSE) {
		syslog(LOG_INFO, 'Comment insertion failed with sql ' + $sql);
	}
}
