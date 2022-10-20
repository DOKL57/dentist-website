<?php

use Ozplugin\Ajax;
use Ozplugin\Appointment as oz_Appointment;

class Oz_REST_Appointments_Controller extends Oz_Utils {
	
	private $by_month_start = null;
	private $by_month_end = null;
	
	/**
	 *  @brief Register the routes for appointments
	 *  
	 *  @param [in] $namespace Route namespace
	 *  @param [in] $endpoint Route endpointe
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function register_routes($namespace, $endpoint) {
		$args = [
			'methods'  => [WP_REST_Server::READABLE, WP_REST_Server::CREATABLE],
			'callback' => [$this, 'get_items'],
			'permission_callback' => [$this, 'admin__employee__customer_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint, $args);

		$args = [
			'methods'  => [WP_REST_Server::READABLE, WP_REST_Server::CREATABLE],
			'callback' => [$this, 'get_item'],
			'permission_callback' => [$this, 'admin__employee__customer_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint.'/(?P<id>\d+)', $args);

		$args = [
			'methods'  => [WP_REST_Server::CREATABLE],
			'callback' => [$this, 'add_item'],
			'permission_callback' => [$this, 'admin__employee_permissions']
		];
		register_rest_route( $namespace, '/'.$endpoint.'/add', $args);	

		$args = [
			'methods'  => [WP_REST_Server::CREATABLE],
			'callback' => [$this, 'reschedule'],
			'permission_callback' => [$this, 'admin__employee_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint.'/reschedule/(?P<id>\d+)', $args);		
		
		$args = [
			'methods'  => [WP_REST_Server::DELETABLE],
			'callback' => [$this, 'delete_item'],
			'permission_callback' => [$this, 'admin__employee_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint.'/delete/(?P<id>\d+)', $args);
	}
	
	/**
	 *  @brief Get appointment by ID
	 *  
	 *  @param [in] $request WP_REST_Request
	 *  @return JSON with appointment data
	 *  
	 *  @details 3.0.3
	 */
	public function get_item($request) {
		$id = $request->get_param( 'id' );
		$id = (int)($id);
		$app = new oz_Appointment();
		$app->getByID($id);
		return rest_ensure_response($app->toArrayREST());
	}
	
	/**
	 *  @brief Filter query by appointment month
	 *  
	 *  @param [in] $join SQL Join query
	 *  @param [in] $wp_query WP_QUERY
	 *  @return string
	 *  
	 *  @details 3.0.3
	 */
	public function by_month ( $join, $wp_query ) {
		global $wpdb,$pagenow;
		if($this->by_month_start) {
		$month_start = esc_sql($this->by_month_start);
		$month_end = esc_sql($this->by_month_end);
		$join .= " INNER JOIN {$wpdb->postmeta} date ON ".
			"date.post_id = {$wpdb->posts}.ID ".
		   "and date.meta_key = 'oz_start_date_field_id' and STR_TO_DATE(date.meta_value,'%d.%m.%Y')";
		$join .= " BETWEEN '{$month_start}' and '{$month_end}' ";
		}
		return $join;
	}
	
	/**
	 *  @brief Get appointments
	 *  
	 *  @param [in] $request WP_REST_Request
	 *  @return JSON with apps
	 *  
	 *  @details 3.0.3
	 */
	public function get_items($request) {
		$params = $request->get_json_params();
		if (class_exists('Ozplugin\Ajax')) {
				$apps = [];
				$posts = [];
				$isAdminOrManager = current_user_can('administrator') || book_oz_user_can();
				$isEmployee = current_user_can('oz_employee');
				$isCustomer = current_user_can('oz_customer');
			if ($isEmployee) {
				$args = [
				'author'        =>  wp_get_current_user()->ID,
				'orderby'       =>  'post_date',
				'order'         =>  'ASC',
				'post_type'		=> 'personal',
				'posts_per_page' => 1
				];
				$posts = get_posts($args);
			}
			if (count($posts) || $isAdminOrManager || $isCustomer) {
				$args1 = [
					'post_type'		=> 'clients',
					'posts_per_page' => 100,
					'orderby'       =>  'ID',
					'order'         =>  'DESC',
					];
				if ($isEmployee) {
				$args1 = array_merge($args1,[
					'meta_key' => 'oz_personal_field_id',
					'meta_value' => $posts[0]->ID
					]);					
				}
				elseif($isCustomer) {
				$args1 = array_merge($args1,[
					'meta_key' => 'oz_user_id',
					'meta_value' => wp_get_current_user()->ID
					]);						
				}
		if (isset($params['month_start']) && $params['month_start'] && isset($params['month_start']) && $params['month_end']) {
			$args1['posts_per_page'] = -1;
			$this->by_month_start = $params['month_start'];
			$this->by_month_end = $params['month_end'];
			add_filter( 'posts_join' , [$this, 'by_month'],10,2 );
		}					
			$apps1 = new WP_Query($args1);
			if ($this->by_month_start) {
					$this->by_month_start = null;
					$this->by_month_end = null;
					remove_filter( 'posts_join' , [$this, 'by_month'],10,2 );
			}
			if ($apps1->have_posts()) {
				while ( $apps1->have_posts() ) { $apps1->the_post();
					$id = get_the_id();
					$date = DateTime::createFromFormat('d.m.Y H:i P', get_post_meta($id, 'oz_start_date_field_id', true).' '.get_post_meta($id, 'oz_time_rot', true).''.wp_timezone_string());
					$emp_id = (int) (get_post_meta($id, 'oz_personal_field_id', true));
					$apps[] = [
						'id' => $id,
						'title' => apply_filters('book_oz_REST_appTitle', get_the_title(), $id),
						'start' => $date->format('c'),
						'end' => $date->add(new DateInterval('PT15M'))->format('c'),
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
			wp_reset_postdata();
			}
			return rest_ensure_response($apps);
		}
	}
	
	/**
	 *  @brief Add appointment
	 *  
	 *  @param [in] $request WP_REST_Request
	 *  @return JSON with appointment data
	 *  
	 *  @details 3.0.5
	 */
	public function add_item($request) {
		$app_data = $request->get_json_params();
		$app = new oz_Appointment();
		$new_app = $app->addNew($app_data);
		if (!is_wp_error($new_app)) {
			$ans = $new_app;
		}
		else {
			$ans = [
				'code' => $new_app->get_error_code(),
				'message' => $new_app->get_error_message(),
				];
		}
		return rest_ensure_response($ans);
	}
	
	/**
	 *  @brief Reschedule event
	 *  
	 *  @param [in] $request array with params id - appointment id, start - appointment start ISO
	 *  @return this appointment
	 *  
	 *  @details 3.0.5
	 */
	public function reschedule($request) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		if (isset($data['day']) && isset($data['time'])) {
			$day = book_oz_validateDate($data['day']) ? $data['day'] : '';
			$time = book_oz_validateDate($data['time'], 'H:i') ? $data['time'] : '';
			if ($day && $time) {
				$app = new oz_Appointment();
				$new_app = $app->reschedule([
					'id' => $id,
					'day' => $day,
					'time' => $time,
				]);
				return rest_ensure_response($new_app);
			}
		}
		return rest_ensure_response([$data, $id]);
	}
	
	/**
	 *  @brief Delete appointment
	 *  
	 *  @param [in] $request WP_REST_Request
	 *  @return JSON with appointment data
	 *  
	 *  @details 3.0.5
	 */
	public function delete_item($request) {
		$id = $request->get_param( 'id' );
		$emp_id = (int) (get_post_meta($id, 'oz_personal_field_id', true));
		$ans = wp_trash_post($id);
		book_oz_update_spisok_klientov_func($emp_id);
		return rest_ensure_response($ans);
	}
}