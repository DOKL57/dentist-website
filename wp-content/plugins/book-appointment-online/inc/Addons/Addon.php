<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2019 Ozplugin
 * @ver 3.0.9
 */

namespace Ozplugin\Addons;

if ( ! defined( 'ABSPATH' ) ) { exit; }

abstract class Addon {

    const NAME = self::NAME;

    public $base = null;

    abstract public function getOptions();
    
    abstract public function init();

    public function setBase($base) {
        $this->base = $base;
        return $this;
    }

    protected function opts($name) {
		return (isset($this->options[$name])) ? $this->options[$name] : '';
	}

	abstract protected function options();

}

?>