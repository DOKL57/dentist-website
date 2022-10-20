<?php 
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
/*
$id - booking id
$status - canceling status
*/
if (!$id && !$status) return;
$userid = get_post_meta($id,'oz_user_id',true);
$user = get_userdata($userid);
$user_name = $user->first_name;
$time = apply_filters('book_oz_timeFormat',get_post_meta($id,'oz_time_rot',true));
$date = apply_filters('book_oz_dateFormat',get_post_meta($id,'oz_start_date_field_id',true)).' '.$time;
?>
 <html>
	<body>
		<p><?php echo sprintf(__('%s, %s booking on %s', 'book-appointment-online'),$user_name,$status,$date); ?></p>
	</body>
 </html>