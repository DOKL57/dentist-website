<?php
/*
Plugin Name: OzApp - appointment booking plugin (ex. Book an appointment online) 
Plugin URI: http://demo.oz-plugin.ru/
Description: Appointment online to a specialist. Perfectly suitable for beauty salons, medical centers, car services, etc.
Version: 3.1.0.1
Author: oz-plugin
Author URI: http://demo.oz-plugin.ru/
License: GPLv2
Text Domain: book-appointment-online
*/

define( 'OZAPP_VER', '3.1.0.1' );	
define( 'OZAPP_FILE', __FILE__ );	
define( 'OZAPP_PATH', dirname(__FILE__) );	
define( 'OZAPP_ADDONS_PATH', OZAPP_PATH.'/inc/addons/' );	
define( 'OZAPP_TEMPLATES_PATH', OZAPP_PATH.'/inc/templates/' );	
define( 'OZAPP_URL', plugins_url('/', OZAPP_FILE) );	
define( 'OZAPP_LANG', 'book-appointment-online' );	
$theme = get_option('oz_theme');
$vid = get_option('oz_vid');
global $oz_theme, $iz_vid;
$oz_theme = isset($theme['chk']) ? $theme['chk'] : $theme;
$oz_vid = isset($vid['chk']) ? $vid['chk'] : $vid;
require 'vendor/autoload.php';

add_action( 'plugins_loaded', 'book_oz_plugin_load_textdomain' );
function book_oz_plugin_load_textdomain() {
  $lang = get_locale();
  load_textdomain( 'book-appointment-online', OZAPP_PATH."/languages/book-appointment-online-$lang.mo" );
  //load_plugin_textdomain( 'book-appointment-online', false, 'book-appointment-online/languages' ); 
}



/**
 * not used code
 */

add_action('wp_ajax_checkRas', 'book_oz_checkRasAX');
function book_oz_checkRasAX() {

if (defined('DOING_AJAX') && DOING_AJAX) {
$nonce = (isset($_POST['nonce'])) ? $_POST['nonce'] : '';
$id = $_GET['id'];
$arr = json_decode(get_post_meta($id,'oz_raspis',true),true);
$zapisi = get_post_meta($id,'oz_clientsarray',true);
if ($arr) :
$start = array_column($arr, 'start');
$end = array_column($arr, 'end');
$start =  min($start);
$end =  max($end);
$raspi = get_post_meta($id,'oz_re_timerange',true);
$prom = array_column($raspi,'oz_select_time_serv');
$prom = ($prom) ? min($prom) : '';
$prom = (isset($prom)) ? ',"prom":"'.$prom.'"' : '';
$res = '[{"start":"'.$start.'","end":"'.$end.'"'.$prom.'},'.$zapisi.']';
else :
	$res = 'nothing';
endif;
check_ajax_referer( 'ozajax-nonce', 'nonce' );
	echo $res;
}
	wp_die();
}

?>