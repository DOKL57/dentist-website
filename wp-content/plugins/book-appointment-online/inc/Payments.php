<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
namespace Ozplugin;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Payments {

	public $methods = [];

	public function init() {
		$locally = new LocallyPayment();
		$locally->init();
		do_action('book_oz_addNewPaymentMethod', $this);
		add_action('init', [$this,'registerPostType']);
		add_action('register_book_oz_settings_advanced', array($this,'init_opts'));
		
		if (get_option('oz_payment')) {
		add_filter('manage_edit-clients_columns', array($this,'client_cols'));
		add_action('manage_clients_posts_custom_column', array($this,'client_col'), 10, 2); 
		add_action('book_oz_add_client_data',array($this,'add_sum'));
		add_action('book_oz_in_metabox',array($this,'post_client_data'),10,2);
		add_action('book_oz_add_fields_to_post', array($this, 'add_payment'), 10,2 );
		add_filter('book_oz_react_options', [$this, 'front_options']);
		}
	}

    public function registerPostType() {
        register_post_type( 'oz_payments',
		array(
			'labels' => array(
				'name' => __('Payments', 'book-appointment-online'),
				'singular_name' => __('Payment', 'book-appointment-online'),
				'add_new' => __('Add payment', 'book-appointment-online'),
				'add_new_item' => __('Add new payment', 'book-appointment-online'),
				'edit' => __('Edit payment', 'book-appointment-online'),
				'edit_item' => __('Edit payment', 'book-appointment-online'),
				'new_item' => __('New payment', 'book-appointment-online'),
				'view' => __('View payment', 'book-appointment-online'),
				'view_item' => __('View payment', 'book-appointment-online'),
				'search_items' => __('Search payment', 'book-appointment-online'),
				'not_found' => __('Payments not found', 'book-appointment-online'),
				'not_found_in_trash' => __('Payments not found in trash', 'book-appointment-online'),
				'parent' => __('Parent payment', 'book-appointment-online'),
			),
			'public' => false,
			'menu_position' => 7,
			'supports' => array( 'title', 'custom-fields' ),
			'menu_icon' => 'dashicons-money-alt',
			'has_archive' => false,
			'exclude_from_search' => true, 
			'publicly_queryable'  => false,
			'map_meta_cap'        => true,
			'capability_type'     => array('oz_payment','oz_payments'),
		)
	);
    }

	public function addPaymentMethod($method) {
			$this->methods = array_merge($this->methods, $method);
	}
	
	public function post_client_data($arg,$key) {
		if ($arg == 'book_oz_clientTime' && $key == 6 && isset($_GET['post'])) {
	$id = $_GET['post'];
	$sum = get_post_meta($id, 'oz_order_sum',true);
	$cur = get_option('oz_default_cur');
	$sum_string = get_option('oz_currency_position') == 'left' ? get_option('oz_default_cur').' '. $sum : $sum.' '.get_option('oz_default_cur');
	$p_type = get_post_meta($id,'oz_payment_method',true);
	$p_type = isset($this->methods[$p_type]) ? $this->methods[$p_type]['name'] : $p_type;	
	if ($sum) :
	$payment_id = get_post_meta($id, 'oz_capture_id', true);
			?>
			<tr>
				<td class="at-field " colspan="2">
					<div class="at-label">
						<label for="oz_payment_info"><?php  _e("Payment info", 'book-appointment-online'); ?></label>
					</div>
					<div>
					<?php _e("Amount", 'book-appointment-online'); ?>: <?php echo $sum_string; ?><br>
					<?php _e("Method", 'book-appointment-online'); ?>: <?php echo $p_type; ?>
					<?php if ($payment_id) : ?><br><?php _e("Payment ID", 'book-appointment-online'); ?>: <?php echo $payment_id; ?><?php endif; ?>
					</div>
				</td>
			</tr>
			<?php
		endif;
		}
	}
	
	public function add_sum($id) {
		if (isset($_POST) && isset($_POST['oz_order_sum'])) {
			$sum = strip_tags($_POST['oz_order_sum']);
			if (is_numeric($sum)) update_post_meta($id,'oz_order_sum', $sum);
			if (isset($_POST['oz_payment_method'])) {
				$p_type = sanitize_text_field($_POST['oz_payment_method']);
				update_post_meta($id,'oz_payment_method', $p_type);
			}
		}
	}
	
	public function client_cols( $columns ) {
    $columns["oz_payment_td"] = __("Payment info", 'book-appointment-online');
    return $columns;
	}

	public function client_col( $colname, $cptid ) {
	$sum = esc_html(get_post_meta($cptid, 'oz_order_sum',true));
	$cur = esc_html(get_option('oz_default_cur'));
	$sum_string = get_option('oz_currency_position') == 'left' ? $cur.' '. $sum : $sum.' '.$cur;
	$p_type = get_post_meta($cptid,'oz_payment_method',true) ?: 'locally';
	$p_name = isset($this->methods[$p_type]) ? $this->methods[$p_type]['name'] : $p_type;
     if ( $colname == 'oz_payment_td' && $sum) {
          echo  '<small>'.__("Amount", 'book-appointment-online').': '.$sum_string.'<br> '.__("Method", 'book-appointment-online').': '.$p_name.'</small>';
		 }
	}
	
	public function init_opts() {
		$this->options();
	}
	
	/* добавляем опции для payment */
	private function options() {
		$opt_list = array(
		'oz_payment',
		'oz_payment_method',
		'oz_paypal_account',
		'oz_paypal_sandbox',
		'oz_paypal_return_url',
		'oz_paypal_cancel_url',
		'oz_paypal_currency',
		'oz_advancedPayments',
		'oz_payment_locally'
		);
		foreach ($opt_list as $opt) {
		register_setting('book_oz_settings', $opt);
		}
	}
	
	/**
	 * todo insert add_payment before appointment created with appointment data - 
	 * todo to make an appointment manually if something goes wrong
	 * todo add payment statuses
	 * Insert post with payment data
	 *
	 * @param  int $app_id appointment id
	 * @param  array $data $_POST with appointment data
	 * @return void
	 */
	public function add_payment($app_id, $data) {
		if (!isset($data['oz_payment_method'])) return;
		$p_type = sanitize_text_field($data['oz_payment_method']);
		$sum = strip_tags($data['oz_order_sum']);
		$payment_id = get_post_meta($app_id, 'oz_payment_id', true);
		$postarr = [
			'post_title' => __('Appointment №', 'book-appointment-online').' '.$app_id,
			'post_status' => 'publish',
			'post_type' => 'oz_payments',
			'meta_input' => [
				'oz_payment_method' => $p_type,
				'oz_order_sum' => $sum,
				'oz_appointment_id' => $app_id,
				'oz_status' => 'success',
				'oz_payment_id' => $payment_id,
			]
		];
		wp_insert_post($postarr);
	}
	
	/**
	 * Add options to booking form
	 *
	 * @param  array $opts array with options
	 * @return array
	 */
	public function front_options($opts) {
			$i = 20;
			$select_names = [];
			$advPayments = get_option('oz_advancedPayments', []);
			$vals = [];
			$main = [];
			if (get_option('oz_payment_locally')) {
				$vals[] = 'locally';
				$select_names['locally'] = __('Locally', 'book-appointment-online');
			}
			if (get_option('oz_payment_method')) {
				$vals[] = 'paypal';
				$select_names['paypal'] = __('PayPal', 'book-appointment-online');
			}				
			foreach($advPayments as $key => $pm) {
				if (isset($pm['enable']) && $pm['enable']) {
					$vals[] = $key;
					if ($key == 'stripe') $select_names['stripe'] = __('Stripe', 'book-appointment-online');
					elseif($key == 'yandex') $select_names['yandex'] = __('Online Card (Yandex Kassa)', 'book-appointment-online');
				}
			}
			$main = [
				'order' => ($i*5) + 100,
				'name' => __('Payment method', 'book-appointment-online'),
				'type' => 'select',
				'meta' => 'oz_payment_method',
				'values' => implode("\n",$vals),
				'select_names' => $select_names,
				'required' => isset($field['req']) && $field['req'],
				'validation' => [],
				'classes' => []
			];
			//print_r(get_option('oz_advancedPayments'));
			array_push($opts['fields'], $main);
			array_multisort($opts['fields'], SORT_ASC, SORT_NUMERIC, array_column($opts['fields'], 'order'));
		return $opts;
	}
	
}