<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2019 Ozplugin
 * @ver 3.1.0
 */
 
namespace Ozplugin;

use \DateTime;
use \DateInterval;
use \DateTimeZone;
use \WP_Error;
use \Exception;
use Ozplugin\Addons\Email;
use Ozplugin\Ajax as oz_AJAX;

 
 class Appointment {
	 
	 // appointment not formatted
	 private $appointment = [
		'ID' => 0,
		'clientName' => '', //string
		'oz_clientEmail' => '', //string
		'oz_clientPhone' => '', //string
		'oz_start_date_field_id' => '', //string
		'oz_time_rot' => '', //string
		'oz_uslug_set' => [], // array
		'oz_personal_field_id' => 0, //int
		'oz_order_sum' => 0, //float
		'oz_remList' => 0, //float
		'oz_payment_method' => '', //string
		'oz_user_id' => 0, //int
		'oz_custom_fields_post' => '', //sanitized array
		'oz_coupon_added' => '',
		'oz_reccuring_parent' => 0, // int
		'oz_remote_id' => 0, // int
		'oz_use_deposit' => [], // sanitized array
		'oz_extra_time' => 0, // int
		'oz_app_status' => '', // string [approved,onhold,canceled]
		'oz_color' => '', // string
		'oz_uniqid' => '' // string
	 ];
	 
	 // appointment json formatted
	 private $appointmentJSON = [
		'ID' => 0,
		'additional' => [
			/*
			[
				'key' => '',
				'value' => '', // or []
				'meta' => ''
			] */ 
			// ...
		],
		'amount' => [
			'total' => 0,
			'currency' => '',
			'paymentMethod' => '',
			'coupon' => '',
		],
		'client' => [
			'id' => 0,
		],
		'color' => '',
		'conference_url' => '',
		'custom_fields' => [
			/*
			[
				'key' => '',
				'value' => '', // or []
				'meta' => ''
			] */ 
			// ...
		],
		'email' => '',
		'employee' => [
			'id' => 0,
			'ttile' => '',
		],
		'end' => '', // ISO DateTime
		'oz_extra_time' => 0,
		'oz_reccuring_parent' => 0,
		'oz_use_deposit' => [],
		'phone' => '',
		'services' => [ // oz_AJAX::get_services response
		/*
			'args' => [],
			'found' => 0,
			'list' => []
		*/
		],
		'start' => '', // ISO DateTime
		'status' => '',
		'title' => '',
	];
	 
	 private $dateTime = null;
	 
	 private $timeZone = null;
	 private $hash = '';
	 
	 /**
	  *  @brief Create appointment object by appointment ID
	  *  
	  *  @param [in] $id appointment ID
	  *  @return class object
	  *  
	  *  @version 3.0.1
	  */
	 public function getByID($id = 0) {
		 try {
		 if (get_post_meta($id, 'oz_timezone',true)) {
			$this->appointment['oz_timezone'] = (int) (get_post_meta($id, 'oz_timezone',true));
			$ctz = (int) (get_post_meta($id, 'oz_timezone',true));
			$minus = $ctz < 0 ? '-' : '+';
			$tz = new DateTime('today '.abs($ctz).' minutes');
			$this->timeZone = new DateTimeZone($minus.$tz->format('H:i'));
		 }
		 $app = get_post($id);
		 if ($app) {
			 $this->set('ID', $id);
			 $this->set('clientName', $app->post_title);
			 $app_meta = get_post_meta($id);
			 $date = get_post_meta($id, 'oz_start_date_field_id', true);
			 $time = get_post_meta($id, 'oz_time_rot', true);
			 if ($date && $time) {
				 $tz = strpos(wp_timezone_string(), ':') !== false ? 'P' : 'e';
				 $this->dateTime = $this->addDate($date.' '.$time.' '.wp_timezone_string(), 'd.m.Y H:i '.$tz);
				 //unset($app_meta['oz_start_date_field_id']);
				 //unset($app_meta['oz_time_rot']);
			 }
			 if (is_array($app_meta)) {
				foreach($app_meta as $key => $data) {
						$this->set($key, maybe_unserialize($data[0]));
					}
				}
			}
			return $this;
		 }
		 catch(Exception $e) {
			 return new WP_Error( 'oz_appointment', $e->getMessage(), $e->getCode() );
		 }
	 }
	 
	 /**
	  *  @brief Create appointment from HTTP request
	  *  
	  *  @param [in] $request HTTP Request ($_POST)
	  *  @return class object
	  *  
	  *  @version 3.0.1
	  */
	 public function getByRequest($request) {
		 try {
		 if (isset($_COOKIE) && 
			 isset($_COOKIE['oz_timezone']) && 
			 $_COOKIE['oz_timezone'] != 'no' && 
			 $_COOKIE['oz_timezone'] != (get_option('gmt_offset')*60)) {
			 $this->appointment['oz_timezone'] = (int) ($_COOKIE['oz_timezone']);
			$ctz = (int) ($_COOKIE['oz_timezone']);
			$minus = $ctz < 0 ? '-' : '+';
			$tz = new DateTime('today '.abs($ctz).' minutes');
			$this->timeZone = new DateTimeZone($minus.$tz->format('H:i'));
		 }
		 if (is_array($request)) {
			 if (array_key_exists('oz_start_date_field_id', $request) && array_key_exists('oz_time_rot', $request)) {
				 $this->dateTime = $this->addDate($request['oz_start_date_field_id'].' '.$request['oz_time_rot'], 'd.m.Y H:i');
				 //unset($request['oz_start_date_field_id']);
				 //unset($request['oz_time_rot']);
			 }
			 
			 $cust_fields = $this->getCustomFields($request);
			 if (!empty($cust_fields)) {
				 $this->appointment['oz_custom_fields_post'] = $cust_fields;
			 }
			 
			 foreach($request as $key => $data) {
					 $this->set($key, $data);
				 }
			 }
			 
			return $this;
		 }
		 catch(Exception $e) {
			 return new WP_Error( 'oz_appointment', $e->getMessage(), $e->getCode() );
		 }
	}
	 
	 /**
	  *  @brief Add params to appointment object
	  *  
	  *  @param [string] $meta name of param
	  *  @param [diff] $data param values
	  *  @return appointment object
	  *  
	  *  @version 3.0.1
	  */
	 public function set($meta, $data) {
		 switch($meta) {
			 case 'ID' :
			 case 'oz_personal_field_id' :
			 case 'oz_reccuring_parent' :
			 case 'oz_user_id' :
			 case 'oz_extra_time' :
			 $this->appointment[$meta] = (int)($data);
			 break;
			 case 'clientName' :
			 case 'clientPhone' :
			 case 'oz_clientPhone' :
			 case 'oz_coupon_added' :
			 case 'oz_payment_method' :
			 case 'oz_remote_id' :
			 case 'oz_color' :
			 case 'oz_app_status' :
			 case 'oz_uniqid' :
			 $meta = $meta == 'clientPhone' ? 'oz_clientPhone' : $meta;
			 $this->appointment[$meta] = sanitize_text_field($data);
			 break;
			 case 'oz_clientEmail' :
			 case 'clientEmail' :
			 $meta = $meta == 'clientEmail' ? 'oz_clientEmail' : $meta;
			 $this->appointment[$meta] = sanitize_email($data);
			 break;
			 case 'oz_order_sum' :
			 $this->appointment['oz_order_sum'] = (float)($data);
			 break;
			 case 'oz_use_deposit' :
			 if ($data && is_array($data))
			 $this->appointment['oz_use_deposit'] = array_map('intval', $data);
			 break;
			 case 'oz_uslug_set' :
			 if (!is_array($data) && strpos($data, ',') !== false) $data = explode(',', $data);
			 $this->appointment['oz_uslug_set'] = is_array($data) ? array_map('intval', $data) : [(int)($data)];
			 break;
			 case 'oz_start_date_field_id' :
			 $this->appointment['oz_start_date_field_id'] = $this->addDate($data) ? $data : '';
			 break;
			 case 'oz_time_rot' :
			 $this->appointment['oz_time_rot'] = $this->addDate($data,'H:i') ? $data : '';
			 break;
			 case 'oz_custom_fields_post' :
			 $this->appointment['oz_custom_fields_post'] = $this->mapCustomFields($data);
			 break;
			 case 'recurring' :
			 $this->appointment['recurring'] = $this->sanitizeRecurring($data);
			 break;
		 }
		 return $this->appointment;
	 }
	 
	 /**
	  *  @brief Get appointment params by name
	  *  
	  *  @param [string] $meta param name
	  *  @return param value
	  *  
	  *  @version 3.0.1
	  */
	 public function get($meta) {
		 return isset($this->appointment[$meta]) ? $this->appointment[$meta] : false;
	 }
	 
	 /**
	  *  @brief Get appointment timezone
	  *  
	  *  @return DateTimeZone object
	  *  
	  *  @version 3.0.1
	  */
	 public function getTimezone() {
		 return $this->timeZone;
	 }
	 
	 /**
	  *  @brief Create DateTime object from appointment date
	  *  
	  *  @param [string] $date date
	  *  @param [string] $format date format
	  *  @return DateTime object or null
	  *  
	  *  @version 3.0.1
	  */
	 public function addDate($date, $format = 'd.m.Y') {
		 $d = DateTime::createFromFormat($format, $date);
		 return $d && $d->format($format) == $date ? $d : null; 
	 }
	 
	 /**
	  *  @brief Get appointment total
	  *  
	  *  @return int or null
	  *  
	  *  @version 3.0.1
	  */
	 public function getTotal() {
		 return $this->appointment['oz_order_sum'];
	 }
	 
	 /**
	  *  @brief Return custom field value
	  *  
	  *  @param [in] $data Description for $data
	  *  @return hash
	  *  
	  *  @version 3.0.1
	  */
	 public function mapCustomFields($data) {
		 return sanitize_text_field($data);
	 }
	 
	 /**
	  *  @brief Sanitize recurring appointments from request
	  *  
	  *  @param [in] $data array with appointments
	  *  @return array with recurring appointments
	  *  
	  *  @version 3.0.1
	  */
	 public function sanitizeRecurring($data) {
		 $sanitized_data = [];
		 if (is_array($data)) {
			 foreach($data as $dat) {
				 $day = isset($dat['day']) && $this->addDate($dat['day']) ? $dat['day'] : '';
				 $time = isset($dat['time']) && $this->addDate($dat['time'],'H:i') ? $dat['time'] : '';
				 if ($day && $time)
				 $sanitized_data[] = [
					'day' => $day,
					'time' => $time
				 ];
			 }
		 }
		 return $sanitized_data;
	 }
	 
	 /**
	  *  @brief Get custom fields from request
	  *  
	  *  @param [in] $request HTTP request with appointment data
	  *  @return array with custom fields
	  *  
	  *  @version 3.0.1
	  */
	 public function getCustomFields($request) {
		$finded = [];
		$updates = [];
		foreach($request as $key => $ar) {
			$str = explode('_', $key);
			if ($str[0] == 'cf') {
			$finded[$key] = $ar;
			}
		}
		if (!empty($finded)) :
		$opts = get_option('oz_cust_fields');
		foreach ($finded as $key => $field) :
			$val = explode('cf_',$key)[1];
			if (strpos($val,'__') !== false) {
				$val = explode('__',$val)[0];
			}
			$key1 = array_search($val, array_column($opts, 'meta'));
			$meta = $opts[$key1]['meta'];
			$meta_key = $opts[$key1]['name'];
			$meta_value = sanitize_text_field($field);
			$exist =  array_search($meta, array_column($updates, 'meta'));
			if (is_numeric($exist)) {
				$updates[$exist]['value'] = $updates[$exist]['value'].', '.$meta_value; 
			}
			else {
				$updates[] = array(
				'key' => $meta_key,
				'value' => $meta_value,
				'meta' => $meta
				);
			}
		endforeach;

		// проверяю каких произвольных полей не хватает и задаю им пустые значения
		$empty_keys = array_keys(array_diff(array_column($opts, 'meta'), array_column($updates, 'meta')));
		if ($empty_keys) {
			foreach($empty_keys as $empty_key) :
				$updates[] = array(
				'key' => $opts[$empty_key]['name'],
				'value' => '',
				'meta' => $opts[$empty_key]['meta']
				);
			endforeach;
		}
		endif;
		return $updates;
	}
	 
	 /**
	  *  @brief Create hash of appointment (for Woocommerce for example)
	  *  
	  *  @return hash
	  *  
	  *  @version 3.0.1
	  */
	 public function hash() {
		 if ($this->hash) return $this->hash;
		 $id = time();
		 $this->hash = hash('sha1', $id.'&'.$this->get('oz_start_date_field_id').'&'.$this->get('oz_time_rot'));
		 return $this->hash;
	 }
	 
	 /**
	  *  @brief Return appointment as array
	  *  
	  *  @return array
	  *  
	  *  @version 3.0.1
	  */
	 public function toArray() {
		 return $this->appointment['ID'] ? $this->appointment : [];
	 }
	 
	 /**
	  *  @brief Get default Appointment JSON
	  *  
	  *  @return array
	  *  
	  *  @details 3.0.7
	  */
	 public function getEmptyAppJSON() {
		 return $this->appointmentJSON;
	 }
	 
	 /**
	  *  @brief Return appointment as array and pretty for reading
	  *  
	  *  @return array
	  *  
	  *  @version 3.0.3
	  */
	 public function toArrayREST() {
		 if (!$this->appointment['ID']) return [];
		 $app = $this->getEmptyAppJSON();
		 $additional = [];
		 foreach (array_keys($this->appointment) as $key) {
			 switch($key) {
				 case 'oz_clientEmail' :
				 $app['email'] = $this->appointment[$key];
				 break;
				 case 'oz_clientPhone' :
				 $app['phone'] = $this->appointment[$key];
				 break;
				 case 'clientName' :
				 $app['title'] = $this->appointment[$key];
				 break;
				 case 'oz_start_date_field_id' :
				 case 'oz_time_rot' :
				 if (isset($app['start']) && $app['start']) {
					 
				 } 
				 else {
				 $app['start'] = $this->dateTime->format('c');
				 $app['end'] = $this->dateTime->format('c'); 
				 }
				 break;
				 case 'oz_personal_field_id' :
				 $emp = get_post($this->appointment[$key]);
				 if ($emp)
				 $app['employee'] = [
					'id' => $emp->ID,
					'title' => $emp->ID ? get_the_title($emp->ID) : '',
					];
				 break;
				 case 'oz_order_sum' :
				 if ($this->appointment[$key] > 0) {
				 $app['amount'] = [
						'total' => $this->appointment[$key],
						'currency' => get_option('oz_default_cur'),
						'paymentMethod' => isset($this->appointment['oz_payment_method']) ? $this->appointment['oz_payment_method'] : '',
						'coupon' => isset($this->appointment['oz_coupon_added']) ? $this->appointment['oz_coupon_added'] : ''
					];
				 }
				 break;
				 case 'oz_uslug_set' :
				 $services = Ajax::get_services([
					'post__in' => $this->appointment[$key]
				 ]);
				 $app['services'] = $services;
				 break;
				 case 'oz_custom_fields_post' :
				 $fields = get_post_meta($this->appointment['ID'], 'oz_custom_fields_post', true) ?: [];
				 $app['custom_fields'] = $fields;
				 $additional = array_merge( $additional, $fields);
				 break;
				 case 'oz_remList' :
				 $arg = [
						'key' => __('Remind at SMS', 'book-appointment-online'),
						'value' => $this->appointment[$key]
						];
				 $additional[] = $arg;
				 break;
				 case 'oz_user_id' :
				 $app['client'] = [
					'id' => $this->appointment[$key]
				 ];
				 break;
				 case 'oz_remote_id' :
				 $app['conference_url'] = self::getConferenceURL($this->appointment['ID']);
				 break;
				 case 'oz_payment_method' :
				 case 'oz_coupon_added' :
				 break;
				 case 'oz_color' :
				 $app['color'] = $this->appointment[$key];
				 break;
				 case 'oz_app_status' :
				 $app['status'] = $this->appointment[$key];
				 break;
				 default:
				 $app[$key] = $this->appointment[$key];
			 }
		 }
		 
		 if (isset($app['services']) && is_array($app['services'])) {
			 $min = array_column($app['services']['list'], 'w_time');
			 if (count($min)) {
				$end = clone $this->dateTime;
				$app['end'] = $end->add(new DateInterval('PT'.array_sum($min).'M'))->format('c');
			 }
		 }
		  $app['additional'] = $additional;
		 return $app;
	 }
	 
	 /**
	  *  @brief Return services names as array
	  *  
	  *  @return array
	  *  
	  *  @version 3.0.2
	  */
	 public function getServicesNames() {
		 $services = [];
		 $serv = $this->get('oz_uslug_set');
		 if ($serv) {
			 foreach($serv as $serv_id) {
				 $services[] = esc_html( get_the_title($serv_id) );
			 }
		 }
		 return $services;
	 }
	 
	 /**
	  *  @brief Get conference url
	  *  
	  *  @return string URL or empty
	  *  
	  *  @version 3.0.2
	  */
	 public static function getConferenceURL($app_id) {
		 $conf_id = get_post_meta($app_id, 'oz_remote_id', true);
		 $conf_page = get_option('oz_conf_pageid');
		 $url = '';
		 if ($conf_page && $conf_id) {
				$url = parse_url(get_permalink($conf_page));
				$ap = isset($url['query']) && $url['query'] ? '&' : '?';
				$url = get_permalink($conf_page).$ap.'conference_id='.$conf_id;
		 }
		return $url;
	 }
	 
	 /**
	  *  @brief Get appointment start
	  *  
	  *  @return DateTime object
	  *  
	  *  @version 3.0.4
	  */
	 public function getStart() {
		 return $this->dateTime;
	 }
	 
	 /**
	  *  @brief Get appointment end
	  *  
	  *  @return DateTime object
	  *  
	  *  @version 3.0.4
	  */
	 public function getEnd() {
		 $end = clone $this->dateTime;
		 if ($this->get('oz_extra_time')) {
			 $extra = $this->get('oz_extra_time');
			 $end = $end->add(new DateInterval('PT'.$extra.'S'));
		 }
		 if ($this->appointment['oz_uslug_set']) {
			 $services = $this->getServices();
			 if ($services['found']) {
				 $min = array_column($services['list'], 'w_time');
				 if (count($min))
				 return $end->add(new DateInterval('PT'.array_sum($min).'M'))->format('c');
			 }
		 }		 
		 return $end;
	 }
	 
	 /**
	  *  @brief Get services
	  *  
	  *  @return array
	  *  
	  *  @version 3.0.4
	  */
	 public function getServices() {
		if (isset($this->services)) return $this->services;
		$services = oz_AJAX::get_services([
			'post__in' => $this->appointment['oz_uslug_set']
		]);
		$this->services = $services;
		return $this->services;
	 }
	 

	 /**
	  *  @brief Return conferencel log as array
	  *  
	  *  @param [in] $app_id appointment id
	  *  @param [in] $log custom field with log data
	  *  @return log data as array
	  *  
	  *  @details 3.0.4
	  */
	 public function getConferenceLog($app_id, $log = []) {
		$this->getByID($app_id);
		
		if (empty($log)) {
			$remote_id = get_post_meta($app_id, 'oz_remote_id', true);
			$log = $remote_id ? get_post_meta($app_id, 'oz_conf_log_'.sanitize_text_field($remote_id), true) : [];
		}
		
		if (empty($log)) return [];
		
		$params = [
			'id' => (int) ($app_id),
			'type' => 'conferenceLog',
			'start' => $this->getStart()->format('c'),
			'end' => $this->getEnd(),
			'module_id' => 'module-'.wp_generate_uuid4()
		];
		foreach($log as $key => $lo) {
			if ($lo['user_id']) {
				$user_info = get_userdata((int) ($lo['user_id']));
				$log[$key]['user_name'] = $user_info ? $user_info->user_login : __('Unknown', 'book-appointment-online').' '.$lo['unknown_id'];
			}
			else {
				$log[$key]['user_name'] = __('Unknown', 'book-appointment-online').' '.$lo['unknown_id'];
			}
		}
		$params['log'] = json_encode($log);
		return $params;
	 }
	 
	
	/**
	 *  @brief Convert custom fields to format
	 *  
	 *  @param [in] $fields custom fields data
	 *  @return custom fields value
	 *  
	 *  @details 3.0.5
	 */
	public function convert_custom_fields($fields) {
		if (!get_option('oz_cust_fields')) return [];
		$opts = get_option('oz_cust_fields');
		$updates = array();
		foreach ($fields as $key => $field) :
			$metas = array_column($opts, 'meta');
			$key = array_search($field['meta'], $metas);
			$meta = $opts[$key]['meta'];
			$meta_key = $opts[$key]['name'];
			if (is_array($field['value'])) {
				$meta_value = array_map('sanitize_text_field', $field['value']);
			}
			else {
				$meta_value = sanitize_text_field($field['value']);
			}
				$updates[] = array(
				'key' => $meta_key,
				'value' => $meta_value,
				'meta' => $meta
				);
		endforeach;

		// проверяю каких произвольных полей не хватает и задаю им пустые значения
		$empty_keys = array_keys(array_diff(array_column($opts, 'meta'), array_column($updates, 'meta')));
		if ($empty_keys) {
			foreach($empty_keys as $empty_key) :
				$updates[] = array(
				'key' => $opts[$empty_key]['name'],
				'value' => '',
				'meta' => $opts[$empty_key]['meta']
				);
			endforeach;
		}
		return $updates;
	}
	 
	 /**
	  *  @brief Adding new appointment
	  *  
	  *  @param [in] $app_data array with appointment data
	  *  @return log appointment as array
	  *  
	  *  @details 3.0.5
	  */
	 public function addNew($app_data) {
		 $new_id = 0;
		 $rescheduled = false;
			 $err = [
				'code' => 'empty_data',
				'message' => __('Empty data', 'book-appointment-online'),
			 ];
		 if (is_array($app_data)) {
			 $req = ['title', 'start', 'end', 'services'];
			 $keys = array_keys($app_data);
			 $hasReq = true;
			 foreach($req as $key) {
					if (!in_array($key,$keys) || empty($app_data[$key])) {
						//$err['message'] = __('Required params missed', 'book-appointment-online');
						$err['message'] = empty($keys[$key]);
						$hasReq = false;
						break;
					}
			 }
			 
			 if ($hasReq) {
			 $author = isset($app_data['oz_personal_field_id']) ? (int) ($app_data['oz_personal_field_id']) : 0;
			 if ($author) {
				 $author = get_post($author);
				 $author = $author->post_author;
			 }
				 $postarr = [
				 'post_type' => 'clients',
				 'post_status' => 'publish',
				 'post_author' => $author ?: wp_get_current_user()->ID
				 ];
				 foreach($keys as $key) {
					 switch($key) {
						 case 'id':
						 if (strpos($app_data[$key], 'temp-') === false)  {
						 $postarr['ID'] = (int) ($app_data[$key]); 
						 }
						 break;
						 
						 case 'title':
						 $postarr['post_title'] = sanitize_text_field($app_data[$key]);
						 break;
						 
						 case 'email':
						 $postarr['meta_input']['oz_clientEmail'] = sanitize_email($app_data[$key]);
						 break;
						 
						 case 'phone':
						 $postarr['meta_input']['oz_clientPhone'] = sanitize_text_field($app_data[$key]);
						 break;
						 
						 case 'services':
						 $serv = is_array($app_data[$key]) ? implode(',',array_map('intval', $app_data[$key])) : (int)($app_data[$key]);
						 $postarr['meta_input']['oz_uslug_set'] = $serv;
						 break;
						 
						 case 'employee':
						 $postarr['meta_input']['oz_personal_field_id'] = (int) ($app_data[$key]['id']);
						 break;
						 
						 case 'start':
						 $micro = DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $app_data[$key]);
						 $d = $micro ?: DateTime::createFromFormat('Y-m-d\TH:i:sP', $app_data[$key]);
						 if ($d) {
							$postarr['meta_input']['oz_start_date_field_id'] = $d->format('d.m.Y');
							$postarr['meta_input']['oz_time_rot'] = $d->format('H:i'); 
						 }
						 break;
						 
						 case 'status' :
						 $postarr['meta_input']['oz_app_status'] = $app_data[$key];
						 break;
						 
						 case 'custom_fields' :
						 $postarr['meta_input']['oz_custom_fields_post'] = $this->convert_custom_fields($app_data[$key]);
						 break;
						 
						 case 'additional' :
						 if (isset($app_data[$key]['smsreminder']) && $app_data[$key]['smsreminder'] > 0) {
							$postarr['meta_input']['oz_remList'] = (int) ($app_data[$key]['smsreminder']); 
						 }
						 break;
						 
						 case 'color' :
						 $postarr['meta_input']['oz_color'] = sanitize_text_field($app_data[$key]);
						 break;
					 }
				 }
				
				if (apply_filters('book_oz_saving_toLog', true)) { 
					$old_post = false;
					$postarrNew = $postarr;
					$cust = $postarrNew['meta_input'];
					unset($postarrNew['meta_input']);
					$postarrNew = array_merge($postarrNew, $cust);
					if (isset($postarr['ID']) && $postarr['ID']) {
						$old_post = get_post($postarr['ID']);
					}
					Clients::book_oz_save_to_log([], $postarrNew, $old_post);
					remove_filter( 'wp_insert_post_data', 'book_oz_save_to_log');
				}
				
				if (isset($postarr['ID']) && $postarr['ID']) {
					$old_date = get_post_meta($postarr['ID'], 'oz_start_date_field_id', true);
					$old_time = get_post_meta($postarr['ID'], 'oz_time_rot', true);
					$rescheduled = $postarr['meta_input']['oz_start_date_field_id'] != $old_date || $postarr['meta_input']['oz_time_rot'] != $old_time; 
				}
				
				$new_id = wp_insert_post( apply_filters('book_oz_pre_appointment_data',$postarr));
			 }
		 }
		 if (!$new_id) {
			return new WP_Error( $err['code'], $err['message']); 
		 }
		 else {
			 if ($app_data['notify']) {
				 //emails
				 if (in_array('email',$app_data['notify'])) {
					 $email = new Email();
					 $email->toClient($new_id);
					 $email->addReminder();
				 }
				 
				 //sms
				 if (in_array('sms',$app_data['notify'])) {
					 add_filter('option_oz_smsToAdmin', '__return_false', 99);
					 add_filter('book_oz_admin_phone_number', '__return_false');
					 SMS::sendSMS($new_id);
					 remove_filter('pre_option_oz_smsToAdmin', '__return_false');
					 remove_filter('book_oz_admin_phone_number', '__return_false');
				 }
			 }
			 
			 if ($rescheduled) {		 
				 do_action('book_oz_on_appointment_rescheduled', $new_id);
			 }
			 
			 $emp_id = (int) (get_post_meta($new_id, 'oz_personal_field_id', true));
			 book_oz_update_spisok_klientov_func($emp_id);
			 
			 // uniqid for different purposes (for print pdf for example)
			 $hash = $new_id.'&'.get_post_meta($new_id, 'oz_start_date_field_id', true).'&'.get_post_meta($new_id, 'oz_time_rot', true);
			 update_post_meta($new_id,'oz_uniqid', hash('sha1', $hash));
			 
			 do_action('book_oz_on_appointment_created', $new_id);
			 //return $postarr;
				$id = $new_id;
				$date = DateTime::createFromFormat('d.m.Y H:i P', get_post_meta($id, 'oz_start_date_field_id', true).' '.get_post_meta($id, 'oz_time_rot', true).''.wp_timezone_string());
				$app = [
					'id' => $id,
					'title' => apply_filters('book_oz_REST_appTitle', get_the_title($id), $id),
					'start' => $app_data['start'],
					'end' => $app_data['end'],
					'services' => get_post_meta($id, 'oz_uslug_set', true),
					'employee' => [
							'id' => $emp_id,
							'title' => $emp_id ? get_the_title($emp_id) : ''
						],
					'summary' => apply_filters('book_oz_REST_appSummary', 'ID '.$id.' ',$id),
					'color' => get_post_meta($id, 'oz_color', true)
				];
			 return $app;
		 }
	 }
	 
	 /**
	  *  @brief Reschedule appointment
	  *  
	  *  @param [in] $params array 
	  *  @return array with appointment data
	  *  
	  *  @details 3.0.5
	  */
	 public function reschedule($params) {
		$id = $params['id'];
		$day = $params['day'];
		$time = $params['time'];
		update_post_meta($id, 'oz_start_date_field_id', $day); 
		update_post_meta($id, 'oz_time_rot', $time);
		
		$emp_id = (int) (get_post_meta($id, 'oz_personal_field_id', true));
		book_oz_update_spisok_klientov_func($emp_id);
		
		$email = new Email();
		$email->hasChanges = true;
		$email->on_rescheduled(false, $id, 'oz_time_rot');
		
		//sms
		SMS::book_oz_on_rescheduled(false, $id, 'oz_time_rot');
		 
		do_action('book_oz_on_appointment_rescheduled', $id);		
		
		return $this->getShortAppointment($id);
	 }
	 
	 /**
	  *  @brief Get short data about appointment
	  *  
	  *  @param [in] $id appointment id
	  *  @return array
	  *  
	  *  @details 3.0.5
	  */
	 public function getShortAppointment($id) {
		$date = DateTime::createFromFormat('d.m.Y H:i P', get_post_meta($id, 'oz_start_date_field_id', true).' '.get_post_meta($id, 'oz_time_rot', true).''.wp_timezone_string());
		$emp_id = (int) (get_post_meta($id, 'oz_personal_field_id', true));
		 return [
					'id' => $id,
					'title' => apply_filters('book_oz_REST_appTitle', get_the_title($id), $id),
					'start' => $date->format('c'),
					//'end' => $app_data['end'],
					'services' => get_post_meta($id, 'oz_uslug_set', true),
					'employee' => [
							'id' => $emp_id,
							'title' => $emp_id ? get_the_title($emp_id) : ''
						],
					'summary' => apply_filters('book_oz_REST_appSummary', 'ID '.$id.' ',$id),
					'color' => get_post_meta($id, 'oz_color', true)
				];
	 }
	 
 }