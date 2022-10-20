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

class Strings {
    public static function main() {
        return [
            'str1' => __('You booked!', 'book-appointment-online'), //304
            'str2' => __('Morning', 'book-appointment-online'), //436
            'str3' => __('Day', 'book-appointment-online'), //440
            'str4' => __('Evening', 'book-appointment-online'), //444
            'str5' => __('Contact information', 'book-appointment-online'), //604
            'str6' => __('Select service', 'book-appointment-online'),
            'str7' => __('Booking to service', 'book-appointment-online'),
            'str8' => __('Select time booking', 'book-appointment-online'),
            'str9' => __('Select date', 'book-appointment-online'),
            'str10' => __('Select specialist', 'book-appointment-online'),
            'str11' => __('Service not worked at now', 'book-appointment-online'),
            'str12' => __('Skip selecting specialist', 'book-appointment-online'),
            'str13' => __('No available time for selected date', 'book-appointment-online'),
            'str14' => sprintf(__('You will be moved to the payment page in %s seconds. if not, %s click here %s', 'book-appointment-online'),'<b>5</b>','<a class="paypal_link" href="#">', '</a>'),
            'strStaff' => __('Select employee', 'book-appointment-online'),
            'strBr' => __('Select branch', 'book-appointment-online'),
            'strSelect' => __('Select', 'book-appointment-online'),
            'prtch' => __('Proceed to checkout?', 'book-appointment-online'),
            'wfp' => __('Waiting for confirmation of payment', 'book-appointment-online'),
            'smsc' => __('SMS code', 'book-appointment-online'),
            'code_valid' => __('The code is valid for', 'book-appointment-online'),
            'apply' => __('Apply', 'book-appointment-online'),
            'yes' => __('Yes', 'book-appointment-online'),
            'no' => __('No', 'book-appointment-online'),
            'tzmsg' => __('Your time zone is different from the site\'s time zone (%s). Show the time in your time zone?', 'book-appointment-online'),
            ];
    }

    public static function admin() {
        return  [
            'str1' => __('First work day:', 'book-appointment-online'), 
            'str2' => __('Contact with developers!', 'book-appointment-online'), 
            'str3' => __('Add work hours', 'book-appointment-online'),
            'str4' => __('from', 'book-appointment-online'),
            'str5' => __('before', 'book-appointment-online'), 
            'str6' => __('First work day:', 'book-appointment-online'),		
            'str7' => __('Service not set', 'book-appointment-online'),		
            'str8' => __('Date not set', 'book-appointment-online'),
            'str9' => __('Employee schedule is required!', 'book-appointment-online'),
            'user_online' => __('User online', 'book-appointment-online'),
            'lost_connection' => __('Lost connection', 'book-appointment-online'),
            'conference' => __('Conference', 'book-appointment-online'),
            'conf_log' => __('Conference Log', 'book-appointment-online'),
            'name' => __('Name', 'book-appointment-online'),
            'online' => __('Online', 'book-appointment-online'),
            'communication' => __('Communication', 'book-appointment-online'),
            'no_data' => __('No data', 'book-appointment-online'),
            'copied' => __('Copied!', 'book-appointment-online'),
            'pluginsettings' => __('Plugin Settings', 'book-appointment-online'),
            'addons' => __('Addons', 'book-appointment-online'),
            'installing' => __('Installing...', 'book-appointment-online'),
            'install' => __('Install', 'book-appointment-online'),
            'installed' => __('Installed', 'book-appointment-online'),
            'buyregister' => __('Buy and register PRO version to install addons', 'book-appointment-online'),
            'wherecode' => __('Where is my purchase code?', 'book-appointment-online'),
            'register' => __('Register', 'book-appointment-online'),
            'successrefresh' => __('Success! The page will refresh automatically', 'book-appointment-online'),
            'refreshtransl' => __('The plugin version has changed. Need to link old translations with the current version of the plugin', 'book-appointment-online'),
            'update' => __('Update', 'book-appointment-online'),
            'notsettingsthistab' => __('Update', 'book-appointment-online'),
            'backtowp' => __('Back to WP', 'book-appointment-online'),
        ];
    }
}