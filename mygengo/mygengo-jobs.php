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
 * Jobs file. 
 * This file shows the page that containes the myGengo jobs sent by the current
 * user.
 * Please note that this page uses the PERSONAL keys if they exists, and if they
 * do not exist then uses the PUBLIC keys, but NEVER both of them.
 * This may be consider a bug, so if there is any request to include both if the
 * user is the owner of the blog, then I may reconsider it.
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

global $_REQUEST,$wp_admin_url;
$current_status = isset($_REQUEST['mg_status'])?$_REQUEST['mg_status']:'';
$current_group  = isset($_REQUEST['mg_group_id'])?$_REQUEST['mg_group_id']:'';
?>
<script type="text/javascript">

var mygengo_jobs=new Array();
var current_page=1;
var jobs_per_page=10;
var number_pages=1;

function go(target_page) {
	current_page = target_page;
	var tbody = document.getElementById('jobs-body');
	if ( tbody.hasChildNodes() ) {
		while ( tbody.childNodes.length >= 1 ) {
			tbody.removeChild( tbody.firstChild );       
		} 
	}
	var idx = (jobs_per_page*current_page)-jobs_per_page;
	refreshJob(idx, 0);
}

function back() {
	if (current_page > 1) {
		var tbody = document.getElementById('jobs-body');
		if ( tbody.hasChildNodes() ) {
			while ( tbody.childNodes.length >= 1 ) {
				tbody.removeChild( tbody.firstChild );       
			} 
		}
		var idx = (jobs_per_page*--current_page)-jobs_per_page;
		refreshJob(idx, 0);
	}
}

function next() {
	if (current_page < number_pages) {
		var tbody = document.getElementById('jobs-body');
		if ( tbody.hasChildNodes() ) {
			while ( tbody.childNodes.length >= 1 ) {
				tbody.removeChild( tbody.firstChild );       
			} 
		}
		var idx = (jobs_per_page*++current_page)-jobs_per_page;
		refreshJob(idx, 0);
	}
}

function processJobs() {
	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
		var jobs = JSON.parse(xmlhttp.responseText);
		if (jobs.opstat == 'error') {
			alert('Error code: ' + jobs.err.code + ' ' + jobs.err.msg);
			return;
		}
		var idx=0;
		var callidx = 1;
		for (var job in jobs) {
			var jobid = jobs[job].job_id;
			mygengo_jobs[idx++] = jobid;
			if (idx == ((current_page-1)*jobs_per_page + 1)) {
				refreshJob(idx-1, 0);
			}
		}
		number_pages = Math.ceil(mygengo_jobs.length / jobs_per_page);
		var nb = document.getElementById('navbar');
		var ih = '| ';
		for(var i=1; i<=number_pages; i++) {
			ih += "<li><a href='javascript:void(0);' onclick='setTimeout(go, 100, ["+i+"])'>"+i+"</a> |</li>";
		}
		nb.innerHTML = ih;
	}
}

function refreshJobs(filter) {
	getJobs(processJobs,filter);
}

function processJob(idx, cnt) {
	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
		var job = JSON.parse(xmlhttp.responseText);
		if (job.opstat == 'error') {
			alert('Error code: ' + jobs.err.code + ' ' + jobs.err.msg);
			return;
		}
		if(job && job.length > 0) {
			printRow(JSON.parse(job[0]));
			refreshComments(idx, cnt);
		}
	}
}

function refreshJob(idx, cnt) {
	if (cnt < jobs_per_page) {
		var fn = function() { processJob(idx,cnt)};
		getJob(mygengo_jobs[idx], fn);
	}
}

function processComments(idx, cnt) {
	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
		var job = JSON.parse(xmlhttp.responseText);
		if (job.opstat == 'error') {
			alert('Error code: ' + jobs.err.code + ' ' + jobs.err.msg);
			return;
		}
		printComments(job.job_id, job.comments, job.unread);
		refreshJob(idx+1, cnt+1);
	}
}

function refreshComments(idx, cnt) {
	if (cnt < jobs_per_page) {
		var fn = function() { processComments(idx,cnt)};
		getComments(mygengo_jobs[idx], fn);
	}
}

var rowclass = 'alternate' == (rowclass=='') ? 'alternate' : '';

var jobs_columns = [<?php $str = ""; $keys = array_keys($job_headers); foreach($keys as $key) { $str .= ",'".$key."'"; } echo substr($str,1); ?>];
var hidden       = [""];

if(!Array.indexOf){
	Array.prototype.indexOf = function(obj){
		for(var i=0; i<this.length; i++){
			if(this[i]==obj){
				return i;
			}
		}
		return -1;
	}
}	

function printRow(job) {

	var jb = document.getElementById('jobs-body');
	var rowTag = document.createElement('tr');
	rowTag.setAttribute('id','job-'+job.job_id);
	rowTag.setAttribute('class', rowclass + ' status-'+job.job_status+' iedit');
	rowTag.setAttribute('valign', 'top');
	jb.appendChild(rowTag);

	var len = jobs_columns.length;
	for (var i = 0; i < len; i++) {
		var row = "";
		var cellTag     = document.createElement('td');
		var column_name = jobs_columns[i];
		cellTag.setAttribute('class', column_name+' column-'+column_name);
		var style       = '';
		if (hidden.indexOf(column_name) > -1) {
			style = 'display:none;';
		}
		cellTag.setAttribute('style', style);

		if (column_name == 'job_id') {
			cellTag.setAttribute('class', 'post-title column-title');
			edit_link  = "<?php echo $wp_admin_url; ?>/admin.php?page=mygengo.phpjobs&action=view&job_id="+job.job_id;
			row += "<strong><a class='row-title' href='"+edit_link+"'>"+job.job_id+"</a></strong><div style='margin:0; padding:0;'>"+job.job_slug+"</div>";
		} else if (column_name == 'job_ctime') {
			var jobdate = unixtimetodate(job.job_ctime);
			row += ""+jobdate+"";
		} else if (column_name == 'comments') {
			row += "<div id='pending-comments-"+job.job_id+"' class='post-com-count-wrapper'></div>";
		} else {
			row += ""+job[column_name]+"";
		}
		cellTag.innerHTML = row;
		rowTag.appendChild(cellTag);
	}
}

function printComments(job_id, comments, unread) {
	var comment_cell = document.getElementById('pending-comments-'+job_id);
	if (comment_cell) {
		var cell_content = (unread > 0) ? '<strong>' : '';
		cell_content    += '<a href="javascript:void(0);" title="'+unread+' new" class="post-com-count"><span class="comment-count">'+comments+'</span></a>';
		cell_content    += (unread > 0) ? '<strong>' : '';
		comment_cell.innerHTML = cell_content;
	}
}


window.onload = function() {
	setTimeout(refreshJobs, 100, ['&mg_status=<?php echo $current_status;?>&mg_group_id=<?php echo $current_group;?>']);
}
</script>

        <div class="wrap">

        <h2><?php _e('MyGengo Translator'); ?></h2>

	<div class="tablenav">
	<form id="jobs-form" action="<?php echo mygengo_getLink($wp_admin_url.'/admin.php',array(),true);?>" method="post">
		<ul id="navbar" class="subsubsub"></ul>
		<div class="alignleft actions">
			<select name="mg_status">
<?php 
	echo mygengo_generate_select_from_status($current_status,"<option value=''>Show all statuses</option>"); 
?>
			</select>
			<input type="submit" id="job-query-submit" value="Filter" class="button-secondary" />
		</div>
		<div class="alignleft actions">
			<select name="mg_group_id">
<?php 
	echo mygengo_generate_select_from_groups($current_group,"<option value=''>Show all groups</option>"); 
?>
			</select>
			<input type="submit" id="group-query-submit" value="Filter" class="button-secondary" />
		</div>
		<br class="clear" />
	</form>
	</div>
	<div class="clear"></div>

	<table class="widefat post fixed" cellspacing="0">
        	<thead>
        		<tr>
				<?php print_column_headers('mygengo.phpjobs'); ?>
		        </tr>
	        </thead>
	        <tfoot>
        		<tr>
				<?php print_column_headers('mygengo.phpjobs', false); ?>
		        </tr>
	        </tfoot>
        	<tbody id="jobs-body">
        	</tbody>
	</table>
	</div>
<?php
?>
