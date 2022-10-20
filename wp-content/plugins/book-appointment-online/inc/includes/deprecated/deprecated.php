<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * V2 engine function deprecated since version 3.0.5
 */
function book_oz_render_services($service_query = array(), $include_branches = []) {
				$categories = get_categories(array(
					'taxonomy'     => 'oz_service_cats',
					'type'         => 'services',
					'hide_empty'   => 1,
					'include'		=> isset($service_query['cat']) ? $service_query['cat'] : ''
					));
					$categories = ($categories) ? $categories : array((object) array('name' => 'No cats','term_id' => 0) );
				$firstLoop = 1;
				foreach ($categories as $cats) :
					$args = array(
					'post_type' => 'services',
					'post_status' => 'publish',
					'posts_per_page'   => -1,
					);
	if ($cats->term_id > 0) {
	$args['tax_query'] = array(
		array(
			'taxonomy' => 'oz_service_cats',
			'field'    => 'term_id',
			'terms'    => $cats->term_id,
		)
	);	
	}
	if ($service_query) {
		if (isset($service_query['cat'])) unset($service_query['cat']);
		if (isset($service_query['id'])) $args['post__in'] = (is_array($service_query['id'])) ? $service_query['id'] : explode(',',$service_query['id']);
		$args = array_merge($args,$service_query);
	}
$query = new WP_Query( $args );
$skipService = !get_option('oz_skip_step_ifOne',1);
$oneUsl = 0;
if ($query->have_posts() && $query->post_count == 1 && $cats->term_id == 0) {
	$oneUsl = !$skipService;
}
if (!$oneUsl && $firstLoop == 1) echo '<ul data-name="'.__('Select service', 'book-appointment-online').'" class="uslugas">';
	if ($cats->term_id && count($categories) > 1) echo '<li class="cat_zag oz_li-as-acc" id="tabId-'.$cats->term_id.'">'.$cats->name.'</li>';
if ($query) {
	$skolkoUslug = 0; // посчитаем услуги, если ноль, то выдадим ошибку
	while ( $query->have_posts() ) : $query->the_post();
	$id = get_the_id();
	$termsId = (isset($_POST['id'])) ? (int) ($_POST['id']) : '';
	$est_pers = false; /* есть ли персонал с не заданными услугами*/
	$args1 = array();
$args1 = array(
'post_type' => 'personal',
'post_status' => 'publish', //oz_re_timerange
'posts_per_page'   => -1,
);

$query1 = new WP_Query( $args1 );
if ($query1) {
	$newAr = array();
	$fe = array();
	$client_ID = array();
	$branches = array();
	$daysoff = array();
	$clientsRasOneServ = array();
	while ( $query1->have_posts() ) : $query1->the_post();
	$id1 = get_the_id();
	/* исключаем услуги, которые специалист не оказывает или оказывает, только некоторые, тогда включаем их*/
	$incl = get_post_meta($id1,'oz_book_provides_services',true);
	if ($incl == 'exclude') {
		$mass = get_post_meta($id1,'oz_re_timerange',true);
		if (book_oz_in_array_r($id,$mass)) {
			continue;
		}
	}
	if ($incl == 'include') {
		$mass = get_post_meta($id1,'oz_re_timerange',true);
		if (!book_oz_in_array_r($id,$mass)) {
			continue;
		}
	}
	$chistAr = json_decode(get_post_meta($id1,'oz_raspis',true),true);
	$client_ID[] = $id1;
	$brn = get_the_terms($id1,'filial');
	if ($brn) {
		$andBranches = (!empty($include_branches)) ? in_array($brn[0]->term_id,$include_branches) : true;
		if (!isset($branches['branch_'.$brn[0]->term_id]))  {
			if ($andBranches) {
				$branches['branch_'.$brn[0]->term_id] = array(
				'id' => $brn[0]->term_id,
				'name' => $brn[0]->name,
				'personal' => array($id1),
				'description' => $brn[0]->description
				);
			}
		}
		else {
			if ($branches['branch_'.$brn[0]->term_id]) $branches['branch_'.$brn[0]->term_id]['personal'][] = $id1;
		}
	}
	$dayoff = get_post_meta($id1, 'oz_days_off_list',true);
	if ($dayoff) {
		if ($query1->post_count != 1 || ($skipService && $query1->post_count == 1))  {
			$daysoff[] =  array( $id1 => $dayoff, 'pId' => $id1);
		}
		else {
			$daysoff = $dayoff;
		}
	}
	$breakOneServ = get_post_meta($id1, 'oz_breaklist',true);
	if ($breakOneServ) {
		if ($query1->post_count != 1 || ($skipService && $query1->post_count == 1))  {
			$breaksOneServ[$id1] =  json_decode($breakOneServ);
		}
		else {
			$breaksOneServ = $breakOneServ;
		}
	}
	$clientRasOneServ = get_post_meta($id1, 'oz_clientsarray',true);
	if ($clientRasOneServ) {
		$clientRasOneServ = json_decode($clientRasOneServ,true);
		foreach ($clientRasOneServ as $clRasOneServ) {
			$clientsRasOneServ[] = array_merge($clRasOneServ, array('pers_id' => $id1));
		}
	}
	$newAr[] = array_merge($newAr,$chistAr);
	foreach ($chistAr as $key => $chist) {
		if (isset($chist['day']) && $chist['day']) {
	// заменил функцию проверки на уникальность в массиве, просто иссетом в версии 2.0.0  
		$fe[] = array(
		'day' => $chist['day'],
		'start' => $chist['start'],
		'end' => $chist['end'],
		'pId' => (isset($chist['pId'])) ? $chist['pId'] : ''
		);
	}
	elseif (isset($chist['dayF']) && $chist['dayF']) {
		$fe[] = array(
		'dayF' => $chist['dayF'],
		'start' => $chist['start'],
		'end' => $chist['end'],
		'graph' => $chist['graph'],
		'pId' => (isset($chist['pId'])) ? $chist['pId'] : ''
		);
	}
	elseif (isset($chist['days']) && $chist['days']) {
		$fe[] = array(
		'days' => $chist['days'],
		'time' => array(
				'start' => $chist['time']['start'],
				'end' => $chist['time']['end'],
		),
		'pId' => (isset($chist['pId'])) ? $chist['pId'] : ''
		);		
	}	

	}
		endwhile;
	}
	$query1->reset_postdata();
	/* пока что выводятся все услуги без разницы есть они у специалиста или нет */
	if ($fe) :
		$timeUslug = get_post_meta($id,'oz_serv_time',true);
		$mintimeUslug = (!isset($mintimeUslug) || $timeUslug < $mintimeUslug) ? $timeUslug  : $mintimeUslug;
		$price = get_post_meta($id,'oz_serv_price',true);
	if ($oneUsl) {
		//print_r($breaksOneServ);
		$usl_id = $query->posts[0]->ID;
		$daysoff = ($daysoff) ? $daysoff : [];
		$one_found = array(
		'id' =>  $usl_id,
		'time' => $fe,
		'ids' => $client_ID,
		//'clientRas' => json_encode($clBr),
		'daysoff' => $daysoff,
		'breaks' => isset($breaksOneServ) ? $breaksOneServ : array(),
		'clientRas' => isset($clientsRasOneServ) ? $clientsRasOneServ : array(),
		'mtime' => get_post_meta($query->posts[0]->ID, 'oz_serv_time', true)
		
		);
		echo "<script> var oneUsl = ".json_encode(apply_filters('book_oz_usl_params_arr', $one_found, $usl_id),JSON_UNESCAPED_SLASHES).";</script>"."\n";
		echo isset($breaksOneServ) ? "<script> var staffBreaks = ".json_encode($breaksOneServ,JSON_UNESCAPED_SLASHES).";</script>" : '';
	}
	else {
		$badge = get_post_meta($id, 'oz_badge', true);
	?>
	<?php 
	?>
	<li id="<?php echo $id; ?>" data-price="<?php echo $price; ?>" data-mintime="<?php echo $timeUslug; ?>" data-ids="<?php echo json_encode($client_ID); ?>" <?php echo $branches ? "data-branches='".json_encode($branches, JSON_HEX_APOS)."'" : ''; ?> data-days='<?php echo json_encode($fe,JSON_UNESCAPED_SLASHES); ?>' data-raspis="" data-daysoff='<?php if ($daysoff) echo is_array($daysoff) ? json_encode($daysoff,JSON_UNESCAPED_SLASHES) : $daysoff; ?>' data-tabId="<?php echo $cats->term_id; ?>" <?php do_action('book_oz_usl_params', $id); ?> class="oz_service <?php if ($cats->term_id > 0 && count($categories) > 1) echo 'oz_tab_item oz_hide'; ?>">
		<?php if ($badge) : ?><span class="oz_badge oz_badge-right"><?php echo $badge; ?></span><?php endif; ?>	
		<p><?php echo get_the_title($id); ?></p>
			<div class="params_usl">
				<div data-time="<?php echo get_post_meta($id,'oz_serv_time',true); ?>" data-buffer="<?php echo get_post_meta($id,'oz_serv_buffer',true); ?>" class="oz_usl_time">
					<?php  echo get_post_meta($id,'oz_serv_time',true); ?>
					<span class="oz_op"><?php _e('time (min)', 'book-appointment-online'); ?></span>
				</div>
				<?php if ($price) : ?>
				<div class="oz_usl_price">
					<?php echo $price; ?>
					<span class="oz_op"><?php _e('price', 'book-appointment-online'); ?> <?php if (get_option('oz_default_cur')) : echo '('.get_option('oz_default_cur').')'; endif; ?></span>
				</div>
				<?php endif; ?>
			<?php if (get_post_field('post_content', $id)) : ?>
			<div class="oz_serv_content">
				<?php echo get_post_field('post_content', $id); ?>
			</div>
			<?php endif; ?>
			</div>
	</li>
	<?php
	}
	$skolkoUslug++;
	endif;
	endwhile;
	if (!$skolkoUslug) _e('Services not found! Choose employee', 'book-appointment-online'); // добавил 08.04 вместо нижней строки
	if (!$oneUsl) do_action('book_oz_after_uslugas');
	if (isset($mintimeUslug) && $mintimeUslug) echo '<script>var mintimeUslug = '.$mintimeUslug.'; </script>';
	}
	else {
	_e('Error!', 'book-appointment-online');
	}
	if (!$oneUsl && $firstLoop == count($categories)) echo '</ul>';
wp_reset_query();
$firstLoop++;
endforeach; 
}


/**
 * V2 engine function deprecated since version 3.0.5
 */
 
add_action('book_oz_before_step_title', 'book_oz_add_main_preloader');
function book_oz_add_main_preloader() {
	?>
	<div class="oz_loading"></div>
	<?php
}

add_action('book_oz_after_listUslug', 'book_oz_multiselect_serv_btn');
add_action('book_oz_after_uslugas', 'book_oz_multiselect_serv_btn');
/**
 * V2 engine function deprecated since version 3.0.5
 */
function book_oz_multiselect_serv_btn() {
	if (get_option('oz_multiselect_serv') && get_option('book_oz_skip_personal') != 1) :
	?>
	<li class="oz_li_as_div multiselect-block">
		<div class="oz_btn oz_multiselect_step"><?php _e('Next step', 'book-appointment-online'); ?></div>
	</li>
	<?php
	endif;
}
add_action('book_oz_after_step_title', 'book_oz_multiselect_serv_btn_mob');
/**
 * V2 engine function deprecated since version 3.0.5
 */
function book_oz_multiselect_serv_btn_mob() {
	if (!get_option('oz_multiselect_serv')) return;
?>
	<div class="oz_multiselect_step_on_mob">
		<div class="oz_btn oz_multiselect_step"><?php _e('Next step', 'book-appointment-online'); ?></div>
	</div>
<?php
}
add_action('book_oz_li_listUslug', 'book_oz_add_multiselect_elements');
/**
 * V2 engine function deprecated since version 3.0.5
 */
function book_oz_add_multiselect_elements($id) {
	if (!get_option('oz_multiselect_serv')) return;
	?>
	<div class="oz_multi_block">
		<div class="oz_book_flex">
			<div class="oz_ok_multi">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" id="oz_ok_icon"><path class="" fill="none" stroke="" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>
			</div>
			<div class="oz_return_multi">
				<span class="oz_link oz_return_link"><?php _e('Cancel', 'book-appointment-online'); ?></span>
			</div>
		</div>
	</div>
	<?php
}