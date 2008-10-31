<?php /*
Plugin Name: CommentMilk
Plugin URI: http://www.johncow.com/commentmilk/
Description: This is the CommentMilk Plugin. By Johncow.com
Version: 1.03
Author: johncow
Author URI: http://www.johncow.com/commentmilk/
*/

session_start();

add_action('admin_menu', 'show_milk_option');
add_action('comment_form','add_milk_area');
add_action('comment_post', 'comment_post_area');
add_action('comment_post','grab_latest_post');
add_action("widgets_init", "add_authors_top_commentor_init");
add_filter('get_comment_author_link', 'show_comment_content', 11);
add_filter('comment_text', 'show_comment_text', 10);

function Create_Campaign_Table()
{
	global $wpdb;
	$campaign_table = "CREATE TABLE `".$wpdb->prefix."campaigns` (
	`id` BIGINT NOT NULL AUTO_INCREMENT ,
    `name` TEXT NOT NULL ,
    `nofollow` VARCHAR( 5 ) DEFAULT 'No' NOT NULL,
    `keyword_display` VARCHAR( 300 ) DEFAULT '' NULL,
	`keyword_hyperlink` VARCHAR( 5 ) DEFAULT 'No' NOT NULL,
	`keyword_text` VARCHAR( 500 ) DEFAULT '' NULL,
	`keyword_style` VARCHAR( 500 ) DEFAULT '' NULL,
	`is_blank` VARCHAR( 5 ) DEFAULT 'No' NOT NULL,
	`is_subscribe` VARCHAR( 5 ) DEFAULT 'No' NOT NULL,
	`subscribe_text` VARCHAR( 500 ) DEFAULT '' NOT NULL,
	`subscribe_points` INT UNSIGNED DEFAULT '1' NOT NULL,
	`is_assign` VARCHAR( 50 ) DEFAULT '0' NOT NULL,
	`post_id` BIGINT UNSIGNED DEFAULT '0' NOT NULL,
  PRIMARY KEY ( `id` )
  ) TYPE=MyISAM;";
  require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
  maybe_create_table(($wpdb->prefix."campaigns"),$campaign_table);
  $sql = "SELECT * FROM " . $wpdb->prefix . "campaigns";
  $test_results = $wpdb->get_results($sql);
  if( count($test_results) < 1 )
	if(!$wpdb->query("INSERT INTO `".$wpdb->prefix."campaigns` ( `name`, `nofollow`, `keyword_display`, `keyword_hyperlink`, `keyword_text`, `keyword_style`, `is_blank`, `is_subscribe`, `subscribe_text`, `subscribe_points`, `is_assign`, `post_id` ) VALUES ( 'Default', 'No', '[name]s latest: [link]', 'No', 'Link Keyword Phrase to above entered URI. If no keyword is entered it will instead try and fetch latest link from above entered URL. Powered by [CommentMILK]', 'border:5px solid red; display:block; padding:4px;', 'No', 'No', 'Subscribed to Newsletter', 1, 'No', 0 )")) {
		die(mysql_error());
	}
}

function show_milk_option() {
  // Add a new submenu under Options:
  	add_options_page('CommentMilk', 'CommentMilk', 8, 'commentmilk', 'milk_option_page');
  	add_option('milk_link_permit','No');
	add_option('criteria_post_id','1');
	add_option('milk_keyword_allow','No');
	add_option('keyword_checkbox_text','Link Keyword Phrase to above entered URI. If no keyword is entered it will instead try and fetch latest link from above entered URL. Powered by [CommentMILK]');
	add_option('subscribe_checkbox_text','Subscribed to Newsletter');
	add_option('milk_blank_link','No');
	add_option('keyword_display','[name]s latest: [link]');
	add_option('is_show_subscribe','No');
	add_option('first_extra_form','-Select-');
	add_option('first_extra_text','Blog Post URL');
	add_option('first_extra_point','0');
	add_option('first_extra_form1','-Select-');
	add_option('first_extra_text1','Second Criteria');
	add_option('first_extra_point1','0');
	add_option('first_extra_form2','-Select-');
	add_option('first_extra_text2','Third Criteria');
	add_option('first_extra_point2','0');
	add_option('first_extra_form3','-Select-');
	add_option('first_extra_text3','Fourth Criteria');
	add_option('first_extra_point3','0');
	add_option('first_extra_form4','-Select-');
	add_option('first_extra_text4','Fifth Criteria');
	add_option('first_extra_point4','0');
	add_option('first_extra_url','0');
	add_option('first_extra_url1','0');
	add_option('first_extra_url2','0');
	add_option('first_extra_url3','0');
	add_option('first_extra_url4','0');
	add_option('assign_points_allow','No');
	add_option('subscribe_points','1');
	add_option('css_style_text','border:5px solid red; display:block; padding:4px;');
	add_option('milk_topcommentor_count',20);
	add_option('is_all_post_comment','No');
	Create_Campaign_Table();
	add_option('camp_flag','none');
	add_option('camp_name','none');
	add_option('camp_id','0');
	add_option('affiliate_id','0');
	add_option('milk_exclude','');
}


function grab_latest_post($comment_id) {
global $wpdb;

	$sql = "SELECT * FROM " . $wpdb->prefix . "comments WHERE comment_ID='" . $comment_id . "'";
	$results = $wpdb->get_results($sql);

	if ($results[0]->comment_author_url != "") {


	// Check if RSS feed exists

	$userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';
$target_url = $results[0]->comment_author_url."/?feed=rss2";

$ch = curl_init();
curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
curl_setopt($ch, CURLOPT_URL,$target_url);
curl_setopt($ch, CURLOPT_FAILONERROR, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$html = curl_exec($ch);
if ($html) {

	if (ereg("<\?xml",$html)) {
		if ($xml = simplexml_load_string($html)) {
			$items = $xml->channel;
			$latest = $items->item[0];
	
			$wpdb->query("ALTER TABLE $wpdb->comments ADD COLUMN latest_post_title VARCHAR(200) NULL default ''");
			$wpdb->query("ALTER TABLE $wpdb->comments ADD COLUMN latest_post_link VARCHAR(200) NULL default ''");
		
			$sql = "UPDATE " . $wpdb->prefix . "comments SET latest_post_title='". $latest->title ."' WHERE comment_ID='" . $comment_id ."'";
			$wpdb->query($sql);
			$sql = "UPDATE " . $wpdb->prefix . "comments SET latest_post_link='". $latest->link ."' WHERE comment_ID='" . $comment_id ."'";
			$wpdb->query($sql);
		}
	}

}

	}

}

function grab_latest_rss() {
	global $wpdb;


	// Check if RSS feed exists

	$userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';
	$target_url = "http://www.johncow.com/category/commentmilk/feed";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_URL,$target_url);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$html = curl_exec($ch);
	if ($html) {

		if ($xml = simplexml_load_string($html)) {
			$items = $xml->channel;
			$latest = $items->item[0];
			$return = "<li><a href=\"" . $latest->link . "\" target=\"_blank\">" . $latest->title ."</a></li>";
	
		}

	}
	
	return $return;

}

function comment_post_area($s)
{
	global $wpdb,$user_ID;
	$is_admin = false;
	if( get_option('is_all_post_comment') == 'Yes' )
	{
		if( $user_ID )
		{
			$sql = "SELECT * FROM " . $wpdb->prefix . "users WHERE ID=$user_ID";
			$exist_user = $wpdb->get_results($sql);
			$admin_name = $exist_user[0]->user_login;
			if( $admin_name == 'admin' )
				$is_admin = true;
			$sql = "SELECT * FROM " . $wpdb->prefix . "comments WHERE comment_author='" . $exist_user[0]->display_name . "' AND comment_author_email = '" . $exist_user[0]->user_email . "' ORDER BY comment_date DESC";
			$results1 = $wpdb->get_results($sql);
			$new_comment_id = $results1[0]->comment_ID;
			$new_post_id = $results1[0]->comment_post_ID;
		}
		else
		{
			$comment_author = $_POST['author'];
			$comment_email = $_POST['email'];
			$sql = "SELECT * FROM " . $wpdb->prefix . "comments WHERE comment_author='" . $comment_author . "' AND comment_author_email = '" . $comment_email . "' ORDER BY comment_date DESC";
			$results = $wpdb->get_results($sql);
			$new_comment_id = $results[0]->comment_ID;
			$new_post_id = $results[0]->comment_post_ID;
		}
		if( get_option('milk_keyword_allow') == 'Yes' )
		{
			$is_keyword_data = $_POST['is_keyword'];
			if( $is_keyword_data == 'keyword' )
			{
				$keyword_text = $_POST['keyword_phrase_text'];
				$sql = "UPDATE " . $wpdb->prefix . "comments SET is_keyword_phrase = 'Y', keyword_phrase_text='$keyword_text
				' WHERE comment_ID = $new_comment_id";
				$wpdb->query($sql);
			}
		}
		if( $new_post_id == intval(get_option('criteria_post_id')) )
		{
			for( $idx=0; $idx<5; $idx++ )
			{
				if( $idx == 0 )
					$tmp_vars = 'first_extra_form';
				else
					$tmp_vars = 'first_extra_form' . $idx;
				if( get_option($tmp_vars) != '-Select-' )
				{
					if( $idx == 0 )
					{
						$first_criteria_text = $_POST['blog_post_text'];
						$is_first_criteria_text = $_POST['is_first_extra'];
					}
					else
					{
						$tmp_var = 'blog_post_text' . $idx;
						$tmp_var1 = 'is_first_extra' . $idx;
						$first_criteria_text = $_POST[$tmp_var];
						$is_first_criteria_text = $_POST[$tmp_var1];
					}
					if( get_option($tmp_vars) == 'Textbox' )
						$out_text = $first_criteria_text;
					else if( get_option($tmp_vars) == 'Checkbox' )
					{
						$out_text = $is_first_criteria_text;
						if( $idx == 0 )
							$tmp_p = "first";
						else
							$tmp_p = "first" . $idx;
						if( $out_text == $tmp_p )
							$out_text = "Y";
						else
							$out_text = "N";
					}
					$sql = "";
					if( $idx == 0 && $out_text != '' )
						$sql = "UPDATE " . $wpdb->prefix . "comments SET first_criteria='$out_text' WHERE comment_ID = $new_comment_id";
					else if( $out_text != '' )
						$sql = "UPDATE " . $wpdb->prefix . "comments SET first_criteria" . $idx . "='$out_text' WHERE comment_ID = $new_comment_id";
					if( $sql != '' )
						$wpdb->query($sql);
				}
			}
		}
		if( get_option('is_show_subscribe') == 'Yes' )
		{
			$is_newsletter = $_POST['is_subscribe_newsletter'];
			if( $is_newsletter == 'subscribe' )
			{
				$sql = "UPDATE " . $wpdb->prefix . "comments SET is_subscribe_newsletter = 'Y' WHERE comment_ID = $new_comment_id";
				$wpdb->query($sql);
			}
		}
	}
	return $s;
}

function milk_option_page(){
	global $wpdb;
	?>
	<div class="wrap">
	<form method="post" action="options.php" id="options" name="options" >
	<?php wp_nonce_field('update-options') ?>
	<h2>CommentMilk Wordpress Plugin</h2>
	<h3>Latest CommentMilk News</h3>
	<ul><?=grab_latest_rss()?></ul>

    <hr>
	<h3>General Settings</h3>
		<p>Do you want CommentMilk "Default" Campaign Enabled for all Posts&nbsp;&nbsp;
			<select id="is_all_post_comment" name="is_all_post_comment">
				<option <?php if(get_option('is_all_post_comment')=="Yes") {echo "selected=selected";}?> >Yes</option>
				<option <?php if(get_option('is_all_post_comment')=="No") { echo "selected=selected";}?> >No</option>
			</select>
		</p>
		<p>Enter the ID's of users you want to exclude from processing (seperate with commas)</p>
		<input type="text" id="milk_exclude" name="milk_exclude" value="<?php echo get_option('milk_exclude')?>" />
		<br />

    <hr>
    <h3>Current Running Campaigns</h3>
		<?php
		$camp_id_temp = get_option('camp_id');
		$camp_name_temp = get_option('camp_name');
		$camp_flag_temp = get_option('camp_flag');
		$sql = "";
		if( $camp_flag_temp == 'delete' )
			$sql = "DELETE FROM " . $wpdb->prefix . "campaigns WHERE id = " . intval($camp_id_temp) . " LIMIT 1";
		else if( $camp_flag_temp == 'create' )
			$sql = "INSERT INTO " . $wpdb->prefix . "campaigns (name, nofollow, keyword_display, keyword_hyperlink, keyword_text, keyword_style, is_blank, is_subscribe, subscribe_text, subscribe_points, is_assign, post_id ) VALUES('" . $camp_name_temp . "', '" . get_option('milk_link_permit') . "', '" . get_option('keyword_display') . "', '" . get_option('milk_keyword_allow') . "', '" . get_option('keyword_checkbox_text') . "', '" . get_option('css_style_text') . "', '" . get_option('milk_blank_link') . "', '" . get_option('is_show_subscribe') . "', '" . get_option('subscribe_checkbox_text') . "', '" . get_option('subscribe_points') . "', '" . get_option('assign_points_allow') . "', " . intval(get_option('criteria_post_id')) . ")";
		else if( $camp_flag_temp == 'update' )
			$sql = "UPDATE " . $wpdb->prefix . "campaigns SET name='" . $camp_name_temp . "', nofollow='" . get_option('milk_link_permit') . "', keyword_display='" . get_option('keyword_display') . "', keyword_hyperlink='" . get_option('milk_keyword_allow') . "', keyword_text='" . get_option('keyword_checkbox_text') . "', keyword_style='" . get_option('css_style_text') . "', is_blank='" . get_option('milk_blank_link') . "', is_subscribe='" . get_option('is_show_subscribe') . "', subscribe_text='" . get_option('subscribe_checkbox_text') . "', subscribe_points='" . get_option('subscribe_points') . "', is_assign='" . get_option('assign_points_allow') . "', post_id=" . intval(get_option('criteria_post_id')) . " WHERE id=" . $camp_id_temp;
		if( $sql != '' )
			if (!$wpdb->query($sql)) die(mysql_error());
		update_option('camp_flag','');
		?>
		<p>
			<table border="0" cellspacing="0" cellpadding="0" style="250px" id="camp_table" name="camp_table">
			<colgroup>
				<col width="150px">
				<col width="80px">
				<col width="70px">
			</colgroup>
			<?php
			$sql = "SELECT * FROM " . $wpdb->prefix . "campaigns ORDER BY id";
			$campaigns = $wpdb->get_results($sql);
			if( count($campaigns) != 0 ){
			?>
			<tr>
				<td style="display:none"><?php echo $campaigns[0]->id; ?></td>
				<td><?php echo $campaigns[0]->name; ?></td>
				<td style="display:none"><?php echo $campaigns[0]->nofollow; ?></td>
				<td style="display:none"><?php echo $campaigns[0]->keyword_display; ?></td>
				<td style="display:none"><?php echo $campaigns[0]->keyword_hyperlink; ?></td>
				<td style="display:none"><?php echo $campaigns[0]->keyword_text; ?></td>
				<td style="display:none"><?php echo $campaigns[0]->keyword_style; ?></td>
				<td style="display:none"><?php echo $campaigns[0]->is_blank; ?></td>
				<td style="display:none"><?php echo $campaigns[0]->is_subscribe; ?></td>
				<td style="display:none"><?php echo $campaigns[0]->subscribe_text; ?></td>
				<td style="display:none"><?php echo $campaigns[0]->is_assign; ?></td>
				<td style="display:none"><?php echo $campaigns[0]->post_id; ?></td>
				<td><a href="#camp_name" onclick="CampaignEdit(0)">Edit</a></td>
				<td>&nbsp;</td>
			</tr>
			<?php
			}
			if( count($campaigns) > 1 ){
				for( $i=1; $i<count($campaigns); $i++ ){
					$camp = $campaigns[$i];
			?>
			<tr>
				<td style="display:none"><?php echo $camp->id; ?></td>
				<td><?php echo $camp->name; ?></td>
				<td style="display:none"><?php echo $camp->nofollow; ?></td>
				<td style="display:none"><?php echo $camp->keyword_display; ?></td>
				<td style="display:none"><?php echo $camp->keyword_hyperlink; ?></td>
				<td style="display:none"><?php echo $camp->keyword_text; ?></td>
				<td style="display:none"><?php echo $camp->keyword_style; ?></td>
				<td style="display:none"><?php echo $camp->is_blank; ?></td>
				<td style="display:none"><?php echo $camp->is_subscribe; ?></td>
				<td style="display:none"><?php echo $camp->subscribe_text; ?></td>
				<td style="display:none"><?php echo $camp->is_assign; ?></td>
				<td style="display:none"><?php echo $camp->post_id; ?></td>
				<td><a href="#camp_name" onclick="CampaignEdit(<?php echo $i; ?>)">Edit</a></td>
				<td><a href="#" onclick="CampaignDelete(<?php echo $i; ?>)">Delete</a></td>
			</tr>
			<?php
				}
			}
			?>
			</table>
		</p>

    <hr>
    <h3>Campaign Settings</h3>
		<p><label for="camp_name">Campaign Name</label>&nbsp;&nbsp;<input type="text" id="camp_name" name="camp_name" style="width:150px" value=""></p>
		<input type="hidden" id="camp_id" name="camp_id" value="0">
		<input type="hidden" id="camp_flag" name="camp_flag" value="">

		<p><strong>Do you want to add rel="nofollow" to links?</strong></p>
		<select id="milk_link_permit" name="milk_link_permit">
			<option <?php if(get_option('milk_link_permit')=="Yes") {echo "selected=selected";}?> >Yes</option>
			<option <?php if(get_option('milk_link_permit')=="No") { echo "selected=selected";}?> >No</option>
		</select>

		<p><strong>Enter the text you want displayed in the comment</strong></p>
		<input type="text" id="keyword_display" name="keyword_display" style="width:900px" value="<?php echo get_option('keyword_display'); ?>">

		<p><strong>Do you want to allow users to enter a keyword phrase? This replaces their post URL's</strong></p>
		<select id="milk_keyword_allow" name="milk_keyword_allow">
			<option <?php if(get_option('milk_keyword_allow')=="Yes") {echo "selected=selected";}?> >Yes</option>
			<option <?php if(get_option('milk_keyword_allow')=="No") { echo "selected=selected";}?> >No</option>
		</select>

		<p><strong>Keyword Phrase: Enter text to replace message when keywords are enabled</strong></p>
		<input type="text" id="keyword_checkbox_text" name="keyword_checkbox_text" style="width:900px" value="<?php echo str_replace("lastest..","latest: ",get_option('keyword_checkbox_text')); ?>">

		<p><strong>CSS Styling for latest post</strong></p>
		<input type="text" id="css_style_text" name="css_style_text" style="width:600px" value="<?php echo get_option('css_style_text'); ?>">

		<p><strong>Do you want links to open in a new window (target="_blank")?</strong></p>
		<select id="milk_blank_link" name="milk_blank_link">
			<option <?php if(get_option('milk_blank_link')=="Yes") {echo "selected=selected";}?> >Yes</option>
			<option <?php if(get_option('milk_blank_link')=="No") { echo "selected=selected";}?> >No</option>
		</select>
		
    <hr>
    <h3>Contest Criteria</h3>
		<p><strong>Do you want to show Subscribe Newsletter Checkbox?</strong></p>
		<select id="is_show_subscribe" name="is_show_subscribe">
			<option <?php if(get_option('is_show_subscribe')=="Yes") {echo "selected=selected";}?> >Yes</option>
			<option <?php if(get_option('is_show_subscribe')=="No") { echo "selected=selected";}?> >No</option>
		</select>
		
		<p><strong>How many points should users receive for subscribing?</strong></p>
		<input type="text" id="subscribe_points" name="subscribe_points" style="width: 80px" value="<?php echo get_option('subscribe_points'); ?>">

		<p><strong>Input Subscribe Checkbox Text</strong><p>
		<input type="text" id="subscribe_checkbox_text" name="subscribe_checkbox_text" style="width:900px" value="<?php echo get_option('subscribe_checkbox_text'); ?>">

		<br />
		<p><strong>Should a user receive points if they leave a comment?</strong></p>
		<select id="assign_points_allow" name="assign_points_allow">
			<option <?php if(get_option('assign_points_allow')=="Yes") {echo "selected=selected";}?> >Yes</option>
			<option <?php if(get_option('assign_points_allow')=="No") { echo "selected=selected";}?> >No</option>
		</select>

		<p>
			<span style="font-size:13px;font-weight:bold"><strong>Enter the id of the post you want to apply these settings to:</strong> </span>
			<input type="text" id="criteria_post_id" name="criteria_post_id" style="height:12px;margin-left:20px;font-size:11px" size="10" onkeypress="onKeyDownPostID(event)" value="<?php echo get_option('criteria_post_id'); ?>">
		</p>

	<br />

    <hr>
    <h3>Extra Comment Fields</h3>
		<font style="font-size:13px;font-weight:bold">First Extra Form</font>
		<?php
			function add_extra_form($num_text){
			if( $num_text == '0' )
				$num_text = '';
		?>
		<p>
		<table border="0" cellpadding="0" cellspacing="0" width="820px">
		<colgroup>	
			<col width="100px">
			<col width="500px">
			<col width="120px">
			<col width="200px">
		</colgroup>
		<tr>
			<td><input type="text" name="first_extra_text<?php echo $num_text; ?>" value="<?php $option_temp1 = 'first_extra_text' . $num_text; echo get_option($option_temp1); ?>" style="width:500px;height:14px"></td>
			<td align="center"><select id="first_extra_form<?php echo $num_text; ?>" name="first_extra_form<?php echo $num_text; ?>" style="width:100px">
				<option <?php $option_temp = 'first_extra_form' . $num_text; if(get_option($option_temp)=="-Select-" || get_option($option_temp1) == '' ) {echo "selected=selected"; update_option($option_temp,'-Select-'); }?> >-Select-</option>
				<option <?php if(get_option($option_temp)=="Checkbox") {echo "selected=selected";}?> >Checkbox</option>
				<option <?php if(get_option($option_temp)=="Textbox") { echo "selected=selected";}?> >Textbox</option>
			</select></td>
			<td align="center"><select id="first_extra_point<?php echo $num_text; ?>" name="first_extra_point<?php echo $num_text; ?>" style="width:50px">
			<?php
				$option_temp = 'first_extra_point' . $num_text;
				if( intval(get_option($option_temp)) == 0 || get_option($option_temp1) == '' )
				{
					echo '<option value="0" selected>0</option>';
					update_option($option_temp,'0');
				}
				else
					echo '<option value="0">0</option>';
				for( $i=1; $i<21; $i++ ){
			?>
				<option <?php if(intval(get_option($option_temp)) == $i) { echo "selected=selected"; }?>><?php echo $i; ?></option>
			<?php
				}
			?>
			</select>&nbsp;points</td>
		</tr>
		</table>
		</p>
		<?php
		}
		add_extra_form('0');
		?>
		<font style="font-size:13px;font-weight:bold">Second Extra Form</font>
		<?php add_extra_form('1'); ?>

		<font style="font-size:13px;font-weight:bold">Third Extra Form</font>
		<?php add_extra_form('2'); ?>

		<font style="font-size:13px;font-weight:bold">Fourth Extra Form</font>
		<?php add_extra_form('3'); ?>

		<font style="font-size:13px;font-weight:bold">Fifth Extra Form</font>
		<?php add_extra_form('4'); ?>

		<h4>Reward Your Blog Top Commenters:</h4>
		<p>Insert [commentmilk num=#] into your page, post and / or sidebar to display the top commenters.<br>Replace the # with the number you want to display.</p>
		<p>If you would like to use the Top Commenters widget you can find it in <a href="<?php echo get_option('siteurl'); ?>/wp-admin/widgets.php">Presentation -> Widgets</a> menu</p>

    <hr>
    <h3>Affiliate ID</h3>
		<p>Enter your Affiliate ID below:</p>
		<input type="text" id="affiliate_id" name="affiliate_id" style="width:80px" value="<?php echo get_option('affiliate_id'); ?>">

	<input type="hidden" name="page_options" value="milk_link_permit,milk_keyword_allow,milk_blank_link,milk_topcommentor_count,assign_points_allow,first_extra_form,first_extra_form1,first_extra_form2,first_extra_form3,first_extra_form4,first_extra_text,first_extra_text1,first_extra_text2,first_extra_text3,first_extra_text4,is_show_subscribe,keyword_checkbox_text,subscribe_checkbox_text,subscribe_points,keyword_display,css_style_text,first_extra_point,first_extra_point1,first_extra_point2,first_extra_point3,first_extra_point4,criteria_post_id,is_all_post_comment,camp_flag,camp_id,camp_name,affiliate_id,milk_exclude" />

	<input type="hidden" name="action" value="update" />
	<p class="submit">
		<input type="button" name="btn" value="<?php _e('Add New') ?>" style="width:150px" onclick="evalNewCampaign()" />&nbsp;&nbsp;
		<input type="submit" name="Submit" value="<?php _e('Update Options') ?>" style="width:150px" />
	</p>
	</form>
	<script language="javascript">
	function evalNewCampaign()
	{
		if( document.getElementById("camp_name").value == "" )
		{
			document.getElementById("camp_name").style.borderColor = "#F00000";
			document.getElementById("camp_name").focus();
		}
		else
		{
			document.getElementById("camp_flag").value = "create";
			document.getElementById("options").submit();
		}
	}
	function CampaignEdit(num)
	{
		var table_handle = document.getElementById("camp_table");
		var table_row = document.getElementById("camp_table").rows;
		var table_td, cur_id, temp_var;
		if (navigator.appName == "Netscape")
		{
			table_td = table_row[num].cells;
			cur_id = table_td[0].innerHTML;
			document.getElementById("camp_name").value = table_td[1].innerHTML;
			temp_var = table_td[2].innerHTML;
			if( temp_var.indexOf('Yes') != -1 )
				document.getElementById("milk_link_permit").selectedIndex = 0;
			else
				document.getElementById("milk_link_permit").selectedIndex = 1;
			temp_var = table_td[3].innerHTML;
			document.getElementById("keyword_display").value = temp_var;
			temp_var = table_td[4].innerHTML;
			document.getElementById("keyword_unchecked_text").value = temp_var;
			temp_var = table_td[5].innerHTML;
			if( temp_var.indexOf('Yes') != -1 )
				document.getElementById("milk_keyword_allow").selectedIndex = 0;
			else
				document.getElementById("milk_keyword_allow").selectedIndex = 1;
			temp_var = table_td[6].innerHTML;
			document.getElementById("keyword_checkbox_text").value = temp_var;
			temp_var = table_td[7].innerHTML;
			document.getElementById("css_style_text").value = temp_var;
			temp_var = table_td[8].innerHTML;
			if( temp_var.indexOf('Yes') != -1 )
				document.getElementById("milk_blank_link").selectedIndex = 0;
			else
				document.getElementById("milk_blank_link").selectedIndex = 1;
			temp_var = table_td[9].innerHTML;
			if( temp_var.indexOf('Yes') != -1 )
				document.getElementById("is_show_subscribe").selectedIndex = 0;
			else
				document.getElementById("is_show_subscribe").selectedIndex = 1;
			temp_var = table_td[10].innerHTML;
			document.getElementById("subscribe_checkbox_text").value = temp_var;
			temp_var = table_td[11].innerHTML;
			if( temp_var.indexOf('Yes') != -1 )
				document.getElementById("assign_points_allow").selectedIndex = 0;
			else
				document.getElementById("assign_points_allow").selectedIndex = 1;
			temp_var = table_td[12].innerHTML;
			document.getElementById("criteria_post_id").value = temp_var;
		}
		else {
			table_td = table_row(num).cells;
			cur_id = table_td(0).innerHTML;
			document.getElementById("camp_name").value = table_td(1).innerHTML;
			temp_var = table_td(2).innerHTML;
			if( temp_var.indexOf('Yes') != -1 )
				document.getElementById("milk_link_permit").selectedIndex = 0;
			else
				document.getElementById("milk_link_permit").selectedIndex = 1;
			temp_var = table_td(3).innerHTML;
			document.getElementById("keyword_display").value = temp_var;
			temp_var = table_td(4).innerHTML;
			document.getElementById("keyword_unchecked_text").value = temp_var;
			temp_var = table_td(5).innerHTML;
			if( temp_var.indexOf('Yes') != -1 )
				document.getElementById("milk_keyword_allow").selectedIndex = 0;
			else
				document.getElementById("milk_keyword_allow").selectedIndex = 1;
			temp_var = table_td(6).innerHTML;
			document.getElementById("keyword_checkbox_text").value = temp_var;
			temp_var = table_td(7).innerHTML;
			document.getElementById("css_style_text").value = temp_var;
			temp_var = table_td(8).innerHTML;
			if( temp_var.indexOf('Yes') != -1 )
				document.getElementById("milk_blank_link").selectedIndex = 0;
			else
				document.getElementById("milk_blank_link").selectedIndex = 1;
			temp_var = table_td(9).innerHTML;
			if( temp_var.indexOf('Yes') != -1 )
				document.getElementById("is_show_subscribe").selectedIndex = 0;
			else
				document.getElementById("is_show_subscribe").selectedIndex = 1;
			temp_var = table_td(10).innerHTML;
			document.getElementById("subscribe_checkbox_text").value = temp_var;
			temp_var = table_td(11).innerHTML;
			if( temp_var.indexOf('Yes') != -1 )
				document.getElementById("assign_points_allow").selectedIndex = 0;
			else
				document.getElementById("assign_points_allow").selectedIndex = 1;
			temp_var = table_td(12).innerHTML;
			document.getElementById("criteria_post_id").value = temp_var;
		}
		document.getElementById("camp_flag").value = "update";
		document.getElementById("camp_id").value = cur_id;
		document.getElementById("camp_name").focus();
		document.getElementById("camp_name").select();
	}
	function CampaignDelete(num)
	{
		var table_handle = document.getElementById("camp_table");
		var table_row = document.getElementById("camp_table").rows;
		var table_td, cur_id;
		if (navigator.appName == "Netscape")
		{
			table_td = table_row[num].cells;
			cur_id = table_td[0].innerHTML;
		}
		else
		{
			table_td = table_row(num).cells;
			cur_id = table_td(0).innerHTML;
		}
		if( confirm("Do you want actually delete the campaign?") )
		{
			document.getElementById("camp_id").value = cur_id;
			document.getElementById("camp_flag").value = "delete";
		    document.getElementById("options").submit();
		}
	}
	function onKeyDownPostID(e)
	{
		var keynum;
		if(window.event) // IE
		{
			keynum = e.keyCode;
			if( keynum < 48 || keynum > 57 )
				e.keyCode = "";
		}
		else if(e.which) // Netscape/Firefox/Opera
		{
			keynum = e.which;
			if( keynum < 48 || keynum > 57 )
				e.which = "";
		}

	}
	</script>

</div>
 <?php }

function add_milk_area($id){
	global $wpdb, $user_ID;
	if( $user_ID )
	{
		$sql = "SELECT * FROM " . $wpdb->prefix . "users WHERE ID=" . intval($user_ID);
		$user_results = $wpdb->get_results($sql);
	}
	if( $user_results[0]->user_login != 'admin' )
	{
		if( get_option('is_all_post_comment') == "Yes" )
		{
			?>
			<input type="hidden" id="keyword_hidden_text1" name="keyword_hidden_text1" value="<?php echo get_option('keyword_checkbox_text'); ?>">
			<input type="hidden" id="keyword_allow" name="keyword_allow" value="<?php echo get_option('milk_keyword_allow'); ?>">

			<script language="javascript">
				var keyword_ctrl = document.createElement('div');
				var checkbox_div = document.createElement('div');
				var checkbox_div1 = document.createElement('div');
				var temp_text;
				if( document.getElementById("keyword_allow").value == 'Yes' )
					temp_text = document.getElementById("keyword_hidden_text1").value.replace("[","<a href='http://www.johncow.com/commentmilk/'>");
				else
					temp_text = document.getElementById("keyword_hidden_text").value.replace("[","<a href='http://www.johncow.com/commentmilk/'>");
				temp_text = temp_text.replace("]","</a>");
				keyword_ctrl.innerHTML = '<input type="text" id="keyword_phrase_text" name="keyword_phrase_text" class="textarea" size="22" tabindex="4"><br>';
				keyword_ctrl.id = "keyword_ctrl_div";
				keyword_ctrl.name = "keyword_ctrl_div";
				keyword_ctrl.style.display = "none";
				<?php if ( get_option('milk_keyword_allow') == "Yes" ) { ?>
				checkbox_div.innerHTML = '<input type="checkbox" id="is_keyword" name="is_keyword" value="keyword" onclick="doKeywordClick()" class="textarea" style="width: auto;"><label for="is_keyword"><small>' + temp_text + '</small></label>';
				<?php } ?>
				checkbox_div.id = "keywordphrase";
				checkbox_div.name = "keywordphrase";
				
				if( document.getElementById("comment") )
					document.getElementById("comment").tabIndex = "5";
				var url_ctrl;
				if( document.getElementById("subscribe") )
				{
					var sub_ctrl = document.getElementById("subscribe");
					sub_ctrl.parentNode.insertBefore(checkbox_div,sub_ctrl);
					sub_ctrl.parentNode.insertBefore(keyword_ctrl,sub_ctrl);
				}
				else
				{
					url_ctrl = document.getElementById("comment");
					if( url_ctrl )
					{
						url_ctrl.parentNode.insertBefore(checkbox_div,url_ctrl);
						url_ctrl.parentNode.insertBefore(keyword_ctrl,url_ctrl);
					}
				}
				function doKeywordClick()
				{
					if( document.getElementById("is_keyword").checked )
					{
						temp_text = document.getElementById("keyword_hidden_text1").value.replace("[","<a href='http://www.johncow.com/commentmilk/'>");
						temp_text = temp_text.replace("]","</a>");
						document.getElementById("keyword_ctrl_div").style.display = "inline";
					}
					else
					{
						temp_text = document.getElementById("keyword_hidden_text").value.replace("[","<a href='http://www.johncow.com/commentmilk/'>");
						temp_text = temp_text.replace("]","</a>");
						document.getElementById("keyword_ctrl_div").style.display = "none";
					}
				}
			</script>
			<?php
			$column_name = 'keyword_phrase_text';
			$column_name1 = 'is_keyword_phrase';
			$is_create_key = 0;
			$is_create_key1 = 0;
			foreach ( (array) $wpdb->get_col("DESC $wpdb->comments", 0) as $column )
			{
				if ($column == $column_name && $column == $column_name1)
				{
					$is_create_key = 1;
					$is_create_key1 = 1;
				}
				if ($column == $column_name)
					$is_create_key = 1;
				if ($column == $column_name1)
					$is_create_key1 = 1;

			}
			if( $is_create_key == 0 )
				$wpdb->query("ALTER TABLE $wpdb->comments ADD COLUMN keyword_phrase_text VARCHAR(200) NULL default ''");
			if( $is_create_key1 == 0 )
				$wpdb->query("ALTER TABLE $wpdb->comments ADD COLUMN is_keyword_phrase enum('Y','N') NOT NULL default 'N'");
			if( $id == intval(get_option('criteria_post_id')) )
			{
				if( get_option('is_show_subscribe') == 'Yes' )
				{
					?>
					<input type="hidden" id="subscribe_hidden_text" name="subscribe_hidden_text" value="<?php echo get_option('subscribe_checkbox_text'); ?>">
					<script language="javascript">
						var subscribe_div = document.createElement('div');
						subscribe_div.innerHTML = '<input type="checkbox" id="is_subscribe_newsletter" name="is_subscribe_newsletter" value="subscribe"  style="width: auto;"><label for="is_subscribe_newsletter"><small>' + document.getElementById("subscribe_hidden_text").value + '</small></label>';
						if( document.getElementById("subscribe") )
						{
							var sub_ctrl = document.getElementById("subscribe");
							sub_ctrl.parentNode.insertBefore(subscribe_div,sub_ctrl);
						}
						else
						{
							url_ctrl = document.getElementById("comment");
							if( url_ctrl )
								url_ctrl.parentNode.insertBefore(subscribe_div,url_ctrl);
						}
					</script>
				<?php
				}
				?>
				<script language="javascript">
				var extra_small_ctrl, temp_txt, extra_sp_txt, extra_sub_ctrl;
				var is_first_extra_div;
				</script>
				<?php
				for( $k=0; $k<5; $k++ )
				{
					if( $k == 0 )	$prefix_text = '';
					if( $k == 1 )	$prefix_text = 1;
					if( $k == 2 )	$prefix_text = 2;
					if( $k == 3 )	$prefix_text = 3;
					if( $k == 4 )	$prefix_text = 4;
					$option_name = 'first_extra_form' . $prefix_text;
					$option_txt = 'first_extra_text' . $prefix_text;
					?>
					<input type="hidden" id="first_criteria_hidden<?php echo $prefix_text; ?>" name="first_criteria_hidden<?php echo $prefix_text; ?>" value="<?php echo get_option($option_txt); ?>">
					<?php
					if( get_option($option_name) == 'Textbox' )
					{
						?>
						<script language="javascript">
							extra_small_ctrl = document.createElement('small');
							extra_post_div = document.createElement('div');
							if( '<?php echo $k; ?>' == '0' )
							{
								temp_txt = "first_criteria_hidden";
								extra_sp_txt = document.createTextNode(document.getElementById(temp_txt).value);
								extra_post_div.innerHTML = '<input type="text" id="blog_post_text" name="blog_post_text" tabindex="5">';
							}
							else
							{
								temp_txt = "first_criteria_hidden" + '<?php echo $k; ?>';
								extra_sp_txt = document.createTextNode(document.getElementById(temp_txt).value);
								extra_post_div.innerHTML = '<input type="text" id="blog_post_text' + '<?php echo $k; ?>' + '" name="blog_post_text' + '<?php echo $k; ?>' + '" class="textarea" tabindex="5">';
							}
							extra_small_ctrl.appendChild(extra_sp_txt);
							if( document.getElementById("comment") )
								document.getElementById("comment").tabIndex = "5";
							if( document.getElementById("subscribe") )
							{
								extra_sub_ctrl = document.getElementById("subscribe");
								sub_ctrl.parentNode.insertBefore(extra_small_ctrl,extra_sub_ctrl);
								sub_ctrl.parentNode.insertBefore(extra_post_div,extra_sub_ctrl);
							}
							else
							{
								extra_sub_ctrl = document.getElementById("comment");
								if( extra_sub_ctrl )
								{
									url_ctrl.parentNode.insertBefore(extra_small_ctrl,extra_sub_ctrl);
									url_ctrl.parentNode.insertBefore(extra_post_div,extra_sub_ctrl);
								}
							}
						</script>
					<?php
					}
					else if( get_option($option_name) == 'Checkbox' )
					{
						?>
						<script language="javascript">
							is_first_extra_div = document.createElement('div');
							if( '<?php echo $k; ?>' == '0' )
								temp_txt = "first_criteria_hidden";
							else
								temp_txt = "first_criteria_hidden" + '<?php echo $k; ?>';
							is_first_extra_div.innerHTML = '<input type="checkbox" id="is_first_extra<?php echo $k; ?>" name="is_first_extra<?php echo $k; ?>" value="first<?php echo $k; ?>" style="width: auto;"><label for="is_first_extra<?php echo $k; ?>"><small>' + document.getElementById(temp_txt).value + '</small></label>';
							if( document.getElementById("subscribe") )
							{
								extra_sub_ctrl = document.getElementById("subscribe");
								extra_sub_ctrl.parentNode.insertBefore(is_first_extra_div,extra_sub_ctrl);
							}
							else
							{
								extra_sub_ctrl = document.getElementById("comment");
								if( extra_sub_ctrl )
									extra_sub_ctrl.parentNode.insertBefore(is_first_extra_div,extra_sub_ctrl);
							}
						</script>
					<?php
					}
				}
				$column_name = 'first_criteria';
				$column_name1 = 'is_subscribe_newsletter';
				$is_create_key = 0;
				$is_create_key1 = 0;
				foreach ( (array) $wpdb->get_col("DESC $wpdb->comments", 0) as $column )
				{
					if ($column == $column_name && $column == $column_name1)
					{
						$is_create_key = 1;
						$is_create_key1 = 1;
					}
					if ($column == $column_name)
						$is_create_key = 1;
					if ($column == $column_name1)
						$is_create_key1 = 1;

				}
				if( $is_create_key == 0 )
					$wpdb->query("ALTER TABLE $wpdb->comments ADD COLUMN first_criteria VARCHAR(200) NULL default ''");
				if( $is_create_key1 == 0 )
					$wpdb->query("ALTER TABLE $wpdb->comments ADD COLUMN is_subscribe_newsletter enum('Y','N') NOT NULL default 'N'");
				$column_name = 'first_criteria1';
				$column_name1 = 'first_criteria2';
				$is_create_key = 0;
				$is_create_key1 = 0;
				foreach ( (array) $wpdb->get_col("DESC $wpdb->comments", 0) as $column )
				{
					if ($column == $column_name && $column == $column_name1)
					{
						$is_create_key = 1;
						$is_create_key1 = 1;
					}
					if ($column == $column_name)
						$is_create_key = 1;
					if ($column == $column_name1)
						$is_create_key1 = 1;

				}
				if( $is_create_key == 0 )
					$wpdb->query("ALTER TABLE $wpdb->comments ADD COLUMN first_criteria1 VARCHAR(200) NULL default ''");
				if( $is_create_key1 == 0 )
					$wpdb->query("ALTER TABLE $wpdb->comments ADD COLUMN first_criteria2 VARCHAR(200) NULL default ''");
				$column_name = 'first_criteria3';
				$column_name1 = 'first_criteria4';
				$is_create_key = 0;
				$is_create_key1 = 0;
				foreach ( (array) $wpdb->get_col("DESC $wpdb->comments", 0) as $column )
				{
					if ($column == $column_name && $column == $column_name1)
					{
						$is_create_key = 1;
						$is_create_key1 = 1;
					}
					if ($column == $column_name)
						$is_create_key = 1;
					if ($column == $column_name1)
						$is_create_key1 = 1;

				}
				if( $is_create_key == 0 )
					$wpdb->query("ALTER TABLE $wpdb->comments ADD COLUMN first_criteria3 VARCHAR(200) NULL default ''");
				if( $is_create_key1 == 0 )
					$wpdb->query("ALTER TABLE $wpdb->comments ADD COLUMN first_criteria4 VARCHAR(200) NULL default ''");
			}
		}
	
	}
	$url = get_bloginfo('url');
	if (get_option('affiliate_id') != "0" && get_option('affiliate_id') != "") {
		echo "<p><a href=\"http://www.johncow.com/commentmilk/".get_option('affiliate_id')."\" target=\"_blank\"><img src=\"" . $url . "/wp-content/plugins/commentMilk/commentmilk.gif\" style=\"border: none;padding: 4px\" alt=\"Powered by CommentMilk\" /></a></p>";
	} else {
		echo "<p><a href=\"http://www.johncow.com/commentmilk/\" target=\"_blank\"><img src=\"" . $url . "/wp-content/plugins/commentMilk/commentmilk.gif\" style=\"border: none;padding: 4px\" alt=\"Powered by CommentMilk\" /></a></p>";
	}
	return $id; // need to return what we got sent
}

function perform_curl_operation(& $remote_url) {
   $remote_contents = "";
   $empty_contents = "";
   $curl_handle = curl_init();

   if ($curl_handle) {

      curl_setopt($curl_handle, CURLOPT_URL, $remote_url);
      curl_setopt($curl_handle, CURLOPT_HEADER, false);
      curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 0);
      curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);

      $remote_contents = curl_exec($curl_handle);
      curl_close($curl_handle);
      if ($remote_contents != false) {
         return($remote_contents);
      } else {
         return($empty_contents);
      }
   } else {
      return($empty_contents);
   }
}

function show_comment_text($pre)
{
	global $wpdb, $comment;
	global $user_ID;
	$current_post_id = $comment->comment_post_ID;
	$current_comment_author = $comment->comment_author;
	$current_comment_author_email = $comment->comment_author_email;
	$current_comment_author_url = $comment->comment_author_url;
	$sql = "SELECT * FROM " . $wpdb->prefix . "users WHERE user_login='admin' AND user_email='" . $current_comment_author_email . "' AND user_url='" . $current_comment_author_url . "'";
	$user_result = $wpdb->get_results($sql);
	$sql = "SELECT * FROM " . $wpdb->prefix . "users WHERE ID=" . $user_ID;
	$mem_results = $wpdb->get_results($sql);
	$skip=1;
	$exclude = str_replace(", ",",",get_option('milk_exclude'));
	$exclude_array = explode(",",$exclude);
	foreach ($exclude_array as $ide) {
		if ($ide == $comment->user_id) {
			$skip = 0;
		}
	}
	if( $skip==1 )
	{
		if( count($user_result) < 1 )
		{
			if( get_option('is_all_post_comment') == 'Yes' )
			{
				if( get_option('assign_points_allow') == 'Yes' && $current_post_id == intval(get_option('criteria_post_id')) )
				{
					$current_blog_post_text = $comment->first_criteria;
					$current_blog_post_text1 = $comment->first_criteria1;
					$current_blog_post_text2 = $comment->first_criteria2;
					$current_blog_post_text3 = $comment->first_criteria3;
					$current_blog_post_text4 = $comment->first_criteria4;
					$current_is_subscribe = $comment->is_subscribe_newsletter;
					$current_keyword_text = $comment->keyword_phrase_text;
					$pre .= "<br>";
					if( $current_keyword_text != '' )
						$pre .= "<p style='margin:0px;padding:0px'>Keyword Phrase: " . $current_keyword_text . "</p>";
					if( get_option('is_show_subscribe') == 'Yes' ){
						if( get_option('subscribe_checkbox_text') != '' )
							$pre .= "<p style='margin:0px;padding:0px'>" . get_option('subscribe_checkbox_text') . ": " . $current_is_subscribe . "</p>";
					}
					$total_points = 0;
					for( $i=0; $i<5; $i++ )
					{
						if( $i == 0 )
							$vars = 'current_blog_post_text';
						else
							$vars = 'current_blog_post_text' . $i;
						if( $$vars != '' )
						{
							if( $i == 0 )
							{
								if( get_option('first_extra_text') != '' )
								{
									$pre .= "<p style='margin:0px;padding:0px'>" . get_option('first_extra_text') . ": " . $$vars . "</p>";
									$point_temp = "first_extra_point";
									if( $$vars != 'N' )
										$total_points += intval(get_option($point_temp));
								}
							}
							else
							{
								$temp = 'first_extra_text' . $i;
								if( get_option($temp) != '' )
								{
									$pre .= "<p style='margin:0px;padding:0px'>" . get_option($temp) . ": " . $$vars . "</p>";
									$point_temp = "first_extra_point" . $i;
									if( $$vars != 'N' )
										$total_points += intval(get_option($point_temp));
								}
							}
						}
					}
					if( $current_is_subscribe == 'Y' )
						$total_points += get_option('subscribe_points');
					$pre .= "<p style='margin:0px;padding:0px'>Total Points: " . $total_points . "</p>";
				}
				$pre = str_replace("\n", "<br>", $pre);
				//echo "<br><p>$pre</p>";
				$is_keyword_text = $comment->is_keyword_phrase;
				$current_comment_author = $comment->comment_author;
				$display_text = get_option('keyword_display');
				$display_text = $display_text . " ";
				$url = $comment->comment_author_url;
				if ($comment->latest_post_link != "") {
					$css_styles = get_option('css_style_text');
					if( $css_styles != '' )
						$pre .= "<div style='width:95%;margin-top: 10px;" . $css_styles . "'>";
					else
						$pre .= "<div style='width:95%;margin-top: 10px;'>";
					if ($comment->keyword_phrase_text == "") {
						$display_text = str_replace("[name]", $current_comment_author . "'", $display_text);
						$display_text = str_replace("[link]", "<a href=\"".$comment->latest_post_link."\">".$comment->latest_post_title, $display_text);
					} else {
						$display_text = "<a href=\"".$comment->latest_post_link."\">".$comment->keyword_phrase_text;
					}
					$pre .= $display_text . "</a></div>";
					//echo "<p style='margin:0px;padding:0px'>Latest Post: <a href=\"" . $comment->latest_post_link . "\" target=\"_blank\">" . $comment->latest_post_title . "</a></p>";
				}
				/*
				if( $is_keyword_text != 'Y' )
				{
					if(substr($url,-1)=="/")
						$url = substr($url, 0, -1);
					$content = perform_curl_operation($url);
					$temp = split("<a ",$content);
					$url_text = '';
					if( count($temp) > 0 )
					{
						for( $i=0; $i<count($temp); $i++ )
						{
							$tmp_text[$i] = "<a " . substr_replace($temp[$i],"",strpos($temp[$i],"</a>"),-1);
							if( strpos($tmp_text[$i], "http:") === false || strpos($tmp_text[$i], "bookmark") === false )
								$tmp_text[$i] = "";
							else
							{
								$url_text = $tmp_text[$i];
								$i = count($temp);
							}
						}
					}
					if( strpos($url_text, "timestamp") === false )
						$none_var = "";
					else
					{
						$temp = split("<a ",$content);
						$url_text = '';
						if( count($temp) > 0 )
						{
							for( $i=0; $i<count($temp); $i++ )
							{
								$tmp_text[$i] = "<a " . substr_replace($temp[$i],"",strpos($temp[$i],"</a>"),-1);
								if( strpos($tmp_text[$i], "http:") === false )
									$tmp_text[$i] = "";
								else
								{
									if( strpos($tmp_text[$i], "timestamp") === false )
									{
										$url_text = $tmp_text[$i];
										$i = count($temp);
									}
								}
							}
						}
					}
					if( $url_text != '' )
					{
						$css_styles = get_option('css_style_text');
						if( $css_styles != '' )
							echo "<div style='width:95%;" . $css_styles . "'>";
						else
							echo "<div style='width:95%;'>";
						$current_array = split("title=",$url_text);
						$current_key = substr($current_array[1],1,strlen($current_array[1])-1);
						$pos = strpos($current_key,'"');
						if( $pos === false )
							$current_key = $current_key;
						else
							$current_key = substr($current_key,0,$pos);
						$url_text = substr($url_text,0,strlen($url_text)-1);
						$display_text = str_replace("[name]", $current_comment_author . "'", $display_text);
						$display_text = str_replace("[link]", $url_text, $display_text);
						echo $display_text . "</a></div>";
					}
				}*/

				//$pre = "";
			}
		}
	}
	return $pre;
}

//add nofollow link and delete nofollow link
function show_comment_content($nofollow)
{
	global $wpdb, $comment;

	$queryStringUser="SELECT user_email FROM ".$wpdb->users." WHERE user_email='".$wpdb->escape($comment->comment_author_email)."'";
	$registered_user = $wpdb->get_var($queryStringUser);
	if ($comment->comment_author_email == "$registered_user" && get_option('milk_link_permit') == "Yes")
		$nofollow = preg_replace("/rel='external nofollow'>/","rel='nofollow'>", $nofollow);
	else
		$nofollow = preg_replace("/rel='nofollow'>/","rel='external nofollow'>", $nofollow);
	if (get_option('milk_blank_link') == "Yes")
	{
		$nofollow = preg_replace("/rel='nofollow'>/","rel='nofollow' target='_blank'>", $nofollow);
		$nofollow = preg_replace("/rel='external nofollow'>/","rel='external nofollow' target='_blank'>", $nofollow);
	}
	else
	{
		$nofollow = preg_replace("/rel='nofollow' target='_blank'>/","rel='nofollow'>", $nofollow);
		$nofollow = preg_replace("/rel='external nofollow' target='_blank'>/","rel='external nofollow'>", $nofollow);
	}
	return $nofollow;
}

//widget area
function add_authors_top_commentor_init() {
	if (!function_exists('register_sidebar_widget'))
		return;

	function add_authors_top_commentor($args) {
		global $wpdb;
		extract($args);
		$options = get_option('authors_top_commentor');
		$title = $options['title'];
		$commentor_num = $options['commentor_number'];
		$image_size = $options['img_size'];
		$days = $options['days'];

		$querystr = "SELECT *, COUNT(*) as num_comments FROM ".$wpdb->comments." WHERE comment_approved='1' AND comment_type!='trackback' AND comment_type!='pingback' AND comment_date BETWEEN DATE_SUB(CURDATE(),INTERVAL " . $days ." DAY) AND CURDATE() GROUP BY comment_author_email ORDER BY num_comments DESC LIMIT $commentor_num";
		$results = $wpdb->get_results($querystr);

        print $before_widget;
		print $before_title . $title . $after_title;
		?>
		<table border="0" cellpadding="8" cellspacing="0" style="width:100%;text-align:center;vertical-align:middle;padding-top:10px">
		<?php
			if( count($results) > 0 )
			{
				$count_num = 1;
				foreach ($results as $row)
				{
					echo "<tr><td style='text-align:left;padding-left:10px;'>";
					if( $row )
					{
						if ($row->comment_author_url != "") {
							$a_url = $row->comment_author_url;
						}
						 else {
							$a_url = get_option('siteurl');
						}
						echo '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
						echo '<tr><td align="left" valign="middle" style="width:20px">';
						echo '#' . $count_num++;
						echo '</td><td style="width:60px;vertical-align:middle;text-align:center">';
						echo '<a href="'.$a_url.'" rel="nofollow">' . get_avatar( $row, intval($image_size) ) . '</a>';
						echo '</td><td align="left" valign="middle">';
						echo '<a href="'.$a_url.'">' . $row->comment_author . '</a>';
						echo '</td></tr></table>';
					}
					echo "</td></tr>";
				}
			}
		?>
		</table>
		<?php
		print $after_widget;
	}

	// Widget admin control
	function add_authors_top_commentor_control() {
		$options = get_option('authors_top_commentor');
		if ( !is_array($options) )
			$options = array('title'=>'CommentMilk', 'commentor_number'=>'20', 'img_size'=>'32', 'days' => '30');
		if (isset($_POST['authors_commentor_submit']))
		{
			$options['title'] = strip_tags(stripslashes($_POST['top-commentor-title']));
			$options['commentor_number'] = strip_tags(stripslashes($_POST['top-commentor-count']));
			$options['img_size'] = strip_tags(stripslashes($_POST['avatar-size']));
			$options['days']     = strip_tags(stripslashes($_POST['top-commentor-days']));
			update_option('authors_top_commentor', $options);
		}
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$commentor_num = htmlspecialchars($options['commentor_number'], ENT_QUOTES);
		$avatar_size = htmlspecialchars($options['img_size'], ENT_QUOTES);
		$days = htmlspecialchars($options['days'], ENT_QUOTES);

		?>
		<div style="text-align:left">
	        <h3>CommentMilk Widget</h3>
	        <p style="text-align:left"><input type="text" id="top-commentor-title" name="top-commentor-title" size="25" value="<?php echo $title; ?>" /> Title for the widget (default "CommentMilk")</p>
	        <p style="text-align:left">
				<select id="top-commentor-count" name="top-commentor-count" style="width:100px">
					<option value="5" <?php if(intval($commentor_num)==5) echo "selected"; ?>>5</option>
					<option value="10" <?php if(intval($commentor_num)==10) echo "selected"; ?>>10</option>
					<option value="15" <?php if(intval($commentor_num)==15) echo "selected"; ?>>15</option>
					<option value="20" <?php if(intval($commentor_num)==20) echo "selected"; ?>>20</option>
					<option value="50" <?php if(intval($commentor_num)==50) echo "selected"; ?>>50</option>
				</select>&nbsp;Select # of Top Commenters to Show
			</p>
			<p style="text-align:left">
				<select id="avatar-size" name="avatar-size" style="width:100px">
					<option value="16" <?php if(intval($avatar_size)==16) echo "selected"; ?>>16</option>
					<option value="32" <?php if(intval($avatar_size)==32) echo "selected"; ?>>32</option>
					<option value="48" <?php if(intval($avatar_size)==48) echo "selected"; ?>>48</option>
					<option value="56" <?php if(intval($avatar_size)==56) echo "selected"; ?>>56</option>
					<option value="80" <?php if(intval($avatar_size)==80) echo "selected"; ?>>80</option>
					<option value="90" <?php if(intval($avatar_size)==90) echo "selected"; ?>>90</option>
					<option value="100" <?php if(intval($avatar_size)==100) echo "selected"; ?>>100</option>
					<option value="160" <?php if(intval($avatar_size)==160) echo "selected"; ?>>160</option>
				</select>&nbsp;Select Avatar Size
			</p>
			<p style="text-align:left">
				<input type="text" id="top-commentor-days" name="top-commentor-days" size="3" value="<?php echo $days; ?>" /> Day limit (0 for unlimited)
			</p>
	        <p style="text-align:left"><input type="hidden" name="authors_commentor_submit" id="authors_commentor_submit" value="1" /> </p>
	        </div>
		<?php
	}

	if(function_exists('register_sidebar_widget')) {
		register_sidebar_widget(__('CommentMilk'), 'add_authors_top_commentor');
		register_widget_control(array('CommentMilk', 'widgets'), 'add_authors_top_commentor_control', 300, 200);
	}
}

function commentmilk_shortcode($atts) {
	extract( shortcode_atts( array(
		'num' => 5,
		'days' => 30,
		), $atts ) );

		global $wpdb;
		$commentor_num = $num;

		$querystr = "SELECT *, COUNT(*) as num_comments FROM ".$wpdb->comments." WHERE comment_approved='1' AND comment_type!='trackback' AND comment_type!='pingback' AND comment_date BETWEEN DATE_SUB(CURDATE(),INTERVAL " . $days ." DAY) AND CURDATE() GROUP BY comment_author_email ORDER BY num_comments DESC LIMIT $commentor_num";
		$results = $wpdb->get_results($querystr);

		$return = "<table border=\"0\" cellpadding=\"8\" cellspacing=\"0\" style=\"width:100%;text-align:center;vertical-align:middle;padding-top:10px\">";
			if( count($results) > 0 )
			{
				$count_num = 1;
				foreach ($results as $row)
				{
					$return .= "<tr><td style='text-align:left;padding-left:10px;'>";
					if( $row )
					{
						if ($row->comment_author_url != "") {
							$a_url = $row->comment_author_url;
						}
						 else {
							$a_url = get_option('siteurl');
						}
						$return .= '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
						$return .= '<tr><td align="left" valign="middle" style="width:20px">';
						$return .= '#' + $count_num++;
						$return .= '</td><td style="width:60px;vertical-align:middle;text-align:center">';
						$return .= '<a href="'.$a_url.'" rel="nofollow">' . get_avatar( $row, 48 ) . '</a>';
						$return .= '</td><td align="left" valign="middle">';
						$return .= '<a href="'.$a_url.'">' . $row->comment_author . '</a>';
						$return .= '</td></tr></table>';
					}
					$return .= "</td></tr>";
				}
			}
		$return .= "</table>";


    return $return;  
}

add_shortcode('commentmilk', 'commentmilk_shortcode');
?>