<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */

use Ozplugin\Assets;
use Dompdf\Dompdf;
use Dompdf\Options;
use Ozplugin\Addons\Email;
use Ozplugin\Appointment;

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter('book_oz_change_Wtime_afterAdd', 'book_oz_wTimePlus',10,3); /*обновляем общее время перед записью к сотруднику */
add_filter('book_oz_dayRaspis_wTime', 'book_oz_wTimePlus',10,3); /*обновляем общее время перед выводом времени */
add_filter('book_oz_checkCurrentZapisi_wTime', 'book_oz_wTimePlus',10,3); /*обновляем занятое время в админке у клиентов*/
add_filter('book_oz_consoleCalendar_wTime', 'book_oz_wTimePlus',10,3); /*обновляем занятое время в консоле*/
add_filter('book_oz_sotrudniki_wTime', 'book_oz_wTimePlus',10,3); /*обновляем занятое время в сотрудниках*/
add_filter('book_oz_userArea_wTime', 'book_oz_wTimePlus',10,3); /*обновляем занятое время в ЛК пользователя*/

function book_oz_wTimePlus($time, $uslugi, $useBuffer = true) {
	$uslugi = (is_array($uslugi)) ? $uslugi : explode(',',$uslugi);
	if (!empty($uslugi)) {
			$time = 0;
			foreach ($uslugi as $usl) {
			$usltime = 0;
			$usltime = get_post_meta($usl,'oz_serv_time',true) ?: 0;
			
			//if ($useBuffer)
			//$usltime = get_post_meta($usl,'oz_serv_buffer',true) ? get_post_meta($usl,'oz_serv_buffer',true) + $usltime : $usltime;
			
			$time = $time + $usltime;
			}
	}
	return $time;
}

add_filter('book_oz_consoleCalendar_uslugi', 'book_oz_uslTitle',10,2);
add_filter('book_oz_sotrudniki_uslTitle', 'book_oz_uslTitle',10,2);
add_filter('book_oz_userArea_uslTitle', 'book_oz_uslTitle',10,2);
add_filter('book_oz_uslugi_uslTitle', 'book_oz_uslTitle',10,2);

function book_oz_uslTitle($title, $uslugi) {
	if (get_option('oz_multiselect_serv')) {
		$uslugi = (is_array($uslugi)) ? $uslugi : explode(',',$uslugi);
		if (count($uslugi) > 1 ) {
			$title = '';
			foreach ($uslugi as $usl) {
			$usl = apply_filters('book_oz_WPML_id',$usl);
			$usl_post = get_post( $usl );
			$title_new = isset( $usl_post->post_title ) ? $usl_post->post_title : '';
			$title = ($title) ? $title.', '.$title_new : $title_new;
			}	
		}
	}
	return $title;
}


add_filter('book_oz_sotrudniki_uslbuffer', 'book_oz_sotrudniki_uslbuffer_func',10,2);

function book_oz_sotrudniki_uslbuffer_func($buffer, $uslugi, $useBuffer = true) {
	$uslugi = (is_array($uslugi)) ? $uslugi : explode(',',$uslugi);
	if (!empty($uslugi)) {
			$before = 0;
			$after = 0;
			foreach ($uslugi as $usl) {
				if ($useBuffer) {
				$before = get_post_meta($usl,'oz_serv_buffer_before',true) && get_post_meta($usl,'oz_serv_buffer_before',true) > $before ? get_post_meta($usl,'oz_serv_buffer_before',true) : $before;
				$after = get_post_meta($usl,'oz_serv_buffer',true) && get_post_meta($usl,'oz_serv_buffer',true) > $after ? get_post_meta($usl,'oz_serv_buffer',true) : $after;
				}
			}
			$buffer = [$before, $after];
	}
	return $buffer;	
}


/**
 *  JS options on front and backend for min time and canceling booking
 *  
 *  @param array()    $opts list of options
 *  @return list of options
 *  
 *  @version 2.0.3
 */
function book_oz_MinTimeBooking($opts) {
	$opts['minTime'] = (get_option('oz_time_min_show'));
	$opts['minTimeCancel'] = get_option('oz_time_min_cancel');
	return $opts;
}

add_filter('book_custAdmin_JSOptions', 'book_oz_MinTimeBooking');
add_filter('book_custFront_JSOptions', 'book_oz_MinTimeBooking');

add_filter('book_oz_get_posts', 'book_oz_suppress_disabled');

/**
 *  Disable suppress_filters for WPML
 *  
 *  @param array()    $args parametres for get_posts
 *  @return array of parametres
 *  
 *  @version 2.0.3
 */
function book_oz_suppress_disabled($args) {
	$args['suppress_filters'] = false;
	return $args;
}

add_filter('book_oz_personal_field', 'book_oz_find_pers_id_wpml',10,1);
add_filter('book_oz_update_personal_field_onDelete', 'book_oz_find_pers_id_wpml',10,1);
add_filter('book_oz_pers_onSkip', 'book_oz_find_pers_id_wpml',10,1); // выводим список персонала если чел пропускает выбор спеца


/**
 *  Find personal ID. This need to find Staff ID on other languages. Also for create calendar on staff page WPML.
 *  
 *  @param string    $pers_id Staff ID
 *  @return array() array of staff ID on other languages
 *  
 *  @version 2.0.3
 */
function book_oz_find_pers_id_wpml($pers_id) {
		if (function_exists('icl_get_languages')) :
			$langs = icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
			$langs = wp_list_pluck($langs, 'language_code');
			$pers_id_array = array();
			$pers_id = (is_array($pers_id)) ? $pers_id : array($pers_id);
			foreach ($langs as $lang) {
					foreach ($pers_id as $pers) : 
						$Id = icl_object_id($pers,'post',false, $lang);
						if ($Id) $pers_id_array[] = icl_object_id($pers,'post',false, $lang); 
					endforeach;
			}
			if ($pers_id_array) $pers_id = $pers_id_array;
		endif;
	return $pers_id;
}	
add_action('book_oz_send_ok', 'book_oz_duplicate_clients_wpml');

/**
 *  Duplicate clients booking from personal on other languages WPML
 *  
 *  @param int    $client_id Client ID
 *  @return void
 *  
 *  @version 2.0.3
 */
function book_oz_duplicate_clients_wpml($client_id) {
if (!function_exists('icl_get_languages')) return;
$langs = icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
$cur_lang = ICL_LANGUAGE_CODE;
$langs = wp_list_pluck($langs, 'language_code');
$pers_id = get_post_meta($client_id,'oz_personal_field_id', true);
$clienti = get_post_meta($pers_id,'oz_clientsarray',true);
foreach ($langs as $lang) {
		$pers_id_l = icl_object_id($pers_id,'post',false, $lang);
		update_post_meta($pers_id_l, 'oz_clientsarray', $clienti);
}
}

/**
 *  Return post ID on current language WPML
 *  
 *  @param int    $id Post ID
 *  @param string    $type Post type
 *  @return Return post ID for current language
 *  
 *  @version 2.0.3
 */
function book_oz_WPML_get_ID($id, $type = 'post') {
	if (defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE && strpos($id, ',') === false) {
		$id = icl_object_id($id,$type,false, ICL_LANGUAGE_CODE);
	}
	return $id;
}

add_filter('book_oz_WPML_id', 'book_oz_WPML_get_ID');


/**
 *  Add name for status column
 *  
 *  @param array    $columns column names
 *  @return array with names
 *  
 *  @version 2.0.9
 */
function book_oz_clients_statusColumn_title($columns) {
	if (get_option('book_oz_enable_statuses')) {
    $columns['oz_status_td'] = __("Appointment status", 'book-appointment-online');
	}
	return $columns;
}
add_filter('manage_edit-clients_columns', 'book_oz_clients_statusColumn_title',15);

add_action('book_oz_send_ok', 'book_oz_setStatusonBooking');

/**
 *  Add status post meta to the appointment on booking 
 *  
 *  @param int    $id Appointment id
 *  @return void
 *  
 *  @version 2.0.9
 */
function book_oz_setStatusonBooking($id) {
	if (get_option('book_oz_enable_statuses')) {
		update_post_meta($id, 'oz_app_status', apply_filters('book_oz_status_onBooking',get_option('oz_status_def'), $id));
		$post_id = get_post_meta((int)($id), 'oz_personal_field_id',true);
		book_oz_update_spisok_klientov_func($post_id);
	};
}

add_action('admin_init', 'book_oz_status_parse');
/**
 *  Change appointments status 
 *  
 *  @return void
 *  
 *  @version 2.0.9
 */
function book_oz_status_parse() {
	if (isset($_GET['oz_status']) && book_oz_user_can(false,false,'changeStatus') ) {
		$new_status = $_GET['oz_status'];
		if ($_GET['oz_status'] && isset($_GET['post'])) {
			$old_status = get_post_meta((int)($_GET['post']), 'oz_app_status',true);
			if (!$old_status || $old_status != $_GET['oz_status']) {
				update_post_meta((int)($_GET['post']), 'oz_app_status',$new_status);
				$post_id = get_post_meta((int)($_GET['post']), 'oz_personal_field_id',true);
				book_oz_update_spisok_klientov_func($post_id);
				do_action('book_oz_onAppointmentStatusChange',(int)($_GET['post']),$_GET, $old_status);
			}
		}
	}
}

add_filter('book_oz_get_posts', 'book_oz_get_only_approved',10,2);
add_filter('book_oz_wp_query', 'book_oz_get_only_approved',10,2);																 
/**
 *  Return only approved appointments
 *  
 *  @param array    $args meta_query for get_posts
 *  @param string    $where where it's filter should work
 *  @return meta_query
 *  
 *  @version 2.0.9
 */
function book_oz_get_only_approved($args, $where = '') {
	if (isset($args['post_type']) && $args['post_type'] == 'clients' && get_option('book_oz_enable_statuses') && $where != 'dashboard') {
		$approved = array(
			'relation' => 'OR',
			array(
			'key' => 'oz_app_status',
			'value'   => array('approved', 'onhold', ''),
			'compare' => 'IN',
			)
		);
		if (isset($args['meta_query'])) {
			array_push($args['meta_query'], $approved);
		}
		else {
			$args['meta_query'] = $approved;
		}
	}
	return $args;
}

//TODO move admin interface functions below this line into admin.php

add_filter('book_oz_dashboard_appointments_JSON', 'book_oz_setStatusonDashboard', 10, 2);

/**
 *  Set color for appointments on dashboard
 *  
 *  @param array    $appParams Appointment params for calendar on dashboard
 *  @param array    $id Appointment id
 *  @return array with params
 *  
 *  @version 2.0.9
 */
function book_oz_setStatusonDashboard($appParams, $id) {
	if (get_option('book_oz_enable_statuses')) {
	$appParams['color'] = '#2dde98';
	$appParams['status'] = 'approved';
		$statuses = array(
		'approved' => array(
			'status' => 'approved',
			'name' => __("Approved", 'book-appointment-online'),
			'color' => '#2dde98'
		),
		'onhold' => array(
			'status' => 'onhold',
			'name' => __("On hold", 'book-appointment-online'),
			'color' => '#F2B134'
		),
		'canceled' => array(
			'status' => 'canceled',
			'name' => __("Canceled", 'book-appointment-online'),
			'color' => '#ED553B'
		),
		);
		$def = get_option('oz_status_def');
		if (isset($statuses[$def])) {
		$appParams['color'] = $statuses[$def]['color'];
		$appParams['status'] = $statuses[$def]['status'];
		}
		$status = get_post_meta($id,'oz_app_status',true);
		if ($status && isset($statuses[$status])) {
			$appParams['color'] = $statuses[$status]['color'];
			$appParams['status'] = $statuses[$status]['status'];
		}
	}
	return $appParams;
}

add_action('book_oz_dashboard_selects_before', 'book_oz_statuses_filter_dashboard' );
function book_oz_statuses_filter_dashboard() {
		if (!get_option('book_oz_enable_statuses'))  return;
		$statuses = array(
		'approved' => array(
			'status' => 'approved',
			'name' => __("Approved", 'book-appointment-online'),
			'color' => '#2dde98'
		),
		'onhold' => array(
			'status' => 'onhold',
			'name' => __("On hold", 'book-appointment-online'),
			'color' => '#F2B134'
		),
		'canceled' => array(
			'status' => 'canceled',
			'name' => __("Canceled", 'book-appointment-online'),
			'color' => '#ED553B'
		),
		);
	?>
	<div class="oz_statuses_dashboard">
		<div class="oz_statuses_dashboard_flex">
		<?php foreach ($statuses as $status) { ?>
			<div data-status="<?php echo $status['status']; ?>" class="oz_status_dashboard"><span style="background-color:<?php echo $status['color']; ?>"></span><?php echo $status['name']; ?></div>
		<?php } ?>
		</div>
	</div>
	<?php
}

add_filter('book_custFront_JSOptions', 'book_oz_redirect_url_js_option');

function book_oz_redirect_url_js_option($opts) {
	$opts['redirect_url'] = get_option('oz_redirect_url');
	return $opts;	
}

/**
 *  Return min time of service for staff
 *  
 *  @param int    $pers_id staff id
 *  @return min time in minutes
 *  
 *  @version 2.1.0
 */
function book_oz_findMinServTime($pers_id) {
		$minpersTime = 9999;
		$incl_serv = get_post_meta($pers_id, 'oz_book_provides_services',true);
		if ($incl_serv == 'include') {
			$allusl =  get_post_meta($pers_id, 'oz_re_timerange',true);
				if ($allusl) {
				foreach ($allusl as $usl) {
				$id = $usl['oz_personal_serv_name'];
				$min = get_post_meta($id, 'oz_serv_time',true);
				if ($min < $minpersTime ) $minpersTime = $min;
				}
			}
		}
		if ($minpersTime == 9999) {
		$count_services = wp_count_posts('services');
			if ($count_services && $count_services->publish == 1) {
			$args = array(
			'post_type' => 'services',
			'post_status' => 'publish',
			'posts_per_page'   => -1,

			);
			$findService = new WP_Query( $args );
		$minTime = $findService->posts[0]->ID;
		$minTime = get_post_meta($minTime, 'oz_serv_time', true);
		if ($minTime && $minTime < $minpersTime ) $minpersTime = $minTime;
			}
		}
		return $minpersTime;
}


add_filter( 'do_shortcode_tag','oz_enq_scripts_as_shortcode',10,3);
/**
 *  Enqueue scripts if using oz_template shortocde
 *  
 *  @param html    $output HTML template of booking form
 *  @param string    $tag Shortcode name
 *  @param array    $attr Shortcode attributes
 *  @return html template
 *  
 *  @version 2.1.9
 */
function oz_enq_scripts_as_shortcode($output, $tag, $attr){
	if ($tag == 'oz_template') {
		Assets::book_oz_front_scripts(true);
	}
	return $output;
}

add_action('wp_ajax_oz_types', 'book_oz_post_types');
function book_oz_post_types() {
	if (defined('DOING_AJAX') && DOING_AJAX) {
		$args = array(
		'post_type' => sanitize_text_field($_POST['post_type']),
		'post_per_page' => -1,
		);
		$serv = get_posts($args);
		echo json_encode($serv, JSON_FORCE_OBJECT);
	}
	wp_die();
}

add_filter('book_custFront_JSOptions', function ($vars) { $vars['dateFormat'] = convertPHPToMomentFormat(get_option('date_format')); return $vars;});
	

// enabled skip option or not
add_filter('book_oz_form_cssClasses', 'classForSkipActive'); 
function classForSkipActive($classes) { 
	if (!get_option('oz_skip_step_ifOne')) 
		array_push($classes, 'noSkipSteps');
	if (get_option('book_oz_skip_personal') == 1) 
		array_push($classes, 'onlyServices');
	return $classes;
}




add_filter('book_oz_primaryColors', 'book_oz_primaryneu');
function book_oz_primaryneu($colors) {
	global $oz_theme;
	if ($oz_theme == 'default') return $colors;
	$cols = array(
	'.oz_hid  .ui-datepicker-header',
	'.oz_zapis_info',
	'.oz_back_btn',
	);
	foreach ($colors['background-color'] as $key => $color) :
	if (in_array($color, $cols)) {
		unset($colors['background-color'][$key]);
	}
	endforeach;
	return $colors;
}

add_filter('book_oz_secondColors', 'book_oz_secondneu');
function book_oz_secondneu($colors) {
	global $oz_theme;
	if ($oz_theme == 'default') return $colors;
	$cols = array(
	'.oz_back_btn:hover'
	);
	foreach ($colors['background-color'] as $key => $color) :
	if (in_array($color, $cols)) {
		unset($colors['background-color'][$key]);
	}
	endforeach;
	return $colors;
}

add_image_size( 'thumb-neu', 300, 382, true );

add_filter('book_oz_dateFormat', 'book_oz_setDateFormat');

function book_oz_setDateFormat($date) {
	return date_i18n(get_option('date_format'),strtotime($date));
}

/**
 *  Analog wp_timezone_string
 *  
 *  @return timezone string
 *  
 *  @version 2.3.8
 */
function book_oz_timezone_string($num = '') {
    // $timezone_string = get_option( 'timezone_string' );
 
    // if ( $timezone_string ) {
        // return $timezone_string;
    // }
	if ($num == '') $num = get_option('gmt_offset');
    $offset  = (float) $num;
    $hours   = (int) $offset;
    $minutes = ( $offset - $hours );
 
    $sign      = ( $offset < 0 ) ? '-' : '+';
    $abs_hour  = abs( $hours );
    $abs_mins  = abs( $minutes * 60 );
    $tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
 
    return $tz_offset;
}

add_filter('book_oz_onUserCancelEmail', 'book_oz_onUserCancelLog',10,2);

function book_oz_onUserCancelLog($email, $id) {
	if (wp_get_current_user()->exists()) {
	$logs = get_post_meta($id, 'oz_logs', true) ?: [];
	$mess = [
		'changed' => [[
			'from' => 'publish',
			'to' => 'draft',
			'what' => __('Post status', 'book-appointment-online'),
			'what_string' => 'post_status'
		]],
		'who' => [
			'name' => wp_get_current_user()->display_name,
			'id' => wp_get_current_user()->ID,
		],
		'when' => current_time('mysql')
	];
	$logs[time()] = $mess;
	update_post_meta($id, 'oz_logs', $logs);
	do_action('book_oz_after_logs_updated', $id, $mess);
	}
	return $email;
}

add_action('book_oz_onAppointmentStatusChange', 'book_oz_onStatusChangeLog',10,3);

function book_oz_onStatusChangeLog($id, $get, $old_status) {
	$id = (int)($_GET['post']);
			if ($old_status || $old_status != $_GET['oz_status']) {
		$statuses = array(
		'approved' => __("Approved", 'book-appointment-online'),
		'onhold' => __("On hold", 'book-appointment-online'),
		'canceled' => __("Canceled", 'book-appointment-online'),
		);
		// if statiuses was disable
		if (!$old_status) {
			$old_status = 'approved';
		}
				$logs = get_post_meta($id, 'oz_logs', true) ?: [];
				$mess = [
					'changed' => [[
						'from' => $statuses[$old_status],
						'to' => $statuses[sanitize_text_field($_GET['oz_status'])],
						'what' => __('Post status', 'book-appointment-online'),
						'what_string' => 'post_status'
					]],
					'who' => [
						'name' => wp_get_current_user()->display_name,
						'id' => wp_get_current_user()->ID,
					],
					'when' => current_time('mysql')
				];
				$logs[time()] = $mess;
				update_post_meta($id, 'oz_logs', $logs);
				do_action('book_oz_after_logs_updated', $id, $mess);
			}
}

add_action('init', 'book_oz_cancel_app_by_link');

function book_oz_cancel_app_by_link() {
	if (isset($_GET['oz_cancel']) && $_GET['oz_cancel'] && isset($_GET['oz_cancel_code']) && $_GET['oz_cancel_code']) {
		$id = (int) ($_GET['oz_cancel']);
		$app_code = hash('sha1', $id.'&'.get_post_meta($id,'oz_start_date_field_id',true).'&'.get_post_meta($id,'oz_time_rot',true));
		$min = get_option('oz_time_min_cancel')*3600;
		$t = ($id) ? get_post_meta($id,'oz_start_date_field_id',true).' '.get_post_meta($id,'oz_time_rot',true) : '' ;
		$gmt = current_time( 'timestamp' ) - time();
		$time = DateTime::createFromFormat('d.m.Y H:i', $t);
		$time = $time->format('U') - $gmt;
		$timeNow = current_time('timestamp',1);
		$deltatime = $time - $timeNow;
		if ($_GET['oz_cancel_code'] == $app_code && get_post_status($id) == 'publish' && ($deltatime >= $min)) {
			$status =  __('Canceled', 'book-appointment-online');
			update_post_meta($id,'canceled_by_user',$status);
			$old_status = get_post_meta($id,'oz_app_status',true);
			update_post_meta($id,'oz_app_status','canceled');
			if (wp_update_post(array('ID'    =>  $id, 'post_status'   =>  'draft'))) {
				do_action('book_oz_on_canceled_by_link',$id, $old_status);
			}
			wp_die(sprintf(__('Appointment №%s was canceled successfully', 'book-appointment-online'), $id));
		}
	}
}

add_action('book_oz_on_canceled_by_link', 'book_oz_onCancelLinkLog',10,2);

function book_oz_onCancelLinkLog($id, $old_status = 'approved') {
		$name = wp_get_current_user()->exists() ? wp_get_current_user()->display_name : __('Canceled by link', 'book-appointment-online');
		$statuses = array(
		'approved' => __("Approved", 'book-appointment-online'),
		'onhold' => __("On hold", 'book-appointment-online'),
		'canceled' => __("Canceled", 'book-appointment-online'),
		);
		$logs = get_post_meta($id, 'oz_logs', true) ?: [];
		$mess = [
			'changed' => [[
				'from' => $statuses[$old_status],
				'to' => $statuses['canceled'],
				'what' => __('Post status', 'book-appointment-online'),
				'what_string' => 'post_status'
			]],
			'who' => [
				'name' => $name,
				'id' => wp_get_current_user()->exists() ? wp_get_current_user()->ID : 0,
			],
			'when' => current_time('mysql')
		];
		$logs[time()] = $mess;
		update_post_meta($id, 'oz_logs', $logs);
		do_action('book_oz_after_logs_updated', $id, $mess);		
}

add_action('book_oz_on_canceled_by_link', 'book_oz_on_sendCanceledByLink',10,2);
function book_oz_on_sendCanceledByLink($id,$old_status) {
	$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
				'X-Mailer: PHP/' . phpversion();
	$email = (get_option('oz_default_email')) ? get_option('oz_default_email') : get_option('admin_email');
	add_filter('wp_mail_from',function($from_email) { $from_email = (get_option('oz_email_from_email')) ? get_option('oz_email_from_email') : $from_email; return $from_email;},20,1);
	add_filter('wp_mail_from_name',function($name) { $name = (get_option('oz_default_email_name')) ? get_option('oz_default_email_name') : get_bloginfo('name'); return $name;},20,1);
	ob_start(); 
	include_once(plugin_dir_path( dirname(__FILE__) ).'templates/emails/canceledbylink_booking.php');
	$mess = ob_get_contents();
	ob_end_clean();
	wp_mail(apply_filters('book_oz_onUserCancelEmailByLink', $email, $id),__('User canceled booking', 'book-appointment-online'), $mess,$headers);
}

add_action('book_oz_add_fields_to_post', 'book_oz_timezone_to_post');

function book_oz_timezone_to_post($app_id) {
	if (isset($_COOKIE) && isset($_COOKIE['oz_timezone']) && $_COOKIE['oz_timezone'] != 'no' && $_COOKIE['oz_timezone'] != (get_option('gmt_offset')*60)) {
		update_post_meta((int) ($app_id), 'oz_timezone', (int) ($_COOKIE['oz_timezone']));
	}
}

function book_oz_service_time($usl) {
	$time = 0;
	if ($usl) {
		$usl = is_array($usl) ? $usl : explode(',',$usl);
		foreach ($usl as $us) :
		$time = $time + get_post_meta($us, 'oz_serv_time', true);
		endforeach;
	}
		return $time;
}

add_action( 'post_submitbox_misc_actions', 'book_oz_insert_notify_checkboxes');

function book_oz_insert_notify_checkboxes($post) {
	if ($post && $post->post_type == 'clients') :
	//print_r($post);
	$isNewPost = $post->post_date_gmt == '0000-00-00 00:00:00' && in_array($post->post_status, ['pending', 'draft', 'auto-draft']);
	if (!$isNewPost) return;
	?>
	<div class="misc-pub-section">
		<?php if (get_option('oz_e_before')) : ?>
		<label for="oz_notify_by_email"> <input type="checkbox" id="oz_notify_by_email" name="oz_notify_by_email" >
			<?php _e('Send email to the customer', 'book-appointment-online'); ?>
		</label>
		<br><br>
		<?php endif; ?>
		<?php if (get_option('oz_smsIntegration')) : ?>
		<label for="oz_notify_by_sms"> <input type="checkbox" id="oz_notify_by_sms" name="oz_notify_by_sms" >
			<?php _e('Send sms to the customer', 'book-appointment-online'); ?>
		</label>
		<?php endif; ?>
	</div>
	<?php
	endif;
}

add_action('book_oz_after_metabox', 'book_oz_deposit_settings_script');

function book_oz_deposit_settings_script($arg) {
	if ($arg == 'book_oz_service') {
	?>
	<script>
		if (document.querySelector('input[name="oz_serv_deposit"]')) {
			if (document.querySelector('select[name="oz_serv_deposit_type"]').value == 'percent') {
				document.querySelector('input[name="oz_serv_deposit"]').max = 100
				if (document.querySelector('input[name="oz_serv_deposit"]').value > 100) {
					document.querySelector('input[name="oz_serv_deposit"]').value = 100
				}
			}
			else {
				document.querySelector('input[name="oz_serv_deposit"]').max = ''
				if (document.querySelector('input[name="oz_serv_deposit"]').value > document.querySelector('input[name="oz_serv_price"]').value) {
					document.querySelector('input[name="oz_serv_deposit"]').value = document.querySelector('input[name="oz_serv_price"]').value
				}
			}
		document.querySelector('select[name="oz_serv_deposit_type"]').onchange = () => {
				if (document.querySelector('select[name="oz_serv_deposit_type"]').value == 'percent') {
					document.querySelector('input[name="oz_serv_deposit"]').max = 100
					if (document.querySelector('input[name="oz_serv_deposit"]').value > 100) {
						document.querySelector('input[name="oz_serv_deposit"]').value = 100
					}
				}
				else {
					document.querySelector('input[name="oz_serv_deposit"]').max = ''
					if (document.querySelector('input[name="oz_serv_deposit"]').value > document.querySelector('input[name="oz_serv_price"]').value) {
						document.querySelector('input[name="oz_serv_deposit"]').value = document.querySelector('input[name="oz_serv_price"]').value
					}
				}				
			}
		}
	</script>
	<?php
	}
}

	add_action('book_oz_in_metabox', 'book_oz_use_deposit_info', 10,2);
	function book_oz_use_deposit_info($arg,$key) {
		if ($arg == 'book_oz_clientTime' && $key == 6 && isset($_GET['post'])) {
	$id = $_GET['post'];
	$deposit = get_post_meta($id, 'oz_use_deposit',true);
	if ($deposit && is_array($deposit) && count($deposit)) :
			?>
			<tr>
				<td class="at-field " colspan="2">
					<div class="at-label">
						<label for="oz_payment_info"><?php  _e("Deposit", 'book-appointment-online'); ?></label>
					</div>
					<div>
					<?php _e("Yes", 'book-appointment-online'); ?><br>
					</div>
				</td>
			</tr>
			<?php
		endif;
		}
	}
	
	function book_oz_defSchedule($id = 0) {
		$schedule = [];
		$days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
		$temp = rand(100000, 999999);
		foreach($days as $day) {
			$schedule[] = [
				'day' => 'oz_'.$day,
				'start' => '10:00',
				'end' => '19:00',
				'id' => 'line-'.$temp,
				'pId' => $id
			];
		}
		return $schedule;
	}
	
	add_action('wp_ajax_nopriv_oz_auth','book_oz_auth');
	
	function book_oz_auth() {
		if (wp_doing_ajax() && check_ajax_referer('wp_rest', '_wpnonce') ) {
			$user = wp_signon();
			if ( is_wp_error($user) ) {
			echo json_encode([
				'success' => false,
				'text' => $user->get_error_message()
			]);				
			}
			else {
				echo json_encode([
					'success' => true,
					'text' => ''
				]);				
			}
		}
		wp_die();
	}
	
	add_action('wp_ajax_nopriv_oz_user_create','book_oz_create_employee');
	
	function book_oz_create_employee() {
		if (wp_doing_ajax() && check_ajax_referer('wp_rest', '_wpnonce') ) {
			$emp = get_option('oz_employees');
			$canRegisterEmployee = $emp && isset($emp['register_form']) && $emp['register_form'];
			if (!$canRegisterEmployee) {
				echo json_encode([
					'success' => false,
					'text' => 'Registration disabled',
				]);					
			}
			else {
				$code = isset($_POST['code']) ? (int) ($_POST['code']) : '';
				$email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
				$name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
				if (!$code || !$email || !$name) {
					echo json_encode([
						'success' => false,
						'text' => 'Empty code or email',
					]);		
				}
				elseif(get_transient('oz_'.$code) != $email) {
					echo json_encode([
						'success' => false,
						'text' => 'Wrong or expired code',
						'code' => 'wrong_code',
					]);					
				}
				else {
					$pass = wp_generate_password();
					$userdata = [
						'user_login' => $email,
						'user_pass' => $pass,
						'role'      => 'oz_employee',
						'display_name'    => $name,
						'first_name'    => $name,
						'user_email' => $email,
						
					];
					$user = wp_insert_user( $userdata );
					if ( is_wp_error($user) ) {
					echo json_encode([
						'success' => false,
						'text' => $user->get_error_message(),
						'code' => $user->get_error_code(),
					]);				
					}
					else {
						wp_set_auth_cookie( $user, true );
						$emailMess = new Email();
						$emailMess->emailOnRegister($user, $userdata);
						do_action('book_oz_onEmployeeRegistered', $user);
						$emp_id = wp_insert_post([
							'post_title'    => sanitize_text_field( $_POST['name'] ),
							'post_type' => 'personal',
							'post_author' => $user,
							'post_status' => apply_filters('book_oz_onRegisterEmployeeStatus', 'publish', $user)
						]);
						if (!is_wp_error($emp_id)) {
							$sched = book_oz_defSchedule($emp_id);
							update_post_meta($emp_id, 'oz_raspis', json_encode($sched));
							update_post_meta($emp_id, 'oz_notification_email', $email);
							echo json_encode([
								'success' => true,
								'text' => [$pass, $user]
							]);	
						}
						else {
							echo json_encode([
								'success' => false,
								'text' => $emp_id->get_error_message(),
								'code' => $emp_id->get_error_code(),
							]);
						}			
					}				
				}
			}

		}
		wp_die();
	}
	
	add_action('wp_ajax_nopriv_oz_user_verify','book_oz_create_verifyCode');
	
	function book_oz_create_verifyCode() {
		if (wp_doing_ajax() && check_ajax_referer('wp_rest', '_wpnonce') ) {
			$code = rand(100000, 999999);
			$email = $_POST['email'];
			if ($email) {
				$email = sanitize_email($email);
				set_transient( 'oz_'.$code, $email, MINUTE_IN_SECONDS * 15 );
				$msg = __('Your verification code is', 'book-appointment-online');
				$send = new Email();
				$send->mail($email, __('Сonfirm your registration on the site', 'book-appointment-online'), $msg.': '.$code);
				echo json_encode([
					'success' => true,
				]);
			}
			else {
				echo json_encode([
					'success' => false,
					'text' => 'Invalid email'
				]);				
			}
		}
		wp_die();
	}
	
	add_action('wp_ajax_nopriv_oz_reset_password','book_oz_resetPassword');
	
	function book_oz_resetPassword() {
		if (wp_doing_ajax() && check_ajax_referer('wp_rest', '_wpnonce') ) {
			$email = isset($_POST['email']) && $_POST['email'] ? sanitize_email($_POST['email']) : '';
			if ($email) {
				$res = book_oz_retrieve_password($email);
				if (!is_wp_error($res)) {
					echo json_encode([
						'success' => true,
					]);					
				}
				else {
					echo json_encode([
						'success' => false,
						'text' => $res->get_error_message(),
						'code' => $res->get_error_code(),
					]);					
				}

			}
			else {
				echo json_encode([
					'success' => false,
					'text' => 'Invalid email'
				]);				
			}
		}
		wp_die();
	}
	
/**
 * Copied core retrieve_password
 *
 * @since 2.5.0
 * @since 5.7.0 Added `$user_login` parameter.
 *
 * @global wpdb         $wpdb       WordPress database abstraction object.
 * @global PasswordHash $wp_hasher  Portable PHP password hashing framework.
 *
 * @param string $user_login Optional. Username to send a password retrieval email for.
 *                           Defaults to `$_POST['user_login']` if not set.
 * @return true|WP_Error True when finished, WP_Error object on error.
 */
function book_oz_retrieve_password( $user_login = null ) {
	$errors    = new WP_Error();
	$user_data = false;

	// Use the passed $user_login if available, otherwise use $_POST['user_login'].
	if ( ! $user_login && ! empty( $_POST['user_login'] ) ) {
		$user_login = $_POST['user_login'];
	}

	if ( empty( $user_login ) ) {
		$errors->add( 'empty_username', __( '<strong>Error</strong>: Please enter a username or email address.' ) );
	} elseif ( strpos( $user_login, '@' ) ) {
		$user_data = get_user_by( 'email', trim( wp_unslash( $user_login ) ) );
		if ( empty( $user_data ) ) {
			$errors->add( 'invalid_email', __( '<strong>Error</strong>: There is no account with that username or email address.' ) );
		}
	} else {
		$user_data = get_user_by( 'login', trim( wp_unslash( $user_login ) ) );
	}

	/**
	 * Filters the user data during a password reset request.
	 *
	 * Allows, for example, custom validation using data other than username or email address.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_User|false $user_data WP_User object if found, false if the user does not exist.
	 * @param WP_Error      $errors    A WP_Error object containing any errors generated
	 *                                 by using invalid credentials.
	 */
	$user_data = apply_filters( 'lostpassword_user_data', $user_data, $errors );

	/**
	 * Fires before errors are returned from a password reset request.
	 *
	 * @since 2.1.0
	 * @since 4.4.0 Added the `$errors` parameter.
	 * @since 5.4.0 Added the `$user_data` parameter.
	 *
	 * @param WP_Error      $errors    A WP_Error object containing any errors generated
	 *                                 by using invalid credentials.
	 * @param WP_User|false $user_data WP_User object if found, false if the user does not exist.
	 */
	do_action( 'lostpassword_post', $errors, $user_data );

	/**
	 * Filters the errors encountered on a password reset request.
	 *
	 * The filtered WP_Error object may, for example, contain errors for an invalid
	 * username or email address. A WP_Error object should always be returned,
	 * but may or may not contain errors.
	 *
	 * If any errors are present in $errors, this will abort the password reset request.
	 *
	 * @since 5.5.0
	 *
	 * @param WP_Error      $errors    A WP_Error object containing any errors generated
	 *                                 by using invalid credentials.
	 * @param WP_User|false $user_data WP_User object if found, false if the user does not exist.
	 */
	$errors = apply_filters( 'lostpassword_errors', $errors, $user_data );

	if ( $errors->has_errors() ) {
		return $errors;
	}

	if ( ! $user_data ) {
		$errors->add( 'invalidcombo', __( '<strong>Error</strong>: There is no account with that username or email address.' ) );
		return $errors;
	}

	// Redefining user_login ensures we return the right case in the email.
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;
	$key        = get_password_reset_key( $user_data );

	if ( is_wp_error( $key ) ) {
		return $key;
	}

	// Localize password reset message content for user.
	$locale = get_user_locale( $user_data );

	$switched_locale = switch_to_locale( $locale );

	if ( is_multisite() ) {
		$site_name = get_network()->site_name;
	} else {
		/*
		 * The blogname option is escaped with esc_html on the way into the database
		 * in sanitize_option. We want to reverse this for the plain text arena of emails.
		 */
		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	$message = __( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
	/* translators: %s: Site name. */
	$message .= sprintf( __( 'Site Name: %s' ), $site_name ) . "\r\n\r\n";
	/* translators: %s: User login. */
	$message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
	$message .= __( 'If this was a mistake, ignore this email and nothing will happen.' ) . "\r\n\r\n";
	$message .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";
	$message .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . '&wp_lang=' . $locale . "\r\n\r\n";

	if ( ! is_user_logged_in() ) {
		$requester_ip = $_SERVER['REMOTE_ADDR'];
		if ( $requester_ip ) {
			$message .= sprintf(
				/* translators: %s: IP address of password reset requester. */
				__( 'This password reset request originated from the IP address %s.' ),
				$requester_ip
			) . "\r\n";
		}
	}

	/* translators: Password reset notification email subject. %s: Site title. */
	$title = sprintf( __( '[%s] Password Reset' ), $site_name );

	/**
	 * Filters the subject of the password reset email.
	 *
	 * @since 2.8.0
	 * @since 4.4.0 Added the `$user_login` and `$user_data` parameters.
	 *
	 * @param string  $title      Email subject.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 */
	$title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );

	/**
	 * Filters the message body of the password reset mail.
	 *
	 * If the filtered message is empty, the password reset email will not be sent.
	 *
	 * @since 2.8.0
	 * @since 4.1.0 Added `$user_login` and `$user_data` parameters.
	 *
	 * @param string  $message    Email message.
	 * @param string  $key        The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 */
	$message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) ) {
		$errors->add(
			'retrieve_password_email_failure',
			sprintf(
				/* translators: %s: Documentation URL. */
				__( '<strong>Error</strong>: The email could not be sent. Your site may not be correctly configured to send emails. <a href="%s">Get support for resetting your password</a>.' ),
				esc_url( __( 'https://wordpress.org/support/article/resetting-your-password/' ) )
			)
		);
		return $errors;
	}

	return true;
}

add_action('init','book_oz_print_pdf');

function book_oz_print_pdf() {
	if (isset($_GET['oz_print_appointment']) && $_GET['oz_print_appointment']) {
	$html = '';
	$id = 0;
	$hash = sanitize_text_field($_GET['oz_print_appointment']);
	$post = get_posts([
		'post_type' => 'clients',
		'meta_key' => 'oz_uniqid',
		'meta_value' => $hash,
		'posts_per_page' => 1,
	]);
	$mess = get_option('oz_finalMessage');
	if ($mess && count($post)) {
		$id = $post[0]->ID;
		$app = new Appointment();
		$app = $app->getById($id);
		$replacer = new OZ_ShortcodeReplacer();
		$replacer->init($id, $app);
		$mess = $replacer->replace($mess);
		$html = wp_kses_post($mess);
	}
		ob_start();
	?>
	<html>
	<head>
		<title><?php _e('Appointment', 'book-appointment-online'); ?> <?php echo $id; ?></title>
	</head>
	<body>
		<div style="font-family:DejaVu Sans !important;">
			<?php echo apply_filters('book_oz_printPDF_html', $html, $id); ?>
		</div>
	</body>
	</html>
	<?php
	$html = ob_get_clean();
 	$options = new Options();
	if (!is_writable(ini_get('open_basedir'))) {
	$options->set('temp_dir', OZAPP_PATH.'/tmp');
	$options->set('logOutputFile', OZAPP_PATH.'tmp/log.htm');
	$options->set('isRemoteEnabled', TRUE);
	}
	$dompdf = new Dompdf($options);
	$dompdf->loadHtml($html);
	$dompdf->setPaper('A4', 'portrait');
	$dompdf->render(); 
	$dompdf->stream('presentation',array('Attachment' => 0));
	exit;
	}
}

add_action('init', 'book_oz_generate_ics');
function book_oz_generate_ics() {
	if (isset($_GET['ics']) && $_GET['ics'] == 'oz_ics') {
		$id = 0;
		$hash = sanitize_text_field($_GET['id']);
		$post = get_posts([
			'post_type' => 'clients',
			'meta_key' => 'oz_uniqid',
			'meta_value' => $hash,
			'posts_per_page' => 1,
		]);
		if (count($post)) {
			$id = $post[0]->ID;
		}
		header('Content-type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$id.'.ics"');
		$app = new Appointment();
		$app = $app->getById($id);
		if (!is_wp_error($app)) :
		$ap = $app->toArrayREST();
		$start = $end = '';
		$date = DateTime::createFromFormat('Y-m-d\TH:i:sP', $ap['start']);
		$dateEnd = DateTime::createFromFormat('Y-m-d\TH:i:sP', $ap['end']);
		
		if ($date) {
		$date->setTimeZone(new DateTimeZone('UTC'));
		$start = $date->format('Ymd\THis\Z');
		}
		
		if ($dateEnd) {
		$dateEnd->setTimeZone(new DateTimeZone('UTC'));
		$end = $dateEnd->format('Ymd\THis\Z');
		}
		
		$summary = '';
		$description = $ap['employee']['title'];
		if ($ap['services']['found']) {
			$serv = array_column($ap['services']['list'], 'title');
			$summary = implode(', ', $serv);
		}
		?>
BEGIN:VCALENDAR<?php echo "\n"; ?>
VERSION:2.0<?php echo "\n"; ?>
BEGIN:VEVENT
URL:<?php echo site_url(); ?><?php echo "\n"; ?>
DTSTART:<?php echo $start; ?><?php echo "\n"; ?>
DTEND:<?php echo $end; ?><?php echo "\n"; ?>
SUMMARY:<?php echo $summary; ?><?php echo "\n"; ?>
DESCRIPTION:<?php echo $description; ?><?php echo "\n"; ?>
LOCATION:<?php echo "\n"; ?>
END:VEVENT<?php echo "\n"; ?>
END:VCALENDAR<?php echo "\n"; ?>
		<?php
		endif;
	exit;	
	}
}

add_filter('book_oz_Send_status', 'book_oz_finalMessage', 10,2);

function book_oz_finalMessage($res, $id) {
	$app = new Appointment();
	$app = $app->getById($id);
	
	if (!is_wp_error($app)) {
		$res['uniqid'] = $app->get('oz_uniqid');
	}

	$res['appointment'] = !is_wp_error($app) ? $app->toArrayREST() : [];
	
	$mess = get_option('oz_finalMessage');
	if ($mess) {
		$replacer = new OZ_ShortcodeReplacer();
		$replacer->init($id, $app);
		$mess = $replacer->replace($mess);
		$res['finalMessage'] = wp_kses_post($mess);
	}
	return $res;
}