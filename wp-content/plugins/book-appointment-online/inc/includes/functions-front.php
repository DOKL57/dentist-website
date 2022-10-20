<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (!function_exists('book_oz_in_array_r')) {
function book_oz_in_array_r($needle, $haystack, $strict = false) {
	if (is_array($haystack)) {
		foreach ($haystack as $item) {
			if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && book_oz_in_array_r($needle, $item, $strict))) {
				return true;
			}
		}
	}

    return false;
}
}
function book_oz_enqueue_datepicker() {
if ( defined("SHOW_CT_BUILDER") ) return; // if oxygen builder return
wp_enqueue_script( 'jquery-ui-datepicker' );
}
add_action( 'wp_enqueue_scripts', 'book_oz_enqueue_datepicker' );
add_action('wp_ajax_dayRaspis', 'book_oz_dayRaspis');
add_action('wp_ajax_nopriv_dayRaspis', 'book_oz_dayRaspis');


/* валидатор даты */
function book_oz_validateDate($date, $format = 'd.m.Y') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}



function book_oz_dayRaspis() {
	if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['ids'])) {
		$speci = (is_array(json_decode($_POST['ids']))) ? json_decode($_POST['ids']) : '';
		$speci = apply_filters('book_oz_pers_onSkip',$speci);
		$dates = (isset($_POST['dateText']) && book_oz_validateDate($_POST['dateText'])) ? $_POST['dateText'] : '';
		
		$compare = '=';
		// для timezone
		if (isset($_COOKIE) && isset($_COOKIE['oz_timezone']) && $_COOKIE['oz_timezone'] != 'no' && $_COOKIE['oz_timezone'] != (get_option('gmt_offset')*60)) {
			$dateBefore = date('d.m.Y', strtotime('-1 day', strtotime($dates)));
			$dateAfter = date('d.m.Y', strtotime('+1 day', strtotime($dates)));
			$dates = [$dates,$dateBefore,$dateAfter];
			$compare = 'IN';
		}
		
		$args = array(
		'post_type' => 'clients',
		'post_status' => 'publish',
		'posts_per_page' => -1,
 		'meta_query' => array(
			'relation' => 'AND',
			array(
			'key' => 'oz_start_date_field_id',
			'value' => $dates,
			'compare' => $compare,
			), 
 			array(
			'key' => 'oz_personal_field_id',
			'value' => $speci,
			'compare' => 'IN'
			), 
		) );
		$args = apply_filters('book_oz_wp_query', $args, 'on_day_raspis');															
		$query = new WP_Query( $args );
		$clBr = array();
if ($query) {
	$breakList = array();
	while ( $query->have_posts() ) : $query->the_post();
$timeStart = get_post_meta(get_the_id(),'oz_time_rot',true);
$usl =  get_post_meta(get_the_id(),'oz_uslug_set',true);
$w_time = apply_filters('book_oz_dayRaspis_wTime', get_post_meta($usl,'oz_serv_time',true), $usl);
$buffer = apply_filters('book_oz_sotrudniki_uslbuffer',[0,0],$usl);
	$clBr[] = array(
			  'dayStart' => get_post_meta(get_the_id(),'oz_start_date_field_id',true),
			  'timeStart' => str_replace(' ','',$timeStart),
			  'pers_id' => get_post_meta(get_the_id(),'oz_personal_field_id',true),
			  'w_time' => $w_time,
			  'buffer' => $buffer
			  );
	endwhile;
}

/* 2.0.0 добавляем перерывы как будто это записи в массив*/
	$args = array( 
	'post__in' => $speci, 
	'post_type' => 'personal',
	'posts_per_page' => -1,
	);
	$args = apply_filters('book_oz_get_posts', $args);
	$personals = get_posts( $args );
	$breakList = array();
	$dates = is_array($dates) ? $dates : [$dates];
	foreach ($dates as $date) :
	$day = DateTime::createFromFormat('d.m.Y', $date);
	$day = strtolower('oz_'.$day->format('D'));
		foreach ( $personals as $personal ) : setup_postdata( $personal );
			$id = $personal->ID;
			$breaks = json_decode(get_post_meta($id,'oz_breaklist',true),true);
				if ($breaks) {
						foreach ($breaks as $break) {
							if ($break['day'] == $day) {
							$start = DateTime::createFromFormat('H:i', $break['start'])->format('U');
							$end = DateTime::createFromFormat('H:i', $break['end'])->format('U');
							$w_time = ($end - $start)/60;
							$start = $break['start'];
							$pId = $break['pId'];
								$clBr[] = array(
										  'dayStart' => $date,
										  'timeStart' => "$start",
										  'pers_id' => "$pId",
										  'w_time' => "$w_time",
										  'breaks' => true
										  );
							}
						}
				}
			
		endforeach;
	endforeach;
	wp_reset_postdata();
	
	
	$clBr = json_encode($clBr);
	echo $clBr;
	}
	wp_die();
	
}

function checkSlot(&$persId, $date = 0, $time = 0) {
	if (!$persId) return [];
	$args = array(
	'post_type' => 'clients',
	'post_status' => 'publish',
	'posts_per_page'   => -1,
	'meta_query' => array(
		array(
		'key' => 'oz_start_date_field_id',
		'value' => $date,
		), 
		// array(
		// 'key' => 'oz_time_rot',
		// 'value' => $time,
		// ),  
	) );
	if (isset($_POST['persIds']) && $_POST['persIds'] && get_option('book_oz_skip_personal') == 1) {
		$args['meta_query'][] = array(
		'key' => 'oz_personal_field_id',
		'value' => array_map('intval', $_POST['persIds']),
		'compare' => 'IN'
		);
	}
	else {
		$args['meta_query'][] = array(
		'key' => 'oz_personal_field_id',
		'value' => $persId,
		);		
	}
	
	if (get_option('book_oz_enable_statuses')) {
		$args['meta_query'][] =	array(
			'key' => 'oz_app_status',
			'value'   => array('approved', 'onhold', ''),
			'compare' => 'IN',
			);		
	}
	
	$apps = new WP_Query( $args );
	$ids = array();
	$endCur = strtotime($time);
	while ($apps->have_posts()) {
		$apps->the_post();
		$id = get_the_id();
		$duration = 0;
		$start = strtotime(get_post_meta($id, 'oz_time_rot', true));
		$services = explode(',', get_post_meta($id, 'oz_uslug_set', true));
		foreach ($services as $service) {
			$sum_buf = (int) (get_post_meta($service, 'oz_serv_time', true)) + (int) (get_post_meta($service, 'oz_serv_buffer', true));
			$duration = $duration + (int) ($sum_buf);
		}
		
		$uslugi = (isset($_POST['oz_uslug_set']) && $_POST['oz_uslug_set']) ? sanitize_text_field($_POST['oz_uslug_set']) : '';
		if ($uslugi) {
			$curDuration = 0;
			$curServices = explode(',', $uslugi);
			foreach ($curServices as $service) {
				$sum_buf = (int) (get_post_meta($service, 'oz_serv_time', true)) + (int) (get_post_meta($service, 'oz_serv_buffer', true));
				$curDuration = $curDuration + (int) ($sum_buf);
			}
			$endCur = strtotime($time) +  $curDuration * 60;
		}		
		if ((strtotime($time) >= $start && strtotime($time) <  ($start + 60*$duration)) || ( $endCur > $start && $endCur <  ($start + 60*$duration) ) ) {
			$ids[] = array(
			'persId' => get_post_meta($id, 'oz_personal_field_id', true),
			'dayStart' => $date,
			'timeStart' => get_post_meta($id, 'oz_time_rot', true),
			'w_time' => $duration
			);
			}
	}
	wp_reset_postdata();
	if (!isset($_POST['recurring']) && isset($_POST['persIds']) && $_POST['persIds'] && get_option('book_oz_skip_personal') == 1) {
		if (count(array_unique(array_column($ids,'persId'))) >= count($_POST['persIds'])) {
			return $ids;
		}
		else {
			foreach ($_POST['persIds'] as $pid) {
				if (!in_array($pid,array_unique(array_column($ids,'persId')))) {
					$persId = (int) ($pid);
				}
			}
			return [];
		}
	}
	return $ids;
}


add_action('wp_ajax_do_zapis', 'book_oz_do_zapis');
add_action('wp_ajax_nopriv_do_zapis', 'book_oz_do_zapis');
function book_oz_do_zapis() {
	if (defined('DOING_AJAX') && DOING_AJAX) {
		$persId = (isset($_POST['oz_personal_field_id'])) ? (int) ($_POST['oz_personal_field_id']) : '';
		$date = (book_oz_validateDate($_POST['oz_start_date_field_id'])) ? $_POST['oz_start_date_field_id'] : '';
		$time = (book_oz_validateDate($_POST['oz_time_rot'],'H:i')) ? $_POST['oz_time_rot'] : '';
		if (!$persId) {
		$args3 = array(
		'post_type' => 'personal',
		'post_status' => 'publish',
		'posts_per_page'   => -1,
		'post__in' => (is_array(json_decode($_POST['oz_filials']))) ? json_decode($_POST['oz_filials']) : '' ,
 		'meta_query' => array(
 			array(
			'key' => 'oz_clientsarray',
			'value' => $date.' '.$time,
			'compare' => 'NOT LIKE'
			), 
		) );
		$query3 = new WP_Query( $args3 );
		if ($query3) :
		while ( $query3->have_posts() ) : $query3->the_post();
		$persId =  get_the_id();
		endwhile;
		endif;
			//oz_clientsarray
		}
		
		if (apply_filters('book_oz_additional_check',true)) {
			$hasApp = checkSlot($persId, $date, $time);
			if ($hasApp) {
				echo json_encode(array('error' => true, 'text' => __('Someone has already taken the selected time. Please choose another', 'book-appointment-online'), 'query' => $hasApp));
				wp_die();
			}
		}		
		
		$email = (isset($_POST['clientEmail'])) ? sanitize_email($_POST['clientEmail']) : '';
		$clientName = (isset($_POST['clientName'])) ? sanitize_text_field($_POST['clientName']) : '';
		$date = (isset($_POST['oz_start_date_field_id']) && book_oz_validateDate($_POST['oz_start_date_field_id'])) ? $_POST['oz_start_date_field_id'] : '';
		$time = (isset($_POST['oz_time_rot']) && book_oz_validateDate($_POST['oz_time_rot'],'H:i')) ? $_POST['oz_time_rot'] : '';

		$phone = (isset($_POST['clientPhone'])) ? sanitize_text_field($_POST['clientPhone']) : '';
		$remonsms = (isset($_POST['oz_remList']) && $_POST['oz_remList']) ? (int) ($_POST['oz_remList']) : 0; 
		$uslugi = (isset($_POST['oz_uslug_set']) && $_POST['oz_uslug_set']) ? sanitize_text_field($_POST['oz_uslug_set']) : '';
		$deposit = (isset($_POST['oz_use_deposit']) && $_POST['oz_use_deposit']) ? sanitize_text_field($_POST['oz_use_deposit']) : '';
		if ($deposit) {
			if (strpos($deposit,',') !== false) {
				$deposit = array_map('intval', explode(',',$deposit));
			}
			else {
				$deposit = [(int)($deposit)];
			}
		}
		$postarr = array(
		'post_title' => $clientName,
		'post_type' => 'clients',
		'post_status' => 'publish',
		// 'meta_input' => array(
		// 'oz_start_date_field_id' => $date,
		// 'oz_personal_field_id' =>$persId,
		// 'oz_time_rot' => $time,
		// 'oz_clientPhone' => $phone,
		// 'oz_clientEmail' => $email,
		// 'oz_remList' => $remonsms,
		// 'oz_uslug_set' => $uslugi
		
		// )
		);
		$res = apply_filters('book_oz_before_appointment_insert',array());
		if (!$res && $suc = wp_insert_post( apply_filters('book_oz_pre_appointment_data',$postarr))) {
		// почему то перестало работать meta_input
		update_post_meta($suc,'oz_start_date_field_id', $date);
		update_post_meta($suc,'oz_personal_field_id', $persId);
		update_post_meta($suc,'oz_time_rot', $time);
		update_post_meta($suc,'oz_clientPhone', $phone);
		update_post_meta($suc,'oz_clientEmail', $email);
		update_post_meta($suc,'oz_remList', $remonsms);
		update_post_meta($suc,'oz_uslug_set', $uslugi);
		update_post_meta($suc,'oz_uniqid', hash('sha1', "$suc&$date&$time"));
		if ($deposit) update_post_meta($suc,'oz_use_deposit', $deposit);
		//update_post_meta($suc,'oz_user_id', $user_id);
		// custom fields update
		do_action('book_oz_add_client_data', $suc);
		$uslTime = apply_filters('book_oz_change_Wtime_afterAdd', get_post_meta($uslugi,'oz_serv_time',true),$uslugi);
		$buffer = apply_filters('book_oz_sotrudniki_uslbuffer',[0,0],$uslugi);
		$asr = array(array(
		'start' => $date.' '.$time,
		'w_time' => ($uslugi) ? $uslTime : '',
		'buffer' => $buffer,
		));
		$der = json_decode(get_post_meta($persId ,'oz_clientsarray', true),true);
		$der = ($der) ? $der : array();
		$cer = array_merge($asr, $der);
		$cer = json_encode($cer,JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT);
		update_post_meta($persId,'oz_clientsarray', $cer);
		do_action('book_oz_add_fields_to_post',$suc,$_POST);
		$app_data = [
			'post' => [ 
				'post_title' => $clientName,
				'post_type' => 'clients',
				'post_status' => 'publish',	
			],
			'meta' => [
				'oz_start_date_field_id' => $date,
				'oz_personal_field_id' => $persId,
				'oz_time_rot' => $time,
				'oz_clientPhone' => $phone,
				'oz_clientEmail' => $email,
				'oz_remList' => $remonsms,
				'oz_uslug_set' => $uslugi,			
			]
		];
		do_action('book_oz_send_ok',$suc, $app_data);
		$response = apply_filters('book_oz_Send_status',array('id' => $suc),$suc,$_POST);
		echo json_encode($response);
		}
		else {
			 echo json_encode($res);
		}
	}
	wp_die();
}	