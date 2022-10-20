<?php 
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 * Email reminder to employee
 */
if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div>
	<strong><?php _e('Service', 'book-appointment-online'); ?>: </strong>%service%<br>
	<strong><?php _e('Specialist', 'book-appointment-online'); ?>: </strong>%employee%<br>
	<strong><?php _e('Date', 'book-appointment-online'); ?>: </strong>%date% %time%<br>
	<strong><?php _e('Duration (min)', 'book-appointment-online'); ?>: </strong>%duration%<br>
	<strong><?php _e('Client data', 'book-appointment-online'); ?>: </strong>%name%, %email%, %phone%<br>
</div>