<?php

class Oz_REST_Clients_Controller extends Oz_Utils {
	
	
	/**
	 *  @brief Register the routes for clients
	 *  
	 *  @param [in] $namespace Route namespace
	 *  @param [in] $endpoint Route endpointe
	 *  @return void
	 *  
	 *  @details 3.0.5
	 */
	public function register_routes($namespace, $endpoint) {
		$args = [
			'methods'  => [WP_REST_Server::READABLE, WP_REST_Server::CREATABLE],
			'callback' => [$this, 'get_items'],
			'permission_callback' => [$this, 'admin__employee_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint, $args);
	}
	
	
	/**
	 *  @brief Get clients
	 *  
	 *  @param [in] $request WP_REST_Request
	 *  @return JSON with apps
	 *  
	 *  @details 3.0.5
	 */
	public function get_items($request) {
		$params = $request->get_json_params();
		$id = get_current_user_id();
		$args = [
			'role' => 'oz_customer',
		];
		if (current_user_can('oz_employee')) {
			$ids = get_user_meta($id, 'oz_clients', true);
			$args['include'] = $ids;
		}
		$clients = get_users(apply_filters('book_oz_get_clients', $args, $id));
		if (count($clients)) {
			$cli = [];
			foreach ($clients as $client) :
				$cli[] = [
				'id' =>  $client->ID,
				'name' =>  $client->display_name,
				'email' =>  $client->user_email,
				'phone' =>  get_user_meta($client->ID, 'oz_phone', true),
				];
			endforeach;
			return rest_ensure_response($cli);
		}
		return rest_ensure_response([]);
	}
}