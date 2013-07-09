<?php
/*
Plugin Name: WP Private Messages
Plugin URI: http://www.wpuzmani.com
Description: Private Messaging Plugin for Bloggers
Author: Semih Aksu
Version: 1.0.2
Author URI: http://www.wpuzmani.com/
*/

add_action('init', 'wpu_pm_language_pack');
add_action('admin_notices', 'wpu_new_msg_control');
add_action('admin_head', 'wpu_pm_style');
add_action('admin_menu', 'wpu_add_pm_page');
register_activation_hook( __FILE__, 'wpu_private_messages_activate' );

global $wpulang;
$wpulang = "wpuprivatemessages";

function wpu_pm_language_pack() {
	global $wpulang;

	$wpu_pm_locale = get_locale();
	$wpu_pm_lang_file = WP_CONTENT_DIR . "/plugins/".dirname(plugin_basename(__FILE__))."/languages/".$wpulang."-". $wpu_pm_locale.".mo";
	load_textdomain($wpulang, $wpu_pm_lang_file);
	}

function wpu_private_messages_activate() {
   global $wpdb, $wpulang;

   $table_name = $wpdb->prefix . "private_messages";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE " . $table_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		senderid int(11) DEFAULT '0' NOT NULL,
		rcpid int(11) DEFAULT '0' NOT NULL,
		sender VARCHAR(30) NOT NULL,
		recipient VARCHAR(30) NOT NULL,
		subject VARCHAR(255) NOT NULL,
		message text NOT NULL,
		fromsee TINYINT(2),
		tosee TINYINT(2),
		date TIMESTAMP,
		status TINYINT(2),
		ip VARCHAR(15),
	  PRIMARY KEY id (id)
	);";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
	  
		$senderid = "0";	$sender = "WPU PM";
		$rcpid = "1";		$recipient = "admin";
		$subject = __('WPU Private Messages', $wpulang);
		$ads = "http://www.wpuzmani.com/";
		$message = sprintf(__('This message mean, you successfully run our plugin! Visit our website for more information!
%s
Support Us!', $wpulang), $ads);
		$date = date("Y-m-d H:i:s");
		$status = "0";
		$ip = "127.0.0.1";

		$insert = "INSERT INTO " . $table_name ." (senderid, rcpid, sender, recipient, subject, message, fromsee, tosee, date, status, ip) " .
   			      "VALUES ('".$wpdb->escape($senderid)."','".$wpdb->escape($rcpid)."','".$wpdb->escape($sender)."','".$wpdb->escape($recipient)."','".$wpdb->escape($subject)."','".$wpdb->escape($message)."', 0, 1, '".$wpdb->escape($date)."','".$wpdb->escape($status)."','".$wpdb->escape($ip)."')";
      $results = $wpdb->query( $insert );
	}
}

function wpu_new_msg_control() {
	global $wpdb, $wpulang, $current_user;
	$countnew = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->prefix".private_messages." WHERE rcpid = $current_user->ID AND status = 0");
	$msglink = "profile.php?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=recieved";
	if($countnew > 0) { echo "<div id=\"message\" class=\"error\"><p><strong>".sprintf(__('You have %d new messages! <a href="%s">You can see here &raquo;</a>', $wpulang), $countnew, $msglink)."</strong></p></div>"; }
}

// We r Creating Our Message Page
function wpu_add_pm_page(){
	global $wpulang;
		add_users_page('WPU Private Messages', __('Private Messages', $wpulang), 0,  __FILE__, 'wpu_pm_page');
	}

// Private Messages Page 1st
function wpu_pm_page() {
	global $wpulang, $current_user;
	
	echo "<div class=\"wrap\"><div class=\"wpupmpage\"><h2>".__('Private Messages', $wpulang)."</h2>";
	$table_name = $wpdb->prefix . "private_messages";
	
	get_currentuserinfo();
	$name = $current_user->user_login;
?>
	<p class="pmmenu">
		<a href="?page=<?php echo dirname(plugin_basename(__FILE__)); ?>/wpu_private_messages.php&wpu=newpm"><?php _e('New Private Message', $wpulang); ?></a> | 
        <a href="?page=<?php echo dirname(plugin_basename(__FILE__)); ?>/wpu_private_messages.php&wpu=recieved"><?php _e('Recieved Messages', $wpulang); ?></a> | 
        <a href="?page=<?php echo dirname(plugin_basename(__FILE__)); ?>/wpu_private_messages.php&wpu=send"><?php _e('Send Messages', $wpulang); ?></a>
	</p>
<?php
		$wpu = $_GET['wpu'];
		switch($wpu) {
			case 'recieved':
				wpu_recieved();
			break;
			
			case 'newpm':
				wpu_new_pm();
			break;
			
			case 'send':
				wpu_sent();
			break;
			
			case 'read':
				wpu_read();
			break;
			
			case 'reply':
				wpu_reply_pm();
			break;
			
			case 'sendpm':
				wpu_send_pm();
			break;
			
			case 'memberlist':
				wpu_member_list();
			break;
			
			case 'delete':
				wpu_delete_pm();
			break;
			
			default:
				wpu_pm_dashboard();
			break;
		}
	echo "</div></div>";
}

// Recieved Messages
function wpu_recieved() {	
	global $wpdb, $current_user, $wpulang;
	echo "<h3>".__('Recieved Messages', $wpulang)."</h3>";

	$messages = $wpdb->get_results("SELECT id, sender, subject, date, status FROM $wpdb->prefix".private_messages." WHERE rcpid = '".$current_user->ID."' AND tosee = 1 ORDER BY date DESC");

	echo "\n<table id=\"messagelist\">\n<tr class=\"tablemenu\"><td>".__('From', $wpulang)."</td><td>".__('Subject', $wpulang)."</td><td>".__('Date', $wpulang)."</td><td>".__('Actions', $wpulang)."</td></tr>\n";
		
		$b = "<td>"; $e = "</td>\n";
			
		foreach ($messages as $message) {
			$status = $message->status;
			if($status == "0") { $class = " class=\"unread\""; } else { $class = " class=\"read\""; }
			
	echo "<tr".$class.">\n";
	echo $b."<a href=\"".get_settings('siteurl')."/author/".$message->sender."\" title=\"".sprintf(__('Author Page for %d', $wpulang), $message->sender)."\" class=\"pmauthor\">".$message->sender."</a>".$e;
	echo $b."<a href=\"?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=read&id=".$message->id."&r=recieved\" title=\"".__('Read This Message', $wpulang)."\">".$message->subject."</a>".$e;
	echo "<td class=\"center\">".$message->date.$e;
	echo "<td class=\"center\">";
	echo "<a href=\"?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=reply&msgid=".$message->id."\"><img src=\"". get_settings('siteurl') . "/wp-content/plugins/".dirname(plugin_basename(__FILE__))."/icons/reply.png\" alt=\"Reply!\" title=\"".__('Reply!', $wpulang)."\"></a>";
	echo "<a href=\"?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=delete&id=".$message->id."&r=recieved\"><img src=\"". get_settings('siteurl') . "/wp-content/plugins/".dirname(plugin_basename(__FILE__))."/icons/delete.png\" alt=\"Delete\" title=\"".__('Delete This Message!', $wpulang)."\"></a>";
			echo $e;
			
			echo "</tr>\n";
		}
			echo "</table>";
	}

function wpu_pm_dashboard() {
	global $wpulang;
	wpu_recieved();
	$lnn = get_locale();
	if($lnn == "tr_TR") { $link = "http://www.venois.net.tr"; } else { $link = "http://www.venois.net"; }
	echo "<p style=\"margin-top:100px;\"></p><h2>".__('Like this plugin?', $wpulang)."</h2>";
	echo "<ul>
			<li>".__('Share it and write a review about it.', $wpulang)."</li>
			<li>".sprintf(__('<a href="%s">Donate Us</a> to Develop Another Plugins', $wpulang), 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=semih%40wpuzmani%2ecom&lc=GB&item_name=WP%20Private%20Messagin%20Plugin&item_number=101&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted')."</li>
			<li>".__('Give it a good rating on WordPress.org', $wpulang)."</li>
		</ul>";
	echo "<h2>".__('Need Help?', $wpulang)."</h2>";
	echo sprintf(__('If you have any problems please visit our <a href="%s">website</a>', $wpulang), 'http://www.wpuzmani.com/');
	echo "<h2>".__('Notes', $wpulang)."</h2>";
	echo sprintf(__('You need hosting? You can try: <a href="%s">Reliable and Ultrafast Hosting Solutions</a>', $wpulang), $link);
}

// Sent Messages
function wpu_sent() {
	global $wpdb, $current_user, $wpulang;
	echo "<h3>".__('Sent Messages', $wpulang)."</h3>";

	$messages = $wpdb->get_results("SELECT id, sender, subject, date, status FROM wp_private_messages WHERE senderid = '".$current_user->ID."' AND fromsee = 1 ORDER BY date DESC");

	echo "\n<table id=\"messagelist\">\n<tr class=\"tablemenu\"><td>".__('To', $wpulang)."</td><td>".__('Subject', $wpulang)."</td><td>".__('Date', $wpulang)."</td><td>".__('Actions', $wpulang)."</td></tr>\n";
		
		$b = "<td>"; $e = "</td>\n";
			
		foreach ($messages as $message) {
			$status = $message->status;
			if($status == "0") { $class = " class=\"unread\""; } else { $class = " class=\"read\""; }
			
	echo "<tr".$class.">\n";
	echo $b."<a href=\"".get_settings('siteurl')."/author/".$message->sender."\" title=\"".sprintf(__('Author Page for %d', $wpulang), $message->sender)."\" class=\"pmauthor\">".$message->sender."</a>".$e;
	echo $b."<a href=\"?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=read&id=".$message->id."&r=sent\" title=\"".__('Read This Message', $wpulang)."\">".$message->subject."</a>".$e;
	echo "<td class=\"center\">".$message->date.$e;
	echo "<td class=\"center\">";
	echo "<a href=\"?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=delete&id=".$message->id."&r=sent\"><img src=\"". get_settings('siteurl') . "/wp-content/plugins/".dirname(plugin_basename(__FILE__))."/icons/delete.png\" alt=\"Delete\" title=\"".__('Delete This Message!', $wpulang)."\"></a>";
			echo $e;
			
			echo "</tr>\n";
		}
			echo "</table>";
	}


// New Private Message
function wpu_new_pm() {
	global $current_user, $wpulang;

	$id = $_GET["id"];
	$user = $_GET["name"];
	$userip = $_SERVER['REMOTE_ADDR'];
	$link = "?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=memberlist";
	if($id == "" || $user == "") { $image = ' <a href="'.$link.'"><img src="'.get_option('siteurl').'/'.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/icons/finduser.png" /></a>'; }
?>
		<form name="omforms" class="omlist" method="post" action="<?php echo "?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=sendpm"; ?>">
			<input type="hidden" name="sender" id="sender" value="<?php echo $current_user->user_login; ?>" readonly="readonly" />
			<label for="recipient"><?php _e('To', $wpulang); ?> <small>(<?php _e('Click image near the input area to choose member!', $wpulang); ?>)</small></label>
				<p><input type="text" name="recipient" id="recipient" value="<?php echo $user; ?>" readonly="readonly" /><?php echo $image; ?></p>
			<label for="subject"><?php _e('Subject', $wpulang); ?> <small>(<?php _e('Required', $wpulang); ?>)</small></label>
				<p><input type="text" name="subject" id="subject" value="" /></p>
			<label for="pmessage"><?php _e('Your Message', $wpulang); ?> <small>(<?php _e('Required', $wpulang); ?>)</small></label>
				<p><textarea name="pmessage" id="messagebox"></textarea></p>
                <p><input type="checkbox" name="savesend" value="1" /> <?php _e('Save This Message', $wpulang); ?></p>
                <p><input type="hidden" name="userid" value="<?php echo $id; ?>" /></p>
                <p><input type="hidden" name="userip" value="<?php echo $userip; ?>" /></p>
                <p><input type="submit" name="submit" value="<?php _e('Send PM', $wpulang); ?>" /></p>
				<p><?php echo sprintf(__('Your ip adress: %s (Note: Recording for security only.)<br />Do not spam and share illegal contents to other users', $wpulang), $userip); ?></p>
		</form>

<?php
}

// Private Message Sender Function
function wpu_send_pm() {
	global $current_user, $wpdb, $wpulang;
	
	#Get all data
	$from = $_POST["sender"];
	$to = $_POST["recipient"];
	$sub = $_POST["subject"];
	$msg = $_POST["pmessage"];
	$save = $_POST["savesend"];
	$toid = $_POST["userid"];
	$ip = $_POST["userip"];
	$date = date("Y-m-d H:i:s");
	$fromid = $current_user->ID;
	if(!$save) { $save = "0"; }
	$b = "<p>"; $a = "</p>";
	$tryagain = __('Please, try again.', $wpulang);

	if(($from == "") || ($to == "") || ($sub == "") || ($msg == "") || ($toid == "") || ($ip == "")) {
		if($from == "") { echo $b.__('Your username is empty!', $wpulang).$tryagain.$a; }
		else if($to == "") { echo $b.__('Your reciepent is empty!', $wpulang).$tryagain.$a; }
		else if($sub == "") { echo $b.__('Your message subject is empty!', $wpulang).$tryagain.$a; }
		else if($msg == "") { echo $b.__('Ooops, what are you doing? Where is your message?', $wpulang).$a; }
		else if($toid == "") { echo $b.__('Something wrong! I think user id is missing.', $wpulang).$tryagain.$a; }
		else if($ip == "") { echo $b.__('Are you hiding? Lets see your ip adress!', $wpulang).$tryagain.$a; }
		else { echo $b.__('Something Wrong', $wpulang).$tryagain.$a; }
	return;
	}
	
	$sname = $current_user->user_login;
	if($from != $sname) { _e('Your username doesn\'t match.', $wpulang); return; }
	
	$name = $wpdb->get_var("SELECT user_nicename FROM $wpdb->users WHERE ID=".$toid);

	if($name != $to) { _e('Recipient "ID" and "Name" doesn\'t match. Cheating huh?', $wpulang); return; }

	$wpdb->query( $wpdb->prepare("INSERT INTO ".$wpdb->prefix."private_messages(senderid, rcpid, sender, recipient, subject, message, fromsee, tosee, date, status, ip) VALUES ( %d, %d, %s, %s, %s, %s, %d, %d, %s, %d, %s )", $fromid, $toid, $from, $to, $sub, $msg, $save, 1, $date, 0, $ip ) );

	$wpdb->print_error();
	$wpdb->flush();
	echo "<p class=\"succesful\">".__('Your message was sent.', $wpulang)."</p>";
}


// Read Private Message
function wpu_read() {
	global $wpdb, $current_user, $wpulang;
	$id = $_GET["id"];
	$r = $_GET["r"];
	if(!$r || !$id) { echo "Error #4"; return; }
	if($r == "sent") { $f = "senderid"; }
	elseif($r == "recieved") { $f = "rcpid"; }
	else { return; }
	
	$bf = "<tr><td class=\"left\">"; $mid = ": </td><td>"; $af = "</td></tr>";
	
	$pm = $wpdb->get_row("SELECT * FROM $wpdb->prefix".private_messages." WHERE id = $id", ARRAY_A);
	
	echo "<table id=\"readpm\">";

	if($pm[$f] == $current_user->ID) {
		if($f == "rcpid" && $pm['status'] == "0") {
			$wpdb->query("UPDATE $wpdb->prefix".private_messages." SET status = 1 WHERE id = $id"); // Set status unread to read
		}

	$first	= array("[REPLY]", "[/REPLY]");
	$second	= array("<div class=\"reply\">", "</div>");

	$message = str_replace($first, $second, $pm['message']);
	
	echo $bf.__('From', $wpulang).$mid.$pm['sender'].$af;
	echo $bf.__('To', $wpulang).$mid.$pm['recipient'].$af;
	echo $bf.__('Subject', $wpulang).$mid.$pm['subject'].$af;
	echo "<tr><td colspan=\"2\" class=\"msgtop\">".__('Message Content', $wpulang)."</td></tr>";
	echo "<tr><td colspan=\"2\" class=\"message\">".$message."</td></tr>";
	echo "<tr><td colspan=\"2\"><a href=\"?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=reply&msgid=".$id."\">".__('Reply!')."</a> & <a href=\"?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=delete&id=".$id."&r=$r\">".__('Delete')."</a></td></tr>";
	} else {
	echo $bf."<b>".__('Hack Attempt: You don\'t allowed to see the others message(s)!', $wpulang)."</b>".$af;
	}
	echo "</table>";
}


// Reply Private Message
function wpu_reply_pm() {
	global $current_user, $wpdb, $wpulang;
	$msgid = $_GET["msgid"];
	if(!$msgid || $msgid == "") { echo "Error while messaging!"; return; }
	$pm = $wpdb->get_row("SELECT * FROM $wpdb->prefix".private_messages." WHERE id = $msgid", ARRAY_A);

	if($pm['rcpid'] != $current_user->ID) { echo "<p>".__('Hack Attempt: You don\'t allowed to reply this message!', $wpulang)."</p>"; return; }

	$id = $pm['senderid'];
	$user = $pm['sender'];
	$userip = $_SERVER['REMOTE_ADDR'];

?>
<form name="omforms" class="omlist" method="post" action="<?php echo "?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=sendpm"; ?>">
	<input type="hidden" name="sender" id="sender" value="<?php echo $current_user->user_login; ?>" readonly="readonly" />
	<label for="recipient"><?php _e('To', $wpulang); ?></label>
	<p><input type="text" name="recipient" id="recipient" value="<?php echo $user; ?>" readonly="readonly" /></p>
	<label for="subject"><?php _e('Subject', $wpulang); ?> <small>(<?php _e('Required', $wpulang); ?>)</small></label>
	<p><input type="text" name="subject" id="subject" value="<?php echo __('Re:', $wpulang)." ".$pm['subject']; ?>" /></p>
	<label for="pmessage"><?php _e('Your Message', $wpulang); ?> <small>(<?php _e('Required', $wpulang); ?>)</small></label>
		<p><textarea name="pmessage" id="messagebox"><?php echo "[REPLY]".$pm['message']."[/REPLY]"; ?></textarea></p>
		<p><input type="checkbox" name="savesend" value="1" /> <?php _e('Save This Message', $wpulang); ?></p>
        <p><input type="hidden" name="userid" value="<?php echo $id; ?>" /></p>
        <p><input type="hidden" name="userip" value="<?php echo $userip; ?>" /></p>
        <p><input type="submit" name="submit" value="<?php _e('Send Reply', $wpulang); ?>" /></p>
		<p><?php echo sprintf(__('Your ip adress: %s (Note: Recording for security only.)<br />Do not spam and share illegal contents to other users', $wpulang), $userip); ?></p>
</form>

<?php
}

// Delete Private Message
function wpu_delete_pm() {
	global $wpdb, $wpulang, $current_user;

	$id = $_GET["id"];
	$r = $_GET["r"];
	if(!$r || !$id) { echo "Error #4"; return; }
	if($r == "sent") { $f = "senderid"; }
	elseif($r == "recieved") { $f = "rcpid"; }
	else { return; }

	$pm = $wpdb->get_row("SELECT * FROM $wpdb->prefix".private_messages." WHERE id = $id", ARRAY_A);
	if($pm[$f] == $current_user->ID) {
		if($f == "senderid") {
			if($pm['tosee'] == 1) {
				$wpdb->query("UPDATE $wpdb->prefix".private_messages." SET fromsee = 0 WHERE id = $id");
			}
			if($pm['tosee'] == 0) {
				$wpdb->query("DELETE FROM $wpdb->prefix".private_messages." WHERE id = $id");
			}
		}
		if($f == "rcpid") {
			if($pm['fromsee'] == 1) {
				$wpdb->query("UPDATE $wpdb->prefix".private_messages." SET tosee = 0 AND status = 1 WHERE id = $id");
			}
			if($pm['fromsee'] == 0) {
				$wpdb->query("DELETE FROM $wpdb->prefix".private_messages." WHERE id = $id");
			}
		}
		echo "<b>".__('You have successfully delete the message.', $wpulang)."</b>";
	}
	else {
		echo "<p>".__('Hack Attempt: You don\'t allowed to delete this message!', $wpulang)."</p>";
	}
}

// Member list Page
function wpu_member_list() {
	global $wpdb, $wpulang;
	
	$query = "SELECT ID, user_nicename from $wpdb->users ORDER BY ID";
	$authors = $wpdb->get_results($query);
	$authr = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->users;");
	
	echo "<p class=\"information\">".sprintf(__('Now, we have %s registered users, but you don\'t see subscribers in this list!', $wpulang), $authr)."<br/>
	".__('Reason: They don\'t allowed chatting!', $wpulang)."</p>\n";

	foreach($authors as $author) :
	$curauth = get_userdata($author->ID);
	if($curauth->user_level >= 0 || $curauth->user_login == 'admin') :
	$user_link = get_author_posts_url($curauth->ID);
	$avatar = 'wavatar';
	$website = $curauth->user_url;
	if($website == "http://") { $website = get_option('siteurl'); }
	$pmlink = "?page=".dirname(plugin_basename(__FILE__))."/wpu_private_messages.php&wpu=newpm&id=".$curauth->ID."&name=".$curauth->user_nicename;
?>
	<div id="userlist"><?php echo get_avatar($curauth->user_email, '32', $avatar); ?>
	<?php echo "#".$curauth->ID." ".$curauth->display_name; ?><br />
	<p class="userright">
	<?php echo sprintf(__('<a href="%s">What %s Wrotes?</a> / <a href="%s">Visit His/Her Website!</a> / <a href="%s">Send PM to %s!</a>', $wpulang), $user_link, $curauth->user_nicename, $website, $pmlink, $curauth->user_nicename); ?>
    </p>
    <div class="clear"></div>
    </div>
<?php endif; ?>
<?php endforeach; ?>
<?php
}

// Load Pm Style
function wpu_pm_style() {
	$url = get_settings('siteurl');
	$url = $url . "/wp-content/plugins/".dirname(plugin_basename(__FILE__))."/style.css";
	echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . $url . "\" />\n";
}
?>