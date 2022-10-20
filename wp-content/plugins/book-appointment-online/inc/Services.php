<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.0.9
 */
namespace Ozplugin;
//use Ozplugin\Settings;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Services {
    public function init() {
        add_action('init', [$this, 'registerPostType']);
		add_action('init', [$this, 'add_metabox']);
    }

    public function registerPostType() {

    register_post_type( 'services',
		[
			'labels' => array(
				'name' => __('Services', 'book-appointment-online'),
				'singular_name' => __('Service', 'book-appointment-online'),
				'add_new' => __('Add service', 'book-appointment-online'),
				'add_new_item' => __('Add service', 'book-appointment-online'),
				'edit' => __('Edit service', 'book-appointment-online'),
				'edit_item' => __('Edit service', 'book-appointment-online'),
				'new_item' => __('New service', 'book-appointment-online'),
				'view' => __('View service', 'book-appointment-online'),
				'view_item' => __('View service', 'book-appointment-online'),
				'search_items' => __('Search service', 'book-appointment-online'),
				'not_found' => __('Services not found', 'book-appointment-online'),
				'not_found_in_trash' => __('Service not found', 'book-appointment-online'),
				'parent' => __('Parent service', 'book-appointment-online'),
			),
			'public' => true,
			'menu_position' => 6,
			'supports' => array( 'title','editor' ),
			'menu_icon' => 'dashicons-backup',
			'has_archive' => false,
			'exclude_from_search' => true, 
			'publicly_queryable'  => false,
			'map_meta_cap'        => true,
			'capability_type'     => array('service','services'),
        ]
        );
    }

	function add_metabox() {
		$prefix = 'oz_';
		if (is_admin()){
			$servConf = array(
				'id'             => 'book_oz_service',
				'title'          => __('Service params', 'book-appointment-online'),
				'pages'          => array('services'),
				'context'        => 'normal',
				'priority'       => 'high',
				'fields'         => array(),         
				'local_images'   => false,
				'use_with_theme' => false,
				'callback'		 => 'book_oz_service'
			  );
			  
			  $time = array();
			  $dur = 15;
			  for ($i=0; $i <= 15;$i++) {
				  switch ($i) {
					  case 13 :
					  $minutes = 240;
					  break;
					  case 14 :
					  $minutes = 360;
					  break;
					  case 15 :
					  $minutes = 480;
					  break;
					  default :
					  $minutes = $i*$dur;
				  }
				  $time[$minutes] = apply_filters('oz_service_duration',$minutes);
			  }
			  $time = apply_filters('oz_service_duration',$time);
				 $personalC =  new \AT_Meta_Box($servConf);
				$personalC->addText($prefix.'serv_price',array('name'=> __('Price', 'book-appointment-online'), 'desc' => __('Decimal separator is point', 'book-appointment-online')));
				if (get_option('oz_engine') == 'react') {
				$personalC->addNumber($prefix.'serv_deposit',array('name'=> __('Deposit', 'book-appointment-online'), 'std' => 100, 'style' => 'width:100%;', 'inGroup' => true));
				$personalC->addSelect($prefix.'serv_deposit_type',['percent' => __('Percent', 'book-appointment-online'), 'amount' => __('Fixed amount', 'book-appointment-online')], array('name'=> __('Deposit type', 'book-appointment-online'),'style' => 'width:100%;', 'inGroup' => true));
				}
				$personalC->addSelect($prefix.'serv_time',$time, array('name'=> __('Service time', 'book-appointment-online'),'style' => 'width:100%;'));
				$personalC->addSelect($prefix.'serv_buffer_before',$time, array('name'=> __('Buffer time before', 'book-appointment-online'),'style' => 'width:100%;', 'inGroup' => true, 'desc' => __('Time needed to prepare for the appointment', 'book-appointment-online')));
				$personalC->addSelect($prefix.'serv_buffer',$time, array('name'=> __('Buffer time after', 'book-appointment-online'),'style' => 'width:100%;', 'inGroup' => true, 'desc' => __('Time needed after appointment', 'book-appointment-online')));
				$personalC->addText($prefix.'badge',array('name'=> __('Badge', 'book-appointment-online')));
				do_action('oz_add_fields_service', $personalC);
				$personalC->Finish(); 			
		}
	}
}