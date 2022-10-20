<?php

/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2019 Ozplugin
 * @ver 3.1.0
 */

use Ozplugin\Appointment;
use Ozplugin\Updater;
use Ozplugin\Utils;

class oz_ShortcodeReplacer {
	public $shortcodes;
	public $current;
	public $id = 0;
	
	/**
	 *  @brief Init class instance
	 *  
	 *  @param [in] $id appointment id
	 *  @param [in] $Appointment oz_Appointment object
	 *  @return $this
	 *  
	 *  @details 3.0.7
	 */
	public function init($id = 0, $Appointment = null) {
		$this->Appointment = $Appointment instanceof Appointment ? $Appointment : null;
		if (!$Appointment) {
			$this->Appointment = new Appointment();
			$this->Appointment = $this->Appointment->getByID($id);
		}
		$this->AppointmentArray = $this->Appointment ? $this->Appointment->toArrayREST() : [];
		$this->id = $id;
		$this->set();
		do_action('book_oz_onShortcodesSet', $this);
		return $this;
	}
	
	/**
	 *  @brief set function/value to handle shortcode value
	 *  
	 *  @return void
	 *  
	 *  @details 3.0.7
	 */
	public function set() { 
			$this->shortcodes = [
			'%sitename%' => [
				'label' => __('Site URL', 'book-appointment-online'),
				'value' => site_url(),
				'group' => ['links'],
				],
			'%conference_url%' => [
				'label' => __('Conference URL', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['links'],
			],
			'%ID%' => [
				'label' => __('ID of booking (post ID)', 'book-appointment-online'),
				'value' => $this->id,
				'group' => ['main'],
			],
			'%id%' => [
				'label' => __('ID of booking (post ID)', 'book-appointment-online').' '.__('(deprecated)', 'book-appointment-online'),
				'value' => $this->id,
				'group' => ['main'],
			],
			'%name%' => [
				'label' => __('Client name', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['main', 'register'],
			],
			'%date%' => [
				'label' => __('Date booking', 'book-appointment-online'),
				'value' =>  [$this, 'getDate'],
				'group' => ['main'],
			],
			'%date_tz%' => [
				'label' => __('Date booking', 'book-appointment-online').' '.__('(in the customer\'s time zone)', 'book-appointment-online'),
				'value' =>  [$this, 'getDate', ['timezone' => true]],
				'group' => ['main'],
			],
			'%time%' => [
					'label' => __('Time booking', 'book-appointment-online'),
					'value' => [$this, 'getDate', ['time' => true]],
					'group' => ['main'],
			],
			'%time_tz%' => [
					'label' => __('Time booking', 'book-appointment-online').' '.__('(in the customer\'s time zone)', 'book-appointment-online'),
					'value' => [$this, 'getDate', ['time' => true, 'timezone' => true]],
					'group' => ['main'],
			],
			'%duration%' => [
				'label' => __('Appointment duration', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['main'],
				],
			'%end%' => [
				'label' => __('Appointment end', 'book-appointment-online'),
				'value' => [$this, 'getDate', ['dateTime' => true, 'end' => true]],
				'group' => ['main'],
				],
			'%email%' => [
				'label' => __('Client email', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['contacts'],
			],
			'%phone%' => [
				'label' => __('Client phone', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['contacts'],
			],
			'%employee%' => [
				'label' => __('Staff name', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['main'],
			],
			'%specialist%' => [
				'label' => __('Staff name', 'book-appointment-online').' '.__('(deprecated)', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['main'],
			],
			'%branch%' => [
				'label' => __('Branch', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['main'],
			],
			'%service%' => [
				'label' => __('Service name', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['main'],
			],
			'%total%' => [
				'label' => __('Amount', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['payment'],
				],
			'%payment%' => [
				'label' => __('Payment method', 'book-appointment-online'),
				'value' => [$this, 'get'],
				'group' => ['payment'],
				],
			'%cancel_url%' => [
				'label' => __('Cancel appointment link', 'book-appointment-online'),
				'value' => [$this, 'getCancelURL'],
				'group' => ['links'],
			],
			'%recurring_table%' => [
				'label' => __('List of all appointments if this appointment is recurring', 'book-appointment-online'),
				'value' => [$this, 'recurringTable'],
				'group' => ['recurring'],
			],
			'%appointment%' => [
				'label' => __('New Appointment status', 'book-appointment-online'),
				'value' => [$this, 'getStatus'],
				'group' => ['main'],
			],
			'%login%' => [
				'label' => __('Customer login', 'book-appointment-online'),
				'value' => null,
				'group' => ['register'],
			],
			'%pass%' => [
				'label' => __('Password', 'book-appointment-online'),
				'value' => null,
				'group' => ['register'],
			],
		];
		
		if (get_option('oz_cust_fields')) {
			foreach(get_option('oz_cust_fields') as $field) {
				$this->shortcodes['%field-'.$field['meta'].'%'] = [
				'label' => $field['name'],
				'value' => [$this, 'getCustomField'],
				'group' => 'custom',
				];
			}
		}
	}
	
	/**
	 *  @brief Add custom shortcode in action book_oz_onShortcodesSet
	 *  
	 *  @param [in] $shortcode array with shortcode data
	 *  @return void
	 *  
	 *  @details 3.0.7
	 */
	public function add($shortcode) {
		if (!is_array($shortcode) || !isset($shortcode['shortcode']) || !isset($shortcode['label']) || !isset($shortcode['value'])) return;
		$name = $shortcode['shortcode'];
		if (!isset($this->shortcodes[$name])) {
			$this->shortcodes[$name] = [
				'label' => $shortcode['label'],
				'value' => $shortcode['value'],
				'group' => isset($shortcode['group']) ? $shortcode['group'] : 'main',
			];
		}
	}
	
	/**
	 *  @brief Generate cancel_url
	 *  
	 *  @return string URL
	 *  
	 *  @details 3.0.7
	 */
	public function getCancelURL() {
		$app_code = hash('sha1', $this->id.'&'.get_post_meta($this->id,'oz_start_date_field_id',true).'&'.get_post_meta($this->id,'oz_time_rot',true));
		$cancel_url = site_url().'?oz_cancel='.$this->id.'&oz_cancel_code='.$app_code;
		return $cancel_url;
	}
	
	/**
	 *  @brief Get custom field of appointment
	 *  
	 *  @return string custom field value
	 *  
	 *  @details 3.0.7
	 */
	public function getCustomField() {
		if (!isset($this->custom_fields) && isset($this->AppointmentArray['custom_fields'])) {
			$this->custom_fields = [];
			foreach($this->AppointmentArray['custom_fields'] as $field) {
				$this->custom_fields['field-'.$field['meta']] = isset($field['value']) ? $field['value'] : '';
			}			
		}
		
		return isset($this->custom_fields) && isset($this->custom_fields[$this->current]) ? $this->custom_fields[$this->current] : '';
	}
	
	/**
	 *  @brief Create html table from recurring appointment
	 *  
	 *  @return html table
	 *  
	 *  @details 3.0.7
	 */
	public function recurringTable() {
		$list = [];
		$cont = '';
		$parent = get_post_meta($this->id, 'oz_first_recurring', true) ? $this->id : get_post_meta($this->id, 'oz_reccuring_parent', true);
			if ($parent || (isset($_POST) && isset($_POST['recurring']))) :
				if (isset($_POST) && isset($_POST['recurring'])) {
					// when booking just created
					$list = $this->recurTable_not_exist($this->id, $_POST['recurring']);
				}
				else {
					$list = $this->recurTable($parent, $this->id);
				}
				if ($list) {
					ob_start();
					echo '<ul>';
					$i = 0;
					$date_timezone = '';
					foreach($list as $li) {
						$i++;
						$ctz = get_post_meta($parent, 'oz_timezone',true);
						if ($ctz) {
							$minus = $ctz < 0 ? '-' : '+';
							$tz = new DateTime('today '.abs($ctz).' minutes');
							$tz = new DateTimeZone($minus.$tz->format('H:i'));	
							$s_tz = (function_exists('wp_timezone_string')) ? wp_timezone_string() : book_oz_timezone_string();
							$dt = DateTime::createFromFormat('d.m.Y H:i P', $li['date_time'].' '.$s_tz);
							$site_timezone = $dt->getTimezone();
							$dt = $dt->setTimezone($tz);
							$date_tz = wp_date(get_option('date_format'),$dt->format('U'), $dt->getTimezone());
							$time_tz = apply_filters('book_oz_timeFormat',$dt->format('H:i'));
							$date_timezone =  '('.$date_tz.' '.$time_tz.' '.$site_timezone->getName().')';
						}
						?>
						<li><span style="min-width:20px;display:inline-block;"><?php echo $i; ?>.</span> <?php echo $li['date'].' '.$li['time'].' '.$date_timezone; ?></li>
						<?php
					}
					echo '</ul>';
					$cont = ob_get_clean();
				}
			endif;
		return $cont;
	}
	
	/**
	 *  @brief Generate array with recurring data
	 *  
	 *  @param [in] $parent ID of parant appointment
	 *  @param [in] $current ID of current appointment
	 *  @return array with recurring appointment
	 *  
	 *  @details 3.0.7
	 */
	public function recurTable($parent, $current) {
		$recs = [];
		$args = array(
					'post_type' => 'clients',
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'meta_query' => array(
						'relation' => 'AND',
						array(
						'key' => 'oz_reccuring_parent',
						'value' => $parent,
						), 
					)
					);
		$rec = get_posts($args);
		$rec_par = get_post($parent);
		array_push($rec,$rec_par);
		if (count($rec)) {
			foreach($rec as $re) {
				$recs[] = [
					'ID' => $re->ID,
					'url' => get_edit_post_link( $re->ID),
					'date' => date_i18n( get_option('date_format'),strtotime(get_post_meta($re->ID,'oz_start_date_field_id',true) )),
					'time' => apply_filters('book_oz_timeFormat',get_post_meta($re->ID,'oz_time_rot',true)),
					'strtotime' => strtotime(get_post_meta($re->ID,'oz_start_date_field_id',true) ),
					'date_time' => get_post_meta($re->ID,'oz_start_date_field_id',true).' '.get_post_meta($re->ID,'oz_time_rot',true),
					'current' => $re->ID == $current,
					'parent' => $re->ID == $parent
				]; 
			}
		}
		return $recs;
	}
	
	/**
	 * Create array with recurring appointments on booking when recurring appointments still not created
	 *
	 * @param  int $parent id of main appointment
	 * @param  array $dates recurring appointment
	 * @return array
	 */
	public function recurTable_not_exist($parent, $dates) {
		$recs = [];
			foreach ($dates as $day) {
					$date = book_oz_validateDate($day['day']) ? $day['day'] : '';
					$time = book_oz_validateDate($day['time'],'H:i') ? $day['time'] : '';
					$recs[] = [
						'date' => date_i18n( get_option('date_format'),strtotime($date)),
						'time' => apply_filters('book_oz_timeFormat',$time),
						'strtotime' => strtotime($date),
						'date_time' => $date.' '.$time,
						'current' => false,
						'parent' => false,
						
					];
			}
		array_push($recs,[
				'date' => date_i18n( get_option('date_format'),strtotime(get_post_meta($parent,'oz_start_date_field_id',true) )),
				'time' => apply_filters('book_oz_timeFormat',get_post_meta($parent,'oz_time_rot',true)),
				'strtotime' => strtotime(get_post_meta($parent,'oz_start_date_field_id',true)),
				'date_time' => get_post_meta($parent,'oz_start_date_field_id',true).' '.get_post_meta($parent,'oz_time_rot',true),
				'current' => true,
				'parent' => true,			
		]);
		return $recs;
	}
	
	/**
	 *  @brief Get appointment date
	 *  
	 *  @return strign date or time
	 *  
	 *  @details 3.0.7
	 */
	public function getDate($params = []) {
		if (!isset($this->AppointmentArray['start']) || isset($params['end']) && !isset($this->AppointmentArray['end'])) return '';
		$from = isset($params['end']) && $params['end'] ? $this->AppointmentArray['end'] : $this->AppointmentArray['start'];
		$this->dateTime = DateTime::createFromFormat('Y-m-d\TH:i:sP', $from);
		if ($this->dateTime) {
			$format = get_option('date_format');
			if (isset($params['end'])) {
				$t = get_option('oz_time_format') ? 'g:i A' : 'H:i';
				$format = $format.' '.$t;
			}
			else {
				if (isset($params['time']) && $params['time']) {
					$format = get_option('oz_time_format') ? 'g:i A' : 'H:i';
				}	
			}
			if (isset($params['timezone']) && $params['timezone'] && get_post_meta($this->id, 'oz_timezone',true)) {
				if (!isset($this->dateTimeTZ) || !$this->dateTimeTZ) {
				$ctz = get_post_meta($this->id, 'oz_timezone',true);
				$minus = $ctz < 0 ? '-' : '+';
				$tz = new DateTime('today '.abs($ctz).' minutes');
				$tz = new DateTimeZone($minus.$tz->format('H:i'));	
				$s_tz = (function_exists('wp_timezone_string')) ? wp_timezone_string() : book_oz_timezone_string();
				$dt = $this->dateTime;
				$site_timezone = $dt->getTimezone();
				$this->dateTimeTZ = $dt->setTimezone($tz);
				}
				return wp_date($format,$this->dateTimeTZ->format('U'), $this->dateTimeTZ->getTimezone());
				//$time_tz = apply_filters('book_oz_timeFormat',$dt->format('H:i'));
			}
			return wp_date($format, $this->dateTime->getTimestamp());
		}
		return '';
	}
	
	/**
	 *  @brief Get appointment data
	 *  
	 *  @return string appointment value
	 *  
	 *  @details 3.0.7
	 */
	public function get() {
		$current = $this->current;
		switch($current) {
			case 'name':
			$current = 'title';
			break;
			case 'specialist':
			case 'employee':
			return isset($this->AppointmentArray['employee']) &&  isset($this->AppointmentArray['employee']['title']) ? $this->AppointmentArray['employee']['title'] : '';
			case 'branch':
			$id = isset($this->AppointmentArray['employee']) &&  isset($this->AppointmentArray['employee']['id']) ? $this->AppointmentArray['employee']['id'] : 0;
			$branches = get_the_terms( $id, 'filial');
			return $branches && !is_wp_error($branches) ? $branches[0]->name : '';
			case 'service':
			$services = isset($this->AppointmentArray['services']) &&  $this->AppointmentArray['services']['found'] ? array_column($this->AppointmentArray['services']['list'], 'title') : [];
			return  implode(', ', $services);
			case 'duration':
			$dur = isset($this->AppointmentArray['services']) &&  $this->AppointmentArray['services']['found'] ? $this->getDuration() : '';
			return  $dur;
			case 'total':
			$ans = isset($this->AppointmentArray['amount']) ? $this->AppointmentArray['amount']['total']  : '';
			return  $ans;
			case 'payment':
			$ans = isset($this->AppointmentArray['amount']) ? $this->AppointmentArray['amount']['paymentMethod']  : '';
			return  $ans;
		}
		return isset($this->AppointmentArray[$current]) ? $this->AppointmentArray[$current] : '';
	}
	
	/**
	 *  @brief Replace shortcodes on values
	 *  
	 *  @param [in] $mess String with shortcodes
	 *  @return string with replaced shortcodes
	 *  
	 *  @details 3.0.7
	 */
	public function replace($mess) {
		foreach($this->shortcodes as $scode => $val) {
			$this->current = str_replace('%', '', $scode);
			$params = is_array($val['value']) && isset($val['value'][2]) ? [$val['value'][2]] : []; 
			$value = is_array($val['value']) && method_exists($val['value'][0], $val['value'][1]) ? call_user_func_array([$this, $val['value'][1]], $params) : $val['value']; 
			$mess = str_replace($scode, $value, $mess);
		}
		return $mess;
	}
	
	public static function instance() {
		$self = new self;
		$self->init();
		return $self;
	}
		
	/**
	 * Return all shortcodes names with description and group
	 *
	 * @return array
	 */
	public function getNames() {
		$ret = [];
		$pro = ['%date_tz%', '%time_tz%', '%recurring_table%', '%cancel_url%', '%conference_url%'];
		foreach($this->shortcodes as $key => $code) {
			if ( in_array($key, $pro) && !Updater::isPro()) {
				continue;
			}
			$ret[$key] = [
				'label' => $code['label'],
				'group' => $code['group'],
			];
		}
		return $ret;
	}

	public function getStatus() {
		$status = isset($this->AppointmentArray['status']) ? $this->AppointmentArray['status'] : ''; 
		if ($status)  {
			$statuses = Utils::get_statuses();
			if (isset($statuses[$status])) {
				$status = $statuses[$status]['name'];
			}
		}
		return $status;
	}
	
	/**
	 * Get Appointment duration in minutes
	 *
	 * @return int
	 */
	private function getDuration() {
		$dur = 0;
		$durs = array_column($this->AppointmentArray['services']['list'], 'w_time'); 
		$durs = array_map('intval', $durs);
		$dur = array_sum($durs);
		return $dur;
	}
}