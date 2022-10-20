<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
namespace Ozplugin;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LocallyPayment {
    public $options = [];
    public $name = null;

    public function init() {
        $this->name = __('Locally', 'book-appointment-online');
        $this->options = [
            'locally' =>
            [
            'name' => $this->name
            ]
        ];
        add_action('book_oz_addNewPaymentMethod', [$this, 'add']);
        add_filter('book_oz_plugin_settings', [$this, 'setOptions']); // only for locally payment
        return $this;
    }

    public function add($payment) {
        $payment->addPaymentMethod($this->options);
    }

    public function setOptions($settings) {
        $options = $this->getOptions();
        $settings['payment']['options'] = array_merge($settings['payment']['options'], $options['payment']);
        return $settings;
    }

    public function getOptions() {
        return [
            'payment' => [
                            [
                                'title' =>  __('Locally', 'book-appointment-online'),
                                'description' => '',
                                'order' => 20,
                                'fields' => [
                                    [
                                        'name' => 'oz_payment_locally',
                                        'value' => get_option('oz_payment_locally'),
                                        'type' => 'checkbox',
                                        'multiple' => false,
                                        'values' => [
                                            [
                                                'label' => '',
                                                'value' => 'oz_payment_locally'
                                            ],
                                        ],
                                    ],
                                    
                                ],
                            ]
                        ]
                ]; 
    }
}