<?php
/*
	Plugin Name:	Notices
	Plugin URI:		http://www.sterling-adventures.co.uk/blog/2008/06/01/notices-ticker-plugin/
	Description:	A plugin which adds a widget with a scrolling "ticker" of notices.
	Author:			Peter Sterling
	Version:		6.1
	Changes:		0.1 - Initial version.
					0.2 - Ticker's "scrollamount" option set, thanks to Klaus.
					0.3 - Error with management menu access fixed.
					0.4 - Added ticker direction, with thanks to Shaunak Sontakke.
					2.0 - Now has fade-in-out efect - many thanks to Alex Gonzalez-Vinas for the idea and motivation.
					2.1 - Javascript uses an object to allow multiple tickers (i.e. widget and paged).
					3.0 - Option to 'tick' recent posts.
					4.0 - Editable start date (allows future notices).  Thanks to Jackey van Melis for the idea.
					5.0 - Add support for notices from a Twitter feed (requires Twitter on Publish plug-in).
					5.1 - Javascript type attribute.
					6.0 - Security enhancements for XSS attacks.
					6.1 - Minor fix to v6.0 for updating data.
	Author URI:		http://www.sterling-adventures.co.uk/
*/

// Default options...
$notices_options = get_option('notices_widget');
if(!is_array($notices_options)) {
	// Options do not exist or have not yet been loaded so we define standard options...
	$notices_options = array(
		'title' => 'Notices',
		'credit' => 'on',
		'speed' => '4',
		'pause' => 'on',
		'limit' => '3',
		'twitter' => 'off',
		'direction' => 'LEFT'
	);
	update_option('notices_widget', $notices_options);
}


// Once only at plugin activation.
function activate_notices()
{
	global $wpdb, $table_prefix;

	// Add administration capability...
	$role = get_role('administrator');
	$role->add_cap('manage_notices');

	// Create MySQL database table...
	include_once(ABSPATH . '/wp-admin/upgrade-functions.php');
	$ddl = "create table " . $table_prefix . "notices (notice_ID bigint(20) NOT NULL auto_increment, active varchar(1) NOT NULL default 'Y', notice_date datetime NOT NULL, notice varchar(500) default NULL, valid smallint(2) default 3, category smallint(2) default NULL, link varchar(255) default NULL, PRIMARY KEY (notice_ID), KEY notice_date (notice_date))";
	return maybe_create_table($table_prefix . 'notices', $ddl);
}


// Get post titles for notices ticker.
function get_recent_posts($sep)
{
	global $notices_options;
	$result = '';
	$dots = false;
	$posts = get_posts("numberposts={$notices_options['limit']}");
	foreach($posts as $post) {
		if($dots) $result .= $sep;
		$result .= $post->post_title;
		$dots = true;
	}
	return $result;
}


// Get notices from database.
function get_seperated_notices($sep)
{
	global $wpdb, $table_prefix, $notices_options;

	$limit = $notices_options['limit'];

	$output = '';
	$dots = false;

	if($notices_options['recent'] == 'B') $output = get_recent_posts($sep);
	if(!empty($output)) $dots = true;

	$cats = get_the_category(); 
    $cat = $cats[0];
	$catID = $cats[0]->term_id;	
	$notices = $wpdb->get_results("select notice, link from {$table_prefix}notices where category = ".$catID." and active = 'Y' and (adddate(notice_date, valid) > now() or valid = 0) and notice_date <= now() order by notice_date DESC limit {$limit}");
	if($notices) {
		foreach($notices as $notice) {
			if ($notice->link != '') {
				$notice->notice = '<a href="' . $notice->link . '">' . $notice->notice . '</a>';
			}
			if($dots) $output .= $sep;
			$dots = true;
			$output .= ' ' . $notice->notice . ' ';
		}
	}

	if($notices_options['recent'] == 'A') {
		$posts = get_recent_posts($sep);
		if(!empty($output) && !empty($posts)) $output .= $sep . $posts;
		else if(empty($output)) $output = $posts;
	}

	if($notices_options['twitter'] == 'on' && function_exists('get_twitter_data_for_notices')) {
		$tweets = get_twitter_data_for_notices($sep, $notices_options['limit']);
		if(!empty($output) && !empty($tweets)) $output .= $sep . $tweets;
		else if(empty($output)) $output = $tweets;
	}

	return $output;
}


// Helper function to generate ticker output...
function get_ticker_content($name)
{
	global $notices_options;

	if($notices_options['direction'] == 'FADE') {
		$output  = "<div id='$name' class='ticker'></div>\n";
		$output .= "<script  type='text/javascript' language='Javascript'>\n";
		$output .= "tick_" . $name . " = new NoticesTicker('" . $name . "', " . (int)($notices_options['speed']) * 1000 . ", " . ($notices_options['pause'] == 'on' ? 'true' : 'false') . ");\n";
		$output .= "tick_" . $name . '.Init(["' . get_seperated_notices('", "') . "\"]);\n";
		$output .= "</script>" . "\n";
	}
	else {
		$output  = '<marquee direction="' . $notices_options['direction'] . '" class="ticker" scrollamount="' . $notices_options['speed'] . '"' . ($notices_options['pause'] == 'on' ? ' onmouseover="this.stop()" onmouseout="this.start()">' : '>');
		$output .= get_seperated_notices(' &nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp; ');
		$output .= '</marquee>';
	}
	return $output;
}


// Notice widget...
function notices_widget_init()
{
	// Check widgets are activated.
	if(!function_exists('register_sidebar_widget')) return;

	// Notice widget.
	function notices_widget($args)
	{
		global $notices_options;
		extract($args);

		echo $before_widget, $before_title, $notices_options['title'], $after_title;
		echo get_ticker_content('ticker_widget');
		echo $after_widget;
	}

	// Control for notices widget.
	function notices_widget_control()
	{
		global $notices_options;
		$newoptions = $notices_options;

		// This is for handing the control form submission.
		if($_POST['notices-submit']) {
			$newoptions['title'] = strip_tags(stripslashes($_POST['notices-title']));
			$newoptions['limit'] = strip_tags(stripslashes($_POST['notices-limit']));
			if($notices_options != $newoptions) {
				update_option('notices_widget', $newoptions);
				$notices_options = $newoptions;
			}
		}

		// Control form HTML for editing options. ?>
		<label for="notices-title" style="line-height: 35px; display: block;">Title <input type="text" name="notices-title" value="<?php echo $notices_options['title']; ?>" /></label>
		<label for="notices-limit" style="line-height: 35px; display: block;">Limit <input type="text" name="notices-limit" value="<?php echo $notices_options['limit']; ?>" /></label>
		<input type="hidden" name="notices-submit" value="1" />
	<?php }

	wp_register_sidebar_widget('notices', 'Notices', notices_widget, array('classname' => 'noticees_widget', 'description' => "Display a scrolling ticker of notices"));
	wp_register_widget_control('notices', 'Notices', 'notices_widget_control', array('width' => 200));
}


// Add management menu to administration interface...
function manage_notices()
{
	if(!current_user_can('manage_notices')) wp_die(__('Cheatin&#8217; uh?'));

	global $wpdb, $table_prefix, $allowedtags;

	$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

	$msg = '';

	if(isset($_POST['submit'])) {
		if(function_exists('wp_create_nonce')) {
			if(!wp_verify_nonce($_POST['notices_noncename'], 'notices-update')) wp_die(__('Cheatin&#8217; uh?'));
		}
		$notice = wp_kses($_POST['notice'], $allowedtags);
		$notice = mysql_real_escape_string(stripslashes($_POST['notice']));
		$_POST['valid'] = mysql_real_escape_string(stripslashes($_POST['valid']));

		$msg = 'Notice added';
		$wpdb->query("insert into {$table_prefix}notices (notice_date, notice, valid, category, link) values (str_to_date(concat('" . $_POST['day'] . ' ' . $_POST['month'] . ' ' . $_POST['year'] . " ', curtime()), '%e %c %Y %T'), '" . $notice . "', '" . $_POST['valid'] . "', '" . $_POST['cat'] . "', '" . $_POST['link'] . "')");
	}

	if(!empty($_GET['act'])) {
		if(function_exists('wp_create_nonce')) {
			if(!wp_verify_nonce($_GET['nonce'], 'notices-update')) wp_die(__('Cheatin&#8217; huh?'));
		}
		$notice = wp_kses($_GET['notice'], $allowedtags);
		$notice = mysql_real_escape_string(stripslashes($_GET['notice']));
		$_GET['id'] = mysql_real_escape_string(stripslashes($_GET['id']));
		
		switch($_GET['act']) {
		case 'update':
			$msg = "Notice {$_GET['id']} updated";
			$_GET['valid'] = mysql_real_escape_string(stripslashes($_GET['valid']));
			$_GET['category'] = mysql_real_escape_string(stripslashes($_GET['category']));
			$wpdb->query("update {$table_prefix}notices set notice_date = str_to_date(concat('" . $_GET['day'] . ' ' . $_GET['month'] . ' ' . $_GET['year'] . " ', curtime()), '%e %c %Y %T'), notice = '" . $notice . "', active = '" . ($_GET['active'] == 'true' ? 'Y' : 'N') . "', valid = '" . $_GET['valid'] . "', category = '" . $_GET['category'] . "' where notice_ID = '{$_GET['id']}'");
			break;

		case 'delete':
			$msg = "Notice {$_GET['id']} deleted";
			$wpdb->query("delete from {$table_prefix}notices where notice_ID = '{$_GET['id']}'");
			break;
		}
	}

	// Output message.
	if(!empty($msg)) echo "<div id='message' class='updated fade'><p>{$msg}.</p></div>";
?>
<?php 
//wp_enqueue_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js', '', '1.8.0', false);

$homeURL = get_settings('home');
wp_enqueue_script('jquery-notices', $homeURL . '/wp-content/plugins/notices/admin.js', 'jquery', '', true);
?>
	<script  type="text/javascript" language="Javascript">

	</script>

	<div class="wrap">
		<h2>Notices</h2>
		Set Notices for the news ticker in the header of the website.

		<h3>Add New Notice</h3>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=manage-notices&updated=true">
			<table class='widefat'>
				<thead>
					<tr><th>Notice Text</th><th>Link to Post or Page <em>(optional)</em></th><th>Category</th><th>Start Date</th><th>Valid for</th><th colspan=2 style="text-align: center;">&nbsp;</th></tr>
				</thead>	
				<tbody>
				<tr>

					<td><input type="text" name="notice" size='60' maxlength="500" /></td>
					<td>
						 <select name="link" id="link" style="width:200px;">
						 <option value="" selected="selected">No link</option>
						 <?php
						 global $post;
						 $args = array( 'numberposts' => -1,'post_type'  =>  array('post', 'page'),);
						 $posts = get_posts($args);
						 foreach( $posts as $post ) : setup_postdata($post); ?>
										<option value="<? echo substr(get_permalink(), strlen(get_settings('home'))); ?>"><?php the_title(); ?> (<?php echo $post->post_type; ?>)</option>
						 <?php endforeach; ?>
						 </select>										
					</td>
					<td><?php wp_dropdown_categories(array('id'=>'')) ?></td>
					<td>
						<select name="day">
							<?php
								for($j = 1; $j < 32; $j++) {
									printf('<option value="%d" %s>%d</option>', $j, (date('j') == $j ? 'selected' : ''), $j);
								}
							?>
						</select>
						<select name="month">
							<?php
								for($j = 1; $j < 13; $j++) {
									printf('<option value="%d" %s>%s</option>', $j, (date('n') == $j ? 'selected' : ''), $months[$j - 1]);
								}
							?>
						</select>
						<input type="text" value="<?php echo date('Y'); ?>" name="year" size="5" maxlength="4" />
					</td>

					<td><input type="text" name="valid" size='3' value='3' /> days</td>
					<td><input type="submit" name="submit" value="Add Notice" class="button-primary" style="float: right;"/></td>
				</tr>
				</tbody>
			</table>

			<?php
				$notices_nonce = '';
				if(function_exists('wp_create_nonce')) {
					$notices_nonce = wp_create_nonce('notices-update');
					printf('<input type="hidden" name="notices_noncename" value="%s" />', $notices_nonce);
				}
			?>
		</form>
	<br><br>
		<h3>Manage Notices</h3>
		<style>
			.widefat td.btn {padding-top:7px;}
			abbr {cursor:help}
		</style>
		<table class='widefat'>
			<thead>
				<tr><th>ID</th><th>Notice Text</th><th>Link</th><th>Category</th><th>Start Date</th><th>Valid for</th><th style="text-align: center;">Active?</th><th colspan=2 style="text-align: center;">Action</th></tr>
			</thead>
			<tbody><?php
				$notices = $wpdb->get_results("select	notice_ID ID,
														notice,
														year(notice_date) year,
														month(notice_date) month,
														day(notice_date) day,
														active,
														valid,
														category,
														link
												from	{$table_prefix}notices
												order by notice_date DESC");
				$i = 0;
				foreach($notices as $notice) {
					printf('<tr%s>', ($i % 2 == 0 ? " class='alternate'" : ""));
					printf('<td>%s.</td>', $notice->ID);
					printf('<td><input type="text" value="%s" id="notice-%s" size="58" maxlength="500" /></td>', $notice->notice, $i);
					echo '<td>';
						if ($notice->link != '') {
							echo '<a href="/' . $notice->link . '" title="/' . $notice->link . '" target="_blank" style="display:block;margin-top:3px;text-decoration:underline;">Links here</a>';
						};
					echo '</td>';					
					echo '<td class="'.$notice->category.'">';
					wp_dropdown_categories(array('id'=>'category-'.$i,'class'=>'category'));
					echo '</td>';	

					printf('<td>');
					printf('<select id="day-%s">', $i);
						for($j = 1; $j < 32; $j++) {
							printf('<option value="%d" %s>%d</option>', $j, ($notice->day == $j ? 'selected' : ''), $j);
						}
					?></select><?php
					printf('<select id="month-%s">', $i);
						for($j = 1; $j < 13; $j++) {
							printf('<option value="%d" %s>%s</option>', $j, ($notice->month == $j ? 'selected' : ''), $months[$j - 1]);
						}
					?></select><?php
					printf('<input type="text" value="%s" id="year-%s" size="5" maxlength="4" />', $notice->year, $i);
					printf('</td>');
					printf('<td><input type="text" value="%s" id="valid-%s" size="3" maxlength="2" /> days</td>', $notice->valid, $i);	
					printf('<td style="text-align: center;"><input type="checkbox" id="active-%s" %s /></td>', $i, ($notice->active == 'Y' ? 'checked' : ''));					
					printf('<td class="btn"><a href="?page=manage-notices&act=update&id=%1$s&nonce=%3$s" class="edit button-primary" onclick="set_input_values(%2$s);" id="href-%2$s">Update</a></td>', $notice->ID, $i, $notices_nonce);
					printf('<td class="btn"><a href="?page=manage-notices&act=delete&id=%s&nonce=%s" class="delete button-secondary">Delete</a></td>', $notice->ID, $notices_nonce);
					printf("</tr>\n");
					$i++;
				}
			?></tbody>
		</table>

		<h3>Notices Usage</h3>
		<ul>
			<li>Define notice text above.  HTML is allowed.</li>
			<li>A valid number of days of 0 makes the notice show indefinitely.</li>
			<li><b>Be careful to avoid <code>"</code> (double quote characters), use <code>'</code> (single quotes) instead</b>.</li>
			<li>Use the <em>Notices</em> widget (<em>Appearance &raquo; Widgets</em>) to show a sidebar widget that shows a chosen number of the most recent notices.</li>
			<li>Or use this <code>&lt;?php put_ticker( [<u>true</u> | false] ); ?&gt;</code> in your template files.  Where <code>true</code> or <code>false</code> determines if the ticker should be hidden when there are no notices to scroll.  For example, <code>&lt;?php put_ticker(false); ?&gt;</code> only shows the ticker when there are notices to scroll, whereas <code>&lt;?php put_ticker(true); ?&gt;</code> always shows the ticker - even an empty one.</li>
		</ul>
	</div>
<?php
}


// Manage options.
function notices_options_page()
{
	global $notices_options;

	if(isset($_POST['option-submit'])) {
		$options_update = array (
			'credit' => ($_POST['credit'] == 'on' ? 'on' : 'off'),
			'speed' => $_POST['speed'],
			'pause' => $_POST['pause'],
			'limit' => $_POST['limit'],
			'recent' => $_POST['recent'],
			'twitter' => ($_POST['twitter'] == 'on' ? 'on' : 'off'),
			'direction' => $_POST['direction']			
		);
		update_option('notices_widget', $options_update);
	}
	$notices_options = get_option('notices_widget');
?>
	<div class="wrap">
		<h2>Notices Options</h2>
		Control the behaviour of the Notices ticker.

		<h3>Notice Options</h3>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=' . basename(__FILE__); ?>&updated=true">
			<table class='form-table'>
				<tr>
					<td>Limit:</td>
					<td><input type='text' name='limit' value='<?php echo $notices_options['limit']; ?>' size='3' /></td>
					<td><small>The maximum number of most recently updated notices to show.</small></td>
				</tr>
				<tr>
					<td>Speed:</td>
					<td><input type='text' name='speed' value='<?php echo $notices_options['speed']; ?>' size='3' /></td>
					<td><small>The speed of the ticker tape (smaller = slower).  Or for fades, the number of seconds between notices.</small></td>
				</tr>
				<tr>
					<td>Behaviour:</td>
					<td><select name="direction">
						<option value="LEFT" <?php if($notices_options['direction'] == "LEFT") echo "selected"; ?> >Left</option>
						<option value="RIGHT" <?php if($notices_options['direction'] == "RIGHT") echo "selected"; ?> >Right</option>
						<option value="UP" <?php if($notices_options['direction'] == "UP") echo "selected"; ?> >Up</option>
						<option value="DOWN" <?php if($notices_options['direction'] == "DOWN") echo "selected"; ?> >Down</option>
						<option value="FADE" <?php if($notices_options['direction'] == "FADE") echo "selected"; ?> >Fade</option>
					</select></td>
					<td><small>Set the behaviour (left, right, up, down or fade).</small></td>
				</tr>
				<tr>
					<td>Pause:</td>
					<td><input type="checkbox" name="pause" <?php echo $notices_options['pause'] == 'on' ? 'checked' : ''; ?> /></td>
					<td><small>Pause the ticker's scrolling on <code>mouseover</code>.</small></td>
				</tr>
				<tr>
					<td>Include Recent Posts:</td>
					<td>
						<input type="radio" name="recent" value="N" <?php echo $notices_options['recent'] == 'N' ? 'checked' : ''; ?> /> Recent posts not included,<br />
						<input type="radio" name="recent" value="B" <?php echo $notices_options['recent'] == 'B' ? 'checked' : ''; ?> /> Recent posts shown before notices, or<br />
						<input type="radio" name="recent" value="A" <?php echo $notices_options['recent'] == 'A' ? 'checked' : ''; ?> /> Recent posts shown after notices.
					</td>
					<td><small>Includes recent posts (number from <i>limit</i> above) in notices.</small></td>
				</tr>
				<tr>
					<td>Include Twitter feed:</td>
					<td>
						<?php if(file_exists(ABSPATH . '/wp-content/plugins/twitter-on-publish/twitter-on-publish.php')) { ?>
							<input type="checkbox" name="twitter" <?php echo $notices_options['twitter'] == 'on' ? 'checked' : ''; ?> />
						<?php } ?>
					</td>
					<td><small>Includes <a href="http://twitter.com/" target="_blank">Twitter</a> feed in notices.
						<?php if(!file_exists(ABSPATH . '/wp-content/plugins/twitter-on-publish/twitter-on-publish.php')) { ?>
							(requires <a href='http://www.sterling-adventures.co.uk/blog/2009/05/01/simple-wordpress-twitter-plugin/' title='Twitter Plug-in'>Simple WordPress Twitter Plug-in</a>)
						<?php } ?>
					</small></td>
				</tr>
				<tr>
					<td>Credit:</td>
					<td><input type="checkbox" name="credit" <?php echo $notices_options['credit'] == 'on' ? 'checked' : ''; ?> /></td>
					<td><small>Includes an invisible credit to <a href='http://www.sterling-adventures.co.uk/' title='Sterling Adventures'>Sterling Adventures</a></small></td>
				</tr>
			</table>
			<p class="submit"><input type="submit" class="button-primary" name="option-submit" value="Update Notice Options" /></p>
		</form>
	</div>
<?php
}


// Add management menu to administration interface...
function manage_notices_menu()
{
	if(function_exists('add_submenu_page')) {
		if(current_user_can('manage_notices')) {
			//add_management_page('Manage Notices', 'Notices', 2, 'manage-notices', 'manage_notices');
			add_menu_page('Manage Notices', 'Notices', 2, 'manage-notices', 'manage_notices');
		}
	}
	if(function_exists('add_options_page')) {
		add_options_page('Notice Options', 'Notices', 8, basename(__FILE__), 'notices_options_page');
	}
}


// Output header (CSS styles, Javascript) for notices in the header.
function add_notice_styles()
{
	global $notices_options;

	//printf("<link rel='stylesheet' media='screen' type='text/css' href='%s/wp-content/plugins/notices/notices.css' />\n", get_settings('home'));
	if($notices_options['direction'] == 'FADE') {
		printf("<script type='text/javascript' src='%s/wp-content/plugins/notices/notices.js'></script>\n", get_settings('home'));
	}
}


// Output the ticker for use within template files.
function put_ticker($show = true)
{
	$ticker = get_ticker_content('ticker');
	if((!$show && !empty($ticker)) || $show) {
		print("<div id='ticker'><strong>News</strong>  <div class='ticker-div'>");
		echo $ticker;
		print("</div></div>");
	}
}


register_activation_hook(__FILE__, 'activate_notices');
add_action('admin_menu', 'manage_notices_menu');
add_action('plugins_loaded', 'notices_widget_init');
add_action('wp_head', 'add_notice_styles');
?>