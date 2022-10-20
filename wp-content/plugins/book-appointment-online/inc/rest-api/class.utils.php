<?php

class Oz_Utils {
/**
	 *  @brief Return true if current user is admin, employee, customer
	 *  
	 *  @return bool
	 *  
	 *  @details 3.0.5
	 */
	public function admin__employee__customer_permissions($request) {
		return 	current_user_can('administrator') ||
				(current_user_can('oz_employee') && apply_filters('book_oz_emp_can_rest', true, $request)) || 
				current_user_can('oz_customer') ||
				book_oz_user_can();
	}
	
	/**
	 *  @brief Return true if current user is admin, employee
	 *  
	 *  @return bool
	 *  
	 *  @details 3.0.5
	 */
	public function admin__employee_permissions($request) {
		return 	current_user_can('administrator') ||
				(current_user_can('oz_employee') && apply_filters('book_oz_emp_can_rest', true, $request)) || 
				book_oz_user_can();
	}
	
	/**
	 *  @brief Return true if current user is admin
	 *  
	 *  @return bool
	 *  
	 *  @details 3.0.5
	 */
	public function admin_permissions() {
		return current_user_can('administrator');
	}
}