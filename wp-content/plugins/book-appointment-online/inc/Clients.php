<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
namespace Ozplugin;
//use Ozplugin\Settings;
use \DateTime;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Clients {
    public function init() {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerTaxonomy']);
        add_action('init', [$this, 'addMetabox']);
        add_filter( 'post_row_actions', [$this, 'book_oz_addIdInList'], 10, 2 );
        add_filter('manage_edit-clients_columns', [$this, 'book_oz_cpt_columns']);
        add_filter('manage_edit-clients_sortable_columns', [$this, 'book_oz_cpt_columns']);
        add_action('manage_clients_posts_custom_column', [$this, 'book_oz_cpt_column'], 10, 2);
        add_filter('dataDayUsl_filter', [$this, 'dataDayUsl_filter_callback'],10,2); 
        add_action('add_meta_boxes', [$this, 'book_oz_app_logs']);
        add_filter( 'wp_insert_post_data', [$this, 'book_oz_save_to_log'], 10, 2 );
        add_filter('book_oz_react_options', [$this, 'front_options']);

        //deprecated
        add_action('admin_footer', [$this, 'book_oz_clientTime'],99,2);
        add_filter('book_oz_persSpis', [$this, 'book_oz_persSpis_callback'],10,1);
        add_action('book_oz_in_metabox', [$this, 'book_oz_in_metabox_clientTime'],10,2);
        add_action('book_oz_before_metabox', [$this, 'book_oz_addAppId']);

        //register a client
        add_action('book_oz_before_appointment_insert',array($this,'check_user'));
        add_action('book_oz_add_fields_to_post', array($this,'register'), 10);
        add_action('book_oz_after_plugin_activate', array($this,'add_role'));

        //user area
        add_filter('manage_edit-clients_columns', [$this,'book_oz_cpt_columns_user']);
        add_filter('manage_edit-clients_sortable_columns', [$this,'book_oz_cpt_columns_user']);
        add_action('manage_clients_posts_custom_column', [$this,'book_oz_cpt_column_user'], 10, 2);
        add_action('book_oz_add_fields_to_post', [$this,'book_oz_add_field_userID'],10,2);
        add_action('wp_ajax_cancelBooking', [$this,'book_oz_cancelBooking']);
        add_action('book_oz_on_canceled', [$this, 'book_oz_on_sendCanceled'],10,2);
        add_action('wp_ajax_oz_get_apps', [$this, 'oz_get_apps']);
        //add_action('wp_ajax_nopriv_oz_get_apps', 'oz_get_apps');
    }
    
    public function registerPostType() {
        register_post_type( 'clients',
		array(
			'labels' => array(
				'name' => __('Appointments', 'book-appointment-online'),
				'singular_name' => __('Appointment', 'book-appointment-online'),
				'add_new' => __('Add appointment', 'book-appointment-online'),
				'add_new_item' => __('Add new appointment', 'book-appointment-online'),
				'edit' => __('Edit appointment', 'book-appointment-online'),
				'edit_item' => __('Edit appointment', 'book-appointment-online'),
				'new_item' => __('New appointment', 'book-appointment-online'),
				'view' => __('View appointment', 'book-appointment-online'),
				'view_item' => __('View appointment', 'book-appointment-online'),
				'search_items' => __('Search appointment', 'book-appointment-online'),
				'not_found' => __('Appointments not found', 'book-appointment-online'),
				'not_found_in_trash' => __('Appointments not found in trash', 'book-appointment-online'),
				'parent' => __('Parent appointment', 'book-appointment-online'),
			),
			'public' => true,
			'menu_position' => 8,
			'supports' => array( 'title' ),
			'menu_icon' => 'dashicons-groups',
			'has_archive' => false,
			'exclude_from_search' => true, 
			'publicly_queryable'  => false,
			'map_meta_cap'        => true,
			'capability_type'     => array('client','clients'),
		)
	);
    }

    public function registerTaxonomy() {
        // 2.1.5 added categories for services
        register_taxonomy( 'oz_service_cats' , 'services', array(
            'hierarchical'	=> true,
            'show_ui'	=> true,
            'show_admin_column'	=> true,
            'update_count_callback'	=> '_update_post_term_count',
            'query_var'	=> true,
            //'rewrite'	=> array( 'slug' => 'filial' ),
        ) );
    }

 
    public function book_oz_addIdInList( $actions, $post ) {
        if ( $post->post_type == "clients" ) {
            $actions['app_id'] = '<span style="color:#777;">ID: '.$post->ID.'</span>';
        }
        return $actions;
    }

    public function book_oz_cpt_columns( $columns ) {
        $columns["oz_date_td"] = __("Booking date", 'book-appointment-online');
        $columns["oz_time_td"] = __("Booking time", 'book-appointment-online');
        $columns["oz_pers_td"] = __("Specialist", 'book-appointment-online');
        return $columns;
    }

    public function book_oz_cpt_column( $colname, $cptid ) {
        if ( $colname == 'oz_date_td')
             echo apply_filters('book_oz_dateFormat', esc_html(get_post_meta( $cptid, 'oz_start_date_field_id', true )));
        if ( $colname == 'oz_time_td') {
            $time = apply_filters('book_oz_timeFormat', esc_html(get_post_meta( $cptid, 'oz_time_rot', true )));
             echo $time;
        }
        if ( $colname == 'oz_pers_td') {
            $pers_id = (int) (get_post_meta( $cptid, 'oz_personal_field_id', true ));
             echo $pers_id  ? get_the_title($pers_id ) : '';
        }
   }

   public function addMetabox() {
    if (!is_admin()) return;
        $prefix = 'oz_';
        
        
        $config = array(
        'id'             => 'client_meta_box',          // meta box id, unique per meta box
        'title'          => __('Client data', 'book-appointment-online'),          // meta box title
        'pages'          => array('clients'),      // post types, accept custom post types as well, default is array('post'); optional
        'context'        => 'normal',            // where the meta box appear: normal (default), advanced, side; optional
        'priority'       => 'high',            // order of meta box: high (default), low; optional
        'fields'         => array(),            // list of meta fields (can be added by field arrays)
        'local_images'   => false,          // Use local or hosted images (meta box images for add/remove)
        'use_with_theme' => false,          //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
        'callback'		=> 'book_oz_clientTime',
    );
    
        $clients =  new \AT_Meta_Box($config); 
        $clients->addText($prefix.'clientPhone',array('name'=> __('Phone', 'book-appointment-online')));
        $clients->addText($prefix.'clientEmail',array('name'=> __('Email', 'book-appointment-online')));
        $clients->addPosts($prefix.'uslug_set',array('post_type' => 'services', 'args' => apply_filters('book_oz_get_posts', array('order' => 'ASC','orderby' => 'title'))),apply_filters('book_oz_select_services_options', array('name'=> __('Services', 'book-appointment-online'),'inGroup' => true, 'emptylabel' => __('Not select', 'book-appointment-online'),'style' => 'width:100%;')));
        $clients->addPosts($prefix.'personal_field_id',array('post_type' => 'personal', 'args' => apply_filters('book_oz_get_posts', array(), 'select_employees') ),apply_filters('book_oz_select_employees_options', array('name'=> __('Employee name', 'book-appointment-online'),'inGroup' => true, 'emptylabel' => __('Not select', 'book-appointment-online'),'style' => 'width:100%;', 'showCats' => 'filial')));
        $clients->addDate($prefix.'start_date_field_id',array('name'=> __('Date', 'book-appointment-online'),'inGroup' => true, 'asDiv' => true)); //'format'=>'yy.mm.dd' - такой формат работает с between
        $clients->addText($prefix.'time_rot',array('name'=> __('Booking time', 'book-appointment-online'),'inGroup' => true));	 // поменять потом на addHidden
        if (get_option('book_oz_enable_statuses')) $clients->addSelect($prefix.'app_status',array('approved'=>__('Approved', 'book-appointment-online'),'onhold'=>__('On hold', 'book-appointment-online'),'canceled'=>__('Canceled', 'book-appointment-online')),array('name'=> __('Status', 'book-appointment-online'), 'std'=> array('approved')));	
        $clients->addSelect($prefix.'remList',array('0'=>__('Not', 'book-appointment-online'),'15'=>__('15 min before', 'book-appointment-online'),'30'=>__('30 min before', 'book-appointment-online'),'60'=>__('1 hour before', 'book-appointment-online'),'120'=>__('2 hours before', 'book-appointment-online'),'240'=>__('4 hours before', 'book-appointment-online'),'480'=>__('8 hours before', 'book-appointment-online'),'1440'=>__('1 day before', 'book-appointment-online')),array('name'=> __('Remind at SMS', 'book-appointment-online'), 'std'=> array('0')));
        $clients->Finish();
   }

   public function book_oz_clientTime($arg) {
	if (isset($_GET['post'])) {
        $id = get_post_meta($_GET['post'],'oz_personal_field_id',true);

        $arr = json_decode(get_post_meta($id,'oz_raspis',true),true);
        if ($arr && isset($arr['end'])) {
        $startC = array_column($arr, 'start');
        $end = array_column($arr, 'end');
        $start =  (isset($startС)) ? min($startС) : '';
        $end =  max($end);
        }
        }
        ?>
        <script>
        jQuery(document).ready(function() {
        var hourStart,hourFinish;
        <?php if (isset($start) && $start != '') : ?>
        dayStart = <?php echo '"'.$start.'"'; ?> ;
        hourStart = <?php echo explode(':',$start)[0]; ?> ;
        <?php endif; ?>
        <?php if (isset($end)) : ?>
        dayFinish =  <?php echo '"'.$end.'"'; ?> ;
        hourFinish = <?php echo explode(':',$end)[0]; ?> ;
        <?php endif; ?>
        });
        </script>
        <?php
    }

    public function book_oz_persSpis_callback($persSpis) {
        $args1 = array(
        'post_type' => 'personal',
        'post_status' => 'publish', //oz_re_timerange
        'posts_per_page' => -1,
        );
        $args1 = apply_filters('book_oz_get_posts', $args1);
        $posts = get_posts( $args1 );
        if ($posts) {
            $persSpis = array();
            foreach( $posts as $post ) : setup_postdata( $post );
            $persSpis[] = $post->ID;
            wp_reset_postdata(); endforeach;
                }
            return $persSpis;
        }

        public function dataDayUsl_filter_callback($p,$id1) {
            $persId = array();
            if ($id1) :
            foreach ($id1 as $id) {
            switch (get_post_meta($id,'oz_book_provides_services',true)) {
                case 'all':
                $persId[] = $id;
                break;
                case 'include':
                $services = get_post_meta($id,'oz_re_timerange',true);
                if (is_array($services)) {
                    foreach( $services as $serv) {
                        if ($serv['oz_personal_serv_name'] == $p) {
                            $persId[] = $id; 
                        }
                    }
                }
                break;
                case 'exclude':
                $services = get_post_meta($id,'oz_re_timerange',true);
                if (is_array($services)) {
                    foreach( $services as $serv) {
                        if ($serv['oz_personal_serv_name'] != $p) {
                            $persId[] = $id; 
                        }
                    }
                }
                break;
                
            }
            }
            return implode(',',$persId);
            endif;
        }

        public function book_oz_in_metabox_clientTime($arg,$position) {
            if ($arg == 'book_oz_clientTime') {
                if ($position == 4) {
                global $post;
                    $today = current_time('d.m.Y');
                    $tr = '';
                    $today = ($today) ? DateTime::createFromFormat('d.m.Y', $today) : '';
                    $today = ($today) ? $today->format('U') : '';
                    $dBooking = ($post) ? get_post_meta($post->ID,'oz_start_date_field_id', true) : '';
                    $tBooking = ($post) ? get_post_meta($post->ID,'oz_time_rot', true) : '';
                    $tBook = ($dBooking) ? DateTime::createFromFormat('d.m.Y', $dBooking) : '';
                    $tBook = ($tBook) ?  $tBook->format('U') : '';
                    $timePass = '';
                    if ($tBook && $today > $tBook) { 
                    $timePass = '<div class="hidenextDiv">';
                    $timePass .= sprintf(__('An appointment has already passed <b>%s</b> at <b>%s</b>', 'book-appointment-online'),$dBooking,$tBooking);
                    $timePass .= ' <span>'.__('Edit', 'book-appointment-online'). '</span></div>';
                    $tr = ($timePass) ? 'hidenextTr' : '';
                    } 
                    echo '<tr class="'.$tr.'"><td class="at-field" colspan="2">'.$timePass.'</td></tr>';
                        if (isset($_GET['post'])) {
                        $id = get_post_meta($_GET['post'],'oz_personal_field_id',true);
                        }
                }
            }
        }

        public function book_oz_addAppId($arg) {
            global $post;
            if ($arg == 'book_oz_clientTime') {
                $dBooking = ($post) ? get_post_meta($post->ID,'oz_start_date_field_id', true) : '';
                $tBooking = ($post) ? get_post_meta($post->ID,'oz_time_rot', true) : '';
                echo '<p style="padding:0 10px; color:#777;">ID: '.$post->ID.'</p>';
                
                if ($tBooking && $dBooking)
                echo '<p style="padding:0 10px; color:#777;">'.__('Date', 'book-appointment-online').': '.book_oz_setDateFormat($dBooking) .' '.book_oz_timeFormat_func($tBooking).'</p>';
                
                if (get_post_meta($post->ID, 'oz_remote_id', true) && get_option('oz_conf_pageid')) :
                $url = parse_url(get_permalink(get_option('oz_conf_pageid')));
                $ap = isset($url['query']) && $url['query'] ? '&' : '?';
                $url = get_permalink(get_option('oz_conf_pageid')).$ap.'conference_id='.get_post_meta($post->ID, 'oz_remote_id', true);
                echo '<p style="padding:0 10px; color:#777;">'.__('Conference URL', 'book-appointment-online').': <a target="_blank" href="'.$url.'">'.$url.'</a></p>';
                endif;
            }
        }

        public function book_oz_app_logs(){
            $screens = array( 'clients' );
            add_meta_box( 'oz_app_logs', __('Logs', 'book-appointment-online'), [$this, 'book_oz_app_logs_callback'], $screens, 'side' );
        }

        public function book_oz_app_logs_callback( $post, $meta ){

            // Поля формы для введения данных
            $logs = get_post_meta($post->ID, 'oz_logs', true);
            //print_r($logs);
            if ($logs) {
                $logs = array_reverse($logs);
                foreach($logs as $key => $log) : ?>
                <div id="oz_log-<?php echo $key; ?>" class="oz_log_msg">
                    <?php if (!isset($log['when'])) echo '<p>'.__('Error with this log message', 'book-appointment-online').'</p>';?>
                    <?php
                    if (isset($log['changed']) && $log['changed']) {
                        echo '<ul>';
                        foreach ($log['changed'] as $change) {
                            $from = is_array(maybe_unserialize($change['from'])) ? implode(', ',maybe_unserialize($change['from'])) : $change['from'];
                            $to = is_array(maybe_unserialize($change['to'])) ? implode(', ',maybe_unserialize($change['to'])) : $change['to'];
                            $from = $from ?: __("Empty", 'book-appointment-online');
                            $to = $to ?: __("Empty", 'book-appointment-online');
                            echo '<li>'.sprintf(__('%s : from %s to %s', 'book-appointment-online'), $change['what'], $from, $to ).'</li>'; 
                        }
                        echo '</ul>';
                    } ?>
                        <p>
                        <?php if (isset($log['who']) && $log['who']) echo $log['who']['name'].' at';  ?>
                        <?php if (isset($log['when'])) : ?><time><?php echo date(get_option('date_format').' '.get_option('time_format'), strtotime($log['when']));  ?><time> <?php endif;?>
                        </p>
                <?php
                echo '</div>';		
                endforeach;
            }
            else {
                echo '<label>' . __("Empty", 'book-appointment-online') . '</label> ';
            }
            //echo '<pre>'; print_r($logs); echo '</pre>'; 
        }

        public static function book_oz_save_to_log( $data, $postarr, $old_post = false ) {
            global $post;
            if (!$post && !$old_post) return $data;
            $post = $old_post ?: $post;
            $isNewPost = $post->post_date_gmt == '0000-00-00 00:00:00' && in_array($post->post_status, ['pending', 'draft', 'auto-draft']);
            if ($isNewPost || $post->post_type != 'clients') return $data;
            //delete_post_meta($post->ID, 'oz_logs');
            $new_data = book_oz_changed_data($postarr);
            $post_data = [
            'post_title' => isset($post->post_title) ? $post->post_title : '', 
            'post_status' => isset($post->post_status) ? $post->post_status : '',
            ];
            $old_data = book_oz_changed_data(get_post_meta($postarr['ID']));
            $old_data = array_merge($post_data, $old_data);
        $result = array_diff(array_map('maybe_serialize', $new_data), array_map('maybe_serialize', $old_data));
        unset($old_data['oz_logs']);
        $mess = [];
        if ($result) {
            $what = [
                'post_title' => __('Appointment name', 'book-appointment-online'),
                'post_status' => __('Post status', 'book-appointment-online'),
                'oz_clientPhone' => __('Phone', 'book-appointment-online'),
                'oz_clientEmail' => __('Email', 'book-appointment-online'),
                'oz_uslug_set' => __('Services', 'book-appointment-online'),
                'oz_personal_field_id' => __('Employee', 'book-appointment-online'),
                'oz_start_date_field_id' => __('Date', 'book-appointment-online'),
                'oz_time_rot' => __('Time', 'book-appointment-online'),
                'oz_app_status' => __('Status', 'book-appointment-online'),
                //'oz_remList' => 'Reminder',
                
            ];
            foreach($result as $key => $res) {
                if ($key == 'oz_custom_fields_post') continue;
                if ($key == 'oz_start_date_field_id') {
                    $res = date_i18n(get_option('date_format'),strtotime($res));
                    $old_data[$key] = date_i18n(get_option('date_format'),strtotime($old_data[$key]));
                }
                elseif ($key == 'oz_time_rot') {
                    $res = apply_filters('book_oz_timeFormat', $res);
                    $old_data[$key] = apply_filters('book_oz_timeFormat', $old_data[$key]);
                } 
                elseif ($key == 'oz_uslug_set') {
                $res = ($res) ? explode(',',$res) : array();
                $services = array();
                if ($res) {
                    foreach ($res as $serv) {
                        array_push($services, get_the_title($serv));	
                    }
                }
                $res = implode(', ', $services); 
                
                $old_data[$key] = ($old_data[$key]) ? explode(',',$old_data[$key]) : array();
                $services = array();
                if ($old_data[$key]) {
                    foreach ($old_data[$key] as $serv) {
                        array_push($services, get_the_title($serv));	
                    }
                }
                $old_data[$key] = implode(', ', $services);
                }
                
                elseif ($key == 'oz_personal_field_id') {
                    $res = get_the_title($res);
                    $old_data[$key] = get_the_title($old_data[$key]);
                }
                
                if (!isset($old_data[$key]) || trim($old_data[$key]) != trim($res)) {
                    $from = isset($old_data[$key]) ? trim($old_data[$key]) : '';  
                    if (!isset($old_data[$key]) && ($key == 'oz_clientEmail' || $key == 'oz_clientPhone')) {
                        $from = '';
                    }
                    if (trim($from) != trim($res))
                    $mess['changed'][] = [
                        'from' => $from,
                        'to' => $res,
                        'what' => isset($what[$key]) ? $what[$key] : '',
                        'what_string' => $key,
                    ];
                }
            }
        }
            
        if (get_option('oz_cust_fields')) {
            $opts = get_option('oz_cust_fields');
            $new_fields = isset($postarr['oz_custom_fields_post']) ? $postarr['oz_custom_fields_post'] : [];
            $old_vals = get_post_meta($postarr['ID'], 'oz_custom_fields_post', true);
            foreach ($opts as $key => $opt) {
                $old = isset($old_vals[$key]) && isset($old_vals[$key]['value']) ? $old_vals[$key]['value'] : '';
                $new = isset($new_fields[$key]) && isset($new_fields[$key]['value']) ? $new_fields[$key]['value'] : '';
                $new = is_array($new) && !is_array($old) ? implode(',',$new) : $new;
                if (maybe_serialize($old) !== maybe_serialize($new)) {
                    $mess['changed'][] = [
                        'from' => $old,
                        'to' => $new,
                        'what' => $new_fields[$key]['key'],
                        'what_string' => $opt['meta']
                    ];			
                }
            }
        }
            
            if ($mess && wp_get_current_user()->exists()) {
                $mess['who'] = [
                'name' => wp_get_current_user()->display_name,
                'id' => wp_get_current_user()->ID
                ];
            }
            if ($mess) {
                $mess['when'] = current_time('mysql');
            }
            $logs = get_post_meta($post->ID, 'oz_logs', true) ?: [];
            $logs[time()] = $mess;
            if ($mess) {
                update_post_meta($post->ID, 'oz_logs', $logs);
                $send_log = !current_user_can('administrator');
                if (apply_filters('book_oz_send_log_mess', $send_log, $post->ID) && $post->ID) {
                    $to = (get_option('oz_default_email')) ? get_option('oz_default_email') : get_option('admin_email');
                    $to = apply_filters('book_oz_send_log_to', $to, $post->ID);
                    $headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
                                'X-Mailer: PHP/' . phpversion();
                    add_filter('wp_mail_from',function($name) { return get_option('oz_email_from_email') ?: $name;},20,1);
                    add_filter('wp_mail_from_name',function($name) { return get_option('oz_default_email_name') ?: $name;},20,1);
                    wp_mail($to, __('There were changes in appointment', 'book-appointment-online'). ' '.$post->ID, apply_filters('book_oz_changes_logs_text', self::book_oz_changes_logs_text($mess), $mess, $post->ID), $headers);
                }
                do_action('book_oz_after_logs_updated', $post->ID, $mess);
            }
        
            return $data;
        }
        
        public static function book_oz_changes_logs_text($changes) {
            global $post;
            $mess = '';
            if (isset($changes['who']) && $changes['who']) $mess .= $changes['who']['name'].' '.__('at', 'book-appointment-online').' ';
            if (isset($changes['when'])) $mess .= date(get_option('date_format').' '.get_option('time_format'), strtotime($changes['when']));
            $mess .= ' '.__('changed', 'book-appointment-online').':';
            $mess .= "<br>";
            foreach ($changes['changed'] as $change) :
            $from = is_array(maybe_unserialize($change['from'])) ? implode(', ',maybe_unserialize($change['from'])) : $change['from'];
            $to = is_array(maybe_unserialize($change['to'])) ? implode(', ',maybe_unserialize($change['to'])) : $change['to'];
            $from = $from ?: __("Empty", 'book-appointment-online');
            $to = $to ?: __("Empty", 'book-appointment-online');
            $mess .= sprintf(__('%s : from %s to %s', 'book-appointment-online'), $change['what'], $from, $to). "<br>";
            endforeach;
            $mess .=  "<br>";
            $mess .= '<a href="'.get_edit_post_link($post->ID).'">'.__('Appointment', 'book-appointment-online').'</a>';
            return $mess;
        }

    /**
	 *  Check exist user or not
	 *  
	 *  @return error if exist
	 *  
	 *  @version 2.4.5
	 */
	public function check_user($res) {
		$user_email = isset($_POST['clientEmail']) && $_POST['clientEmail'] ? sanitize_email($_POST['clientEmail']) : '';
		$reg = isset($_POST['register_me']) && $_POST['register_me'];
		if ($reg && $user_email && get_user_by( 'email', $user_email )) {
			$res = ['error' => true, 'text' => __('User with this email address already exists. Try to sign in and book an appointment again', 'book-appointment-online')];
		}
		return $res;
	}

	/**
	 *  Check active or not this option
	 *  
	 *  @return true if active
	 *  
	 *  @version 2.2.9
	 */
	public function isEnableRegister() {
		return get_option('oz_customer_register');
	}

/**
	 *  Adding new user on booking
	 *  
	 *  @param int    $app_id Appointment id
	 *  @return void
	 *  
	 *  @version 2.2.9
	 */
	public function register($app_id) {
		if (!$this->isEnableRegister()) return;
		$reg = isset($_POST['register_me']) && $_POST['register_me'];
		if ($reg && get_role( 'oz_customer' )) {
			$email = get_post_meta($app_id, 'oz_clientEmail', true);
			$name = get_the_title($app_id);
			$phone = get_post_meta($app_id, 'oz_clientPhone', true);
				if ($email) {
				$userdata = array(
					'user_pass'       => wp_generate_password( 12, true), // обязательно
					'user_login'      => $email, // обязательно
					'user_email'      => $email,
					'first_name'      => $name,
					'role'            => 'oz_customer',
				);

				$user_id = wp_insert_user( apply_filters('book_oz_customer_data_register', $userdata) );
				if (!is_wp_error($user_id)) {
					if ($phone) {
						update_user_meta($user_id, 'oz_phone', $phone);
					}
					
					update_post_meta($app_id, 'oz_user_id', $user_id);
					
					do_action('book_oz_on_customer_added', $user_id, $userdata);
				}
				else {
					
				}
			}
		}
	}

    /**
	 *  Adding role oz_customer
	 *  
	 *  @return void
	 *  
	 *  @version 2.2.9
	 */
	public function add_role() {
		if (!$this->isEnableRegister()) return;
			if (!get_role('oz_customer')) {
				$caps = apply_filters('book_oz_customer_caps', array(
				'read' => 1,
				));
				add_role('oz_customer', __('Customer (Book an appointment online)', 'book-appointment-online'), $caps);
				update_option('oz_e_register', 1);
			}
	}
        
    /**
     * Show columns with user info in Clients
     *
     * @param  array $columns array with columns
     * @return array
     */
    public function book_oz_cpt_columns_user( $columns ) {
        if (get_option('oz_user_area')) {
        $columns["oz_user_td"] = __("User", 'book-appointment-online');
        $columns["oz_canceled_td"] = __("Canceled", 'book-appointment-online');
        }
        return $columns;
     }
     
     /**
      * Show columns with user info in Clients
      *
      * @param  string $colname colname
      * @param  int $cptid post id
      * @return void
      */
     public function book_oz_cpt_column_user($colname, $cptid) {
        if (!get_option('oz_user_area')) return;
         if ( $colname == 'oz_user_td') {  
             $id = get_post_meta( $cptid, 'oz_user_id', true );
             $user_info = ($id) ? get_userdata($id) : false;
             if ($user_info) {
                 echo '<a href="'.site_url().'/wp-admin/user-edit.php?user_id='.$id.'">'.$user_info->user_login.'</a>';
             }
         }
         
         if ( $colname == 'oz_canceled_td') {   
            $status = get_post_meta($cptid, 'canceled_by_user',true);
            if ($status)
            echo '<strong class="oz_red">'.esc_html($status).'</strong>';
         }
    }
    
    /**
     * Adding user ID to appointment when booking
     *
     * @param  int $ID appointment id
     * @param  array $POST post request
     * @return void
     */
    public function book_oz_add_field_userID($ID, $POST) {
        $user_id = (isset($POST['oz_user_id']) && $POST['oz_user_id'] == get_current_user_id() ) ? get_current_user_id() : 0;
    
        if ($user_id)  {
         update_post_meta($ID,'oz_user_id', $user_id);
        }
    }

        
    /**
     * Cancelling booking when user click on cancel link in user area
     *
     * @return void
     */
    public function book_oz_cancelBooking() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            if (isset($_POST)) {
                //print_r($_POST);
                $id = (int) ($_POST['id']);
                if (get_current_user_id() == get_post_meta($id,'oz_user_id',true)) {
                $status = ($_POST['type'] == 'cancel') ? __('Canceled', 'book-appointment-online') : __('Deleted', 'book-appointment-online');
                update_post_meta($id,'canceled_by_user',$status);
                if (wp_update_post(array('ID'    =>  $id, 'post_status'   =>  'draft'))) {
                    echo $status;
                    do_action('book_oz_on_canceled',$id,$status);
                    }
                }
            }
            
        }
        wp_die();
    }

        
    /**
     * Send email to admin when user cancelling appointment
     *
     * @param  int $id appointment id
     * @param  string $status appointment status
     * @return void
     */
    public function book_oz_on_sendCanceled($id,$status) {
        $headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();
        $email = (get_option('oz_default_email')) ? get_option('oz_default_email') : get_option('admin_email');
        add_filter('wp_mail_from',function($from_email) { $from_email = (get_option('oz_email_from_email')) ? get_option('oz_email_from_email') : $from_email; return $from_email;},20,1);
        add_filter('wp_mail_from_name',function($name) { $name = (get_option('oz_default_email_name')) ? get_option('oz_default_email_name') : get_bloginfo('name'); return $name;},20,1);
        ob_start(); 
        include_once(OZAPP_TEMPLATES_PATH.'emails/canceled_booking.php');
        $mess = ob_get_contents();
        ob_end_clean();
        wp_mail(apply_filters('book_oz_onUserCancelEmail', $email, $id),__('User canceled booking', 'book-appointment-online'), $mess,$headers);
    }

    public function oz_get_apps() {
        if (wp_doing_ajax() ) {
            $user_id = get_current_user_id();
            $nothing = '';
            $args = array(
                    'numberposts' => -1,
                    'orderby'     => 'date',
                    'order'       => 'DESC',
                    'include'     => array(),
                    'exclude'     => array(),
                    'meta_key'    => 'oz_user_id',
                    'meta_value'  =>$user_id,
                    'post_type'   => 'clients',
                );
            $args = apply_filters('book_oz_get_posts', $args);
            $posts = get_posts( $args );
            if (!$posts) {
                $nothing = __('You dont have any appointments', 'book-appointment-online');
            }
            $posts_sort = [];
            $newzap = 0;
            if ($posts) {
                        foreach ($posts as $post) : setup_postdata($post);
                        $spec = get_post_meta($post->ID,'oz_personal_field_id', true);
                        $spec = apply_filters('book_oz_WPML_id', $spec);
                        $name = get_the_title($spec);
                        $img = get_the_post_thumbnail_url($spec);
                        $img = ($img) ? $img : OZAPP_URL.'assets/images/pers-ava.svg';
                        $usl = get_post_meta($post->ID,'oz_uslug_set',true);
                        $usl = apply_filters('book_oz_WPML_id', $usl);
                        $usl_name = apply_filters('book_oz_userArea_uslTitle',get_the_title($usl),$usl);
                        $usl_time = apply_filters('book_oz_userArea_wTime',get_post_meta($usl, 'oz_serv_time',true),$usl);
                        $usl_price = apply_filters('book_oz_userArea_uslPrice',get_post_meta($post->ID, 'oz_order_sum',true),$usl);
                        $usl_price_string = get_option('oz_currency_position') == 'left' ? get_option('oz_default_cur').' '.$usl_price :  $usl_price.' '.get_option('oz_default_cur');
                        $date = get_post_meta($post->ID,'oz_start_date_field_id',true);
                        $time = get_post_meta($post->ID,'oz_time_rot',true);
                        $format = "d.m.Y H:i";
                        $startT = ($date && $time) ? DateTime::createFromFormat($format, $date.' '.$time)->format('U') : false;
                        if ($startT > current_time('U')) {
                            $newzap++;
                        }
                        $s_tz = (function_exists('wp_timezone_string')) ? wp_timezone_string() : book_oz_timezone_string();
                        $dt = DateTime::createFromFormat('d.m.Y H:i P', $date.' '.$time.' '.$s_tz);
                        $date = wp_date(get_option('date_format'),$dt->format('U'), $dt->getTimezone());
                        $time = apply_filters('book_oz_timeFormat',$dt->format('H:i'));

                        if ($startT) {
                            
                            // conference_url
                            $conference_url = '';
                            if (get_option('oz_conf_pageid') && get_post_meta($post->ID, 'oz_remote_id', true)) {
                                $link = get_permalink(get_option('oz_conf_pageid'));
                                $rem_id = get_post_meta($post->ID, 'oz_remote_id', true);
                                $url = parse_url($link);
                                $ap = isset($url['query']) && $url['query'] ? '&' : '?';
                                $conference_url = $link.$ap.'conference_id='.$rem_id;			
                            }
                            
                            $posts_sort[$startT] = array(
                                'id' => $post->ID,
                                'name' => $name,
                                'img' => $img,
                                'usl_name' => $usl_name,
                                'usl_time' => $usl_time.' '.__('min', 'book-appointment-online'),
                                'price' => $usl_price ? $usl_price_string : false,
                                'dateTime' => $date.' '.$time,
                                'future' => $startT > current_time('U'),
                                'timestamp' => $startT,
                                'link_text' => $startT > current_time('U') ? __('Cancel', 'book-appointment-online') : __('Delete', 'book-appointment-online'),
                                'conference_url' => $conference_url,
                            );
                            
                        }
                        endforeach; wp_reset_postdata();
            }
                echo json_encode([
                    'success' => true,
                    'payload' => $posts_sort,
                    ]);			
        }
    wp_die();
    }

    /**
	 * Add options to booking form
	 *
	 * @param  array $opts array with options
	 * @return array
	 */
    public function front_options($opts) {
        $opts['userarea'] = Updater::isPro() ? get_option('oz_user_area') : false;
        return $opts;
    }

}