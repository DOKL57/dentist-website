<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2021 Ozplugin
 * @ver 3.1.0
 */

use Ozplugin\Updater;
use Ozplugin\Utils;

if ( ! defined( 'ABSPATH' ) ) { exit; }



//add_filter('http_request_args', 'book_oz_yoomoney_change',10,2);
function book_oz_yoomoney_change($parsed_args, $url) {
	if ($url == 'https://payment.yandex.net/api/v3/payments') {
		$body = json_decode($parsed_args['body'],1);
		$body['confirmation'] = [
			'type' => 'embedded'
		];
		$parsed_args['body'] = json_encode($body);
	}
	return $parsed_args;
}


add_filter('book_custFront_JSOptions', 'book_oz_react_options');

function book_oz_react_options($opts) {
	global $oz_theme;
	$pro = Updater::isPro();
	$main = [];
	$i = 10;
	$main[] = [
		'order' => $i,
		'name' => __('Name', 'book-appointment-online'),
		'type' => 'input',
		'meta' => 'clientName',
		'values' => '',
		'required' => 1,
		'validation' => ['empty'],
		'classes' => []
	];
	
	$oz_polya = get_option( 'oz_polya', ['tel' => ['name' => 1, 'req' => 1], 'email' => ['name' => 1, 'req' => 1]  ] );
	
	// main
	if ($oz_polya) {
		foreach($oz_polya as $key => $field) {
			$i = $i +10;
			if ($key == 'tel' && isset($field['name']) && $field['name']) {
				$validation = ['tel'];
				if (isset($field['req']) && $field['req'])
				$validation[] = 'empty';
				$main[] = [
					'order' => $i,
					'name' => [
						'country' => get_option('oz_custom_tel_country'),
						'placeholder' => get_option('oz_custom_tel_placeholder'),
						'countries' => get_option('oz_tel_country', "")
						],
					'type' => 'tel',
					'meta' => 'clientPhone',
					'values' => '',
					'required' => isset($field['req']) && $field['req'],
					'validation' => $validation,
					'pattern' => '\d*',
					'maxlength' => '25',
					'size' => 40,
					'classes' => ['oz_phone_input'],
					'mask' => ''
				];				
			}
			elseif ($key == 'email' && $field['name']) {
				$validation = ['email'];
				if (isset($field['req']) && $field['req'])
				$validation[] = 'empty';
				$main[] = [
					'order' => $i,
					'name' => __('Email', 'book-appointment-online'),
					'type' => 'input',
					'meta' => 'clientEmail',
					'values' => '',
					'required' => isset($field['req']) && $field['req'],
					'validation' => $validation,
					'pattern' => '',
					'maxlength' => '',
					'size' => 40,
					'classes' => []
				];				
			}
		}
	}
	
	//sms
	if ($pro && get_option('oz_smsIntegration')) {
		$i++;
		$vals = [0,15,30,60,120,240,480,1440];
		$select_names = [
						0 => __('No', 'book-appointment-online'),
						15 => __('15 min before', 'book-appointment-online'),
						30 => __('30 min before', 'book-appointment-online'),
						60 => __('1 hour before', 'book-appointment-online'),
						120 => __('2 hours before', 'book-appointment-online'),
						240 => __('4 hours before', 'book-appointment-online'),
						480 => __('8 hours before', 'book-appointment-online'),
						1440 => __('1 day before', 'book-appointment-online'),			
				];
				$main[] = [
					'order' => ($i*5) + 100,
					'name' => __('Remind at SMS', 'book-appointment-online'),
					'type' => 'select',
					'meta' => 'oz_remList',
					'values' => implode("\n",$vals),
					'select_names' => $select_names,
					'required' => isset($field['req']) && $field['req'],
					'validation' => [],
					'classes' => ['remList']
				];		
	}
	
	//register
	if ($pro && get_option('oz_customer_register') && !is_user_logged_in() && $oz_polya && isset($oz_polya['email']) && isset($oz_polya['email']['req']) && $oz_polya['email']['req']) {
				$i++;
		$validation = [];
		if (get_option('oz_customer_register_req'))
		$validation[] = 'empty';
				$main[] = [
					'order' => ($i*5) + 100,
					'name' => '',
					'type' => 'checkbox',
					'meta' => 'register_me',
					'values' => implode("\n",[1]),
					'select_names' => [1 =>  __('Register me', 'book-appointment-online')],
					'required' => get_option('oz_customer_register_req'),
					'validation' => $validation,
					'classes' => []
				];		
	}
	
	//print_r($main);
	
	// custom
	$custom = [];
	if ($pro && get_option('oz_cust_fields')) {
		foreach(get_option('oz_cust_fields') as $field) {
			$validation = [];
			if (isset($field['required']) && $field['required'])
			$validation[] = 'empty';
			$field['order'] = intval($field['order'] + 50); 
			$field['meta'] = 'cf_'.$field['meta']; 
			$custom[] = array_merge($field, [
				'classes' => ['field-'.$field['meta']],
				'required' => isset($field['required']) && $field['required'],
				'validation' => $validation,
			]);
		}
	}
	
	// discount 
	$disc = get_option('oz_discounts');
	if ($pro && isset($disc['enable']) && $disc['enable']) {
		$i++;
		$main[] = [
			'order' => ($i*5) + 100,
			'name' => __('Coupon code', 'book-appointment-online'),
			'type' => 'input',
			'meta' => 'oz_coupon_code',
			'values' => '',
			'required' => 1,
			'validation' => [],
			'classes' => [],
			'additional' => [[
				'type' => 'button',
				'name' =>  __('Apply', 'book-appointment-online'),
				'action' => 'submit_coupon'
			]]
		];
		
	}
	
	$recaptcha = get_option('oz_recaptcha');
	if ($pro && isset($recaptcha['enable']) && $recaptcha['enable'] && isset($recaptcha['sitekey']) && $recaptcha['sitekey']) {
		$i++;
		$main[] = [
			'order' => ($i*5) + 100,
			'name' => __('Recaptcha', 'book-appointment-online'),
			'type' => 'recaptcha',
			'meta' => 'recaptcha',
			'required' => 1,
			'validation' => ['recaptcha'],
			'classes' => ['g-recaptcha'],
			'key' => $recaptcha['sitekey']

		];		
	}
	
	if ($pro && get_option( 'oz_smsotp' )) {
		$i++;
		$main[] = [
			'order' => ($i*5) + 100,
			'name' => __('SMS code', 'book-appointment-online'),
			'type' => 'input',
			'meta' => 'oz_otp_code',
			'values' => '',
			'required' => 1,
			'validation' => [],
			'classes' => [],
			'pattern' => '\d*',
			'maxlength' => '4',
			'additional' => [
				[
				'type' => 'info',
				'text' =>  __('The code is valid for', 'book-appointment-online'),
				'action' => 'otp_code',
				]		
			]
		];	
	}
	
	
	$opts['fields'] = array_merge($main,$custom);
	$opts['employee_link'] = $pro && get_option('oz_employees') && isset(get_option('oz_employees')['page']) && get_option('oz_employees')['page'];
	$opts['currency'] = get_option('oz_default_cur');
	$opts['currency_position'] = get_option('oz_currency_position');
	$opts['debug'] = defined('WP_DEBUG') && WP_DEBUG && current_user_can('administrator');
	$opts['stepsnames'] = [
	'branches' => __('Select branch', 'book-appointment-online'),
	'employees' => __('Select employee', 'book-appointment-online'), 
	'date' => __('Select date', 'book-appointment-online'),
	'time' => __('Select time booking', 'book-appointment-online'), 
	'services_cats' => __('Select category', 'book-appointment-online'),
	'services' => __('Select service', 'book-appointment-online'),
	'recurring' => __('Repeat this appointment', 'book-appointment-online'),
	'form' => __('Contact information', 'book-appointment-online'),
	];
	$opts['onlyregistred'] = $pro && get_option('oz_customer_register_perm') ? is_user_logged_in() : true;
	if (is_user_logged_in() && get_option('oz_user_area')) {
		$user_info = get_userdata(get_current_user_id());
		$opts['user'] = [
			'id' => get_current_user_id(),
			'name' => $user_info->first_name,
			'email' => $user_info->user_email,
			];
	}
	
	if (get_option('oz_payment_method')) {
		$opts['paypal_client_id'] = get_option('oz_paypal_client_id');
	}
	
	$opts['skipOneStep'] = get_option('oz_skip_step_ifOne');
	$opts['steps'] = Utils::getSteps();
	
	$opts['theme'] = $oz_theme ? $oz_theme.'-theme ' : '';
	
	$red_url = (is_front_page()) ? home_url() : get_permalink();	
	$opts['login_url'] = wp_login_url( $red_url ); 
	$http = strpos(site_url(), 'https:') !== false ? 'https://' : 'http://';
	$linkpage = esc_url( $http. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );	
	$opts['logout_url'] = wp_logout_url( $linkpage ); 
	$opts['scrollToTop'] = false;
	$opts['rtl'] = is_rtl();
	if ($pro && is_single()) {
		global $post;
		if ($post->post_type == 'personal')
		$opts['employee_page'] = $post->post_type == 'personal';
	}
	
	$opts['skipemployee'] = !get_option('oz_skip_emp_btn'); // skip employee btn
	$opts['statusLabels'] = [
		'approved' => __('Approved', 'book-appointment-online'),
		'onhold' => __('On hold', 'book-appointment-online'),
		'canceled' => __('Canceled', 'book-appointment-online')
	];
	
	$opts['paymentLabels'] = [
		'locally' => 	__('Locally', 'book-appointment-online'),
		'paypal' => __('PayPal', 'book-appointment-online'),
		'stripe' => __('Stripe', 'book-appointment-online'),
		'yandex' => __('Online Card (Yandex Kassa)', 'book-appointment-online')
	];
	
	$opts['canPrint'] = !empty(get_option('oz_finalMessage'));
	

	array_multisort($opts['fields'], SORT_ASC, SORT_NUMERIC, array_column($opts['fields'], 'order'));
	if ($pro && get_option('oz_multiselect_serv')) {
		$opts['multiservice'] = 1;
	}
	return apply_filters('book_oz_react_options', $opts);
}

add_action('book_oz_before_appointment_form', 'book_oz_colors_styles');

add_action('book_frontJS_translate', 'book_react_translates');

function book_react_translates($oz_lang) {
	$transl = [
	'r1' => __('You will be moved to the payment page in %s seconds. if not, %s click here %s', 'book-appointment-online'),
	'r2' => __('Select employee', 'book-appointment-online'),
	'r3' => __('Select', 'book-appointment-online'),
	'r4' => __('Any employee', 'book-appointment-online'),
	'r5' => __('More', 'book-appointment-online'),
	'r6' => __('This window will be closed. please try again.', 'book-appointment-online'),
	'r7' => __('time (min)', 'book-appointment-online'),
	'r8' => __('price', 'book-appointment-online'),
	'r9' => __('Cancel', 'book-appointment-online'),
	'r10' => __('Login', 'book-appointment-online'),
	'r11' => __('My appointments', 'book-appointment-online'),
	'r12' => __('You dont have any appointments', 'book-appointment-online'),
	'r13' => __('Log Out', 'book-appointment-online'),
	'r14' => __('Employee not found', 'book-appointment-online'),
	'r15' => __('Employee', 'book-appointment-online'),
	'r16' => __('Date', 'book-appointment-online'),
	'r17' => __('at', 'book-appointment-online'),
	'r18' => __('Service', 'book-appointment-online'),
	'r19' => __('Total', 'book-appointment-online'),
	'r20' => __('Leave your contacts!', 'book-appointment-online'),
	'r21' => __('Book', 'book-appointment-online'),
	'r22' => __('Next', 'book-appointment-online'),
	'r23' => __('Back', 'book-appointment-online'),
	'r24' => __('The payment form will open in a new window. Proceed?', 'book-appointment-online'),
	'r25' => __('Open', 'book-appointment-online'),
	'r26' => __('Close', 'book-appointment-online'),
	'r27' => __('Skip selecting specialist', 'book-appointment-online'),
	'r28' => __('Phone', 'book-appointment-online'),
	'r29' => __('Only registered users can book an appointment', 'book-appointment-online'),
	'r30' => __('Branches not found', 'book-appointment-online'),
	'r31' => __('It seems that the order has been placed, but not confirmed by the site administration. Please contact the site administrator to confirm your order', 'book-appointment-online'),
	'r32' => __('Deposit', 'book-appointment-online'),
	'print' => __('Print', 'book-appointment-online'),
	'gcal' => __('Google Calendar', 'book-appointment-online'),
	'ical' => __('iCal', 'book-appointment-online'),
	];
	return array_merge($oz_lang, $transl);
}