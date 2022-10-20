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
if (!$id) return;
$user_name = wp_get_current_user()->exists() ? wp_get_current_user()->display_name : __('Canceled by link', 'book-appointment-online');
$time = apply_filters('book_oz_timeFormat',get_post_meta($id,'oz_time_rot',true));
$date = apply_filters('book_oz_dateFormat',get_post_meta($id,'oz_start_date_field_id',true)).' '.$time;
?>
 <html>
	<body>
		<p><?php echo sprintf(__('%s, %s booking on %s', 'book-appointment-online'),$user_name,__('Canceled', 'book-appointment-online'),$date); ?></p>
	</body>
 </html>