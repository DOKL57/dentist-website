<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
namespace Ozplugin;
//use Ozplugin\Settings;
use \oz_ShortcodeReplacer;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Assets {
    
    public function init($params) {
        $this->base = $params['base'];
        add_action( 'wp_enqueue_scripts', [$this, 'book_oz_front_scripts'] );
        add_action( 'admin_enqueue_scripts', [$this, 'admin'] );
        add_action( 'admin_enqueue_scripts', [$this, 'wp_localize_jquery_ui_datepicker'],10 );
    }

    public static function book_oz_front_scripts($init = false) {
        global $post, $oz_theme, $oz_vid;
        if (wp_script_is('oz_front_scripts',  'enqueued')) return;
        $ver = (defined('OZAPP_VER') && OZAPP_VER ) ? OZAPP_VER : false;
        if( (is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'oz_template') || has_shortcode( $post->post_content, 'ozapp'))) || $oz_vid == 'as_popbtn' || $init  ) {
        if ( $oz_theme == 'default' || $oz_theme == '' ) {
        wp_register_style( 'oz_default', OZAPP_URL.'assets/css/default.theme.css',false,$ver );
        wp_enqueue_style( 'oz_default' );
        }
        elseif ($oz_theme == 'neumorph') {
        wp_register_style( 'oz_default', OZAPP_URL.'assets/css/neumorph.theme.css',false,$ver );
        wp_enqueue_style( 'oz_default' );		
        }
        wp_register_style( 'oz_front_css', OZAPP_URL.'assets/css/oz_front_css.css',false,$ver );
        
        // deprecated
        //wp_register_style( 'intlTelInput', plugins_url( '/css/intlTelInput.min.css', __FILE__ ) );
        //wp_register_script( 'csstojson', plugins_url('/js/csstojson.min.js', __FILE__),array( 'jquery' ), false, true );
        //wp_register_script( 'inputmask', plugins_url('/js/jquery.inputmask.bundle.min.js', __FILE__),array('jquery'), false, true );
        //wp_register_script( 'intlTelInput', plugins_url('/js/intlTelInput.min.js', __FILE__),array('jquery'), false, true );
        

        wp_register_script( 'book_oz_moment', OZAPP_URL.'assets/js/moment.min.js' );
        wp_register_script( 'oz_polyfills', OZAPP_URL.'assets/js/polyfills.oz_bundle.js',array('jquery'), $ver, true );	
        wp_register_script( 'oz_front_scripts', OZAPP_URL.'assets/js/index.oz_bundle.js',array('jquery'), $ver, true );	

        $oz_vars = array(
        'oz_ajax_url' => admin_url('admin-ajax.php'),
        'scriptPath'  => plugin_dir_url( __FILE__ ),
        'timezone'	=> get_option('gmt_offset'),
        'timezone_string'	=> (function_exists('wp_timezone_string')) ? wp_timezone_string() : book_oz_timezone_string(),
        'timezone_detect'	=> get_option('oz_time_zone'),
        'lang' => get_locale(),
        'dateFormat' => convertPHPToMomentFormat(get_option('date_format')),
        'firstDay' => apply_filters('book_oz_datepicker_first_day', get_option('start_of_week')),
        'nonce' => wp_create_nonce('wp_rest'),
        'AMPM' => get_option('oz_time_format'),
        'timeslot' => get_option('oz_time_duration', 15),
        'payment' => get_option('oz_payment'),
        'maxMonth' => get_option('oz_month_max_show', 2),
        );
        $scripts = ['book_oz_moment', 'oz_front_scripts'];
        if (!wp_script_is('wp-polyfill',  'enqueued')) {
            array_unshift($scripts, 'oz_polyfills');
        }
        wp_enqueue_script($scripts);
        do_action('book_oz_advanced_frontScripts', $ver);
        $oz_vars = apply_filters('book_custFront_JSOptions',$oz_vars);
        wp_localize_script( 'oz_front_scripts', 'oz_vars', $oz_vars );
        $oz_lang = apply_filters('book_frontJS_translate',Strings::main());
        wp_localize_script( 'oz_front_scripts', 'oz_lang', $oz_lang );
        wp_enqueue_style( array('oz_front_css') );
        }
    }

    function admin() {
        $current = get_current_screen();
        $activated = get_option('oz_autoupdated');
        $isActivated = isset($activated['until']) && $activated['until'] !== 'error';
        wp_enqueue_style( 'oz_admin_css', OZAPP_URL.'assets/css/oz_admin_css.css', false, OZAPP_VER );
            if( ($current->post_type == 'clients' && $current->base == 'post') || 
                ($current->post_type == 'personal' && $current->base == 'post') || 
                ($current->post_type == 'clients' && $current->base == 'edit') || 
                $current->base == 'dashboard' ||
                $current->base == 'toplevel_page_'.'book-appointment-online'.'/'.'book-appointment-online' ||
                ($current->post_type == 'services' && $current->base == 'post')
                ) {
            $ver = (defined('OZAPP_VER') && OZAPP_VER ) ? OZAPP_VER : false;
            if (!wp_script_is('jquery-ui-datepicker')) wp_enqueue_script( 'jquery-ui-datepicker');
            if (!wp_script_is('wp-color-picker')) wp_enqueue_script( 'wp-color-picker');
            wp_enqueue_style( 'fullcalendar', OZAPP_URL.'assets/css/fullcalendar.min.css' );
            wp_enqueue_style( 'intlTelInput', OZAPP_URL.'assets/css/intlTelInput.min.css' );
            wp_enqueue_script( 'book_oz_moment', OZAPP_URL.'assets/js/moment.min.js',array( 'jquery-ui-core' ) );
            wp_enqueue_script( 'fullcalendar', OZAPP_URL.'assets/js/fullcalendar.min.js',array( 'jquery-ui-core' ) );
            wp_enqueue_script( 'locale-all', OZAPP_URL.'assets/js/locale-all.js',array( 'jquery-ui-core' ) );
            wp_enqueue_script( 'intlTelInput', OZAPP_URL.'assets/js/intlTelInput.min.js',array('jquery-ui-core'), false, true );
            if (Updater::isPro()) {
                wp_enqueue_script( 'oz-admin-modules', OZAPP_URL.'assets/js/index.oz_admin_bundle.js',array( 'jquery-ui-core' ), $ver, true );
            }
            do_action('book_oz_advanced_scripts',$ver);
            $logo = get_theme_mod( 'custom_logo' );
                $oz_vars = array(
                    'adminAjax' => admin_url('admin-ajax.php'),
                    'adminURL' => get_admin_url(),
                    'nonce' => wp_create_nonce('ozajax-nonce'),
                    'post_type' => ($current->base == 'post') ? get_post_type() : 'none',
                    'scriptPath'  => OZAPP_URL,
                    'lang' => get_option('WPLANG'),
                    'addons' => array_map(function($addon) {return $addon::NAME;}, $this->base->addons),
                    'isPRO' => get_option('oz_purchase_code') && $isActivated,
                    'dateFormat' => convertPHPToMomentFormat(get_option('date_format')),
                    'AMPM' => get_option('oz_time_format'),
                    'timeslot' => get_option('oz_time_duration', 15),
                    'activeTab' => 'main',
                    'logo' => [
                        'img' => wp_get_attachment_image_url( $logo , 'full' )
                    ],
                    'shortcodes' => oz_ShortcodeReplacer::instance()->getNames(),
                    'debug' => defined('WP_DEBUG') && WP_DEBUG,
                    'renameOldTransl' => Utils::checkLocoProi18n(),
                    'maxMonth' => get_option('oz_month_max_show', 2),
                    // 'dateFormat' => convertPHPToMomentFormat(get_option('date_format'))
                    );
                    $oz_vars = apply_filters('book_custAdmin_JSOptions',$oz_vars);
                    if ($current->base == 'toplevel_page_'.'book-appointment-online'.'/'.'book-appointment-online') {
                        wp_enqueue_script( 'oz_admin_js', OZAPP_URL.'assets/js/admin.bundle.js', false, OZAPP_VER );
                        wp_localize_script( 'oz_admin_js', 'oz_vars', $oz_vars );
                    } 
                        wp_enqueue_script( 'ozscripts', OZAPP_URL.'assets/js/ozscripts.js',array( 'jquery-ui-core' ), $ver, true ); //закомментил чтобы работало на главной
                        if (!wp_script_is('jquery-ui-sortable'))wp_enqueue_script( 'jquery-ui-sortable',array( 'jquery-ui-core' )); 
                        wp_localize_script( 'ozscripts', 'oz_vars', $oz_vars );
                        wp_localize_script( 'ozscripts', 'oz_alang', Strings::admin() );
        }
                    
    }

    public function wp_localize_jquery_ui_datepicker() {
        global $wp_locale;
        if ( ! wp_script_is( 'ozscripts', 'enqueued' ) && ! wp_script_is( 'jquery-ui-datepicker', 'enqueued' )) {
            return;
        }
    
        // Convert the PHP date format into jQuery UI's format.
        $datepicker_date_format = str_replace(
            array(
                'd', 'j', 'l', 'z', // Day.
                'F', 'M', 'n', 'm', // Month.
                'Y', 'y'            // Year.
            ),
            array(
                'dd', 'd', 'DD', 'o',
                'MM', 'M', 'm', 'mm',
                'yy', 'y'
            ),
            get_option( 'date_format' )
        );
    
        $datepicker_defaults = wp_json_encode( array(
            'closeText'       => __( 'Close' ),
            'currentText'     => __( 'Today' ),
            'monthNames'      => array_values( $wp_locale->month ),
            'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
            'nextText'        => __( 'Next' ),
            'prevText'        => __( 'Previous' ),
            'dayNames'        => array_values( $wp_locale->weekday ),
            'dayNamesShort'   => array_values( $wp_locale->weekday_abbrev ),
            'dayNamesMin'     => array_values( $wp_locale->weekday_initial ),
            'dateFormat'      => $datepicker_date_format,
            'firstDay'        => absint( get_option( 'start_of_week' ) ),
            'isRTL'           => $wp_locale->is_rtl(),
        ) );
    
        wp_add_inline_script( 'ozscripts', "var book_oz_setLang = {$datepicker_defaults}; jQuery.datepicker.setDefaults(book_oz_setLang);",'before' );
        wp_enqueue_style('at-jquery-ui-css', OZAPP_URL.'assets/js/jquery-ui/jquery-ui.css');
    }
}