<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/*
2.0.0 Break list function
*/
add_action('book_oz_before_metabox','book_oz_breakListSelect',11,1);

function book_oz_breakListSelect($arg) {
	global $post;
	if ($arg == 'book_oz_worktime' ) {
		?>
		<div class="oz_table_time_div">
			<h4 class="oz_h4"><?php _e('Breaks', 'book-appointment-online'); ?></h4>
			<table data-values="oz_breaklist" data-text="<?php _e('Add break hours', 'book-appointment-online'); ?>" class="oz_breaktime oz_table_time">
				<thead>
					<tr>
					<th><?php _e('Breaks', 'book-appointment-online'); ?></th>
					<th><?php _e('MO', 'book-appointment-online'); ?></th>
					<th><?php _e('TU', 'book-appointment-online'); ?></th>
					<th><?php _e('WE', 'book-appointment-online'); ?></th>
					<th><?php _e('TH', 'book-appointment-online'); ?></th>
					<th><?php _e('FR', 'book-appointment-online'); ?></th>
					<th><?php _e('SA', 'book-appointment-online'); ?></th>
					<th><?php _e('SU', 'book-appointment-online'); ?></th>
					<th></th>
					</tr>
				</thead>
				<tbody>
					<tr class="remove">
					<td colspan="7"><?php _e('Add break hours', 'book-appointment-online'); ?></td>
					<td></td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td>			
							<div class="oz_btn add-date button button-primary button-large"><span class="dashicons dashicons-plus"></span> <?php _e('Add new line', 'book-appointment-online'); ?></div>
						</td>
					</tr>
					<tr>
						<td colspan="9">
							<div class="add-block hide">
								<input type="text" class="at-time" name="oz_break" id="oz_start" placeholder="<?php _e('Time start', 'book-appointment-online'); ?>" data-ampm='false' rel='hh:mm' value='' size='30'>
								<input type="text" class="at-time" name="oz_break" id="oz_end" placeholder="<?php _e('Time end', 'book-appointment-online'); ?>" data-ampm='false' rel='hh:mm' value='' size='30'>
								<div class="add-break-time button button-primary button-large"><span class="dashicons dashicons-plus"></span> <?php _e('Add', 'book-appointment-online'); ?></div>
							</div>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
	}
}

add_action('book_oz_before_metabox','book_oz_daysOffList',11,1);
/**
 *  Days off interface in personal
 *  
 *  @param string    $arg name of metabox
 *  @return void
 *  
 *  @version 2.0.5
 */
function book_oz_daysOffList($arg) {
	global $post;
	if ($arg == 'book_oz_worktime' ) {
		global $post;
		$daysoff = get_post_meta($post->ID,'oz_days_off_list',true);
		$futureDaysOff = $daysoff;
		// $current_day = current_time('d.m.Y');
		// $futureDaysOff = array();
		// if ($daysoff) {
			// $daysoff = explode(',', $daysoff);
			// foreach ($daysoff as $day) :
				// if (strtotime($day) >= strtotime($current_day)) $futureDaysOff[] = $day;
			// endforeach;
			// $futureDaysOff = (!empty($futureDaysOff)) ? implode(',',$futureDaysOff) : '';
		// }
		 ?>
		<div class="oz_days_off_list oz_padding10">
		<h4 class="oz_h4"><?php _e('Days off', 'book-appointment-online'); ?></h4>
		<div class="add-days-off button button-primary button-large <?php if ($futureDaysOff) echo 'hide'; ?>"><span class="dashicons dashicons-plus"></span> <?php _e('Add', 'book-appointment-online'); ?></div>
		<div class="oz_days_off_calendar oz_datepicker <?php if (!$futureDaysOff) echo 'hide'; ?>"></div>
		</div>
		<script>
		(function( $ ) {
			$(document).ready(function() {
			$('input[name="oz_days_off_list"]').val('<?php if ($futureDaysOff) echo $futureDaysOff; ?>');
			$( ".oz_days_off_calendar" ).datepicker({
				numberOfMonths: 6,
				//minDate: 0,
				dateFormat: 'dd.mm.yy',
				multidate: true,
				onSelect: function(dateText,inst) {
					var chosenDay = false;
					if ($('input[name="oz_days_off_list"]').val() != '') {
						
						if ($('input[name="oz_days_off_list"]').val().indexOf(dateText+',') > -1) {
							var newDates = $('input[name="oz_days_off_list"]').val().replace(dateText+',', '');
							$('input[name="oz_days_off_list"]').val(newDates);
							var chosenDay = true;
						}
						else if ($('input[name="oz_days_off_list"]').val().indexOf(dateText) > -1) {
							var newDates = $('input[name="oz_days_off_list"]').val().replace(dateText,'');
							$('input[name="oz_days_off_list"]').val(newDates);
							var chosenDay = true;
						}
					}
					if (!chosenDay) {
						var offs = ($('input[name="oz_days_off_list"]').val()) ? $('input[name="oz_days_off_list"]').val()+','+dateText : dateText;
						$('input[name="oz_days_off_list"]').val(offs);
					}
				},
				beforeShowDay: function(date){
					var date = moment(date).format('DD.MM.YYYY');
						var offs = ($('input[name="oz_days_off_list"]').val().indexOf(',') > -1) ? $('input[name="oz_days_off_list"]').val().split(',') : $('input[name="oz_days_off_list"]').val() ;
						var active = '';
						if (offs && offs.indexOf(date) > -1) {
							var active = 'active';
						}
					return [ 1, active, ];
				}
			});
			
			$('.add-days-off').click(function() {
				if ($( ".oz_days_off_calendar" ).hasClass('hide')) $( ".oz_days_off_calendar" ).removeClass('hide');
			});
			});
		 })(jQuery);
		</script>
		<?php
	}
}

/**
 *  Content of status column
 *  
 *  @param string    $colname Name of column
 *  @param int    $cptid Post ID
 *  @return content
 *  
 *  @version 2.0.9
 */
function book_oz_clients_statusColumn( $colname, $cptid ) {
     if ( $colname == 'oz_status_td') {
		$status = get_post_meta($cptid,'oz_app_status',true);
		$status = (!$status) ? 'approved' : $status;
		$statuses = array(
		'approved' => array(
			'status' => 'approved',
			'name' => __("Approved", 'book-appointment-online'),
			'color' => '#2dde98'
		),
		'onhold' => array(
			'status' => 'onhold',
			'name' => __("On hold", 'book-appointment-online'),
			'color' => '#F2B134'
		),
		'canceled' => array(
			'status' => 'canceled',
			'name' => __("Canceled", 'book-appointment-online'),
			'color' => '#ED553B'
		),
		);
		$st = isset($statuses[$status]) ? $statuses[$status] : $statuses['approved'];
        ?>
	<div class="oz_admin_select">
		<ul data-select="oz_status_select" class="oz_select oz_select_bottom">
				<li data-values="<?php echo $st['status'];  ?>" class="oz_li oz_li_sub oz_li_def">
				<span style="background-color:<?php echo $st['color'];  ?>" class="oz_status_color"></span>
				<?php echo $st['name'];  ?>
				</li>
				<li class="oz_li oz_li_sub oz_li_dropdown">
					<ul>
						<?php foreach($statuses as $status) : ?>
						<li class="oz_li_sub_li" data-value="<?php echo $status['status'];  ?>" data-url="<?php echo site_url(); ?>/wp-admin/edit.php?post_type=clients&oz_status=<?php echo $status['status'];  ?>&post=<?php echo $cptid; ?>">
							<span style="background-color:<?php echo $status['color'];  ?>" class="oz_status_color"></span>
							<?php echo $status['name'];  ?>
						</li>
						<?php endforeach; ?>
					</ul>
					<div class="oz_li_sub_li_buttons">
						<?php $email = get_option('oz_e_onStatus') ;
							  $sms = get_option('oz_smsIntegration');
							  if ($sms || $email) :
						?>
						<label>
							<?php _e("Notify customer by", 'book-appointment-online'); ?>:<br>
							<?php if (get_option('oz_e_onStatus')) : ?><span data-url="&oz_notify_email=y" class="oz_notify-icon notify-icon-email"><?php _e("Email", 'book-appointment-online'); ?></span><?php endif; ?>
							<?php if (get_option('oz_smsIntegration')) : ?><span data-url="&oz_notify_sms=y" class="oz_notify-icon notify-icon-sms"><?php _e("SMS", 'book-appointment-online'); ?></span><?php endif; ?>
						</label>
						<?php endif; ?>
						<a class="oz_btn oz_status_url" href="<?php echo site_url(); ?>/wp-admin/edit.php?post_type=clients"><?php _e("Ok", 'book-appointment-online'); ?></a>
					</div>
				</li>
		</ul>
	</div>
		<?php
	 }
}
add_action('manage_clients_posts_custom_column', 'book_oz_clients_statusColumn', 10, 2); 

add_action( 'restrict_manage_posts', 'book_oz_add_filter_status_select' );
/**
 *  Create dropdown filter for statuses
 *  
 *  @param string    $post_type Post type
 *  @return void
 *  
 *  @version 2.0.9
 */
function book_oz_add_filter_status_select($post_type){

    if ($post_type == 'clients' && get_option('book_oz_enable_statuses')){
        $values = array(
            __("Approved", 'book-appointment-online') => 'approved', 
            __("On hold", 'book-appointment-online') => 'onhold',
            __("Canceled", 'book-appointment-online') => 'canceled',
        );
        ?>
        <select name="oz_app_status">
        <option value=""><?php _e('Filter By Status ', 'book-appointment-online'); ?></option>
        <?php
            $current_v = isset($_GET['oz_app_status'])? $_GET['oz_app_status']:'';
            foreach ($values as $label => $value) {
                printf
                    (
                        '<option value="%s"%s>%s</option>',
                        $value,
                        $value == $current_v? ' selected="selected"':'',
                        $label
                    );
                }
        ?>
        </select>
        <?php
    }
}