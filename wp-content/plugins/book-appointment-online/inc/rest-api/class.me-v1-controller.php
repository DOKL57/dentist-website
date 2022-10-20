<?php

class Oz_REST_Me_Controller extends Oz_Utils {
	
	private $api_url = '/oz-rest-api-form/';
	
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
			'permission_callback' => [$this, 'admin__employee__customer_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint, $args);	
		$args = [
			'methods'  => [WP_REST_Server::READABLE],
			'callback' => [$this, 'get_form_url'],
			'permission_callback' => [$this, 'admin__employee__customer_permissions']
		];
		register_rest_route( $namespace, '/' . $endpoint.'/form', $args);	
	}
	
	
	/**
	 *  @brief Return booking form URL for Book tab in mobile App
	 *  
	 *  @return JSON
	 *  
	 *  @details 3.0.3
	 */
	public function get_form_url() {
		$user = wp_get_current_user();
		if ($user && in_array('oz_employee',$user->roles)) {
			
		}
		return rest_ensure_response(['success' => true, 'url' => $this->api_url]);
	}
	
	/**
	 *  @brief Get info about logged in User
	 *  
	 *  @param [in] $request WP_REST_Request
	 *  @return JSON
	 *  
	 *  @details 3.0.3
	 */
	public function get_items($request) {
			$user = wp_get_current_user();
			if ($user) {
			$data = [
				'id' => $user->ID,
				'name' => $user->display_name,
				'roles' => $user->roles,
				'email' => $user->user_email,
			];
			
			if (apply_filters('book_oz_rest_show_book_tab', true)) {
				$data['book_url'] = site_url().$this->api_url;
			}
			
			if (in_array('oz_employee', $user->roles)) {
				$args = array(
					'author'        =>  $user->ID,
					'orderby'       =>  'post_date',
					'order'         =>  'ASC',
					'posts_per_page' => 1,
					'post_type' => 'personal'
					);
				$emp_info = get_posts($args);
				if (count($emp_info)) {
					$data['schedule'] = get_post_meta($emp_info[0]->ID, 'oz_raspis', true);
					$data['thumbnail'] = get_the_post_thumbnail_url($emp_info[0]->ID);
					$data['empID'] = $emp_info[0]->ID;
				}
			}
			return rest_ensure_response($data);	
			}
			else 
			return rest_ensure_response(['code' => 'invalid_user', 'message' => 'Not Auth']);
	}
}