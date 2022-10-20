<!DOCTYPE html>
<html class="no-js <?php echo current_user_can('oz_employee') ? 'm-0' : '';?>" <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0" >
		<link rel="profile" href="https://gmpg.org/xfn/11">
		<title><?php echo wp_get_document_title(); ?></title>
		<?php wp_head(); ?>
		<style>
			.oz_employee_profile_footer {
				>*:not(#wpadminbar) {
					display: none;
				}
			}
		</style>
	</head>
	<body <?php body_class('oz_employee_profile_page'); ?>>
	<?php do_action('book_oz_before_employee_profile_screen'); ?>
	<?php if (current_user_can('administrator') || current_user_can('oz_employee') || book_oz_user_can()) : ?>
		<div id="oz_employee_profile"></div>
	<?php elseif (is_user_logged_in()) : ?>
		<div><?php _e('sorry, you do not have permission to view this page', 'book-appointment-online'); ?></div>
	<?php else : ?>
		<div id="oz_auth_form"></div>
	<?php endif; ?>
	<?php do_action('book_oz_after_employee_profile_screen'); ?>
<div class="oz_employee_profile_footer">
		<?php wp_footer(); ?>
</div>
	</body>
</html>