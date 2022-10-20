<?php 
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2019 Ozplugin
 * @Addon for Book an appoinment online PRO plugin
 * @plugin version 3.1.0
 * @addon version 1.0.0
 */

namespace Ozplugin\Addons;

use Ozplugin\Utils;
use \DateTime;
use \DateTimeZone;
use oz_ShortcodeReplacer;
use Ozplugin\Updater;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Email extends Addon  {

	const NAME = 'email';
	
	public function init() {
	$this->hasChanges = false;
	add_action('register_book_oz_settings_advanced', array($this,'init_opts'));
	add_action('book_oz_send_ok', array($this,'sending_emails'),11);
	add_filter('mce_external_plugins', array($this,'fullpage'));
	add_action( 'after_wp_tiny_mce', array($this,'script'),99 );
	add_action('wp_ajax_oz_def_email', array($this, 'default_template'));
	add_action('book_oz_send_ok',array($this,'email_reminder'));
	add_filter('book_oz_toclient_title',array($this,'toclientTitle'),11,2);
	add_filter('book_oz_toclient_mess',array($this,'toclientMess'),11,2);
	add_filter('book_oz_toadmin_title',array($this,'toadminTitle'),10,2);
	add_filter('book_oz_toadmin_mess',array($this,'toadminMess'),10,2);
	add_action( 'book_oz_EmailReminderBefore',array($this,'email_before'),10,2 );
	add_action( 'book_oz_EmailReminderBeforeEmp',array($this,'email_before_emp'),10,2 );
	add_action( 'book_oz_EmailReminderAfter',array($this,'email_after'),10,2 );
	add_action('register_book_oz_settings_advanced', array($this,'option_from_name'));
	add_action('book_oz_onAppointmentStatusChange', array($this,'emailUserOnStatusChange'),10,2);
	add_action('book_oz_on_customer_added', array($this,'emailonRegister'),10,2);
	add_filter('book_oz_email_types', array($this,'email_onStatus'),10,2);
	add_action('book_oz_description_email_shortcodes', array($this,'email_onStatus_shortcode_desc'),10,2);
	add_filter('book_oz_email_opt_list', array($this,'email_opt_list_status'),10,2);
	add_filter('book_oz_tags_fields',array($this,'emailStatusShortcode'),99,3);
	add_filter('book_oz_after_woo_change_status',array($this,'on_woo_change_status'),10,4);																				 
	add_action('book_oz_after_logs_updated',array($this,'on_app_updated'),10,2);																				 
	add_action('updated_post_meta',array($this,'on_rescheduled'),10,4);
	add_action('wp_insert_post', [$this,'on_publish'], 99,3 );
	}

	public function getOptions() {
		$templates = $this->email_types();
		$options = [];
		$i = 4;
		foreach ($templates as $id => $template) :
			if (!Updater::isPro() && $i > 5) continue;
			$temp_name = str_replace('oz_e_','',$id);
			$editor_id = $id.'_template_email';
			ob_start();
			wp_editor( $this->on_send_text($temp_name), $editor_id, array('textarea_name' => $id.'_template','editor_height' => 425,'wpautop' => false, 
			'tinymce' => [
				'forced_root_block' => false,
				//'valid_elements' => '*[*]',
				//'valid_elements' => 'head,html,body,meta,img[class=myclass|!src|border:0|alt|title|width|height|style]',
			], 
			'editor_css' => 0, 'editor_class' => 'oz_email_editor' ) );
			?>
			<div data-id="<?php echo $id; ?>_template_email" class="oz_set_defemail btn btn-primary btn-sm my-2"><?php _e('Load default template', 'book-appointment-online'); ?></div>
			<?php
			$editor = ob_get_clean();
			$switch = [
				'name' => $id,
				'value' => get_option($id),
				'type' => 'switch',
				'multiple' => false,
				'toggle' => true,
				'fields' => [
					[
						'title' => __('Email title', 'book-appointment-online'),
						'description' => '',
						'order' => 10,
						'fields' => [
							[
								'name' => $id.'_title',
								'value' => get_option($id.'_title', $template['title']),
								'type' => 'input',
								'multiple' => false,
								'values' => [],
							]
						]
					],
					[
						'title' => $template['description'],
						'description' => '',
						'order' => 20,
						'col' => 2,
						'grid' => 1,
						'fields' => [
							[
								'name' => $id.'_template',
								//'value' => get_option($id.'_template', $this->on_send_text($temp_name)),
								'value' => $editor,
								'type' => 'html',
								'multiple' => false,
								'values' => [],
							],
							[
								'name' => $id.'_codes',
								'value' => '',
								'type' => 'shortcodes',
								'multiple' => false,
							],
						]
					],
				]
					];
			
			if ($id == 'oz_e_remind' || $id == 'oz_e_thank' || $id == 'oz_e_remind_emp') :
				$remin = [
					'title' => __('How many minutes before the starting of the booking, send a reminder to the user?', 'book-appointment-online'),
					'description' => '',
					'order' => 30,
					'fields' => [
						[
						'name' => $id.'_min',
						'value' => get_option($id.'_min', 30),
						'type' => 'select',
						'multiple' => false,
						'values' => Utils::generateForSelect([30,60,120,240,1440], '', [], 'm'),
						]
					]
					
				];
				$switch['fields'] = array_merge(array_slice($switch['fields'], 0, 1), [$remin], array_slice($switch['fields'], 1));
			endif;

			$fields = [$switch];
			
				$options[] = [
				'title' => $template['name'],
				'description' => $template['description'],
				'order' => $i*10,
				'fields' => $fields,
			];
			$i++;
		endforeach;
		return [
			'email' => array_merge([
					[
						'title' => __('Email to', 'book-appointment-online'),
						'description' => __('One or more mailboxes separated by commas', 'book-appointment-online'),
						'order' => 10,
						'fields' => [
							[
								'name' => 'oz_default_email',
								'value' => get_option('oz_default_email', ''),
								'type' => 'input',
								'multiple' => false,
								'values' => [],
							],
						],
					],
					[
						'title' => __('From name (name)', 'book-appointment-online'),
						'description' => '',
						'order' => 20,
						'fields' => [
							[
								'name' => 'oz_default_email_name',
								'value' => get_option('oz_default_email_name', ''),
								'type' => 'input',
								'multiple' => false,
								'values' => [],
							],
						],
					],
					[
						'title' => __('From (email)', 'book-appointment-online'),
						'description' => __('E-mail must contain domain of your site. eg: ', 'book-appointment-online').'noreply@'.str_replace(array( 'http://', 'https://', 'www.' ), '', site_url()),
						'order' => 30,
						'fields' => [
							[
								'name' => 'oz_email_from_email',
								'value' => get_option('oz_email_from_email', ''),
								'type' => 'input',
								'multiple' => false,
								'values' => [],
							],
						],
					],
					
				], $options)
		];
	}

	public function options() {
		return [];
	}
	
	/**
	 *  Ex function sending default emails after booking
	 *  
	 *  @return void
	 *  
	 *  @version 2.0.2
	 */
	public function sending_emails($idKlienta) {
		$email = (get_option('oz_default_email')) ? get_option('oz_default_email') : get_option('admin_email');
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',array($this,'wp_mail_from_email'),20,1);
		add_filter('wp_mail_from_name',array($this,'wp_mail_from_name'),20,1);
		$email_mess = $this->email_template('to_admin',$idKlienta);
		$date = date_i18n(get_option('date_format'),strtotime(get_post_meta($idKlienta,'oz_start_date_field_id',true)));
		$title = apply_filters('book_oz_toadmin_title',__('New booking on ', 'book-appointment-online').$date,$idKlienta);
		$canSendAdmin = apply_filters('book_oz_notify_on_send_ok_admin', get_option('oz_e_admin'), $idKlienta);
		if ($canSendAdmin) {
		$email_mess = apply_filters('book_oz_toadmin_mess',$email_mess,$idKlienta);
		wp_mail($email,$title,$email_mess ,$headers);
		}
		$canSend = apply_filters('book_oz_notify_on_send_ok', true, $idKlienta);
		if ($canSend) {
		do_action('book_oz_after_email_toAdmin',$idKlienta,$title,$email_mess ,$headers);
		$email = get_post_meta($idKlienta,'oz_clientEmail',true);
		$email_mess = $this->email_template('to_client',$idKlienta);
		$title = apply_filters('book_oz_toclient_title',__('Thank you for booking', 'book-appointment-online'),$idKlienta);
		$email_mess = apply_filters('book_oz_toclient_mess',$email_mess,$idKlienta);
		if (apply_filters('book_oz_email_to_client', get_option('oz_e_before'), $idKlienta))
		wp_mail($email,$title, $email_mess,$headers);
		}
	}
	
	/**
	 *  Return default email template without styles
	 *  
	 *  @param string    $komy Sending email client or admin
	 *  @param int    $idKlienta Client ID
	 *  @return void
	 *  
	 *  @version 2.0.2 (ex. function book_oz_email_template)
	 */
	public function email_template($komy,$idKlienta) {
	ob_start(); 
		include_once(OZAPP_TEMPLATES_PATH.'emails/'.$komy.'.php');
		$mess = ob_get_contents();
		ob_end_clean();
	return $mess;
	}
		
	/**
	 * Deprecated - old function to replace shortcodes 
	 *
	 * @param  mixed $return email message
	 * @param  mixed $id appointment id
	 * @return string 
	 */
	public function fields($return,$id = 0) {
		if ($return == 'tags')  {
		$return = ["%sitename%", "%date%", "%time%", "%id%", "%specialist%", "%service%", "%total%", "%name%", "%phone%", "%duration%", "%email%", "%branch%", "%payment%", "%cancel_url%", '%date_tz%', '%time_tz%'];
		$return = apply_filters('book_oz_tags_fields', $return);
		}
		if ($return == 'values') {
		$spec_id = get_post_meta($id,'oz_personal_field_id',true);
		$title_spec = get_the_title($spec_id);
		$title_usl = apply_filters('book_oz_uslugi_uslTitle',get_the_title(get_post_meta($id,'oz_uslug_set',true)),get_post_meta($id,'oz_uslug_set',true));
		$total = get_post_meta($id, 'oz_order_sum',true);
		$phone = get_post_meta($id,'oz_clientPhone',true);
		$time = apply_filters('book_oz_timeFormat',get_post_meta($id,'oz_time_rot',true));
		$date = date_i18n(get_option('date_format'),strtotime(get_post_meta($id,'oz_start_date_field_id',true)));
		$date_tz = $date; 
		$time_tz = $time; 
		if (get_post_meta($id, 'oz_timezone',true)) {
			$ctz = get_post_meta($id, 'oz_timezone',true);
			$minus = $ctz < 0 ? '-' : '+';
			$tz = new DateTime('today '.abs($ctz).' minutes');
			$tz = new DateTimeZone($minus.$tz->format('H:i'));	
			$s_tz = (function_exists('wp_timezone_string')) ? wp_timezone_string() : book_oz_timezone_string();
			$dt = DateTime::createFromFormat('d.m.Y H:i P', get_post_meta($id,'oz_start_date_field_id',true).' '.get_post_meta($id,'oz_time_rot',true).' '.$s_tz);
			$site_timezone = $dt->getTimezone();
			$dt = $dt->setTimezone($tz);
			$date_tz = wp_date(get_option('date_format'),$dt->format('U'), $dt->getTimezone());
			$time_tz = apply_filters('book_oz_timeFormat',$dt->format('H:i'));
		}
		$uslugi_id = get_post_meta($id,'oz_uslug_set',true);
		if (strpos($uslugi_id,',') !== false) {
			$uslugi = explode(',',$uslugi_id);
			$duration = 0;
			foreach ($uslugi as $usluga) $duration = $duration + (int) get_post_meta($usluga,'oz_serv_time',true);
			
		}
		else {
			$duration = get_post_meta($uslugi_id, 'oz_serv_time',true);
		}
		$email = get_post_meta($id,'oz_clientEmail',true);
		$branches = get_the_terms( $spec_id, 'filial');
		$branch = $branches && !is_wp_error($branches) ? $branches[0]->name : '';
		$p_type = get_post_meta($id,'oz_payment_method',true);
		$p_types = array(
			'local' => 	__('Locally', 'book-appointment-online'),
			'paypal' => __('PayPal', 'book-appointment-online'),
			'stripe' => __('Stripe', 'book-appointment-online'),
			'yandex' => __('Online Card (Yandex Kassa)', 'book-appointment-online')
		);
		$p_type = isset($p_types[$p_type]) ? $p_types[$p_type] : '';
		$app_code = hash('sha1', $id.'&'.get_post_meta($id,'oz_start_date_field_id',true).'&'.get_post_meta($id,'oz_time_rot',true));
		$cancel_url = site_url().'?oz_cancel='.$id.'&oz_cancel_code='.$app_code;
		$return   = [site_url(), $date, $time, $id, $title_spec,$title_usl, $total, get_the_title($id),$phone, $duration, $email, $branch, $p_type, $cancel_url, $date_tz, $time_tz];
		$return = apply_filters('book_oz_tags_fields', $return, $id, 'values');
		}
		return $return;
	}
	
	public function toclientTitle($title, $idKlienta) {
		if (get_option('oz_e_before') && get_option('oz_e_before_title')) {
		$title = apply_filters('book_oz_toclientTitle',get_option('oz_e_before_title'));
		//$title = str_replace($this->fields('tags'), $this->fields('values',$idKlienta), $title);
		$title = oz_ShortcodeReplacer::instance()->init($idKlienta)->replace($title);
	}
		return $title;
	}
	
	public function toclientMess($email_mess, $idKlienta) {
		$email_mess = $this->on_send_text('before');
		if (get_option('oz_e_before') && $email_mess) {
		$email_mess = oz_ShortcodeReplacer::instance()->init($idKlienta)->replace($email_mess);
		//$email_mess = str_replace($this->fields('tags'), $this->fields('values',$idKlienta), $email_mess);
		}
		return $email_mess;
	}
	
	public function toadminTitle($title, $idKlienta) {
		if (get_option('oz_e_admin') && get_option('oz_e_admin_title')) {
		$title = get_option('oz_e_admin_title');
		$title = oz_ShortcodeReplacer::instance()->init($idKlienta)->replace($title);
		//$title = str_replace($this->fields('tags'), $this->fields('values',$idKlienta), $title);
		}
		return $title;
	}
	
	public function toadminMess($email_mess, $idKlienta) {
		$email_mess = $this->on_send_text('admin');
		if (get_option('oz_e_admin') && $email_mess) {
		$email_mess = oz_ShortcodeReplacer::instance()->init($idKlienta)->replace($email_mess);
		//$email_mess = str_replace($this->fields('tags'), $this->fields('values',$idKlienta), $email_mess);
		}
		return $email_mess;
	}
	
	// email reminder
	public function email_reminder($id) {
	if (!Updater::isPro()) return;
	/*
	$id - id клиента
	*/
	$uslId = ($id) ? get_post_meta($id,'oz_uslug_set',true) : false;
	$uslTime = 0;
	if (strpos($uslId, ',') !== false) {
		$uslId = explode(',',$uslId);
		foreach ($uslId as $uId) {
			$uslTime = $uslTime + get_post_meta($uId,'oz_serv_time',true);
		}
	}
	$uslTime = (!$uslTime) ? get_post_meta($uslId,'oz_serv_time',true) : $uslTime;
	$t = ($id) ? get_post_meta($id,'oz_start_date_field_id',true).' '.get_post_meta($id,'oz_time_rot',true) : '' ;
	$gmt = current_time( 'timestamp' ) - time();
	$time = DateTime::createFromFormat('d.m.Y H:i', $t);
	if (!$time) return;
	$time = $time->format('U') - $gmt;
	if (get_option('oz_e_remind')) {
		$min = get_option('oz_e_remind_min');
		$remBefore = $min*60; // за сколько минут напомнить
		wp_schedule_single_event( $time-$remBefore, 'book_oz_EmailReminderBefore',array($id, $time-$remBefore) ); // созданием cron отложенной отправки
	}
	if ($uslTime && get_option('oz_e_thank')) {
	$min = get_option('oz_e_thank_min');
	$min = $min*60; // через сколько минут после оказания услуги отправить
	$remAfter = $uslTime*60+$min+$time;
	wp_schedule_single_event( $remAfter, 'book_oz_EmailReminderAfter',array($id, $remAfter) );
	}
	
	if (get_option('oz_e_remind_emp')) {
		$min = get_option('oz_e_remind_emp_min');
		$remBefore = $min*60; // за сколько минут напомнить
		wp_schedule_single_event( $time-$remBefore, 'book_oz_EmailReminderBeforeEmp',array($id, $time-$remBefore) ); // созданием cron отложенной отправки
	}	
	}
	
	public function email_before($id, $timeSend) {
		/*
		$id - id client
		$timeSend - time when email will send
		*/
		$timeNow = current_time('timestamp',1);
		$deltatime = $timeNow - $timeSend;
		$status = get_post_meta($id,'oz_app_status', true);
		if (get_post_status($id) !== 'publish' || !get_option('oz_e_remind') || $deltatime > 300 || ($status && $status == 'canceled')) return;
		$email = get_post_meta($id,'oz_clientEmail',true);
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',array($this,'wp_mail_from_email'),20,1);
		add_filter('wp_mail_from_name',array($this,'wp_mail_from_name'),20,1);
		$email_mess = get_option('oz_e_remind_template');
		$email_mess = oz_ShortcodeReplacer::instance()->init($id)->replace($email_mess);
		$title = oz_ShortcodeReplacer::instance()->init($id)->replace(get_option('oz_e_remind_title'));
		//$email_mess = str_replace($this->fields('tags'), $this->fields('values',$id), $email_mess);
		//$title = str_replace($this->fields('tags'), $this->fields('values',$id), get_option('oz_e_remind_title'));
		wp_mail($email,$title,$email_mess,$headers);
	}
	
	public function email_before_emp($id, $timeSend) {
		/*
		$id - id client
		$timeSend - time when email will send
		*/
		$timeNow = current_time('timestamp',1);
		$deltatime = $timeNow - $timeSend;
		$status = get_post_meta($id,'oz_app_status', true);
		if (get_post_status($id) !== 'publish' || !get_option('oz_e_remind_emp') || $deltatime > 300 || ($status && $status == 'canceled')) return;
		$email = (get_option('oz_default_email')) ? get_option('oz_default_email') : get_option('admin_email');
		$emp_id = get_post_meta($id,'oz_personal_field_id',true);
		$emp_email = get_post_meta($emp_id,'oz_notification_email',true);
		if ($emp_email) $email = $email.','.$emp_email;
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',array($this,'wp_mail_from_email'),20,1);
		add_filter('wp_mail_from_name',array($this,'wp_mail_from_name'),20,1);
		$email_mess = get_option('oz_e_remind_emp_template');
		//$email_mess = str_replace($this->fields('tags'), $this->fields('values',$id), $email_mess);
		//$title = str_replace($this->fields('tags'), $this->fields('values',$id), get_option('oz_e_remind_emp_title'));
		$email_mess = oz_ShortcodeReplacer::instance()->init($id)->replace($email_mess);
		$title = oz_ShortcodeReplacer::instance()->init($id)->replace(get_option('oz_e_remind_emp_title'));
		wp_mail(apply_filters('book_oz_EmailReminderBeforeEmp_email',$email, $id),$title,$email_mess,$headers);
	}

	public function email_after($id, $timeSend) {
		/*
		$id - id client
		$timeSend - time when email will send
		*/
		$timeNow = current_time('timestamp',1);
		$deltatime = $timeNow - $timeSend;
		$status = get_post_meta($id,'oz_app_status', true);
		if (get_post_status($id) !== 'publish' || !get_option('oz_e_thank') || $deltatime > 600 || ($status && $status == 'canceled') ) return;
		$email = get_post_meta($id,'oz_clientEmail',true);
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',array($this,'wp_mail_from_email'),20,1);
		add_filter('wp_mail_from_name',array($this,'wp_mail_from_name'),20,1);
		$email_mess = get_option('oz_e_thank_template');
		$email_mess = str_replace($this->fields('tags'), $this->fields('values',$id), $email_mess);
		$title = str_replace($this->fields('tags'), $this->fields('values',$id), get_option('oz_e_thank_title'));		
		$email_mess = oz_ShortcodeReplacer::instance()->init($id)->replace($email_mess);
		$title = oz_ShortcodeReplacer::instance()->init($id)->replace(get_option('oz_e_thank_title'));
		wp_mail($email,$title,$email_mess,$headers);
	}
	
	/*add full html editor to tinymce*/
	public function fullpage ($plugins_array) {
		if (function_exists('get_current_screen')) {
			$screen = get_current_screen();
			if ($screen->parent_base == 'book-appointment-online/book-appointment-online') {
			 $plugins_array[ 'fullpage' ] = OZAPP_URL.'/assets/js/tinymcefullpage.min.js';
			}
		}
		 return $plugins_array;
	}
	
	public function init_opts() {
		$this->options();
	}
	
	public static function get_include_contents($filename) {
		if (is_file($filename)) {
			ob_start();
			include $filename;
			return ob_get_clean();
		}
		return false;
	}

	
	public function on_send_text($template_name = null, $default = false) {
		$def = $this->get_include_contents(OZAPP_TEMPLATES_PATH. 'email_'.$template_name.'.php');
		$text = $def;
		$tags = ["%sitename%"];
		$values   = [site_url()];
		if (!$default) {
			$text = get_option('oz_e_'.$template_name.'_template',$def);
		}
		$text = str_replace($tags, $values, $text);
		return $text;
	}
	
	public function default_template() {
		if (defined('DOING_AJAX') && DOING_AJAX) {
			if (isset($_POST['tmpName'])) {
				$tmpName = sanitize_text_field($_POST['tmpName']);
				echo $this->on_send_text($tmpName,true);
			}
		}
		wp_die();
	}
	
	public function script() {
		$current = get_current_screen();
		if (!$current || $current && $current->base != 'toplevel_page_'.'book-appointment-online/book-appointment-online') return;
		?>
		<script>
				jQuery('body').on('click', '.oz_set_defemail', function() {
					console.log('click')
		var id = jQuery(this).attr('data-id');
		var tmpName = id.replace('oz_e_','');
		var tmpName = tmpName.replace('_template_email','');
		if (tinymce.get(id) !== null) {
		jQuery.ajax( {
		url:'<?php echo admin_url( 'admin-ajax.php' ); ?>',
		data:{'action':'oz_def_email', 'tmpName': tmpName},
		method:'POST',
		success: function(response,status) {
			tinymce.get(id).setContent(response, {format: 'html'});
		},
		});
		}
		});
</script>
		<?php
	}
	
	public function email_types() {
		$emp = Updater::isPro() ? '('.(__('employee', 'book-appointment-online')).')' : '';
		$types = array(
			'oz_e_admin' => array(
			'name' => __('Email template about booking to the admin', 'book-appointment-online'). $emp,
			'description' => __('Email template when user has booked an appointment', 'book-appointment-online'),
			'title' => __('New booking on %date%', 'book-appointment-online')
			),
			'oz_e_before' => array(
			'name' => __('Email template on booking', 'book-appointment-online'),
			'description' => __('Email template when user has booked an appointment', 'book-appointment-online'),
			'title' => __('Thank you for booking', 'book-appointment-online')
			),
			'oz_e_remind' => array(
			'name' => __('Email template reminder', 'book-appointment-online'),
			'description' => __('Email template reminds the client about booking', 'book-appointment-online'),
			'title' => __('%name%, your appointment will start soon', 'book-appointment-online'),
			'minutes' => __('How many minutes before the starting of the booking, send a reminder to the user?', 'book-appointment-online')
			),
			'oz_e_remind_emp' => array(
			'name' => __('Email template reminder (to employee, admin)', 'book-appointment-online'),
			'description' => __('Email template reminds the employee and admin about booking', 'book-appointment-online'),
			'title' => __('Appointment %id% will start soon', 'book-appointment-online'),
			'minutes' => __('How many minutes before the starting of the booking, send a reminder to the user?', 'book-appointment-online')
			),
			'oz_e_rescheduled' => array(
			'name' => __('Email template about reschedule', 'book-appointment-online'),
			'description' => __('This email will be sent to the client if the date or time of appointments changes', 'book-appointment-online'),
			'title' => __('Appointment %id% rescheduled', 'book-appointment-online'),
			),
			'oz_e_thank' => array(
			'name' => __('Email template after booking', 'book-appointment-online'),
			'description' => __('Email template sending after booking', 'book-appointment-online'),
			'title' => __('%name%, thank you for visiting!', 'book-appointment-online'),
			'minutes' => __('How many minutes after the ending of the booking, send email to the user?', 'book-appointment-online')
			),
			'oz_e_register' => array(
			'name' => __('Email template on customer register', 'book-appointment-online'),
			'description' => __('Email template sending on customer register', 'book-appointment-online'),
			'title' => __('%name%, you registered!', 'book-appointment-online'),
			),
		);
		return apply_filters('book_oz_email_types',$types);
	}
	
	/**
	 *  Option to email - Email from name
	 *  
	 *  @return void
	 *  
	 *  @version 2.0.2
	 */
	public function option_from_name() {
		register_setting('book_oz_settings', 'oz_email_from_email');
	}
	
	/**
	 *  Change default value - email from
	 *  
	 *  @param string    $name Email from
	 *  @return Return email from
	 *  
	 *  @version 2.0.2
	 */
	public function wp_mail_from_email($name) {
		$name = (get_option('oz_email_from_email')) ? get_option('oz_email_from_email') : $name;
		return $name;
	}
	
	/**
	 *  Change default value - email from name
	 *  
	 *  @param string    $name Email from name
	 *  @return Return email from name
	 *  
	 *  @version 2.0.2
	 */
	public function wp_mail_from_name($name) {
		$name = (get_option('oz_default_email_name')) ? get_option('oz_default_email_name') : get_bloginfo('name');
		return $name;
	}
	
	/**
	 *  Add dropdown options in plugin settings for email on status change
	 *  
	 *  @param array    $types All dropdowns with email templates settings
	 *  @return array with email dropdowns settings
	 *  
	 *  @version 2.0.9
	 */
	public function email_onStatus($types) {
		$types['oz_e_onStatus'] = array(
			'name' => __('Email template on status change', 'book-appointment-online'),
			'description' => __('Email template sending on change appointment status', 'book-appointment-online'),
			'title' => __('%name%, we changed the status of your appointment', 'book-appointment-online'),
			);
		return $types;
	}
	
	/**
	 *  Description for new email shortocde
	 *  
	 *  @param string    $id name of email dropdown template
	 *  @return echo string
	 *  
	 *  @version 2.0.9
	 */
	public function email_onStatus_shortcode_desc($id) {
	if ('oz_e_onStatus' == $id) 
	echo "<code>%appointment%</code> - ".__('New Appointment status', 'book-appointment-online')."<br><br>";
	}
	
	/**
	 *  Settings for email status
	 *  
	 *  @param array    $opts array of email options
	 *  @return email marketing options
	 *  
	 *  @version 2.0.9
	 */
	public function email_opt_list_status($opts) {
	$eopts = array(
		'oz_e_onStatus',
		'oz_e_onStatus_template',
		'oz_e_onStatus_title',
	);
	$opts = array_merge($opts, $eopts);
		return $opts;
	}	
	
	/**
	 *  Email shortocde %appointment%
	 *  
	 *  @param array    $return Shortcode name or value (if 3rd params exist)
	 *  @param array    $id appointment id
	 *  @param array    $val return as shosrtocde or as value (if 'values')
	 *  @return shortcodes or values
	 *  
	 *  @version 2.0.9
	 */
	public function emailStatusShortcode($return, $id = 0, $val = '') {
		if ($val == 'values') {
		$appointment = get_post_meta($id,'oz_app_status',true);
        $values = array(
				'approved' => __("Approved", 'book-appointment-online'),
				'onhold' => __("On hold", 'book-appointment-online'),
				'canceled' => __("Canceled", 'book-appointment-online')
        );
		if (isset($values[$appointment])) array_push($return, $values[$appointment]);
		}
		else {
		array_push($return, "%appointment%");
		}
		return $return;
	}
	
	/**
	 *  Sending email on status change
	 *  
	 *  @param int    $id Appointment id
	 *  @param array    $params $_GET params
	 *  @return void
	 *  
	 *  @version 2.0.9
	 */
	public function emailUserOnStatusChange($id, $params) {
		if (!get_option('oz_e_onStatus')) return;
		if (isset($params['oz_notify_email']) && $params['oz_notify_email'] == 'y' && get_option('book_oz_enable_statuses')) {
		$email = get_post_meta($id,'oz_clientEmail',true);
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',array($this,'wp_mail_from_email'),20,1);
		add_filter('wp_mail_from_name',array($this,'wp_mail_from_name'),20,1);
		$email_mess = get_option('oz_e_onStatus_template');
		// $values = $this->fields('values',$id);
		// foreach ($values as $key => $vals) : 
		// if (is_array($vals)) {
		// 	$values[$key] = implode(', ', $vals);
		// }
		// endforeach;
		//$email_mess = str_replace($this->fields('tags'), $values, $email_mess);
		$title = get_option('oz_e_onStatus_title');
		//$title = str_replace($this->fields('tags'), $values,$title);
		$email_mess = oz_ShortcodeReplacer::instance()->init($id)->replace($email_mess);
		$title = oz_ShortcodeReplacer::instance()->init($id)->replace($title);
		wp_mail($email,$title,$email_mess,$headers);
		}
	}
	
	/**
	 *  Sending email on customer register
	 *  
	 *  @param int    $id User id
	 *  @param array    $params User register data
	 *  @return void
	 *  
	 *  @version 2.2.9
	 */
	public function emailonRegister($id, $params) {
		if (!get_option('oz_customer_register') && !Updater::isPro()) return;
		$email = $params['user_email'];
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',array($this,'wp_mail_from_email'),20,1);
		add_filter('wp_mail_from_name',array($this,'wp_mail_from_name'),20,1);
		$tags = array(
		'%name%',
		'%login%',
		'%pass%',
		);
		$values = array(
		$params['first_name'],
		$params['user_email'],
		$params['user_pass'],
		);
		$email_mess = get_option('oz_e_register_template');
		$email_mess = str_replace($tags, $values, $email_mess);
		$title = get_option('oz_e_register_title');
		$title = str_replace($tags, $values,$title);
		wp_mail($email,$title,$email_mess,$headers);
	}
	
	public function on_woo_change_status($app_id, $post_type, $app_status, $woo_status) {
		$opts = get_option('oz_woocommerce_options');
		$send_status = isset($opts['send_status']) ? $opts['send_status'] : '';
		if (str_replace('wc-', '',$send_status) == $woo_status) {
		add_filter('book_oz_notify_on_send_ok_admin', '__return_true',11);
		add_filter('book_oz_notify_on_send_ok', '__return_true',11);
		$this->sending_emails($app_id);
		}
	}

	public function on_app_updated($app_id, $logs) {
		if (isset($logs['changed']) && isset($logs['changed']) ) {
			$dateIndex = array_search('oz_start_date_field_id', array_column($logs['changed'], 'what_string'));
			$timeIndex = array_search('oz_time_rot', array_column($logs['changed'], 'what_string'));
			$custom_condition = apply_filters('book_oz_on_app_updated_condition', false, $app_id, $logs);
			if (false !== $dateIndex || false !== $timeIndex || $custom_condition) {
				$this->hasChanges = true;
				if (isset($logs['who']) && isset($logs['who']['id']) && $logs['who']['id'] == 0) { // google calendar
					$this->on_rescheduled(false, $app_id, 'oz_time_rot');
				}
			}
		}
	}
	
	public function on_rescheduled($meta_id, $app_id, $meta_key, $meta_value = '') {
		if (!$this->hasChanges) return;
			if (get_option('oz_e_rescheduled') && in_array($meta_key, ['_edit_lock', 'oz_start_date_field_id', 'oz_time_rot'])) {
				$email = get_post_meta($app_id,'oz_clientEmail',true);
				$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
							'X-Mailer: PHP/' . phpversion();
				add_filter('wp_mail_from',array($this,'wp_mail_from_email'),20,1);
				add_filter('wp_mail_from_name',array($this,'wp_mail_from_name'),20,1);
				$email_mess = get_option('oz_e_rescheduled_template');
				//$email_mess = str_replace($this->fields('tags'), $values, $email_mess);
				$title = get_option('oz_e_rescheduled_title');
				//$title = str_replace($this->fields('tags'), $values,$title);
				$email_mess = oz_ShortcodeReplacer::instance()->init($app_id)->replace($email_mess);
				$title = oz_ShortcodeReplacer::instance()->init($app_id)->replace($title);
				wp_mail($email,$title,$email_mess,$headers);
			}
	}
	
	public function on_publish($idKlienta, $post, $update) {
		if ($post && $post->post_type == 'clients') {
			$isNewPost = isset($_POST) && isset($_POST['original_post_status']) && in_array($_POST['original_post_status'], ['pending', 'draft', 'auto-draft']) && $post->post_status == 'publish';
			if (isset($_POST) && isset($_POST['oz_notify_by_email']) && $_POST['oz_notify_by_email'] && $isNewPost) {
				add_filter('book_oz_notify_on_send_ok_admin', '__return_false');
				$this->sending_emails($idKlienta);
				$this->email_reminder($idKlienta);
			}		
		}
	}
	
	/**
	 * Create email reminders
	 *
	 * @param  int $idKlienta appointment id
	 * @return void
	 */
	public function addReminder($idKlienta = 0) {
		$id = $idKlienta ? $idKlienta : $this->id; 
		$this->email_reminder($id);
		add_action( 'book_oz_EmailReminderBefore',[$this,'email_before'],10,2 );
		add_action( 'book_oz_EmailReminderBeforeEmp',[$this,'email_before_emp'],10,2 );
		add_action( 'book_oz_EmailReminderAfter',[$this,'email_after'],10,2 );
	}
	
	/**
	 * send Email to client
	 *
	 * @param  int $idKlienta appointment id
	 * @return void
	 */
	public function toClient($idKlienta) {
		$this->id = $idKlienta;
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',[$this,'wp_mail_from_email'],20,1);
		add_filter('wp_mail_from_name',[$this,'wp_mail_from_name'],20,1);
		$email = get_post_meta($idKlienta,'oz_clientEmail',true);
		$email_mess = $this->email_template('to_client',$idKlienta);
		$title = apply_filters('book_oz_toclient_title',__('Thank you for booking', 'book-appointment-online'),$idKlienta);
		$email_mess = apply_filters('book_oz_toclient_mess',$email_mess,$idKlienta);
		if (apply_filters('book_oz_email_to_client', get_option('oz_e_before'), $idKlienta))
		$send_ok = wp_mail($email,$title, $email_mess,$headers);
	}

	/**
	 *  Sending email with custom text
	 *  
	 *  @param string    $to email address
	 *  @param string    $title email title
	 *  @param string    $mess email message
	 *  @param int    $app_id appointment id optional
	 *  @return void
	 *  
	 *  @version 3.0.5
	 */
	public function mail($to, $title, $mess, $app_id = 0) {
		if (!$to || !$title || !$mess) return;
		$title = sanitize_text_field($title);
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',array($this,'wp_mail_from_email'),20,1);
		add_filter('wp_mail_from_name',array($this,'wp_mail_from_name'),20,1);
		$tags = array(
		'%sitename%',
		'%email_title%',
		'%email_text%',
		);
		$values = array(
		site_url(),
		$title,
		sanitize_text_field($mess),
		);
		$email_mess = $this->get_include_contents(OZAPP_TEMPLATES_PATH.'email_custom_text.php');
		$email_mess = str_replace($tags, $values, $email_mess);
		$title = str_replace($tags, $values,$title);
		
		if ($app_id) {
			$email_mess = oz_ShortcodeReplacer::instance()->init($app_id)->replace($email_mess);
			$title = oz_ShortcodeReplacer::instance()->init($app_id)->replace($title);
		}

		wp_mail($to,$title,$email_mess,$headers);
	}
}

class BAO_Email_New extends Email {
	public $id = 0;
	function __construct() {
		
	}

	public function getOptions() {
		return [];
	}
	
	public function toClient($idKlienta) {
		$this->id = $idKlienta;
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',[$this,'wp_mail_from_email'],20,1);
		add_filter('wp_mail_from_name',[$this,'wp_mail_from_name'],20,1);
		$email = get_post_meta($idKlienta,'oz_clientEmail',true);
		$email_mess = $this->email_template('to_client',$idKlienta);
		$title = apply_filters('book_oz_toclient_title',__('Thank you for booking', 'book-appointment-online'),$idKlienta);
		$email_mess = apply_filters('book_oz_toclient_mess',$email_mess,$idKlienta);
		if (apply_filters('book_oz_email_to_client', get_option('oz_e_before'), $idKlienta))
		$send_ok = wp_mail($email,$title, $email_mess,$headers);
	}
	
	public function addReminder($idKlienta = 0) {
		$id = $idKlienta ? $idKlienta : $this->id; 
		$this->email_reminder($id);
		add_action( 'book_oz_EmailReminderBefore',[$this,'email_before'],10,2 );
		add_action( 'book_oz_EmailReminderBeforeEmp',[$this,'email_before_emp'],10,2 );
		add_action( 'book_oz_EmailReminderAfter',[$this,'email_after'],10,2 );
	}
	
	/**
	 *  Sending email on register
	 *  
	 *  @param int    $id User id
	 *  @param array    $params User register data
	 *  @return void
	 *  
	 *  @version 3.0.5
	 */
	public function emailOnRegister($id, $params) {
		$email = $params['user_email'];
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',array($this,'wp_mail_from_email'),20,1);
		add_filter('wp_mail_from_name',array($this,'wp_mail_from_name'),20,1);
		$tags = array(
		'%name%',
		'%login%',
		'%pass%',
		);
		$values = array(
		$params['first_name'],
		$params['user_email'],
		$params['user_pass'],
		);
		$email_mess = get_option('oz_e_register_template');
		$email_mess = str_replace($tags, $values, $email_mess);
		$title = get_option('oz_e_register_title');
		$title = str_replace($tags, $values,$title);
		wp_mail($email,$title,$email_mess,$headers);
	}

	/**
	 *  Sending email with custom text
	 *  
	 *  @param string    $to email address
	 *  @param string    $title email title
	 *  @param string    $mess email message
	 *  @return void
	 *  
	 *  @version 3.0.5
	 */
	public function mail($to, $title, $mess, $app_id = 0) {
		if (!$to || !$title || !$mess) return;
		$title = sanitize_text_field($title);
		$headers = 	'Content-Type: text/html; charset=utf-8' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();
		add_filter('wp_mail_from',array($this,'wp_mail_from_email'),20,1);
		add_filter('wp_mail_from_name',array($this,'wp_mail_from_name'),20,1);
		$tags = array(
		'%sitename%',
		'%email_title%',
		'%email_text%',
		);
		$values = array(
		site_url(),
		$title,
		sanitize_text_field($mess),
		);
		$email_mess = $this->get_include_contents(OZAPP_TEMPLATES_PATH.'email_custom_text.php');
		$email_mess = str_replace($tags, $values, $email_mess);
		$title = str_replace($tags, $values,$title);
		wp_mail($to,$title,$email_mess,$headers);
	}
}

 ?>