<?php

use Ozplugin\Ajax;

class Oz_REST_Employees_Controller extends Oz_Utils {
	
	
	/**
	 *  @brief Register the routes for employees
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
			'permission_callback' => [$this, 'admin__employee_permissions']//[$this, 'employee_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint, $args);
	
	}
	
	/**
	 *  @brief Return true if current user is admin
	 *  
	 *  @return bool
	 *  
	 *  @details 3.0.3
	 */
	public function admin_permissions() {
		return current_user_can('administrator');
	}
	
	/**
	 *  @brief Get employees
	 *  
	 *  @param [in] $request WP_REST_Request
	 *  @return JSON
	 *  
	 *  @details 3.0.3
	 */
	public function get_items($request) {
		$params = $request->get_json_params();
		$apps = Ozplugin\Ajax::get_employees();
		return rest_ensure_response($apps);
	}
}