<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2019 Ozplugin
 * @ver 3.0.3
 */

use Ozplugin\Assets;

if ( ! defined( 'ABSPATH' ) ) { exit; }
$employee_id = get_the_id();
$atts = [];
if (isset($_GET['ID']) && $_GET['ID']) {
	$atts = [
		'employee' => [(int) ($_GET['ID'])],
		'page_type' => 'employee'
	];
}
$atts = json_encode($atts);
Assets::book_oz_front_scripts(true);
$result = do_action('book_oz_before_appointment_form')."<div data-atts='$atts' id='oz_appointment'></div>".do_action('book_oz_after_appointment_form');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="robots" content="noindex, nofollow">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<?php wp_head(); ?>
	<style>
	body .oz_container .oz_hid .oz_hid_carousel {
		height:auto !important;
	}
	.oz_my_app_block {
		display:none;
	}
	
	body .oz_container .oz_multiselect_step_mobile {
		bottom: initial !important;
		top:-200px;
		right: 25px;
		left:initial !important;
		position: fixed;
		text-align: right !important;
		padding: 0 !important;
		width: auto;	
	}
	
	body .oz_container .oz_multiselect_step_mobile.active {
		bottom: initial !important;
		top: 5px;
		right: 25px;
		left:initial !important;
		width: auto;
	}
	body {
		height:100%;
		overflow:auto;
	}
	body .oz_hid .oz_hid_carousel > * {
		max-height: 9999999px !important;
	}
	
	body .oz_container .oz_hid .oz_hid_carousel > * {
		padding:20px 0 !important;
	}
	
	body > *:not(.oz_employee_container) {
		display:none !important;
	}
	
	.pers-content .oz_btn.oz_btn_link {
		display:none;
	}

	</style>
</head>
<body style="height:100%">
<div class="oz_employee_container oz_container">
	<div class="">
		<?php echo $result == 1 ? __('It seems that the plugin is already running somewhere on the site. If not, please contact our support team.', 'book-appointment-online') : $result; ?>
	</div>
</div>
<?php wp_footer(); ?>
<?php if (isset($_GET['ID']) && $_GET['ID']) : ?>
	<script>
	oz_vars.employee_page = true;
	</script>
<?php endif; ?>
</body>
</html>