<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2019 Ozplugin
 * @ver 3.0.9
 */
 
namespace Ozplugin;
 

 
 class Ajax {
	 public static function get_employees($arg = []) {
		 	global $oz_theme;
			$args = array_merge([
				'post_type' => 'personal',
				'post_status' => 'publish',
				'posts_per_page'   => -1,
			], $arg);
			$query = new \WP_Query( $args );
			$list = [];
			//foreach ($query->posts as $serv) {
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) { $query->the_post();
					$id = get_the_id();
					$thumb_size = $oz_theme == 'neumorph' ? 'thumb-neu' : 'thumbnail';
					$img = (get_the_post_thumbnail_url($id,$thumb_size)) ?: OZAPP_URL. 'assets/images/pers-ava.svg';
					
					$services = get_post_meta($id, 'oz_re_timerange',true) ?: [];
					if ($services) {
						$services = array_map('intval', array_column($services, 'oz_personal_serv_name'));
					}
					$filials = get_the_terms( $id, 'filial' );
					$json_apps = get_post_meta($id,'oz_clientsarray', true);
					$apps = $json_apps ? json_decode($json_apps,1) : [];
					$future_apps = [];
					if (count($apps)) {
						foreach($apps as $app) {
							if (strtotime($app['start'].' '.wp_timezone_string()) >= strtotime("today", time()))
							$future_apps[] = $app; 
						}
					}
					$isOwner = current_user_can('administrator') || get_the_author_meta('ID') == get_current_user_id();
					$list[] = [
						'id' => (int) $id,
						'title' => get_the_title(),
						'description' => apply_filters( 'the_content', get_the_content() ),
						'occupation' => get_post_meta($id,'oz_specialnost', true),
						'img' => $img,
						'url' => get_permalink(),
						'timeslot' => (int) get_post_meta($id,'oz_ind_timeslot', true),
						'schedule' => json_decode(get_post_meta($id,'oz_raspis', true),1),
						'apps' => $future_apps,
						'breaks' => get_post_meta($id,'oz_breaklist', true) ? json_decode(get_post_meta($id,'oz_breaklist', true),1) : [],
						'services' => $services,
						'services_type' => get_post_meta($id, 'oz_book_provides_services',true),
						'daysOff' => get_post_meta($id, 'oz_days_off_list',true) ? explode(',',get_post_meta($id, 'oz_days_off_list',true)) : [],
						'cats' => $filials ? array_column($filials, 'term_id') : [],
						'email' => $isOwner ? get_post_meta($id,'oz_notification_email', true) : '',
						'phone' => $isOwner ? get_post_meta($id,'oz_notification_sms', true) : '',
						'active' => get_post_status() == 'publish'
					];					
				}
			}
			
			wp_reset_postdata();
			//}
			return [
				'found' => $query->found_posts,
				'list' => $list,
				'args' => $args,
			];	 
	 }
	 
	 public static function get_services($arg = []) {
	 
		$args = array_merge([
			'post_type' => 'services',
			'post_status' => 'publish',
			'posts_per_page'   => -1,
			], $arg);
			$query = new \WP_Query( $args );
			$list = [];
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) { $query->the_post();
			//foreach ($query->posts as $serv) {
				$id = get_the_id();
				$before = (int) get_post_meta($id,'oz_serv_buffer_before',true) ?: 0;
				$after = (int) get_post_meta($id,'oz_serv_buffer',true) ?: 0;
				$rep = get_post_meta($id, 'oz_recurring', true);
				$recurring = [
					'repeat' => $rep,
					'pay' => get_post_meta($id, 'oz_rec_pay', true),
					'min' => get_post_meta($id, 'oz_rec_min', true),
					'max' => get_post_meta($id, 'oz_rec_max', true),
				];
				$cats = get_the_terms( $id, 'oz_service_cats' );
				$price = (float) get_post_meta($id, 'oz_serv_price', true);
				$deposit_type = get_post_meta($id, 'oz_serv_deposit_type', true);
				$deposit_value = (float) get_post_meta($id, 'oz_serv_deposit', true);
				$list[] = [
					'id' => (int) $id,
					'title' => get_the_title(),
					'description' => apply_filters( 'the_content', get_the_content() ),
					'price' => $price,
					'deposit' => [
						'type' => $deposit_type,
						'value' => $deposit_type == 'percent' ? round(($price * $deposit_value / 100),2) : $deposit_value,
						'percent' => $deposit_type == 'percent' ? (int) get_post_meta($id, 'oz_serv_deposit', true) : 0 
					],
					'w_time' => (int) get_post_meta($id, 'oz_serv_time', true),
					'buffer' => [$before, $after],
					'recurring' => $rep ? $recurring : false,
					'isRemote' => get_post_meta($id, 'oz_is_remoted', true),
					'badge' => get_post_meta($id, 'oz_badge', true),
					'cats' => $cats ? array_column((array) $cats, 'term_id') : []
				];
			//}
				}
			}
			wp_reset_postdata();
			return [
				'found' => $query->found_posts,
				'list' => $list,
				'args' => $args
			];	
		}
 }