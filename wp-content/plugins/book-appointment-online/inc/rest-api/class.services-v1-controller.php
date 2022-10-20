<?php

use Ozplugin\Ajax;

class Oz_REST_Services_Controller extends Oz_Utils {
	
	/**
	 *  @brief Register the routes for services
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
			'permission_callback' => [$this,'admin__employee__customer_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint, $args);

		$args = [
			'methods'  => [WP_REST_Server::READABLE, WP_REST_Server::CREATABLE],
			'callback' => [$this, 'get_item'],
			'permission_callback' => [$this,'admin__employee__customer_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint.'/(?P<id>\d+)', $args);		
	}
	
	
	/**
	 *  @brief Get service by ID
	 *  
	 *  @param [in] $request W
	 *  @return JSON
	 *  
	 *  @details 3.0.3
	 */
	public function get_item($request) {
		$id = $request->get_param( 'id' );
		$id = (int)($id);
		$item = Ozplugin\Ajax::get_services(['post__in' => $id]);
		return rest_ensure_response($item);
	}
	
	/**
	 *  @brief Get services
	 *  
	 *  @param [in] $request WP
	 *  @return JSON
	 *  
	 *  @details 3.0.3
	 */
	public function get_items($request) {
		if (class_exists('Ozplugin\Ajax')) {
			$items = Ozplugin\Ajax::get_services();
			return rest_ensure_response($items);
		}
	}
}