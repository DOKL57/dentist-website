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

class AddonInstaller {
    private $API = 'http://api.oz-plugin.ru';
    public function init() {
        add_action('wp_ajax_oz_install_addon', [$this, 'route']);
        add_action('wp_ajax_oz_getAddons', [$this, 'getAddons']);
    }

    public function getAddons() {
        if (wp_doing_ajax() && check_ajax_referer('ozajax-nonce')) {
            $lang = get_locale();
            $response = wp_remote_get($this->API.'/addons?lang='.$lang);
            $answ = wp_remote_retrieve_body( $response );
            if (!is_wp_error($response)) {
                echo $answ;
            }
            else {
                echo json_encode([
                    'success' => false
                ]);
            }
        }
        wp_die();
    }

    private function download($part = 1) {
        $code = get_option('oz_purchase_code');
        $addon = sanitize_text_field($_POST['addon']); 
        $response = wp_remote_post($this->API.'/addons', [
            'body' => [
                'addon' => $addon,
                'code' => $code,
                'part' => $part,
            ]
        ]);   
        if (!is_wp_error($response)) {
            $isPart = wp_remote_retrieve_header( $response, 'Content-Part' );
            $isPart = $isPart ? explode('/',$isPart) : null;
            if (!$isPart) {
                return [
                    'success' => false,
                    'message' => 'Error when dowloading'
                ];                
            }
            $current = $isPart[0];
            $all = $isPart[1];
            if ($current <= $all) {
                $isFile = wp_remote_retrieve_header( $response, 'Content-Disposition' );
                //print_r($isFile);
                preg_match("/filename=\"(.*)\"/", $isFile, $output);
                if ($output && $output[1]) {
                    $body = wp_remote_retrieve_body( $response);
                    $path = OZAPP_ADDONS_PATH;
                    $ext = explode('.', $output[1]);
                    $file = $output[1];
                    if (count($ext) && $ext[1]) {
                        $ext = end($ext);
                        switch($ext) {
                            case 'js':
                                $path = OZAPP_PATH.'/assets/js/';
                            break;
                        }
                    }
                    // todo check hash of the files with sha1 for example
                    $success = file_put_contents($path.$file, $body);
                    if ($success) {
                        if ($current < $all) {
                            $this->download($part+1);
                        }
                        return [
                            'success' => true,
                        ];
                    }
                    else {
                        return [
                            'success' => false,
                        ];                          
                    }
                }
                else {
                    $answer = [
                        'success' => false,
                        'message' => 'Addon not found or problem with downloading'
                    ]; 
                    $body = json_decode(wp_remote_retrieve_body( $response),1);
                    if (isset($body['success']) && $body['success'] === false) {
                        $answer = $body;                        
                    }   
                    return $answer;           
                }
            }
            else {
               return [
                    'success' => false,
                    'message' => 'Addon not found or problem with downloading'
                ];
            }
        }
        else {
            return [
                'success' => false,
                'message' => 'Error with HTTP request'
            ];
        }
    }

    public function route() {
       if (wp_doing_ajax() && check_ajax_referer('ozajax-nonce') && isset($_POST['addon'])) {
                $answer = [
                    'success' => false,
                    'message' => 'In development'
                ];
                // todo download addon from server
                // $answer = $this->download();
                // if ($answer['success']) {
                //     $addon = sanitize_text_field($_POST['addon']);
                //     $Addons = array_unique(get_option('oz_addons', []));
                //     if (!in_array($addon, $Addons)) {
                //         array_push($Addons, $addon);
                //         update_option('oz_addons', $Addons);
                //     }
                // }
                echo json_encode($answer);
        }
        wp_die();
    }
}