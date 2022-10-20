<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
namespace Ozplugin;
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Ozplugin\Addons\Addon;

abstract class PaymentMethod extends Addon {

    //abstract public function createCheckoutBefore()
    //abstract public function createCheckout()
    //abstract public function startCheck()

}