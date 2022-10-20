<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
namespace Ozplugin;

use Exception;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Utils {
    public static function get_statuses() {
        return 		[
            'approved' => array(
                'status' => 'approved',
                'name' => __("Approved",'book-appointment-online'),
                'color' => '#2dde98'
            ),
            'onhold' => array(
                'status' => 'onhold',
                'name' => __("On hold",'book-appointment-online'),
                'color' => '#F2B134'
            ),
            'canceled' => array(
                'status' => 'canceled',
                'name' => __("Canceled",'book-appointment-online'),
                'color' => '#ED553B'
            ),
        ];
    }

	public static function statusesForSelect($default = true) {
		$statuses = self::get_statuses();
		$st = [];
        if ($default) {
            $st[] = [
                'label' => __("Default", 'book-appointment-online'),
                'value' => ''
            ];
        }
		foreach($statuses as $key => $status) {
			$st[] = [
				'label' => $status['name'],
				'value' => $key,
			];
		}
		return $st;
	}

    public static function wooStatusesForSelect() {
        $statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [];
		$st = [
            [
                'label' => __("Any", 'book-appointment-online'),
                'value' => '',
            ],
            [
                'label' => __("Don't send email", 'book-appointment-online'),
                'value' => 'not',
            ]
        ];
		foreach($statuses as $key => $status) {
			$st[] = [
				'label' => $status,
				'value' => $key,
			];
		}
		return $st;
    }

    
    public static function postsForSelect($posts) {
        $options = [
            [
                'label' => __("No", 'book-appointment-online'),
                'value' => '',
            ],
        ];
        if (count($posts)) {
            $newOptions = array_map(function($val) { return [
                'label' => $val->post_title,
                'value' => $val->ID
            ];}, $posts);
            $options = array_merge($options, $newOptions);
        }
        return $options;
    }

    public static function timeDurationForSelect($minutes) {
        $options = [];
        foreach($minutes as $min) {
            $options[] = [
                    'label' => $min.' '.__('minutes', 'book-appointment-online'),
                    'value' => $min
            ];
        }
        return $options;
    }

    public static function timeDuration() {
        return [10,15,20,30,40,60,120];
    }

    public static function generateForSelect($arr, $word = '', $before = [], $duration = '') {
        $options = [];
        if (!empty($before)) {
            foreach($before as $key => $ar) {
                $options[] = [
                    'label' => $ar,
                    'value' => $key
                ];
            }            
        }
        foreach($arr as $ar) {
            $label = $ar.' '.$word;
            if (!$word && $duration) {
                if ($duration == 'h') {
                    $label = ($ar >= 24) ? ($ar/24).' '.__('d', 'book-appointment-online') : $ar.' '.__('h', 'book-appointment-online');
                }
                elseif ($duration == 'm') {
                        if ($ar < 60) {
                            $label = $ar.' '.__('minutes', 'book-appointment-online');
                        }
                        elseif ($ar == 60) {
                            $label = '1 '.__('hour', 'book-appointment-online');
                        }
                        elseif ($ar > 60 && $ar != 1440) {
                            $label = ($ar/60).' '.__('hours', 'book-appointment-online');
                        }
                        elseif ($ar == 1440) {
                            $label = '1 '.__('day', 'book-appointment-online');
                        }
                }
            }
            $options[] = [
                    'label' => $label,
                    'value' => $ar
            ];
        }
        return $options;
    }

    public static function generateForSelectK($arr = []) {
        $options = [];
        if (is_array($arr)) {
            foreach ($arr as $label => $ar) {
                $options[] = [
                    'label' => $label,
                    'value' => $ar
                ];
            }
        }
        return $options;
    }

    public static function maybe_json($string) {
        if (is_array($string)) {
            return $string;
        }
        if (json_decode($string,1)) {
            return json_decode($string,1);
        }
        return $string;
    }

    public static function convertDeprecated($type = 'email') {
        $def = ['name' => 1, 'req' => 1];
        $value = get_option('oz_polya');
        if (!$value) {
            return $def;
        }
        else {
            $value = isset($value[$type]) ? $value[$type] : [];
            if (!isset($value['name'])) {
                $value['name'] = false;
            }
            if (!isset($value['req'])) {
                $value['req'] = false;
            }
            //$value = array_merge($def, $value);
        }
        return $value;
    }

    public static function shortcodesSMS($values = []) {
        $arr = [
            'book_oz_id' => __('Appointment ID', 'book-appointment-online'),
            'book_oz_name' => __('Client name', 'book-appointment-online'),
            'book_oz_timebooking' => __('Time booking', 'book-appointment-online'),
            'book_oz_timebooking_tz' => __('Time booking', 'book-appointment-online').' '.__('(in the customer\'s time zone)', 'book-appointment-online'),
            'book_oz_clientphone' => __('Client phone', 'book-appointment-online'),
            'book_oz_conference_link' => __('Conference link', 'book-appointment-online'),
            'book_oz_cancel_link' => __('Cancel appointment link', 'book-appointment-online'),
            'book_oz_timesms' => __('Time-reminder', 'book-appointment-online'),
            'book_oz_appointment_status' => __('New status', 'book-appointment-online'),
        ];
        if (empty($values)) {
            $values = array_keys($arr);
        }
        ob_start();
        echo '<div class="col">'."\n";
        echo '<label>'. __('Shortcode values', 'book-appointment-online').'</label><br/>'."\n";
        foreach($arr as $key => $ar) {
            if (in_array($key,$values)) {
                echo '<span class="oz_code">['.$key.']</span> - '.$ar.'<br>'."\n";
            }
        }
        echo '</div>'."\n";
        return ob_get_clean();
    }

    public static function sanitize_json(&$item, $key) {
        if (is_numeric($item)) {
            $item = floatval($item);
        }
        elseif($item == 'true' || $item == 'false' || !$item) {
            $item = boolval($item);
        }
        else {
            if ($item && $key == 'values') {
                $item = $item; // todo how to sanitize it but skip \n symbols
            }
            else {
                $item = sanitize_text_field($item);
            }
        }
    }

    public static function steps() {
        $steps = [
            'employees' => __('Employees', 'book-appointment-online'),
            'date,time' => __('Date and time', 'book-appointment-online'),
            'services' => __('Services', 'book-appointment-online'),
            'form' => __('Form', 'book-appointment-online'),
        ];
        return $steps;
    }

    public static function steps_html() {
        $steps = self::steps();
        $sort = get_option('oz_step_sequence');
        ob_start();
        ?>
        <div class="oz_steps_seq oz_flex">
            <?php 
            if (!$sort) $sort = ['employees', 'date,time', 'services', 'form'];
            $sort = is_array($sort) ? $sort : json_decode($sort, true);
            foreach ($sort as $step) : 
            if (isset($steps[$step])) :
            $disabled = $step == 'form' ? 'ui-state-disabled' : '';
            ?>	
            <div data-step="<?php echo $step; ?>" class="oz_step ui-state-default <?php echo $disabled; ?>"><?php echo $steps[$step]; ?></div>
            <?php 
            endif;
            endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function getSteps() {
        $steps = get_option('oz_step_sequence');
        if ($steps) {
            if (is_array($steps)) return $steps;
            else 
            return json_decode($steps, true);
        }
        return array_keys(self::steps()); 
    }

    public static function countries() {
        $json = '[{"af":"Afghanistan"},{"al":"Albania"},{"dz":"Algeria"},{"ad":"Andorra"},{"ao":"Angola"},{"ag":"Antigua and Barbuda"},{"ar":"Argentina"},{"am":"Armenia"},{"aw":"Aruba"},{"au":"Australia"},{"at":"Austria"},{"az":"Azerbaijan"},{"bs":"Bahamas"},{"bh":"Bahrain"},{"bd":"Bangladesh"},{"bb":"Barbados"},{"by":"Belarus"},{"be":"Belgium"},{"bz":"Belize"},{"bj":"Benin"},{"bt":"Bhutan"},{"bo":"Bolivia"},{"ba":"Bosnia and Herzegovina"},{"bw":"Botswana"},{"br":"Brazil"},{"io":"British Indian Ocean Territory"},{"bn":"Brunei"},{"bg":"Bulgaria"},{"bf":"Burkina Faso"},{"bi":"Burundi"},{"kh":"Cambodia"},{"cm":"Cameroon"},{"ca":"Canada"},{"cv":"Cape Verde"},{"bq":"Caribbean Netherlands"},{"cf":"Central African Republic"},{"td":"Chad"},{"cl":"Chile"},{"cn":"China"},{"co":"Colombia"},{"km":"Comoros"},{"cd":"Congo"},{"cg":"Congo"},{"cr":"Costa Rica"},{"ci":"Côte d’Ivoire"},{"hr":"Croatia"},{"cu":"Cuba"},{"cw":"Curaçao"},{"cy":"Cyprus"},{"cz":"Czech Republic"},{"dk":"Denmark"},{"dj":"Djibouti"},{"dm":"Dominica"},{"do":"Dominican Republic"},{"ec":"Ecuador"},{"eg":"Egypt"},{"sv":"El Salvador"},{"gq":"Equatorial Guinea"},{"er":"Eritrea"},{"ee":"Estonia"},{"et":"Ethiopia"},{"fj":"Fiji"},{"fi":"Finland"},{"fr":"France"},{"gf":"French Guiana"},{"pf":"French Polynesia"},{"ga":"Gabon"},{"gm":"Gambia"},{"ge":"Georgia"},{"de":"Germany"},{"gh":"Ghana"},{"gr":"Greece"},{"gd":"Grenada"},{"gp":"Guadeloupe"},{"gu":"Guam"},{"gt":"Guatemala"},{"gn":"Guinea"},{"gw":"Guinea-Bissau"},{"gy":"Guyana"},{"ht":"Haiti"},{"hn":"Honduras"},{"hk":"Hong Kong"},{"hu":"Hungary"},{"is":"Iceland"},{"in":"India"},{"id":"Indonesia"},{"ir":"Iran"},{"iq":"Iraq"},{"ie":"Ireland"},{"il":"Israel"},{"it":"Italy"},{"jm":"Jamaica"},{"jp":"Japan"},{"jo":"Jordan"},{"kz":"Kazakhstan"},{"ke":"Kenya"},{"ki":"Kiribati"},{"xk":"Kosovo"},{"kw":"Kuwait"},{"kg":"Kyrgyzstan"},{"la":"Laos"},{"lv":"Latvia"},{"lb":"Lebanon"},{"ls":"Lesotho"},{"lr":"Liberia"},{"ly":"Libya"},{"li":"Liechtenstein"},{"lt":"Lithuania"},{"lu":"Luxembourg"},{"mo":"Macau"},{"mk":"Macedonia"},{"mg":"Madagascar"},{"mw":"Malawi"},{"my":"Malaysia"},{"mv":"Maldives"},{"ml":"Mali"},{"mt":"Malta"},{"mh":"Marshall Islands"},{"mq":"Martinique"},{"mr":"Mauritania"},{"mu":"Mauritius"},{"mx":"Mexico"},{"fm":"Micronesia"},{"md":"Moldova"},{"mc":"Monaco"},{"mn":"Mongolia"},{"me":"Montenegro"},{"ma":"Morocco"},{"mz":"Mozambique"},{"mm":"Myanmar"},{"na":"Namibia"},{"nr":"Nauru"},{"np":"Nepal"},{"nl":"Netherlands"},{"nc":"New Caledonia"},{"nz":"New Zealand"},{"ni":"Nicaragua"},{"ne":"Niger"},{"ng":"Nigeria"},{"kp":"North Korea"},{"no":"Norway"},{"om":"Oman"},{"pk":"Pakistan"},{"pw":"Palau"},{"ps":"Palestine"},{"pa":"Panama"},{"pg":"Papua New Guinea"},{"py":"Paraguay"},{"pe":"Peru"},{"ph":"Philippines"},{"pl":"Poland"},{"pt":"Portugal"},{"pr":"Puerto Rico"},{"qa":"Qatar"},{"re":"Réunion"},{"ro":"Romania"},{"ru":"Russia"},{"rw":"Rwanda"},{"kn":"Saint Kitts and Nevis"},{"lc":"Saint Lucia"},{"vc":"Saint Vincent and the Grenadines"},{"ws":"Samoa"},{"sm":"San Marino"},{"st":"São Tomé and Príncipe"},{"sa":"Saudi Arabia"},{"sn":"Senegal"},{"rs":"Serbia"},{"sc":"Seychelles"},{"sl":"Sierra Leone"},{"sg":"Singapore"},{"sk":"Slovakia"},{"si":"Slovenia"},{"sb":"Solomon Islands"},{"so":"Somalia"},{"za":"South Africa"},{"kr":"South Korea"},{"ss":"South Sudan"},{"es":"Spain"},{"lk":"Sri Lanka"},{"sd":"Sudan"},{"sr":"Suriname"},{"sz":"Swaziland"},{"se":"Sweden"},{"ch":"Switzerland"},{"sy":"Syria"},{"tw":"Taiwan"},{"tj":"Tajikistan"},{"tz":"Tanzania"},{"th":"Thailand"},{"tl":"Timor-Leste"},{"tg":"Togo"},{"to":"Tonga"},{"tt":"Trinidad and Tobago"},{"tn":"Tunisia"},{"tr":"Turkey"},{"tm":"Turkmenistan"},{"tv":"Tuvalu"},{"ug":"Uganda"},{"ua":"Ukraine"},{"ae":"United Arab Emirates"},{"gb":"United Kingdom"},{"us":"United States"},{"uy":"Uruguay"},{"uz":"Uzbekistan"},{"vu":"Vanuatu"},{"va":"Vatican City"},{"ve":"Venezuela"},{"vn":"Vietnam"},{"ye":"Yemen"},{"zm":"Zambia"},{"zw":"Zimbabwe"}]';
        $countries = json_decode($json, 1);
        return $countries;
    }

    public static function countriesForSelect($empty = false) {
        $countries = self::countries();
        $arr = [];
        if ($empty) {
            $arr[] = [
                'label' => __('No', 'book-appointment-online'),
                'value' => '',
            ];
        }
        foreach($countries as $country) {
            $value = array_keys($country)[0];
            $label = array_values($country)[0];
            $arr[] = [
                'label' => $label,
                'value' => $value,
            ];
        }
        return $arr;
    }

    public static function checkUpdater() {
        try {
        $func = new \ReflectionMethod(Updater::class, 'schedule');
        $filename = $func->getFileName();
        $start_line = $func->getStartLine() - 1;
        $end_line = $func->getEndLine();
        $length = $end_line - $start_line;

        $source = file($filename);
        $body = implode("", array_slice($source, $start_line, $length));
            if (md5($body) != '86d80566aaec43ea79d1a30f5e6493b4') {
                update_option('oz_purchase_code','');
                update_option('oz_autoupdated','');
            }
        }
        catch(Exception $err) {

        }
    }

    public static function purchaseInfo() {
        $res = '';
        $code = get_option('oz_purchase_code');
        $auto = get_option('oz_autoupdated');
        if ($code && $auto) {
            ob_start();
            ?>
            <div class="alert alert-success">
              <div><?php _e('Purchase code', 'book-appointment-online'); ?> : <?php echo $code; ?></div>  
              <div><?php _e('Support expires', 'book-appointment-online'); ?> : <?php echo $auto['until']; ?></div>
            </div>  
            <?php
            $res = ob_get_clean();
        }
        return apply_filters('book_oz_pluginInfo', $res);
    }
    
    /**
     * Check if site has translation made with Loco for 3.0.8 version plugin and less
     *
     * @return bool|array
     */
    public static function checkLocoProi18n($array = false) {
        $loco_dir = glob(ABSPATH. '/wp-content/languages/loco/plugins/book-appointment-online-pro*');
        if ($array) {
            return $loco_dir;
        }
        return count($loco_dir) > 0;
    }

}