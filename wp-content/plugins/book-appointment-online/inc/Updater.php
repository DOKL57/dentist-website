<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */

namespace Ozplugin;

use \stdClass;
   
class Updater
{
    /**
     * The plugin current version
     * @var string
     */
    public $current_version;
	
    /**
     * Plugin Slug (plugin_directory/plugin_file.php)
     * @var string
     */
    public $plugin_slug = 'book-appointment-online/book-appointment-online.php';
	
    /**
     * Plugin name (plugin_file)
     * @var string
     */
    public $slug = 'book-appointment-online';
	
	public $update_path = 'http://oz-plugin.ru/?do_update=plugin';
	
    /**
     * Licence key
     * @var string
     */
    public $licence_key;

	

 
    /**
     * Initialize a new instance of the WordPress Auto-Update class
     * @param string $current_version
     * @param string $update_path
     * @param string $plugin_slug
     */
    public function init()
    {
		
		add_action('book_oz_add_main_options', array($this,'purchase_code_option'));
		add_action('wp_ajax_oz_activate', array($this,'activate_plugin'));
		add_action('current_screen', array($this,'plugin_version'));
		add_filter('plugins_api', array(&$this, 'check_info'), 10, 3);
		add_action('activated_plugin', array($this, 'check_updated'), 10, 3);
		add_filter('book_oz_menu_page_func', array($this, 'check_page'), 10, 3);
		add_action('in_plugin_update_message-'.$this->plugin_slug,array($this,'expired_support_mess'),10,2);
		add_filter( 'cron_schedules', [$this,'cron_weekly'] );
		add_action('oz_updater_check',array($this,'schedule'),10,2);
    }
	
	 /**
	 *  Purchase code options
	 *  
	 *  @return void
	 *  
	 *  @version 2.2.0
	 */
	public function purchase_code_option() {
		$code = get_option('oz_purchase_code');
		$activated = get_option('oz_autoupdated');
	?>
		<tr>
			<th scope="row"><label><?php _e('Register plugin to get auto updates.', 'book-appointment-online'); ?><small></small><br/>
			<small><?php _e('To do this, enter your Envato (Codecanyon) purchase code', 'book-appointment-online'); ?></small>
			</label></th>
			<td class="oz_flex">
				<?php if (isset($activated['until']) && $activated['until'] !== 'error' && strtotime($activated['until']) > time()) : ?>
					<?php if (apply_filters('book_oz_show_hid_input', true)) : ?>
					<input name="oz_purchase_code" type="hidden" value="<?php echo $code; ?>" class="">
					<?php endif; ?>
				<label><?php _e('Сopy is activated. Auto Update until', 'book-appointment-online'); ?>: <?php echo $activated['until']; ?> 
				<?php elseif ((isset($activated['activated']) && $activated['activated'] === 405) || (isset($activated['activated']) && $activated['activated'] == '') ) : ?>
				<label><?php _e('Problem with the activation. Contact our support team', 'book-appointment-online'); ?>
				<?php elseif (isset($activated['activated']) && $activated['activated'] === 305) : ?>
				<label><?php _e('This code is activated on other domain', 'book-appointment-online'); ?>
				<?php else : ?>
				<div class="oz_row-100">
					<input name="oz_purchase_code" type="text" id="oz_purchase_code" value="<?php echo $code; ?>" class="">
					<div class="oz_activateMess"></div>
				</div>
				<div class="oz_input_with_btn">
					<div onclick="var code = document.getElementById('oz_purchase_code').value; oz_postRequest('.oz_activateMess', {'action': 'oz_activate', 'code': code});" class="button"><?php _e('Activate', 'book-appointment-online'); ?></div>
				</div>
				<?php endif; ?>
			</td>
		</tr>
	<?php
	}
	
	/**
	 *  Adding devices on site for push notifications
	 *  
	 *  @return void
	 *  
	 *  @version 2.2.0
	 */
	public function activate_plugin() {
		if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['code'])) {
			$res = '{}';
            $activated = 0;
			$data = array(
			'do_update' => 'activate',
			'code' 		=> sanitize_text_field($_POST['code'])		
			);
			$response = wp_remote_post( 'http://oz-plugin.ru/', array('body' => $data));
			if (is_wp_error($response)) {
			}
			else {
				$updated = ((isset($response['body']) && $response['body'])) ? json_decode($response['body'],true) : array();
				if (is_array($updated) && isset($updated['text']) && isset($updated['text']['activated'])) {
					switch ($updated['text']['activated']) {
						case 200 :
						$activated = 1;
						update_option('oz_purchase_code',$data['code']);
						$this->schedule_create();
						break;
						case 300 :
						$activated = 300;
						break;
						case 405 :
						$activated = 405;
						break;
					}
					update_option('oz_autoupdated', array('activated' => $activated, 'until' => $updated['text']['text']));
				}
				$res = (isset($response['body']) && $response['body']) ? $response['body'] : json_encode(array('text' => 'empty'));
			}
		}
		echo $res;
		wp_die();
		
	}
	
	public function get_status($code) {
		$data = array(
		'do_update' => 'activate',
		'code' 		=> $code		
		);
		$response = wp_remote_post( 'http://oz-plugin.ru/', array('body' => $data));
		return $response;
	}
	
	public function schedule() {
		$code = get_option('oz_purchase_code');
		if (!$code) {wp_clear_scheduled_hook( 'oz_updater_check' ); return;}
			$response = $this->get_status($code);
			if (is_wp_error($response)) {
			}
			else {
				$updated = ((isset($response['body']) && $response['body'])) ? json_decode($response['body'],true) : array();
				if (is_array($updated) && isset($updated['text']) && isset($updated['text']['activated'])) {
					switch ($updated['text']['activated']) {
						case 200 :
						break;
						case 300 :
						case 405 :
						update_option('oz_purchase_code','');
						update_option('oz_autoupdated','');
						break;
					}
				}
			}
	}
	
	/**
	 *  Plugin update by purchase code
	 *  
	 *  @return void
	 *  
	 *  @version 2.2.0
	 */
	public function plugin_version() {
	$current_screen = get_current_screen();
	$plugins = ($current_screen) ? $current_screen->id : '';
	if ( is_admin() && ($plugins == 'update-core' || $plugins == 'plugins') ) {
			if( !function_exists('get_plugin_data') ){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$plugin_data = get_plugin_data(OZAPP_PATH .'/book-appointment-online.php');
			$licence_key = get_option('oz_purchase_code');
			if ($plugin_data && $licence_key) {
				$current_version = $plugin_data['Version'];
				$plugin_slug = 'book-appointment-online/book-appointment-online.php';
				$slug = 'book-appointment-online.php';
				$this->update($current_version, $plugin_slug, $slug,$licence_key);
			}
		}
	}
	
	/**
	 *  Check updates
	 *  
	 *  @param string    $current_version Plugin version
	 *  @param string    $plugin_slug plugin slug
	 *  @param string    $slug Plugin filename
	 *  @return void
	 *  
	 *  @version 2.2.0
	 */
	private function update($current_version, $plugin_slug, $slug, $licence_key) {
        $this->current_version = $current_version;
        $this->update_path = 'http://oz-plugin.ru/?do_update=plugin&code='.$licence_key;
		$this->licence_key = $licence_key;
        $this->plugin_slug = $plugin_slug;
        list ($t1, $t2) = explode('/', $plugin_slug);
        $this->slug = str_replace('.php', '', $t2);
        // define the alternative API for updating checking
        add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_update'));
        // Define the alternative response for information checking
	}
 
    /**
     * Add our self-hosted autoupdate plugin to the filter transient
     *
     * @param $transient
     * @return object $ transient
	 * 
	 * @version 2.2.0
     */
    public function check_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }
 
        // Get the remote version
        $remote_version = $this->getRemote_version();
 
        // If a newer version is available, add the update
        if (version_compare($this->current_version, $remote_version, '<')) {
            $obj = new stdClass;
            $obj->slug = $this->slug;
            $obj->new_version = $remote_version;
            $obj->url = $this->update_path;
            $obj->package = $this->update_path;
            $transient->response[$this->plugin_slug] = $obj;
        }
        //var_dump($transient);
        return $transient;
    }
 
    /**
     * Add our self-hosted description to the filter
     *
     * @param boolean $false
     * @param array $action
     * @param object $arg
     * @return bool|object
	 * 
	 * @version 2.2.0
     */
    public function check_info($false, $action, $arg)
    {
        if (isset($arg->slug) && ($arg->slug === $this->slug) && self::isPro()) {
			$this->licence_key = get_option('oz_purchase_code');
            $information = $this->getRemote_information();
            return $information;
        }
        return false;
    }
 
    /**
     * Return the remote version
     * @return string $remote_version
	 * 
	 * @version 2.2.0
     */
    public function getRemote_version()
    {
        $request = wp_remote_post($this->update_path, array('body' => array('book_oz_action' => 'version', 'licence_key' => $this->licence_key)));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return $request['body'];
        }
        return false;
    }
 
    /**
     * Get information about the remote version
     * @return bool|object
	 * 
	 * @version 2.2.0
     */
    public function getRemote_information()
    {
        $request = wp_remote_post($this->update_path, array('body' => array('book_oz_action' => 'info', 'licence_key' => $this->licence_key)));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return unserialize($request['body']);
        }
        return false;
    }
 
    /**
     * Return the status of the plugin licensing
     * @return boolean $remote_license
	 * 
	 * @version 2.2.0
     */
    public function getRemote_license()
    {
        $request = wp_remote_post($this->update_path, array('body' => array('book_oz_action' => 'license', 'licence_key' => $this->licence_key)));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return $request['body'];
        }
        return false;
    }

/**
	 *  Show this message if support is expired
	 *  
	 *  @param array $plugin_data {
		 *     An array of plugin metadata.
		 *
		 *     @type string $name        The human-readable name of the plugin.
		 *     @type string $plugin_uri  Plugin URI.
		 *     @type string $version     Plugin version.
		 *     @type string $description Plugin description.
		 *     @type string $author      Plugin author.
		 *     @type string $author_uri  Plugin author URI.
		 *     @type string $text_domain Plugin text domain.
		 *     @type string $domain_path Relative path to the plugin's .mo file(s).
		 *     @type bool   $network     Whether the plugin can only be activated network wide.
		 *     @type string $title       The human-readable title of the plugin.
		 *     @type string $author_name Plugin author's name.
		 *     @type bool   $update      Whether there's an available update. Default null.
		 * }
		 * @param array $response {
		 *     An array of metadata about the available plugin update.
		 *
		 *     @type int    $id          Plugin ID.
		 *     @type string $slug        Plugin slug.
		 *     @type string $new_version New plugin version.
		 *     @type string $url         Plugin URL.
		 *     @type string $package     Plugin update package URL.
		 * }
	 *  @return void
	 *  
	 *  @version 2.2.0
	 */
	public function expired_support_mess($plugin_data, $response) {
		//in_plugin_update_message-{$file}", $plugin_data, $response
		$activated = get_option('oz_autoupdated');
		if ($activated && isset($activated['until']) && strtotime($activated['until']) < time() )
		echo ' '.__('Please, extend your support if you want to get this update', 'book-appointment-online');
	}
	
	public function check_updated($plugin) {
		if( $this->plugin_slug == $plugin ) {
				$code = get_option('oz_purchase_code');
				if ($code) {
					$response = $this->get_status($code);
					if (is_wp_error($response)) {
					}
					else {
						$updated = ((isset($response['body']) && $response['body'])) ? json_decode($response['body'],true) : array();
						if (is_array($updated) && isset($updated['text']) && isset($updated['text']['activated'])) {
							if ($updated['text']['activated'] != 200) {
								update_option('oz_purchase_code','');
								update_option('oz_autoupdated','');
							}
						}
					}				
				}
				exit( wp_redirect( admin_url( "admin.php?page=".urlencode($this->plugin_slug) ) ) );
			}
		}
	
	public function check_page($func) {
			$activated = get_option('oz_autoupdated');
			$code = get_option('oz_purchase_code');
				if (!$code || ($code && !$activated) || ($code && !isset($activated['activated']))) {
					return [$this, 'activate_page'];
				}
		return $func;
	}

	public static function isPro() {
		$activated = get_option('oz_autoupdated');
		$code = get_option('oz_purchase_code');
		return !(!$code || ($code && !$activated) || ($code && !isset($activated['activated'])));
	}
	
	public function activate_page() {
		?>
		<div id="oz_activate_screen" style="margin-right: 15px; margin-top:10px">
			<table class="form-table" style="width: 50%; margin: auto; text-align:center">
				<tr>
					<td style="padding-bottom:20px;">
						<h2 style="margin-bottom:0"><?php _e('Plugin activation', 'book-appointment-online'); ?></h2>
						<p style="margin-bottom:15px"><?php _e('Please activate the plugin before start work with it. Enter your purchase code please', 'book-appointment-online'); ?></p>
					<div class="oz_flex oz_align-center">
						<input name="oz_purchase_code" type="text" placeholder="f85011b6-ffa5-3d19-9e43-4cc80973d16b" id="oz_purchase_code" value="" class="" style="height: 25px; border-radius: 4px; margin-right: 5px;">
						<div onclick="var code = document.getElementById('oz_purchase_code').value; oz_activate_func(code)" class="button-primary"><?php _e('Activate', 'book-appointment-online'); ?></div>
					</div>
					<small style="text-align: left;display: block; margin-top: 2px;"><a target="_blank" href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-"><?php _e('Where is my purchase code?', 'book-appointment-online'); ?></a></small>
					<div class="oz_activateMess"></div>
					</td>
				</tr>
			</table>
		</div>
		<style>
		.oz_loading tbody {
			opacity:0.6;
		}
		</style>
		<script>
		async function oz_activate_func(code) {
			if (!code) return;
			let content = '.oz_activateMess'
			document.querySelector(content).innerHTML = ''
			document.getElementById('oz_activate_screen').children[0].className = 'form-table oz_loading'
			let req = await oz_postRequest(content, {'action': 'oz_activate', 'code': code}, '', false);
			document.getElementById('oz_activate_screen').children[0].className = 'form-table'
			if (req && typeof req.text != 'undefined') {
				if (typeof req.text.activated != 'undefined') {
					switch (req.text.activated) {
						case 200 :
						document.querySelector(content).innerHTML = '<span style="color:#4CAF50">Success! The page will refresh automatically</span>'
						setTimeout(() => {
							window.location.reload();
						},2000)
						break;
						case 300 :
						document.querySelector(content).innerHTML = typeof(req.text.text) == 'object' ? '<span class="oz_red">'+JSON.stringify(req.text.text)+'</span>' : '<span class="oz_red">'+req.text.text+'</span>';
						break;
						case 405 :
						document.querySelector(content).innerHTML = typeof(req.text.text) == 'object' ? '<span class="oz_red">'+JSON.stringify(req.text.text)+'</span>' : '<span class="oz_red">'+req.text.text+'</span>';
						break;
					}
					
				}
				else {
					
				}
			}
			console.log(req)
		}
		</script>
		<?php
	}
	
	public static function schedule_create() {
		wp_clear_scheduled_hook( 'oz_updater_check' );
		wp_schedule_event( time(), 'weekly', 'oz_updater_check');
	}
	
	public function cron_weekly($schedules) {
		$schedules['weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display' => __( 'Once Weekly' )
		);
		$schedules['every_min'] = array(
			'interval' => 60,
			'display' => __( 'Every minute' )
		);
		return $schedules;		
	}
	

}