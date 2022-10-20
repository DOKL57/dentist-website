<?php

class Oz_REST_Devices_Controller extends Oz_Utils {
	
	/**
	 *  @brief Register the routes for devices
	 *  
	 *  @param [in] $namespace Route namespace
	 *  @param [in] $endpoint Route endpointe
	 *  @return void
	 *  
	 *  @details 3.0.3
	 */
	public function register_routes($namespace, $endpoint) {

		$args = [
			'methods'  => [WP_REST_Server::READABLE],
			'callback' => [$this, 'get_items'],
			'permission_callback' => [$this, 'admin__permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint, $args);

	$args = [
			'methods'  => [WP_REST_Server::CREATABLE],
			'callback' => [$this, 'add_item'],
			'permission_callback' => [$this, 'admin__employee_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint.'/add', $args);	
	}
	
	/**
	 *  @brief Get all devices for push notifications
	 *  
	 *  @return JSON
	 *  
	 *  @details 3.0.3
	 */
	public function get_items() {
			$res = [];
			$deviceExist = get_posts([
				'post_type'     => 'oz_app_devices',
				'post_per_page' => -1,
			]);
			if (count($deviceExist)) {
				foreach ($deviceExist as $device) {
					$res[] = [
						'token' => $device->post_title,
						'userID' => $device->post_author,
						'isAdmin' => get_post_meta($device->ID, 'isAdmin', true),
						'device_name' => get_post_meta($device->ID, 'device_name', true),
						'empID' => get_post_meta($device->ID, 'empID', true),
						'modified' => $device->post_modified,
					];
				}
			}
			return rest_ensure_response($res);			
	}
	
	/**
	 *  @brief Save device when user logged in in mobile App
	 *  
	 *  @param [in] $request WP_REST_Request
	 *  @return JSON
	 *  
	 *  @details 3.0.3
	 */
	public function add_item($request) {
		$params = $request->get_json_params();
		$user = wp_get_current_user();
		if (!$user) return rest_ensure_response(['code' => 'invalid_user', 'message' => 'User not found']);
		$meta = [];
		if (in_array('administrator', $user->roles)) {
			$meta['isAdmin'] = true;
		}
		elseif (in_array('oz_employee', $user->roles)) {
			$args = array(
				'author'        =>  $user->ID,
				'orderby'       =>  'post_date',
				'order'         =>  'ASC',
				'posts_per_page' => 1,
				'post_type' => 'personal'
				);
			$emp_info = get_posts($args);
			if (count($emp_info)) {
				$meta['empID'] = $emp_info[0]->ID;
			}
		}
		if (isset($params['token']) && $params['token']) {
			$token = sanitize_text_field($params['token']);
			if (isset($params['name']) && $params['name']) {
				$meta['device_name'] = $params['name'];
			}
			$deviceExist = get_posts([
				'title' => $token, 
				'post_type'     => 'oz_app_devices',
				'post_author'   => $user->ID,
			]);
				$args = [
				'post_title'	=> $token,
				'post_type'     => 'oz_app_devices',
				'post_status'   => 'publish',
				'post_author'   => $user->ID,
				'meta_input'    => $meta,
				];
				if (count($deviceExist)) {
					$args['ID'] = $deviceExist[0]->ID;
				}
				$post_id = wp_insert_post($args);
					if( is_wp_error($post_id) ){
						return rest_ensure_response(['code' => 'other_error', 'message' => $post_id->get_error_message()]);
					}
					else {
						return rest_ensure_response($post_id);
					}
			
		}
		return rest_ensure_response(['code' => 'missed_params', 'message' => 'Required params missed']);
	}
}