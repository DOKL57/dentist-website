<?php 
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/*
send email to client

$idKlienta = post id clienta
$komy = client
*/


$title_spec = get_the_title(get_post_meta($idKlienta,'oz_personal_field_id',true));
$title_usl = get_the_title(get_post_meta($idKlienta,'oz_uslug_set',true));
$time = apply_filters('book_oz_timeFormat',get_post_meta($idKlienta,'oz_time_rot',true));
$datas = array(
__('Date', 'book-appointment-online') => array(
	'value' => get_post_meta($idKlienta,'oz_start_date_field_id',true)
	),
__('Time booking', 'book-appointment-online') => array(
	'value' => $time,
	),
__('Service', 'book-appointment-online') => array(
	'value' => $title_usl,
	),
__('Specialist', 'book-appointment-online') => array(
	'value' => $title_spec ,
	)
);
 ?>
 <html>
	<body>
		<p><?php _e('Your appointment will start in 1 hour', 'book-appointment-online'); ?></p>
		<p><?php echo get_the_title($idKlienta); ?>, <?php _e('Recall your data:', 'book-appointment-online'); ?></p>
		<?php if ($datas) : ?>
			<ul>
			<?php foreach($datas as $key => $data) :
			?>
			<?php if ($data['value']) : ?>
				<li><b><?php echo $key; ?>:</b> <?php echo $data['value']; ?></li>
			<?php endif; ?>
			<?php endforeach; ?>
			</ul>
	</body>
 </html>
<?php endif; ?>