<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2019 Ozplugin
 * @ver 3.1.0
 */

use Ozplugin\Assets;

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
$employee_id = get_the_id();
$atts = json_encode([
	'employee' => [$employee_id],
	'page_type' => 'employee'
]);
Assets::book_oz_front_scripts(true);
$result = do_action('book_oz_before_appointment_form')."<div data-atts='$atts' id='oz_appointment'></div>".do_action('book_oz_after_appointment_form');
?>
<div class="oz_employee_container oz_container">
	<?php 	while ( have_posts() ) :
				the_post();
				?>
	<article id="post-<?php the_ID(); ?>" <?php post_class('oz_employee_section oz_book_flex'); ?>>
		<div class="oz_employee_info">
			<div class="oz_employee_info_wrap">
				<div class="oz_employee_img">
					<?php $img = (get_the_post_thumbnail_url(get_the_id(), 'oz_employee_img')) ? get_the_post_thumbnail_url(get_the_id(), 'oz_employee_img') : esc_url(plugins_url( 'images/pers-ava.svg', dirname(__FILE__) )); ?>
					<img class="oz_emp_img" src="<?php echo $img; ?>" alt="<?php the_title(); ?>" />
				</div>
			<p class="oz_name"><?php the_title(); ?></p>
			<div class="oz_emp_meta">
				<p class="oz_prof"><?php echo get_post_meta(get_the_id(), 'oz_specialnost', true); ?></p>
				<?php $branches = wp_get_post_terms($id,'filial');
					$branch = ($branches) ? implode(', ', array_column($branches,'name')) : ''; ?>
				<p class="oz_emp_branch"><?php echo $branch; ?></p>
			<?php
				edit_post_link();
			?>
			</div>
			<div class="oz_emp_description"><?php the_content(); ?></div>
			</div>
		</div>
		<div class="oz_employee_calendar">
			<?php echo $result == 1 ? __('It seems that the plugin is already running somewhere on the site. If not, please contact our support team.', 'book-appointment-online') : $result; ?>
		</div>
	</article><!-- #post-${ID} -->
				<?php
			endwhile;
				?>

</div>
<?php
get_footer();