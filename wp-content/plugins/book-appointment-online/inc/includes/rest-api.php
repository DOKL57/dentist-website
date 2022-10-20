<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2021 Ozplugin
 * @ver 3.1.0
 */
 
 if ( ! defined( 'ABSPATH' ) ) { exit; }
 
 require_once(OZAPP_PATH.'/inc/rest-api/class.utils.php');
 require_once(OZAPP_PATH.'/inc/rest-api/class.appointments-v1-controller.php');
 require_once(OZAPP_PATH.'/inc/rest-api/class.me-v1-controller.php');
 require_once(OZAPP_PATH.'/inc/rest-api/class.services-v1-controller.php');
 require_once(OZAPP_PATH.'/inc/rest-api/class.employees-v1-controller.php');
 require_once(OZAPP_PATH.'/inc/rest-api/class.devices-v1-controller.php');
 require_once(OZAPP_PATH.'/inc/rest-api/class.clients-v1-controller.php');
 
 class Oz_REST_API {
	 
	 protected $namespace = 'oz/v1';
	 

	/**
	 *  @brief Hook into WordPress ready to init the REST API as needed.
	 *  
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
		if (apply_filters('book_oz_REST_enableTemplate', true)) {
			$this->enableRESTTemplate();
			add_action('book_oz_add_fields_to_post', [$this,'saveCustomerID']);
		}
		if (apply_filters('book_oz_REST_enablePush', true)) {
			$this->registerDevicesPostType();
			$this->deleteDeviceAction();
			$this->addPushNotify();
		}
		
		$this->stopWooRedirection();
	}

	/**
	 *  @brief Register REST API routes.
	 *  
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function register_rest_routes() {
			foreach ( $this->get_controllers() as $controller_name => $controller_class ) {
				$this->controllers[ $this->namespace ][ $controller_name ] = new $controller_class();
				$this->controllers[ $this->namespace ][ $controller_name ]->register_routes($this->namespace, $controller_name);
			}
	}
	 
	/**
	 *  @brief List of controllers
	 *  
	 *  @return array
	 *  
	 *  @details 3.0.3
	 */
	protected function get_controllers() {
		return [
			'appointments' => 'Oz_REST_Appointments_Controller',
			'me' => 'Oz_REST_Me_Controller',
			'services' => 'Oz_REST_Services_Controller',
			'employees' => 'Oz_REST_Employees_Controller',
			'devices' => 'Oz_REST_Devices_Controller',
			'clients' => 'Oz_REST_Clients_Controller',
		];
	}
	
	/**
	 *  @brief Enable template for Book tab in mobile App
	 *  
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function enableRESTTemplate() {
		if (strpos($_SERVER['REQUEST_URI'], '/oz-rest-api-form/') !== false) {
		$load = include_once(plugin_dir_path(dirname( __FILE__)).'/templates/single-rest-api.php');
		 if ($load) {
			exit;
		 }
		}
	}
	
	/**
	 *  @brief Register Push hook
	 *  
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function addPushNotify() {
		add_action('book_oz_send_ok', [$this,'send_push']);
	}
	
	/**
	 *  @brief Register custom post type for saving mobile App devices for push notifications
	 *  
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function registerDevicesPostType() {
		register_post_type( 'oz_app_devices',
				[
					'public' => false,
					'show_in_menu' => false,
					'has_archive' => false,
					'exclude_from_search' => true, 
					'publicly_queryable'  => false,
					'map_meta_cap'        => true,
					'capability_type'     => array('oz_app_device','oz_app_devices'),
				]
			);			
	}
	
	/**
	 *  @brief Register hook on logout
	 *  
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function deleteDeviceAction() {
			add_action('wp_delete_application_password', [$this, 'deleteDevice']);
	}
	
	/**
	 *  @brief Delete device in mobile App when logout
	 *  
	 *  @param [in] $userID User id
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function deleteDevice($userID) {
		$devices = get_posts([
					'post_type'     => 'oz_app_devices',
					'post_per_page' => -1,
					'post_author' => (int) ($userID),
		]);
		if (count($devices)) {
			foreach($devices as $device) {
				wp_delete_post($device->ID);
			}
		}
	}
	
	/**
	 *  @brief Send push to mobile App with Expo
	 *  
	 *  @param [in] $client_ID appointment ID
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function send_push($client_ID) {
		$url = apply_filters('book_oz_REST_PushHost', 'https://exp.host/--/api/v2/push/send');
		$date = get_post_meta($client_ID,'oz_start_date_field_id', true);
		$title = apply_filters('book_oz_REST_PushTitle', __('New booking on ', 'book-appointment-online').$date, $client_ID);
		$body = apply_filters('book_oz_REST_PushBody', __('Click to view details', 'book-appointment-online'), $client_ID);
		$empID = get_post_meta($client_ID, 'oz_personal_field_id', true);
		$devices = get_posts([
					'post_type'     => 'oz_app_devices',
					'post_per_page' => -1,
					'meta_query' => [
					'relation' => 'OR',
						[
						'key' => 'isAdmin',
						'value' => true
						],
						[
						'key' => 'empID',
						'value' => $empID
						]
					]
		]);
		if (count($devices)) {
				$tokens = array_column($devices, 'post_title');
				$message = [];
				foreach($tokens as $token) {
				$message[] =  [
					'to' => $token,
					'sound' => 'default',
					'title' => $title,
					'body' => $body,
					'data' => [
						'id' => $client_ID,
						'date' => date('Y-m-d', strtotime($date))
					]
				];	
				}
				$res = wp_remote_post($url, [
					'headers' => [
					  'Accept' => 'application/json',
					  'Accept-encoding' => 'gzip, deflate',
					  'Content-Type' => 'application/json',
					],
					'timeout'     => 1,
					'body' => json_encode($message),
				]);
							if (!is_wp_error($res)) {
								$answ = json_decode(wp_remote_retrieve_body($res),1);	
							}	
		}
	}
	
	/**
	 *  @brief Register actions for Woocommerce redirections
	 *  
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function stopWooRedirection() {
		if (apply_filters('book_oz_woo_redirect_customer', true) === false) return;
		add_filter('template_redirect', [$this, 'stopWooRedirectCustomer'] );
		add_filter( 'woocommerce_prevent_admin_access', [$this, 'wpDashboardForCustomer'], 20, 1 );
	}
	
	/**
	 *  @brief Stop redirection to Woocommerce user area if user role is customer
	 *  
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function stopWooRedirectCustomer() {
		if( function_exists('is_account_page') && is_account_page() && current_user_can('oz_customer') )
			wp_redirect( get_edit_profile_url( get_current_user_id() ) );
	}
	
	/**
	 *  @brief Show dashboard for user role oz_customer
	 *  
	 *  @param [in] $redirect if true then dashboard unavailable
	 *  @return bool
	 *  
	 *  @details 3.0.3
	 */
	public function wpDashboardForCustomer($redirect) {
		if (current_user_can('oz_customer')) return false;
		return $redirect;
	}
	
	/**
	 *  @brief Save Customer User ID if user book an appointment in mobile App
	 *  
	 *  @param [in] $app_id appointment id
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function saveCustomerID($app_id) {
			if (isset($_POST['oz_user_id']) && $_POST['oz_user_id'] && isset($_POST['clientEmail'])) {
				$user = get_user_by('id', (int)($_POST['oz_user_id']));
				if ($user && $user->user_email == $_POST['clientEmail'] ) {
					update_post_meta($app_id, 'oz_user_id', $user->ID);
				}
			}
	}
	 
 }
 
add_action( 'init', 'oz_load_rest_api' );

function oz_load_rest_api() {
	if (apply_filters('book_oz_REST_enable', true)) {
		$rest = new Oz_REST_API();
		$rest->init();
	}
}