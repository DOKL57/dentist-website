<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */

 use Ozplugin\Ajax;


if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_notices', 'book_oz_noAdminNotice' );

/**
 *  Show notice if site has not role 'administrator'
 *  
 *  @return notice
 *  
 *  @version 2.2.0
 */
function book_oz_noAdminNotice() {
	if (get_transient('book_oz_noAdminNotice')) {
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php _e( 'There is no role "Administrator" on this site. Without it, the plugin will not work correctly.', 'book-appointment-online' ); ?></p>
	</div>
	<?php
	delete_transient('book_oz_noAdminNotice');
	}
}

register_activation_hook( OZAPP_FILE, 'on_BAO_activate' );

/**
 *  Add caps for plugin custom post types to admin
 *  
 *  @return void
 *  
 *  @version 2.0.5
 */
function on_BAO_activate() {

	$caps = array(
				'edit',
				'edit_others',
				'publish',
				'read_private',
				'delete',
				'delete_private',
				'delete_published',
				'delete_others',
				'edit_private',
				'edit_published');
	$administrator     = get_role('administrator');
	if ($administrator) {
	foreach ( $caps as $cap ) {
			$plugin_post_types_caps = array('employees','clients','services', 'oz_payments');
			foreach ($plugin_post_types_caps as $plugin_post_types_cap) $administrator->add_cap( "{$cap}_{$plugin_post_types_cap}" );
		}
	}
	else {
		set_transient( 'book_oz_noAdminNotice', true, 5 );
	}
		
	/**
	 *  Remove capabilities for all roles (ex. administrator)
	 *  @version 2.0.7
	 */
		global $wp_roles;
		$all_roles = array_keys($wp_roles->roles);
		if ($all_roles) {
		$all_roles = (is_array($all_roles)) ? $all_roles : array($all_roles);
		foreach($all_roles as $all_role) :
			$role     = get_role($all_role);
			foreach ( $caps as $cap ) {
					$plugin_post_types_caps = array('employees','clients','services', 'oz_payments');
					if ($all_role !== 'administrator') foreach ($plugin_post_types_caps as $plugin_post_types_cap) $role->remove_cap( "{$cap}_{$plugin_post_types_cap}" );
				}
		endforeach;
		}
	
	/**
	 *  Add capabilities for custom roles in plugin options
	 *  @version 2.0.6
	 *  change in 2.1.0 ver ($GLOBALS['wp_roles'])
	 */
	$custom_roles     = get_option('oz_user_role');
		if ($custom_roles) {
		$custom_roles = (is_array($custom_roles)) ? $custom_roles : array($custom_roles);
		foreach($custom_roles as $custom_role) :
			if (isset($GLOBALS['wp_roles']) && $GLOBALS['wp_roles']->is_role( $custom_role )) {
			$role     = get_role($custom_role);
			foreach ( $caps as $cap ) {
					$plugin_post_types_caps = array('employees','clients','services', 'oz_payments');
					foreach ($plugin_post_types_caps as $plugin_post_types_cap) $role->add_cap( "{$cap}_{$plugin_post_types_cap}" );
				}
		}
		endforeach;
		}
		do_action('book_oz_after_plugin_activate');
	}

    function convertPHPToMomentFormat($format)
    {
        $replacements = [
            'd' => 'DD',
            'D' => 'ddd',
            'j' => 'D',
            'l' => 'dddd',
            'N' => 'E',
            'S' => 'o',
            'w' => 'e',
            'z' => 'DDD',
            'W' => 'W',
            'F' => 'MMMM',
            'm' => 'MM',
            'M' => 'MMM',
            'n' => 'M',
            't' => '', // no equivalent
            'L' => '', // no equivalent
            'o' => 'YYYY',
            'Y' => 'YYYY',
            'y' => 'YY',
            'a' => 'a',
            'A' => 'A',
            'B' => '', // no equivalent
            'g' => 'h',
            'G' => 'H',
            'h' => 'hh',
            'H' => 'HH',
            'i' => 'mm',
            's' => 'ss',
            'u' => 'SSS',
            'e' => 'zz', // deprecated since version 1.6.0 of moment.js
            'I' => '', // no equivalent
            'O' => '', // no equivalent
            'P' => '', // no equivalent
            'T' => '', // no equivalent
            'Z' => '', // no equivalent
            'c' => '', // no equivalent
            'r' => '', // no equivalent
            'U' => 'X',
            '\d\e' => '\d\e', // spanish
        ];
        $momentFormat = strtr($format, $replacements);
        return $momentFormat;
    }
    
    function convertPHPToLuxonFormat($format)
    {
        $replacements = [
            'd' => 'dd',
            'D' => 'EEE',
            'j' => 'd',
            'l' => 'EEEE',
            'N' => 'c',
            'S' => '', // no equivalent
            'w' => 'c',
            'z' => 'ooo',
            'W' => 'kk',
            'F' => 'MMMM',
            'm' => 'MM',
            'M' => 'MMM',
            'n' => 'M',
            't' => '', // no equivalent
            'L' => '', // no equivalent
            'o' => 'yyyy',
            'Y' => 'yyyy',
            'y' => 'yy',
            'a' => 'a',
            'A' => 'a',
            'B' => '', // no equivalent
            'g' => 'h',
            'G' => 'H',
            'h' => 'hh',
            'H' => 'HH',
            'i' => 'mm',
            's' => 'ss',
            'u' => 'SSS',
            'e' => 'z', 
            'I' => '', // no equivalent
            'O' => '', // no equivalent
            'P' => '', // no equivalent
            'T' => '', // no equivalent
            'Z' => '', // no equivalent
            'c' => '', // no equivalent
            'r' => '', // no equivalent
            'U' => '', // no equivalent
            '\d\e' => "'de'", // spanish
        ];
        $momentFormat = strtr($format, $replacements);
        return $momentFormat;
    }

    add_action( 'admin_init', 'book_oz_update_spisok_klientov_on_delete_init' );
    function book_oz_update_spisok_klientov_on_delete_init() {
        add_action( 'trashed_post', 'book_oz_update_spisok_klientov_on_delete',10,2 );
        add_action('untrash_post', 'book_oz_update_spisok_klientov_on_delete',10,2);
        add_action('save_post', 'book_oz_update_spisok_klientov_on_delete',10,2);
        add_action('transition_post_status','book_oz_post_idFromStatus_action', 10, 3 ); // если меняем статус черновика, обновляем список записанных у специалиста
        add_action('book_oz_post_idFromStatus','book_oz_update_spisok_klientov_on_delete', 10, 2 );
    }
    
    function book_oz_post_idFromStatus_action($new_status, $old_status, $post) {
        do_action('book_oz_post_idFromStatus',$post->ID,'clients');
    }
    
    function book_oz_update_spisok_klientov_on_delete( $postid, $setPostType = false ){
        global $post_type; 
        /*
        если глобально не передается post_type то пробуем $setPostType
        */
        if ( $post_type != 'clients' && !$setPostType ) return;	
        $post = get_post_meta($postid, 'oz_personal_field_id',true);
        if ($post) {
            do_action('book_oz_update_spisok_klientov',$post);
        }
    }
    
    add_action('book_oz_update_spisok_klientov','book_oz_update_spisok_klientov_func',10,1);
    add_action('book_oz_on_canceled_by_link','book_oz_update_spisok_klientov_on_delete', 10, 2 );
    
    /*
    обновляем oz_clientsarray - список клиентов записанных к сотруднику
    */
    function book_oz_update_spisok_klientov_func($post) {
    $args = array(
        'posts_per_page'   => -1,
        'meta_key'		   => 'oz_personal_field_id',
        'meta_value'       => apply_filters('book_oz_personal_field',$post), // oz_personal_field_id добавил фильтр book_oz_personal_field_id для wpml
        'post_type'        => 'clients',
        'post_status'      => 'publish',
    );
    $args = apply_filters('book_oz_get_posts', $args, 'update_spisok_klientov');
            $posts_array = get_posts( $args );
     
    $cli = array();
    $cliNotFormat = array();
    $raspi = get_post_meta($post,'oz_re_timerange',true);
    $prom = ($raspi) ? array_column($raspi,'oz_select_time_serv') : '';
    $prom = ($prom) ? min($prom) : '';
    $format = "d.m.Y H:i";
    $timeF = "H:i";
     foreach ($posts_array as $clients) {
         $date =  get_post_meta($clients->ID,'oz_start_date_field_id',true);
         $start =  get_post_meta($clients->ID,'oz_time_rot',true);
         if (!$start || !$date) continue;
         $tel =	get_post_meta($clients->ID,'oz_clientPhone',true);
         $email =	get_post_meta($clients->ID,'oz_clientEmail',true);
         $u = get_post_meta($clients->ID,'oz_uslug_set',true);
         $usl = ($u) ? apply_filters('book_oz_sotrudniki_uslTitle',get_the_title($u),$u) : '';
         $w_time = ($u) ? apply_filters('book_oz_sotrudniki_wTime',get_post_meta($u,'oz_serv_time',true),$u) : '';
         $buffer = apply_filters('book_oz_sotrudniki_uslbuffer',[0,0],$u);
         $start = strtotime($start);
         $start = date('H:i', $start);
    
    $start = $date.' '.$start;
    $startISO = DateTime::createFromFormat('d.m.Y H:i P', $start.' '.wp_timezone_string());
    $end = clone $startISO;
    $end = $w_time ? $end->add(new DateInterval("PT{$w_time}M"))->format('c') : $end;
    $cliNotFormat[] = array(
    'start' => $start,
    'startISO' => $startISO->format('c'),
    'end' => $end,
    'w_time' => $w_time,
    'buffer' => $buffer,
    );
    //ver 1.05 проверить потом обновляет ли список клиентов записанных к сотруднику корректно. добавил условие date 
    if ($date) {
        $w_time = apply_filters('book_oz_sotrudniki_wTime',get_post_meta($u,'oz_serv_time',true),$u, false);
        $startT = DateTime::createFromFormat($format, $start)->format(DateTime::ATOM);
        $endT = strtotime("+".$w_time." minutes", strtotime($startT));
        $endT = date('c',$endT);
        $cli[] = apply_filters('book_oz_employeeClientsList', array(
        'title' => $clients->post_title,
        'start' => $startT,
        'end' => $endT,
        'tel' => $tel,
        'email' => $email,
        'usl' => $usl,
        'id' =>	 $clients->ID
        ),$clients->ID );
    }
     }
     $clients = json_encode($cli,JSON_UNESCAPED_SLASHES);
      if ($clients &&  !wp_doing_ajax() && wp_script_is('ozscripts')) {
        wp_localize_script( 'ozscripts', 'clients', $cli );
     }
     $clientsNF = json_encode($cliNotFormat,JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT|JSON_HEX_QUOT|JSON_HEX_APOS);
     $persId = apply_filters('book_oz_update_personal_field_onDelete',$post);
     $persId = (is_array($persId)) ? $persId : array($persId);
        foreach($persId as $pers) :
            update_post_meta($pers,'oz_clientsarray',$clientsNF);
        endforeach;
    }

    add_filter('book_oz_timeFormat', 'book_oz_timeFormat_func');

    function book_oz_timeFormat_func($time) {
        if ($time && get_option('oz_time_format')) {
            $time = DateTime::createFromFormat('H:i', $time);
            $time = $time->format('h:i A');
        }
        return $time;
    }

    function oz_get_services() {
        if (wp_doing_ajax() ) {
            $payload = [];
            $categories = [];
            $args = [];
            $cat = isset($_POST['cat']) ? (int) $_POST['cat'] : 0;
            if (isset($_POST['query']) && $_POST['query'])
            $query = json_decode(stripslashes($_POST['query']), 1);
            if (isset($query) && isset($query['services']) && $query['services']		)
            {
                $ids = explode(',',str_replace('id=', '', $query['services']));
                $args['post__in'] = $ids;
            }
            
            if (isset($query) && isset($query['post__not_in']) && $query['post__not_in']) {
                $ids = array_map('intval', $query['post__not_in']);
                $args['post__not_in'] = $ids;
            }
            if (isset($query) && isset($query['post__in']) && $query['post__in']) {
                $ids = array_map('intval', $query['post__in']);
                $args['post__in'] = $ids;
            }
            
            if (!$cat) 
            $categories = get_categories(array(
                'taxonomy'     => 'oz_service_cats',
                'type'         => 'services',
                'hide_empty'   => 1,
                //'include'		=> isset($service_query['cat']) ? $service_query['cat'] : ''
                ));
            if (!$cat && count($categories)) {
                $payload['type'] = 'categories';
                $list = [];
                foreach ($categories as $cat) {
                    $list[] = [
                        'id' => $cat->term_id,
                        'name' => $cat->name
                    ];
                }
                $res = [
                    'found' => count($categories),
                    'list' => $list
                ];
                $payload['result'] = $res;
                if (isset($_POST['type']) && $_POST['type'] == 'all') {
                    $services = Ajax::get_services($args);
                    if (isset($services['list'])) {
                        $cats_list = array_column($payload['result']['list'], 'id');
                        foreach($services['list'] as $service) {
                                foreach($service['cats'] as $cat) {
                                    $key = array_search($cat,$cats_list);
                                    if ($key !== false) {
                                        if (!isset($payload['result']['list'][$key]['services']))
                                        $payload['result']['list'][$key]['services'] = [];
                                    
                                        $payload['result']['list'][$key]['services'][] = $service['id']; 
                                    }
                                }
                        }
                    }
                    $payload['type'] = 'all';
                    $payload['result'] = [
                    'services_cats' => $payload['result'],
                    'services' => $services
                    ];
                }
            }
            elseif (!count($categories)) {
                $services = Ajax::get_services($args);
                $payload['type'] = 'services';
                $payload['result'] = $services;			
            }
            echo json_encode([
                'success' => true,
                'payload' => $payload,
                ]);
        }
        wp_die();
    }
    
    function oz_get_employees() {
        if (wp_doing_ajax() ) {
            header('Content-type: application/json');
            $payload = [];
            $args = [];
            $cats = [];
            $branches = [];
            $services = [];
            $cat = isset($_POST['cat']) ? (int) $_POST['cat'] : 0;
            if (isset($_POST['query']) && $_POST['query'])
            $query = json_decode(stripslashes($_POST['query']), 1);
            if (isset($query) && isset($query['branches']) && $query['branches']) {
                $branches = explode(',', $query['branches']);
            }
            if (isset($query) && isset($query['services'])) {
            $ids = explode(',',str_replace('id=', '', $query['services']));
            $find_ids = [];
            foreach ($ids as $ids1) {
              $find_ids[] = ['oz_personal_serv_name' => $ids1];
            }
              $args['meta_query'] = [
                'relation' => 'OR',
                  [
                  'relation' => 'AND',
                    [
                      'key' => 'oz_book_provides_services',
                      'value'    => 'include',
                    ],
                    [
                      'key' => 'oz_re_timerange',
                      'value'    => serialize($find_ids),
                      'compare'    => 'LIKE',
                    ],
                  ],
                  [
                  'relation' => 'AND',
                    [
                      'key' => 'oz_book_provides_services',
                      'value'    => 'exclude',
                    ],
                    [
                      'key' => 'oz_re_timerange',
                      'value'    => serialize($find_ids),
                      'compare'    => 'NOT LIKE',
                    ],
                  ],
                  [
                      'key' => 'oz_book_provides_services',
                      'value'    => 'all',
                  ],
                ];    
            }
            if (!$cat && !(isset($query) && isset($query['page_type']) && $query['page_type'] == 'employee'))
            $cats = get_categories(array(
                'taxonomy'     => 'filial',
                'hide_empty'   => 1,
                'include'		=> !empty($branches) ? $branches : []
                ));
            if (!$cat && count($cats)) {
                $payload['type'] = 'branches';
                $list = [];
                foreach ($cats as $cat) {
                    $list[] = [
                        'id' => $cat->term_id,
                        'name' => $cat->name,
                        'count' => $cat->count,
                        'description' => $cat->category_description,
                    ];
                }
                $res = [
                    'found' => count($cats),
                    'list' => $list
                ];
                $payload['result'] = $res;
                if (isset($_POST['type']) && $_POST['type'] == 'all') {
                    if (isset($_POST['query']) && $_POST['query']) {
                        $query = json_decode(stripslashes($_POST['query']), 1);
                        if (isset($query['employee'])) {
                            $args['post__in'] = is_array($query['employee']) ? $query['employee'] : [];
                        }
                        elseif (isset($query['branches']) && $query['branches']) {
                            $branches = explode(',', $query['branches']);
                            $args['tax_query'] = array(
                                array(
                                    'taxonomy' => 'filial',
                                    'field'    => 'term_id',
                                    'terms'    => $branches,
                                )
                            );					
                        }
                    }
                    $emp = Ajax::get_employees($args);
                    $payload['type'] = 'all';
                    $payload['result'] = [
                    'branches' => $payload['result'],
                    'employees' => $emp
                    ];
                }
            }
            elseif (!count($cats)) {
                $query = [];
                if (isset($_POST['query']) && $_POST['query']) {
                    $query = json_decode(stripslashes($_POST['query']), 1);
                    if (isset($query['employee']) && $query['employee']) {
                        $args['post__in'] = is_array($query['employee']) ? $query['employee'] : [(int) ($query['employee'])];
                    }
                    elseif (isset($query['branches']) && $query['branches']) {
                        $branches = explode(',', $query['branches']);
                        $args['tax_query'] = array(
                            array(
                                'taxonomy' => 'filial',
                                'field'    => 'term_id',
                                'terms'    => $branches,
                            )
                        );					
                    }
                }
                $payload['type'] = 'employees';
                $payload['result'] = Ajax::get_employees($args);			
            }
            echo json_encode([
                'success' => true,
                'payload' => $payload,
                ]);
        }
        wp_die();
    }

/**
 *  Color CSS on frontend
 *  
 *  @return void
 *  
 *  @version 2.1.1
 */
function book_oz_colors_styles() {
	$colors = get_option('oz_colors', ['primary' => '', 'secondary' => '', 'background' => '']);
	if (!$colors) return;
	$primaryColors = array(
	'background-color' => array(
	'.oz_btn:not(.oz_usl_btn)',
	'.oz_hid input[type="submit"]',
	'.oz_btn.oz_spec_btn',
	'.oz_hid .ui-datepicker-calendar tbody td[data-handler="selectDay"] a:after',
	'.oz_hid  .ui-datepicker-header',
	'.oz_hid_carousel ul li.squaredThree label:hover',
	'.oz_zapis_info',
	'.oz_back_btn',
	'.oz_future:before',
	'.oz_pop_btn'
	),
	'color' => array(
	'.tz_colors',
	'.tz_stop',
	'.oz_hid .ui-datepicker-title',
	//'.oz_hid .ui-datepicker-calendar tbody td[data-handler="selectDay"] a'
	),
	'border-top-color' => array(
	'.oz_hid_carousel ul li', //#2dde98
	'.oz_future:before',
	'body .oz_hid_carousel ul.oz_select .oz_li_sub ul'
	),
	'stroke' => array(
	'#oz_ok_icon path',
	'.checkmark__check',
	'.checkmark__circle'
	)
	);
	$primaryColors = apply_filters('book_oz_primaryColors', $primaryColors);
	
	$secondColors = array(
	'background-color' => array(
	'.oz_btn.oz_spec_btn:hover',
	'.oz_btn:not(.oz_usl_btn):hover',
	'.oz_hid input[type="submit"]:hover',
	'.oz_btn.oz_spec_btn:active',
	'.oz_btn:not(.oz_usl_btn):active',
	'.oz_hid input[type="submit"]:active',
	'.oz_btn.oz_spec_btn:focus',
	'.oz_btn:not(.oz_usl_btn):focus',
	'.oz_hid input[type="submit"]:focus',
	'.oz_back_btn:hover'
	)
	);
	$secondColors = apply_filters('book_oz_secondColors', $secondColors);
	
	$backColors = array(
	'background-color' => array(
	'.oz_container',
	'.oz_popup'
	)
	);
	$backColors = apply_filters('book_oz_backColors', $backColors);
	if ((isset($colors['primary']) && $colors['primary']) || (isset($colors['secondary']) && $colors['secondary']) || (isset($colors['background']) && $colors['background'])) {
	?>
	<style>
	<?php 
		  foreach ($primaryColors as $key => $primaryColor) {
			  $classes = implode(',',$primaryColors[$key]);
			  echo $classes.' {'.$key.':'.$colors['primary'].' !important}';
		  }
		  //#3cffb2
		  foreach ($secondColors as $key => $secondColor) {
			  $classes = implode(',',$secondColors[$key]);
              $second = isset($colors['second']) ? $colors['second'] : $colors['secondary']; 
			  echo $classes.' {'.$key.':'.$second.' !important}';
		  }
		  
		  foreach ($backColors as $key => $backColor) {
			  $classes = implode(',',$backColors[$key]);
			  echo $classes.' {'.$key.':'.$colors['background'].' !important}';
		  }
	?>
	</style>
	<?php
	}
}

function book_oz_changed_data($data, $id = 0) {
    $new_data = [];
    $data = is_array($data) ? $data : [];
    $exclude = ['oz_notify_by_email', 'oz_notify_by_sms'];
    foreach ($data as $key => $po) {
        if (strpos($key, 'oz_') !== false || in_array($key, ['post_title', 'post_status']) && !in_array($key,$exclude)) {
            if ( is_array( $po ) ) {
                $po = $po[0];
            }
            $po = maybe_unserialize($po);
            $new_data[$key] = $po;
        }
    }
    return $new_data;
}

/**
 *  Shortcode - appointment status
 *  
 *  @param array    $atts shortcode atts
 *  @return ststus of appointment by id
 *  
 *  @version 2.0.9
 */
function book_oz_appointment_status_func( $atts ) {
	$appointment = (isset($atts['id'])) ? get_post_meta($atts['id'], 'oz_app_status', true) : '' ;
	$status = '';
	if ($appointment) {
        $status = array(
				'approved' => __("Approved", 'book-appointment-online'),
				'onhold' => __("On hold", 'book-appointment-online'),
				'canceled' => __("Canceled", 'book-appointment-online')
        );
		$status = (isset($status[$appointment])) ? $status[$appointment] : '';
	}
	return $status;
}
add_shortcode( 'book_oz_appointment_status', 'book_oz_appointment_status_func' );

/* shortcode sms оповещений */

function book_oz_timebooking_func( $atts ) {
	$hours = apply_filters('book_oz_timeFormat',get_post_meta($atts['id'],'oz_time_rot',true));
	$date = date_i18n(get_option('date_format'),strtotime(get_post_meta($atts['id'],'oz_start_date_field_id',true)));
	$time = (isset($atts['id'])) ? $date.' '.$hours : '' ;
	return $time;
}
add_shortcode( 'book_oz_timebooking', 'book_oz_timebooking_func' );

function book_oz_timebooking_tz_func( $atts ) {
	$hours = apply_filters('book_oz_timeFormat',get_post_meta($atts['id'],'oz_time_rot',true));
	$date = date_i18n(get_option('date_format'),strtotime(get_post_meta($atts['id'],'oz_start_date_field_id',true)));
	$time = (isset($atts['id'])) ? $date.' '.$hours : '' ;
	if (get_post_meta($atts['id'], 'oz_timezone',true)) {
		$ctz = get_post_meta($atts['id'], 'oz_timezone',true);
		$minus = $ctz < 0 ? '-' : '+';
		$tz = new DateTime('today '.abs($ctz).' minutes');
		$tz = new DateTimeZone($minus.$tz->format('H:i'));	
		$s_tz = (function_exists('wp_timezone_string')) ? wp_timezone_string() : book_oz_timezone_string();
		$dt = DateTime::createFromFormat('d.m.Y H:i P', get_post_meta($atts['id'],'oz_start_date_field_id',true).' '.get_post_meta($atts['id'], 'oz_time_rot',true).' '.$s_tz);
		$site_timezone = $dt->getTimezone();
		$dt = $dt->setTimezone($tz);
		$date_tz = wp_date(get_option('date_format'),$dt->format('U'), $dt->getTimezone());
		$time_tz = apply_filters('book_oz_timeFormat',$dt->format('H:i'));
		$time = $date_tz.' '.$time_tz;
	}
	return $time;
}
add_shortcode( 'book_oz_timebooking_tz', 'book_oz_timebooking_tz_func' );

function book_oz_name_func( $atts ) {
	$name = (isset($atts['id'])) ? get_the_title($atts['id']) : '' ;
	return $name;
}
add_shortcode( 'book_oz_name', 'book_oz_name_func' );

function book_oz_clientphone_func( $atts ) {
	$phone = (isset($atts['id'])) ? get_post_meta($atts['id'],'oz_clientPhone',true) : '' ;
	return $phone;
}
add_shortcode( 'book_oz_clientphone', 'book_oz_clientphone_func' );

function book_oz_id_func( $atts ) {
	return isset($atts['id']) ? $atts['id'] : '';
}
add_shortcode( 'book_oz_id', 'book_oz_id_func' );

function book_oz_cancel_link_func( $atts ) {
	$id = (isset($atts['id'])) ? (int) ($atts['id']) : 0;
	$app_code = hash('sha1', $id.'&'.get_post_meta($id,'oz_start_date_field_id',true).'&'.get_post_meta($id,'oz_time_rot',true));
	$cancel_url = site_url().'?oz_cancel='.$id.'&oz_cancel_code='.$app_code;
	return apply_filters('book_oz_cancel_url_sms', $cancel_url);
}
add_shortcode( 'book_oz_cancel_link', 'book_oz_cancel_link_func' );

function book_oz_conference_link_func( $atts ) {
	if (!get_option('oz_conf_pageid')) return '';
	$id = (isset($atts['id'])) ? (int) ($atts['id']) : 0;
	$conf_url = get_permalink(get_option('oz_conf_pageid'));
	$ap = parse_url($conf_url);
	$ap = isset($ap['query']) && $ap['query'] ? '&' : '?';
	$url = get_post_meta($id, 'oz_remote_id', true) ? $conf_url.$ap.'conference_id='.get_post_meta($id, 'oz_remote_id', true) : '';
	return apply_filters('book_oz_conference_url_sms', $url);
}
add_shortcode( 'book_oz_conference_link', 'book_oz_conference_link_func' );

function book_oz_timesms_func( $atts ) {
	$remind = (isset($atts['id'])) ? get_post_meta($atts['id'],'oz_remList',true) : '' ;
	switch ($remind) {
		case 15 :
		$remind = __('15 minutes', 'book-appointment-online');
		break;
		case 30 :
		$remind = __('30 minutes', 'book-appointment-online');
		break;
		case 60 :
		$remind = __('1 hour', 'book-appointment-online');
		break;
		case 120 :
		$remind = __('2 hours', 'book-appointment-online');
		break;
		case 240 :
		$remind = __('4 hours', 'book-appointment-online');
		break;
		case 480 :
		$remind = __('8 hours', 'book-appointment-online');
		break;
		case 1440 :
		$remind = __('1 day', 'book-appointment-online');
		break;
	}
	return $remind;
}
add_shortcode( 'book_oz_timesms', 'book_oz_timesms_func' );

add_filter( 'parse_query', 'book_oz_show_filtered_all' );

/**
 *  Filtered posts by parametres
 *  
 *  @param object    $query Query object
 *  @return void
 *  
 *  @version 2.0.9
 */
function book_oz_show_filtered_all( $query ){
	global $pagenow;
    if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'clients' && isset($_GET['filter_action'])
	&& $query->query['post_type'] == 'clients'
	) {
		$query->query_vars['meta_query'] = array();
		foreach ($_GET as $param => $val) :
		if ($val) {
			switch($param) {
				case 'oz_uslug_set' :
				$query->query_vars['meta_query'][] = array(
					'key' => 'oz_uslug_set',
					'value' => (int) ($val),
				);
				break;
				case 'oz_personal_field_id' :
				$query->query_vars['meta_query'][] = array(
					'key' => 'oz_personal_field_id',
					'value' => (int) ($val),
				);
				break;
				case 'oz_app_status' :
				$query->query_vars['meta_query'][] = array(
					'key' => 'oz_app_status',
					'value' => sanitize_text_field($val),
				);
				break;
				case 'oz_user_id' :
				$query->query_vars['meta_query'][] = array(
					'key' => 'oz_user_id',
					'value' => (int) ($val),
				);
				break;
			}
		}
		endforeach;
        }
    }

    /**
 *  Check user capability for access to settings and dashboard calendar
 *  
 *  @param bool    $return_cap if true echoing role
 *  @param bool    $check_hide_settings if true check oz_user_role_showSetting option
 *  @return true if user can see calendar
 *  
 *  @version 2.0.7
 */
function book_oz_user_can( $return_cap = false, $check_hide_settings = false, $where_check = '') {
    if (current_user_can('administrator')) {
        $can = ($return_cap) ? 'administrator' : true;
        return $can;
    }
    $roles = (get_option('oz_user_role')) ? get_option('oz_user_role') : array('administrator') ; 
    $user = (function_exists('wp_get_current_user')) ? wp_get_current_user() : false;
    $can = false;
    if ($user) {
    $caps = $user->allcaps;
    $hide_settings = ($check_hide_settings) ? get_option('oz_user_role_showSetting') : false;
        foreach ($roles as $role) {
            if (isset($caps[$role]) && $caps[$role] && !$hide_settings) {
                $can = ($return_cap) ? $role : true;
                break;
            }
        }
    }
    return apply_filters('book_oz_user_can_filter', $can,$where_check);
}


add_action('admin_footer', 'book_oz_why_deactivate');
 
 
add_action( 'admin_enqueue_scripts', 'book_oz_add_wp_pointer', 99 );
 
function book_oz_add_wp_pointer() {
    wp_enqueue_style( 'wp-pointer' );
    wp_enqueue_script( 'wp-pointer' );
}
 
function book_oz_why_deactivate() {
    $current = get_current_screen();
    if ($current && $current->base == 'plugins') :
    $par =[
        'adminAjax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ozajax-nonce'),
        ];
?>
<style>
    #book_oz_better {
        display:flex;
        flex-direction: column;
        padding:0 20px;
    }
    
    #book_oz_better label {
        margin-bottom:10px;
    }
    .oz_reason_choose {
        padding: 0 !important;
        color: red;
        margin: 0 0 10px;
    }
    
    .book_oz_better_wrapper.oz_load {
        opacity:0.6;
    }
</style>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        let dismissed = false
            $('#deactivate-book-appointment-online').click(function(e) {
                if (dismissed) return;
                e.preventDefault()
                $(this).pointer({
                                content: '<h3><?php _e('Please tell us why are you deactivating the plugin?', 'book-appointment-online'); ?></h3>'+
                                '<p><?php _e('Please tell us why are you deactivating the plugin?', 'book-appointment-online'); ?></p>'+
                                '<div class="book_oz_better_wrapper"><form id="book_oz_better">'+
                                    '<label><input name="reason" type="checkbox" value="Poor design" /> <?php _e('Poor design', 'book-appointment-online'); ?></label>'+
                                    '<label><input name="reason" type="checkbox" value="Too few features" /> <?php _e('Too few features', 'book-appointment-online'); ?></label>'+
                                    '<label><input name="reason" type="checkbox" value="Plugin is buggy on my website" /> <?php _e('Plugin is buggy on my website', 'book-appointment-online'); ?></label>'+
                                    '<label><input name="reason" type="checkbox" value="This is a temporary deactivation" /> <?php _e('This is a temporary deactivation', 'book-appointment-online'); ?></label>'+
                                    '<input style="margin: 5px 0 15px;" type="text" name="another_reason" placeholder="<?php _e('Another reason', 'book-appointment-online'); ?>" />'+
                                    '<input type="submit" style="align-self: flex-start;" class="button" value="<?php _e('Submit', 'book-appointment-online'); ?>">'+
                                '</form></div>',
                                position: 'top',
                                close: function() {
                                    dismissed = true
                                }
                            }).pointer('open');
            })
            
            $('body').on('submit', '#book_oz_better', async function(e) {
                    e.preventDefault();
                    let vals = {
                    reason: [],
                    another_reason: ''
                    }
 
                    $(this).serializeArray().forEach(input => {
                        if (input.name == 'reason') {
                            vals.reason.push(input.value)
                        }
                        else if (input.name == 'another_reason') {
                            vals.another_reason = input.value
                        }
                    })
                    $('.book_oz_better_wrapper').addClass('oz_load')
                    if (vals.reason.length || vals.another_reason.length > 3) {
                        $('.oz_reason_choose').remove()
                        try {
                        let body = new URLSearchParams();
                        body.set('action', 'oz_reason')
                        body.set('form', JSON.stringify(vals))
                        body.set('_ajax_nonce', '<?php echo $par['nonce']; ?>')
                        let res = await (await fetch('<?php echo $par['adminAjax']; ?>', {
                            method: 'post',
                            body
                        })).json()
                        $('.book_oz_better_wrapper').removeClass('oz_load').html('<p><?php _e('Deactivating the plugin...', 'book-appointment-online'); ?></p>')
                        dismissed = true
                        }
                    catch(err) {
                        console.log(err)
                    }
                        window.location.href = $('#deactivate-book-appointment-online').attr('href')
                                                
                    }
                    else {
                        $('<p class="oz_reason_choose"><?php _e('Please choose or typing a reason', 'book-appointment-online'); ?></p>').insertBefore($(this).find('input[type="submit"]'))
                        setTimeout(() => {
                            $('.oz_reason_choose').remove()
                        }, 20000)
                    }
                    $('.book_oz_better_wrapper').removeClass('oz_load')
            })
    })
    </script><?php
    endif;
}
 
add_action('wp_ajax_oz_reason', 'oz_reason_func');
 
function oz_reason_func() {
    if (wp_doing_ajax() && check_ajax_referer('ozajax-nonce')) {
        $form = json_decode(stripslashes($_POST['form']),1);
        $query = http_build_query($form);
        $res = wp_safe_remote_get('http://oz-plugin.ru/?deactivation_reason=1&'.$query);
        if (!is_wp_error($res)) {
            //wp_remote_retrieve_body($res)
        }
        echo json_encode([
            'success' => true,
            'payload' => wp_remote_retrieve_body($res)
        ]);
    }
    wp_die();
}
