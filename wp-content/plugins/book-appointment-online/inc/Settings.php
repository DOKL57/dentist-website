<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
namespace Ozplugin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Settings {
    function __construct($params) {
        $this->base = $params['base'];
    }

    public function init() {
        add_action('admin_menu', [$this,'add']);
        add_action( 'wp_ajax_oz_save_option', [$this,'save'] );
        add_action( 'book_custAdmin_JSOptions', [$this,'addSettings'] );
    }

    public function safe_styles( $styles ) {
		$styles[] = 'display';
		return $styles;
	}

    protected function getOpts($name, $array_key = '') {
        $option = get_option($name);
        if ($array_key) {
            $option = isset($option[$array_key]) ? $option[$array_key] : '';
        }
        return $option;
    }

    public function save() {
        if (wp_doing_ajax() && wp_verify_nonce($_POST['_wpnonce'], 'ozajax-nonce')) {
            if (book_oz_user_can(true,true) && apply_filters('book_oz_canSaveSettings', true)) {
                $name = sanitize_text_field($_POST['name']);
                preg_match('/^([a-zA-Z0-9_]+)(\[[a-zA-Z0-9_]+\])(\[[a-zA-Z0-9_]+\])?(\[[a-zA-Z0-9_]+\])?/', $name, $iSaArrayName);
                $key = '';
                $values = [];
                if ($iSaArrayName && count($iSaArrayName) > 2) {
                    $key = $iSaArrayName[1];
                }
                $value = $_POST['value'];
                $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'string'; 
                switch($type) {
                        case 'number':
                            $value = (int)($value);
                        break;
                        case 'object':
                            $json_decoded = json_decode(stripslashes($value),1);
                            if ($json_decoded && is_array($json_decoded)) {
                                $value = $json_decoded;
                                array_walk_recursive($value, 'Ozplugin\Utils::sanitize_json');
                            }
                            if ($value) {
                                $value = is_array($value) ? $value : explode(',',$value);
                                $mapping = isset($_POST['objectValuesType']) && $_POST['objectValuesType'] == 'number' ? 'intval' : 'sanitize_text_field'; 
                                if (!$json_decoded) {
                                    $value = array_map($mapping, $value);
                                }
                            }
                        break;
                        case 'boolean':
                            $value = $value == 'true';
                        break;
                        case 'html':
                            add_filter( 'safe_style_css', [$this, 'safe_styles'] );
                            $post = array_merge(wp_kses_allowed_html('post'), [
                                'body' => ['class' => 1],
                                'center' => ['class' => 1, 'style' => 1],
                                'head' => ['class' => 1],
                                'html' => ['class' => 1],
                                'meta' => ['charset' => 1, 'name' => 1, 'content' => 1, 'http-equiv' => 1],
                                'style' => []
                            ]);
                            $value = wp_kses($value, $post);
                            remove_filter( 'safe_style_css', [$this, 'safe_styles'] );
                        break;
                        default:
                        //$value = sanitize_text_field($value);
                        $value = esc_html($value);
                }
                
                if ($iSaArrayName && count($iSaArrayName) > 2 && $key) {
                    $values = get_option($key);
                    if (!is_array($values)) {
                        $values = [];
                    }
                    if (count($iSaArrayName) == 3 && $iSaArrayName[2]) {
                        $k = str_replace(['[', ']'], '',$iSaArrayName[2]);
                        if (!isset($values[$k])) {$values[$k] = '';}
                        $values[$k] = $value;
                    }
                    elseif(count($iSaArrayName) == 4 && $iSaArrayName[3]) {
                        $k = str_replace(['[', ']'], '',$iSaArrayName[2]);
                        $t = str_replace(['[', ']'], '',$iSaArrayName[3]);
                        if (!isset($values[$k])) {$values[$k] = [];}
                        if (!isset($values[$k][$t])) {$values[$k][$t] = '';}
                        $values[$k][$t] = $value;
                    }
                    $value = $values;
                    $name = $key;
                }
                $suc = $name && isset($_POST['value']) ? update_option($name, $value) : false;
                $res = [
                    'success' => $name && isset($_POST['value']),
                    'value' => $value,
                    'text' => !$suc ? 'Error with saving' : '',
                    'val' => [$name, $value, $key, $values]
                ];
            }
            else {
                $res = [
                    'success' => false,
                    'text' => __('You do not have enough permissions to change the settings', 'book-appointment-online'),
                ];
            }

            echo (json_encode($res));
        }
        wp_die();
    }

    public function add() {
        add_menu_page(
            __('Booking settings', 'book-appointment-online'),
            __('Booking settings', 'book-appointment-online'), 
            book_oz_user_can(true, true), 
            OZAPP_FILE, 
            //apply_filters('book_oz_menu_page_func', [$this, 'page']),
            [$this, 'page'],
            'dashicons-admin-settings',8
        );
    }

    public function page() {
        $styles = [
            'position'=> 'fixed',
            'z-index' => '99999',
            'top' => '0',
            'left' => '0',
            'width' => '100%',
            'height' => '100%',
            'overflow' => 'auto',
            'background' => '#f9fbfe',
        ];
        $styles_css = '';
        foreach($styles as $key => $style) {
            $styles_css .= "$key:$style;";
        }
        echo '<div style="'.$styles_css.'" id="oz_admin_page"></div>';
    }

    public function wp_roles() {
        global $wp_roles;
 
        $editable_roles = array_reverse( get_editable_roles() );
        $current = wp_get_current_user();
        if ($current && $current->roles) {
            foreach ($current->roles as $role) {
                if (!isset($editable_roles[$role]) && $role != 'administrator' && $wp_roles && $wp_roles->roles && isset($wp_roles->roles[$role])) {
                    $editable_roles[$role] = array(
                        'name' => $wp_roles->roles[$role]['name']
                    );
                }
            }
        }
     
        $r = [];
        foreach ( $editable_roles as $role => $details ) {
            if ($role == 'administrator' || $role == 'oz_employee') continue;
            $name = translate_user_role($details['name'] );
            $r[] = [
                'label' => esc_attr( $name ),
                'value' => esc_attr( $role ),
            ];
        }
        return $r;
    }

    public function addSettings($vars) {
        $current = get_current_screen();
		if (!$current || $current && $current->base != 'toplevel_page_'.'book-appointment-online'.'/'.'book-appointment-online') return $vars;
        Utils::checkUpdater();
        $vars['settings'] = $this->settings();
        if (isset($vars['settings']['sms']) && count($vars['settings']['sms']['options']) < 2) {
            unset($vars['settings']['sms']);
        }
        if (isset($vars['settings']['integration']) && count($vars['settings']['integration']['options']) < 2) {
            unset($vars['settings']['integration']);
        }
        return $vars;
    }

    public function settings() {
        $settings = apply_filters('book_oz_plugin_def_settings', $this->optionsAsText());
        // $settings = array_merge($settings, $email_settings, $this->optionsAsText());
        foreach($this->base->addons as $addon) {
            $options = $addon->getOptions();
            if (is_array($options)) {
                foreach(array_keys($options) as $key) {
                    if(isset($settings[$key]) && isset($settings[$key]['options'])) {
                        $settings[$key]['options'] = array_merge($settings[$key]['options'], $options[$key]);
                    }
                }
            }
        }

        return apply_filters('book_oz_plugin_settings', $settings);
    }

    public function howToPlace() {
        ob_start();
        ?>
        <div class="welcome-panel-white">
            <span class=""><?php _e('Add shortcode to page', 'book-appointment-online'); ?>: <span class="oz_code">[ozapp]</span></span><br>
            <span class=""><?php _e('Add php code to template', 'book-appointment-online'); ?>: <span class="oz_code"><?php echo htmlspecialchars("<?php echo do_shortcode('[ozapp]');?>"); ?></span></span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function optionsAsText() {
        $colors = get_option('oz_colors', ['primary' => '', 'secondary' => '', 'background' => '']);
        if (isset($colors['second'])) {
            $colors['secondary'] = $colors['second'];
        }
        if (!isset($colors['background'])) {
            $colors['background'] = '';
        }
        $minutes = apply_filters('book_oz_time_duration',Utils::timeDuration());
        return [
                'main' => [
                    'name' => __('Main settings', 'book-appointment-online'),
                    'options' => [
                            [
                            'title' => __('Who can manage calendar', 'book-appointment-online'),
                            'description' => __('Restart plugin if this option has been changed', 'book-appointment-online'),
                            'order' => 10,
                            'fields' => [
                                [
                                    'name' => 'oz_user_role',
                                    'value' => get_option('oz_user_role'),
                                    'type' => 'select',
                                    'multiple' => true,
                                    'values' => $this->wp_roles(),
                                ],
                                [
                                    'name' => 'oz_user_role_showSetting',
                                    'value' => get_option('oz_user_role_showSetting'),
                                    'title' => '',
                                    'description' => '',
                                    'type' => 'checkbox',
                                    'multiple' => false,
                                    'values' => [
                                        [
                                            'label' => __('Hide booking settings', 'book-appointment-online'),
                                            'value' => false
                                        ],
                                    ],
                                ]
                            ],
                            ],
                            [
                                'title' => __('Sequence of steps', 'book-appointment-online'),
                                'description' => '',
                                'order' => 20,
                                'fields' => [
                                    [
                                    'name' => 'oz_step_sequence',
                                    'value' => Utils::steps_html(),
                                    'type' => 'html',
                                    'multiple' => false,
                                    'values' => [],
                                    ]
                                ],
                            ],
                            [
                                'title' => __('Skip step if one employee/service', 'book-appointment-online'),
                                'description' => '',
                                'order' => 25,
                                'fields' => [
                                    [
                                        'name' => 'oz_skip_step_ifOne',
                                        'value' => get_option('oz_skip_step_ifOne'),
                                        'title' => '',
                                        'description' => '',
                                        'type' => 'checkbox',
                                        'multiple' => false,
                                        'values' => [
                                            [
                                                'label' => '',
                                                'value' => 'oz_skip_step_ifOne'
                                            ],
                                        ],
                                    ]
                                ],
                            ],
                            [
                                'title' => __('View option', 'book-appointment-online'),
                                'description' => '',
                                'order' => 30,
                                'fields' => [
                                    [
                                        'name' => 'oz_vid',
                                        'value' => get_option('oz_vid') ? (isset(get_option('oz_vid')['chk']) ? get_option('oz_vid')['chk'] : get_option('oz_vid') ) : 'as_shortcode',
                                        'type' => 'select',
                                        'multiple' => false,
                                        'values' => [
                                                [
                                                    'label' => __('As shortcode', 'book-appointment-online'),
                                                    'value' => 'as_shortcode'
                                                ],
                                                [
                                                    'label' => __('As popup', 'book-appointment-online'),
                                                    'value' => 'as_popbtn'
                                                ],
                                            ],
                                    ]
                                ],
                            ],
                            [
                                'title' => __('How to place', 'book-appointment-online'),
                                'description' => '',
                                'order' => 40,
                                'fields' => [
                                    [
                                        'name' => '',
                                        'value' => $this->howToPlace(),
                                        'type' => 'html',
                                        'multiple' => false,
                                        'values' => [],
                                    ]
                                ],
                            ],
                            [
                                'title' => __('Template', 'book-appointment-online'),
                                'description' => '',
                                'order' => 40,
                                'fields' => [
                                    [
                                        'name' => 'oz_theme',
                                        'value' => get_option('oz_theme') ? (isset(get_option('oz_theme')['chk']) ? get_option('oz_theme')['chk'] : get_option('oz_theme') ) : 'default',
                                        'type' => 'select',
                                        'multiple' => false,
                                        'values' => [
                                                [
                                                    'label' => __('Default', 'book-appointment-online'),
                                                    'value' => 'default'
                                                ],
                                                [
                                                    'label' => __('Neumorphism', 'book-appointment-online'),
                                                    'value' => 'neumorph'
                                                ],
                                                [
                                                    'label' => __('No styles', 'book-appointment-online'),
                                                    'value' => 'no_styles'
                                                ],
                                            ],
                                    ]
                                ],
                            ],
                            [
                                'title' => __('Currency', 'book-appointment-online'),
                                'description' => '',
                                'order' => 50,
                                'fields' => [
                                    [
                                        'name' => 'oz_default_cur',
                                        'value' => get_option('oz_default_cur', ''),
                                        'type' => 'input',
                                        'multiple' => false,
                                    ],
                                    [
                                        'name' => 'oz_currency_position',
                                        'value' => get_option('oz_currency_position'),
                                        'title' => '',
                                        'description' => '',
                                        'type' => 'select',
                                        'multiple' => false,
                                        'values' => [
                                            [
                                                'label' => __('Currency position', 'book-appointment-online'),
                                                'value' => ''
                                            ],
                                            [
                                                'label' => __('Left', 'book-appointment-online'),
                                                'value' => 'left'
                                            ],
                                            [
                                                'label' => __('Right', 'book-appointment-online'),
                                                'value' => 'right'
                                            ],
                                        ],
                                    ]
                                ],
                            ],
                            [
                                'title' => __('AM/PM time Format', 'book-appointment-online'),
                                'description' => '',
                                'order' => 60,
                                'fields' => [
                                    [
                                        'name' => 'oz_time_format',
                                        'value' => get_option('oz_time_format'),
                                        'type' => 'checkbox',
                                        'multiple' => false,
                                        'values' => [
                                            [
                                                'label' => '',
                                                'value' => 'oz_time_format'
                                            ],
                                        ],
                                    ]
                                ],
                            ],
                            [

                                'title' => __('Add area for users', 'book-appointment-online'),
                                'description' => '',
                                'order' => 70,
                                'isPRO' => Updater::isPro(),
                                'fields' => [
                                    [
                                        'name' => 'oz_user_area',
                                        'value' => get_option('oz_user_area'),
                                        'type' => 'checkbox',
                                        'multiple' => false,
                                        'values' => [
                                            [
                                                'label' => '',
                                                'value' => 'oz_user_area'
                                            ],
                                        ],
                                    ]
                                ],
                            ],
                            [

                                'title' => __('Allow book an appointment only for registered users', 'book-appointment-online'),
                                'description' => '',
                                'order' => 80,
                                'isPRO' => Updater::isPro(),
                                'fields' => [
                                    [
                                        'name' => 'oz_customer_register_perm',
                                        'value' => get_option('oz_customer_register_perm'),
                                        'type' => 'checkbox',
                                        'multiple' => false,
                                        'values' => [
                                            [
                                                'label' => '',
                                                'value' => 'oz_customer_register_perm'
                                            ],
                                        ],
                                    ]
                                ],
                            ],
                            [

                                'title' => __('Allow customer to register', 'book-appointment-online'),
                                'description' => __('Restart plugin if this option has been changed', 'book-appointment-online'),
                                'order' => 90,
                                'isPRO' => Updater::isPro(),
                                'fields' => [
                                    [
                                        'name' => 'oz_customer_register',
                                        'value' => get_option('oz_customer_register'),
                                        'type' => 'checkbox',
                                        'multiple' => false,
                                        'values' => [
                                            [
                                                'label' => __('Yes', 'book-appointment-online'),
                                                'value' => 'oz_customer_register'
                                            ],
                                        ],
                                    ],
                                    [
                                        'name' => 'oz_customer_register_req',
                                        'value' => get_option('oz_customer_register_req'),
                                        'type' => 'checkbox',
                                        'multiple' => false,
                                        'values' => [
                                            [
                                                'label' => __('Required', 'book-appointment-online'),
                                                'value' => 'oz_customer_register_req'
                                            ],
                                        ],
                                    ]
                                ],
                            ],
                            [
                                'title' => __('Color options', 'book-appointment-online'),
                                'description' => __('Main color, second color, background color', 'book-appointment-online'),
                                'order' => 100,
                                'fields' => [
                                    [
                                        'name' => 'oz_colors[primary]',
                                        'value' => $colors['primary'],
                                        'type' => 'color',
                                        'multiple' => false,
                                    ],
                                    [
                                        'name' => 'oz_colors[secondary]',
                                        'value' => $colors['secondary'],
                                        'title' => '',
                                        'description' => '',
                                        'type' => 'color',
                                        'multiple' => false,
                                    ],
                                    [
                                        'name' => 'oz_colors[background]',
                                        'value' => $colors['background'],
                                        'title' => '',
                                        'description' => '',
                                        'type' => 'color',
                                        'multiple' => false,
                                        ],
                                    ]
                            ],
                            [

                                'title' => __('Plugin information', 'book-appointment-online'),
                                'description' => '',
                                'order' => 210,
                                'isPRO' => Updater::isPro(),
                                'fields' => [
                                    [
                                        'name' => 'oz_purchase_code',
                                        'value' => Utils::purchaseInfo(),
                                        'type' => 'html',
                                        'multiple' => false,
                                    ]
                                ],
                            ],
                    ]
                ],
                'appointment' => [
                    'name' => __('Appointment settings', 'book-appointment-online'),
                    'options' => [
                        [

                            'title' => __('Multiselect for services', 'book-appointment-online'),
                            'description' => '',
                            'order' => 10,
                            'isPRO' => Updater::isPro(),
                            'fields' => [
                                [
                                    'name' => 'oz_multiselect_serv',
                                    'value' => get_option('oz_multiselect_serv'),
                                    'type' => 'checkbox',
                                    'multiple' => false,
                                    'values' => [
                                        [
                                            'label' => '',
                                            'value' => 'oz_multiselect_serv'
                                        ],
                                    ],
                                ]
                            ],
                        ],
                        [
                            'title' => __('Time slot duration', 'book-appointment-online'),
                            'description' => '',
                            'order' => 20,
                            'fields' => [
                                [
                                    'name' => 'oz_time_duration',
                                    'value' => get_option('oz_time_duration', 15),
                                    'type' => 'select',
                                    'multiple' => false,
                                    'values' => Utils::timeDurationForSelect($minutes),
                                ]
                            ],
                        ],
                        [

                            'title' => __('Customers can choose their time zone', 'book-appointment-online'),
                            'description' => __('The customer can choose how to show the time on the site. In his time zone or in the site\'s time zone', 'book-appointment-online'),
                            'order' => 30,
                            'isPRO' => Updater::isPro(),
                            'fields' => [
                                [
                                    'name' => 'oz_time_zone',
                                    'value' => get_option('oz_time_zone'),
                                    'type' => 'checkbox',
                                    'multiple' => false,
                                    'values' => [
                                        [
                                            'label' => '',
                                            'value' => 'oz_time_zone'
                                        ],
                                    ],
                                ]
                            ],
                        ],
                        [
                            'title' => __('Max month for calendar display on front (from now)', 'book-appointment-online'),
                            'description' => '',
                            'order' => 40,
                            'fields' => [
                                [
                                    'name' => 'oz_month_max_show',
                                    'value' => get_option('oz_month_max_show', 2),
                                    'type' => 'select',
                                    'multiple' => false,
                                    'values' => Utils::generateForSelect([1,2,3,4,5,6]),
                                ]
                            ],
                        ],
                        [
                            'title' => __('Min time for booking (from now)', 'book-appointment-online'),
                            'description' => '',
                            'order' => 50,
                            'fields' => [
                                [
                                    'name' => 'oz_time_min_show',
                                    'value' => get_option('oz_time_min_show', 0),
                                    'type' => 'select',
                                    'multiple' => false,
                                    'values' => Utils::generateForSelect([1,3,6,12,24,48,72,168,336], '', [0 => __('None', 'book-appointment-online')], 'h'),
                                ]
                            ],
                        ],
                        [
                            'title' => __('Min time for canceling booking (from now and only for logged in users)', 'book-appointment-online'),
                            'description' => '',
                            'order' => 60,
                            'isPRO' => Updater::isPro(),
                            'fields' => [
                                [
                                    'name' => 'oz_time_min_cancel',
                                    'value' => get_option('oz_time_min_cancel', 0),
                                    'type' => 'select',
                                    'multiple' => false,
                                    'values' => Utils::generateForSelect([1,3,6,12,24,48,72,168,336], '', [0 => __('None', 'book-appointment-online')], 'h'),
                                ]
                            ],
                        ],
                        [

                            'title' => __('Аppointment statuses', 'book-appointment-online'),
                            'description' => '',
                            'order' => 70,
                            'fields' => [
                                [
                                    'name' => 'book_oz_enable_statuses',
                                    'value' => get_option('book_oz_enable_statuses'),
                                    'type' => 'switch',
                                    'multiple' => false,
                                    'fields' => [
                                            [
                                                'title' => __('Status by default', 'book-appointment-online'),
                                                'description' => __('Appointments with status \'Approved\' and \'On hold\' will block the selected employee time slot', 'book-appointment-online'),
                                                'order' => 10,
                                                'fields' => [
                                                    [
                                                        'name' => 'oz_status_def',
                                                        'value' => get_option('oz_status_def', 0),
                                                        'type' => 'select',
                                                        'multiple' => false,
                                                        'values' => Utils::statusesForSelect(false),
                                                    ]
                                                ],
                                            ],
                                        ],
                                    ],
                                ]
                        ],
                        [
                            'title' => __('Redirect to this URL after successful appointment', 'book-appointment-online'),
                            'description' => '',
                            'order' => 90,
                            'fields' => [
                                [
                                    'name' => 'oz_redirect_url',
                                    'value' => get_option('oz_redirect_url', ''),
                                    'type' => 'input',
                                    'multiple' => false,
                                    'values' => [],
                                ]
                            ]
                        ],
                        [
                            'title' => __('Hide button - Skip selecting specialist', 'book-appointment-online'),
                            'description' => '',
                            'order' => 100,
                            'fields' => [
                                [
                                    'name' => 'oz_skip_emp_btn',
                                    'value' => get_option('oz_skip_emp_btn'),
                                    'type' => 'checkbox',
                                    'multiple' => false,
                                    'values' => [
                                        [
                                            'label' => '',
                                            'value' => 'oz_skip_emp_btn'
                                        ],
                                    ],
                                ]
                            ],
                        ],
                    ],
                ],
                'form' => [
                    'name' => __('Form settings', 'book-appointment-online'),
                    'options' => [
                        [
                            'title' => __('Form fields', 'book-appointment-online'),
                            'description' => '',
                            'order' => 10,
                            'noborder' => true,
                            'fields' => [
                                [
                                    'name' => 'oz_polya[email]',
                                    'value' => Utils::convertDeprecated('email'),
                                    'title' => __('Email', 'book-appointment-online'),
                                    'description' => '',
                                    'type' => 'checkbox',
                                    'multiple' => true,
                                    'values' => [
                                        [
                                            'label' => __('Yes', 'book-appointment-online'),
                                            'value' => 'name'
                                        ],
                                        [
                                            'label' => __('Required', 'book-appointment-online'),
                                            'value' => 'req'
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'title' => '',
                            'description' => '',
                            'order' => 20,
                            'noborder' => true,
                            'fields' => [
                                [
                                    'name' => 'oz_polya[tel]',
                                    'value' => Utils::convertDeprecated('tel'),
                                    'title' => __('Phone', 'book-appointment-online'),
                                    'description' => '',
                                    'type' => 'checkbox',
                                    'multiple' => true,
                                    'values' => [
                                        [
                                            'label' => __('Yes', 'book-appointment-online'),
                                            'value' => 'name'
                                        ],
                                        [
                                            'label' => __('Required', 'book-appointment-online'),
                                            'value' => 'req'
                                        ],
                                    ],
                                ],                                
                            ],
                        ],
                        [
                            'title' => '',
                            'description' => '',
                            'order' => 30,
                            'noborder' => true,
                            'fields' => [
                                
                                [
                                    'name' => 'oz_custom_tel_country',
                                    'value' => get_option('oz_custom_tel_country'),
                                    'title' => __('Custom placeholder for your country', 'book-appointment-online'),
                                    'description' => __('Leave this field blank if you want default placeholder', 'book-appointment-online'),
                                    'type' => 'select',
                                    'multiple' => false,
                                    'values' => Utils::countriesForSelect(true),
                                ],
                                [
                                    'name' => 'oz_custom_tel_placeholder',
                                    'value' => get_option('oz_custom_tel_placeholder', ''),
                                    'title' => __('Custom placeholder for your country', 'book-appointment-online'),
                                    'description' => __('Without your country code. Point is a number. Example:', 'book-appointment-online')." (...) ...-..-..",
                                    'type' => 'input',
                                    'multiple' => false,
                                    'values' => [],
                                ]
                            ]
                        ],
                        [
                            'title' => '',
                            'description' => '',
                            'order' => 40,
                            'fields' => [
                                
                                [
                                    'name' => 'oz_tel_country',
                                    'value' => get_option('oz_tel_country'),
                                    'title' => __('Show the flag of only those countries', 'book-appointment-online'),
                                    'description' => __('Leave this field blank if all countries are needed', 'book-appointment-online'),
                                    'type' => 'select',
                                    'multiple' => true,
                                    'values' => Utils::countriesForSelect(),
                                ],
                            ]
                        ],
                        [
                            'title' => __('Final message', 'book-appointment-online'),
                            'description' => __('This message is visible at the last stage when the appointment is made. HTML is allowed', 'book-appointment-online'),
                            'order' => 60,
                            'grid' => 1,
                            'fields' => [
                                [
                                    'name' => 'oz_finalMessage',
                                    'value' => get_option('oz_finalMessage', ''),
                                    'type' => 'textarea',
                                    'html' => true,
                                    'multiple' => false,
                                    'values' => [],
                                ],
                                [
                                    'name' => 'oz_final_codes',
                                    'value' => '',
                                    'type' => 'shortcodes',
                                    'multiple' => false,
                                ],
                                
                            ],
                        ],
                    ],
                ],
                'email' => [
                    'name' => __('Email marketing', 'book-appointment-online'),
                    'options' => [],
                ],
                'payment' => [
                    'name' => __('Payment options', 'book-appointment-online'),
                    'options' => [
                        [
                            'title' =>  __('Enable', 'book-appointment-online'),
                            'description' => '',
                            'order' => 10,
                            'fields' => [
                                [
                                    'name' => 'oz_payment',
                                    'value' => get_option('oz_payment'),
                                    'type' => 'checkbox',
                                    'multiple' => false,
                                    'values' => [
                                        [
                                            'label' => '',
                                            'value' => 'oz_payment'
                                        ],
                                    ],
                                ],
                                
                            ],
                        ],
                    ],
                    
                ],
                'sms' => [
                    'name' => __('SMS', 'book-appointment-online'),
                    'options' => [],
                ],
                'integration' => [
                    'name' => __('Integration', 'book-appointment-online'),
                    'options' => [],
                    
                ]
            ];
    }
}