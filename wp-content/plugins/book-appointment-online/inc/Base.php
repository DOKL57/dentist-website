<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
namespace Ozplugin;

use Ozplugin\Addons\Email;

//use Ozplugin\Settings;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Base {

    public $pages = [];

    public $addons = [];

    public function init() {
        //$this->loadClasses();
        //add_action('init', [$this, 'registerPostTypes']);
        add_action('wp_footer', [$this, 'book_oz_theme_popup']);
        
        $this->shortcodes();
        $this->ajax();
        $classes = ['Assets', 'Settings', 'Dashboard', 'AddonInstaller', 'Updater',
                    'Services', 'Employees', 'Clients', 'Payments', 'SMS', 'Firebase'];
        foreach($classes as $class) {
            $name = $class;
            $class = 'Ozplugin\\'.$class;
            if (class_exists($class)) {
                $inst = new $class(['base' => $this]);
                $this->add($inst, $name);
            }
        }

        $ad = "Ozplugin\\Addons\\Email"; 
            if (class_exists($ad)) {
                $instance = new Email();
                $this->addons[] = $instance;
                $instance->setBase($this);
                $instance->init();
            }

        foreach($classes as $name) {
            $class = 'Ozplugin\\'.$name;
            if (class_exists($class)) {
            $this->$name->init(['base' => $this]);
            }
        }
    }

    public function add($class, $name) {
        $this->$name = $class;
        return $this;
    }

    public function getPosts($post_type = 'post') {
        if (isset($this->posts[$post_type])) return $this->posts[$post_type];
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
        );
        $this->posts[$post_type] = get_posts($args);
        
        return $this->posts[$post_type];
    }

    public function shortcodes() {
        add_shortcode( 'oz_template', [$this, 'template'] );
        add_shortcode( 'ozapp', [$this, 'template'] );
    }

    public function template($atts) {
            global $current_screen,$wp_version;
            /* WP 5.0 - check if Gutenberg editor */
            //if (wp_script_is('oz_front_scripts',  'enqueued')) return __('It seems that the plugin is already running somewhere on the site.');
            if (($current_screen && method_exists($current_screen,'is_block_editor') && $current_screen->is_block_editor()) ||
            function_exists('has_blocks') && has_blocks() && isset($_GET['_locale']) && $_GET['_locale'] == 'user' && version_compare( $wp_version, '5.0', '>=' )
            ) return;
            /* WP 5.0 - check if Gutenberg editor */
            global $short_atts;
            $short_atts = (isset($atts) && $atts) ? $atts : array();
            if (get_option('oz_customer_register_perm', false) && !is_user_logged_in()) {
                return __('Only registered users can book an appointment', 'book-appointment-online');
            }
            
            return do_action('book_oz_before_appointment_form')."<div data-atts='".json_encode($short_atts)."' id='oz_appointment'></div>".do_action('book_oz_after_appointment_form');
    }

    /**
     * тема для всплывающего окна
     */
    public function book_oz_theme_popup() {
        global $post, $oz_vid;
        $emp_page = get_option('oz_employees');
        $isEmpProfilePage = $emp_page && isset($emp_page['profile_page']) && $post && isset($post->ID) && $emp_page['profile_page'] == $post->ID;
        if (function_exists('is_checkout') && is_checkout()) return; // if woocommerce checkout
        if ($isEmpProfilePage) return; // if employe profile page checkout
        if ($oz_vid !== 'as_popbtn' || (is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'oz_template') || has_shortcode( $post->post_content, 'ozapp'))) || apply_filters('book_oz_show_as_popup', false)) return;
        ?>
        <div class="oz_pop_btn"><?php _e('Booking online', 'book-appointment-online'); ?></div>
        <?php
        include_once(OZAPP_TEMPLATES_PATH.'oz_popup.php');
    }

    public function ajax() {
    $pref = 'oz_';
    add_action('wp_ajax_'.$pref.'hook', [$this, 'checkHook']);
    add_action('wp_ajax_oz_get_services', 'oz_get_services');
    add_action('wp_ajax_nopriv_oz_get_services', 'oz_get_services');
    add_action('wp_ajax_oz_get_employees', 'oz_get_employees');
    add_action('wp_ajax_nopriv_oz_get_employees', 'oz_get_employees');
    add_action('wp_ajax_oz_renameFilesWithProTransl', [$this,'renameLocoFiles']);
    }

    public function checkHook() {
        if (wp_doing_ajax() && check_ajax_referer('ozajax-nonce')) {
            if (!wp_get_scheduled_event('oz_updater_check'))
            Updater::schedule_create();
        }
        wp_die();
    }
    
    /**
     * rename old PRO translation Loco translations for 3.0.8 plugin version and less 
     *
     * @return string
     */
    public function renameLocoFiles() {
        if (wp_doing_ajax() && check_ajax_referer('ozajax-nonce')) {
            $files = Utils::checkLocoProi18n(true);
            if (count($files)) {
                foreach($files as $file) {
                    $from = $file;
                    $to = str_replace('book-appointment-online-pro', 'book-appointment-online', $file);
                    rename($from, $to);
                }
            }
            echo json_encode([
                'success' => true,
            ]);
        }
        wp_die();        
    }
}

?>