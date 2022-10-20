<?php 
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$result = do_action('book_oz_before_appointment_form').'<div id="oz_appointment"></div>'.do_action('book_oz_after_appointment_form');
 ?>

	<div id="oz_overlay">
		<div class="oz_popup"><span class="close">×</span>
			<?php echo $result == 1 ? __('It seems that the plugin is already running somewhere on the site. If not, please contact our support team.', 'book-appointment-online') : $result;
			?>
		</div>
	</div>