<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
namespace Ozplugin;
use \DateTime;
//use Ozplugin\Settings;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Employees {
    public function init() {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'addMetabox']);
        add_filter('post_row_actions', [$this, 'book_oz_remove_duplic'],99,2);
        $this->addBranches();

        //deprecated
        add_action('wp_ajax_checkCurrentZapisi', [$this, 'deprecated_book_oz_checkCurrentZapisi']);
        add_action('wp_ajax_checkSvobTime', [$this, 'deprecated_book_oz_checkSvobTime']); // проверяем хватит ли времени до ближайшей следующей записи
        add_action('book_oz_after_metabox', [$this, 'book_oz_calendarSot'],10,2);
        add_action('book_oz_before_metabox', [$this, 'book_oz_rasp_obj'],10,1);
        add_action('book_oz_before_metabox', [$this, 'book_oz_worktime'],10,2);
        add_action('book_oz_before_metabox', [$this, 'book_oz_servListSelect'],11);
        add_action('save_post', [$this, 'book_oz_servListSelectSave']);
    }

    public function registerPostType() {
        register_post_type( 'personal',
		    apply_filters('oz_create_post_type_personal', array(
			'labels' => array(
				'name' => __('Employees', 'book-appointment-online'),
				'singular_name' => __('Employee', 'book-appointment-online'),
				'add_new' => __('Add employee', 'book-appointment-online'),
				'add_new_item' => __('Add employee', 'book-appointment-online'),
				'edit' => __('Edit employee', 'book-appointment-online'),
				'edit_item' => __('Edit employee', 'book-appointment-online'),
				'new_item' => __('New employee', 'book-appointment-online'),
				'view' => __('View employee', 'book-appointment-online'),
				'view_item' => __('View employee', 'book-appointment-online'),
				'search_items' => __('Search employee', 'book-appointment-online'),
				'not_found' => __('Employee not found', 'book-appointment-online'),
				'not_found_in_trash' => __('Employees not found in trash', 'book-appointment-online'),
				'parent' => __('Parent employee', 'book-appointment-online'),
			),
			'taxonomies' => array( '' ),
			'show_ui' => true,
			'public' => true,
			'menu_position' => 7,
			'supports' => array( 'title','editor','thumbnail' ),
			'menu_icon' => 'dashicons-businessman',
			'has_archive' => false,
			'exclude_from_search' => true, 
			'publicly_queryable'  => false,
			'map_meta_cap'        => true,
			'capability_type'     => array('employee','employees'),
		    ))
	    );
    }

    /*добавлена 01.04.18 проверка кто на выбранный день записан*/
    public function deprecated_book_oz_checkCurrentZapisi() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            if (isset($_POST['dateText'])) {
            $zapisi = array();
            $currentClient = (isset($_POST['currentClient'])) ? $_POST['currentClient'] : ''; // исключаем текущую запись если это текущий клиент
            $dateText = $_POST['dateText']; // oz_start_date_field_id
            $args = array(
                'posts_per_page'   => -1,
                'meta_key'       => 'oz_start_date_field_id', // oz_personal_field_id
                'meta_value'       => $dateText,
                'post_type'        => 'clients',
                'post_status'      => 'publish',
                'exclude'		   => array($currentClient),
            );
            $args = apply_filters('book_oz_get_posts', $args);
                $posts_array = get_posts( $args );
                if ($posts_array) {
                    foreach ($posts_array as $post) {
                        $idServ = get_post_meta($post->ID,'oz_uslug_set',true);
                        $w_time = apply_filters('book_oz_checkCurrentZapisi_wTime',get_post_meta($idServ,'oz_serv_time',true),$idServ);
                        $buffer = apply_filters('book_oz_sotrudniki_uslbuffer',[0,0],$idServ);
                        $zapisi[] = array(
                                'dayStart' => get_post_meta($post->ID,'oz_start_date_field_id',true),
                                'start' => get_post_meta($post->ID,'oz_time_rot',true),
                                'pId'	=> get_post_meta($post->ID,'oz_personal_field_id',true),
                                'w_time' => $w_time,
                                'buffer' => $buffer
                                );
                    }
                }
                
                
            $args = array(  
            'post_type' => 'personal',
            'posts_per_page' => -1,
            );
            $args = apply_filters('book_oz_get_posts', $args);
            $personals = get_posts( $args );
            $breakList = array();
            $dates = is_array($dateText) ? $dateText : [$dateText];
            foreach ($dates as $date) :
            $day = DateTime::createFromFormat('d.m.Y', $date);
            $day = strtolower('oz_'.$day->format('D'));
                foreach ( $personals as $personal ) : setup_postdata( $personal );
                    $id = $personal->ID;
                    $breaks = json_decode(get_post_meta($id,'oz_breaklist',true),true);
                        if ($breaks) {
                                foreach ($breaks as $break) {
                                    if ($break['day'] == $day) {
                                    $start = DateTime::createFromFormat('H:i', $break['start'])->format('U');
                                    $end = DateTime::createFromFormat('H:i', $break['end'])->format('U');
                                    $w_time = ($end - $start)/60;
                                    $start = $break['start'];
                                    $pId = $break['pId'];
                                        $zapisi[] = array(
                                                  'dayStart' => $date,
                                                  'start' => "$start",
                                                  'pId' => "$pId",
                                                  'w_time' => "$w_time",
                                                  'breaks' => true
                                                  );
                                    }
                                }
                        }
                    
                endforeach;	
                endforeach;
                echo json_encode($zapisi,JSON_UNESCAPED_SLASHES);
            }
            }
            wp_die();
    }

    public function deprecated_book_oz_checkSvobTime() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
        $nonce = (isset($_POST['nonce'])) ? $_POST['nonce'] : '';
        check_ajax_referer( 'ozajax-nonce', 'nonce' );
        //if (isset($_POST)) echo print_r($_POST,1);
        $spec = (int) ($_POST['spec']);
        $post_ID = isset($_POST['post_ID']) ? (int) ($_POST['post_ID']) : '';
        $date = $_POST['date'];
        $time = $_POST['time'];
        $servtime = $_POST['servtime'];
        $zapisi = json_decode(get_post_meta($spec,'oz_clientsarray',true),true);
        /*чтобы данная проверка не работала для текущего клиента*/
        $ChosenClientTime = ($post_ID) ? get_post_meta($post_ID,'oz_start_date_field_id',true).' '.get_post_meta($post_ID,'oz_time_rot',true) : '' ;
        $ok = 'ok';
        $count = count($zapisi);
        $i = 0;
        if ($zapisi) {
            foreach ($zapisi as $zapis) {
                if (strpos($zapis['start'],$date) !== false) {
                $clos_zapis = DateTime::createFromFormat('d.m.Y H:i', $zapis['start']);
                $tec_zapis = DateTime::createFromFormat('d.m.Y H:i', $date.' '.$time);
                $raznica = ($clos_zapis->format('U') - $tec_zapis->format('U'))/60;
            if ($raznica > 0 && $raznica < $servtime) {
                    $min = $servtime - $raznica;
                    $ok = sprintf( __( 'Closest time to booking for current service is %s minute early', 'book-appointment-online' ), $min );
                }
                else {
                    $i++;
                }
                }
                
                if ($zapis['start'] == $ChosenClientTime ) {
                $ok = 'ok';	
                }
            }
        }
        echo $ok;
        }
        wp_die();
    }

    public function addBranches() {
        $branches = new Branches();
    }

    public function book_oz_remove_duplic($actions, $post) {
        if ($post->post_type == 'personal') {
        unset($actions['edit_as_new_draft']);
        unset($actions['clone']);
        }
        return $actions;
    }

    public function addMetabox() {
        $prefix = 'oz_';
        if (!is_admin()) return;
        $configp1 = array(
            'id'             => 'book_oz_personal1_meta_box',
            'title'          => __('About employe', 'book-appointment-online'),
            'pages'          => array('personal'),
            'context'        => 'normal',
            'priority'       => 'high',
            'fields'         => array(),         
            'local_images'   => false,
            'use_with_theme' => false,
            'callback'		 => ''
          );
          
            $personal1 =  new \AT_Meta_Box($configp1);
            $personal1->addText($prefix.'specialnost',array('name'=> __('Specialty', 'book-appointment-online')));
            $time = apply_filters('book_oz_pers_timeslot_duration', array(
                0 => __('Default', 'book-appointment-online'),
                15 => '15 '.__('minutes', 'book-appointment-online'),
                30 => '30 '.__('minutes', 'book-appointment-online'),
                60 => '60 '.__('minutes', 'book-appointment-online'),
                120 => '120 '.__('minutes', 'book-appointment-online')));
            $personal1->addSelect($prefix.'ind_timeslot',$time,array('name'=> __('Individual time slot duration', 'book-appointment-online')));
            $personal1->Finish();
            
            $config1 = array(
            'id'             => 'book_oz_personal_meta_box',
            'title'          => __('Schedule', 'book-appointment-online'),
            'pages'          => array('personal'),
            'context'        => 'normal',
            'priority'       => 'high',
            'fields'         => array(),         
            'local_images'   => false,
            'use_with_theme' => false,
            'callback'		 => 'book_oz_worktime'
          );
          
            $personal =  new \AT_Meta_Box($config1);
            $personal->addHidden($prefix.'raspis',array('name'=> __('Schedule', 'book-appointment-online')));
            $personal->addHidden($prefix.'breaklist',array('name'=> __('Break list', 'book-appointment-online')));
            $personal->addHidden($prefix.'days_off_list',array('name'=> __('Break list', 'book-appointment-online')));
            $personal->addHidden($prefix.'clientsarray',array('name'=> __('Clients', 'book-appointment-online')));	
            $timeRange[] = $personal->addPosts($prefix.'personal_serv_name',array('post_type' => 'services'),array('name'=> __('Service name', 'book-appointment-online')),true);
            //$timeRange[] = $personal->addSelect($prefix.'select_time_serv',array('5'=>'5','10'=>'10','15'=>'15','20'=>'20','25'=>'25','30'=>'30','35'=>'35','40'=>'40','45'=>'45','50'=>'50','55'=>'55','60'=>'60'),array('name'=> 'Промежуток', 'std'=> array('55')),true);
            $personal->addRepeaterBlock($prefix.'re_timerange',array(
            'inline'   => true, 
            'name'     => __('Services list', 'book-appointment-online'),
            'fields'   => $timeRange, 
            'sortable' => true
          ));
            $personal->Finish();
            
            $cConfig = array(
            'id'             => 'book_oz_calendar_n',
            'title'          => __('Booking calendar', 'book-appointment-online'),
            'pages'          => array('personal'),
            'context'        => 'normal',
            'priority'       => 'high',
            'fields'         => array(),         
            'local_images'   => false,
            'use_with_theme' => false,
            'callback'		 => 'book_oz_calendarSot'
          );
          
             $personalC =  new \AT_Meta_Box($cConfig);
            $personalC->addHidden($prefix.'start_date_field',array('name'=> __('Date', 'book-appointment-online')));
            $personalC->Finish(); 
    }

    public function book_oz_calendarSot($arg) {
        if ($arg == 'book_oz_calendarSot' && isset($_GET['post'])) {
    $post = $_GET['post'];
    do_action('book_oz_update_spisok_klientov',$post);
     ?>
                <?php
    $arr = json_decode(get_post_meta($post,'oz_raspis',true),true);
    if ($arr && array_column($arr, 'start') && array_column($arr, 'end')) :
    $start = array_column($arr, 'start');
    $end = array_column($arr, 'end');
    $start =  min($start);
    $end =  max($end);
    endif;
                ?>
    <script>
    var dayStart, dayFinish, hourStart, minStart, hourFinish, minFinish;
     <?php if (isset($start)) : ?>
    dayStart = <?php echo '"'.$start.'"'; ?> ;
    hourStart = <?php echo '"'.explode(':',$start)[0].'"'; ?> ;
    <?php endif; ?>
    <?php if (isset($end)) : ?>
    dayFinish =  <?php echo '"'.$end.'"'; ?> ;
    hourFinish = <?php echo '"'.explode(':',$end)[0].'"'; ?> ;
    <?php endif; ?>
    </script>
            <div id="calendar"></div>
            <?php
                }
    } 

    public function book_oz_rasp_obj() {
        global $post;
        $rasp = (get_post_meta($post->ID,'oz_raspis',true)) ? get_post_meta($post->ID,'oz_raspis',true) : '""';
        echo '<script> rasp = '.$rasp.'; postId = '.$post->ID.';</script>';
    }

    public function book_oz_worktime($arg) {

        if ($arg == 'book_oz_worktime' ) {
            ?>
            <div class="oz_worktime_div">
                <div class="worktime_check">
                    <label for="oz_stab"><input class="plav_gr" id="oz_stab" type="radio" checked name="oz_stab" value="<?php _e('Constant', 'book-appointment-online'); ?>"> <?php _e('Constant', 'book-appointment-online'); ?> </label>
                    <label for="oz_plav"><input class="plav_gr" id="oz_plav" type="radio" name="oz_plav" value="<?php _e('Shift', 'book-appointment-online'); ?>"> <?php _e('Shift', 'book-appointment-online'); ?> </label>
                    <label for="oz_custom"><input class="plav_gr" id="oz_custom" type="radio" name="oz_custom" value="<?php _e('Custom', 'book-appointment-online'); ?>"> <?php _e('Custom', 'book-appointment-online'); ?> </label>
                </div>
                <table data-graphs="oz_stab" class="oz_worktime">
                    <thead>
                        <tr>
                        <th><?php _e('Schedule', 'book-appointment-online'); ?></th>
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
                        <td colspan="7"><?php _e('Add work hours', 'book-appointment-online'); ?></td>
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
                                    <input type="text" class="at-time" name="oz_23" id="oz_ras_start" placeholder="<?php _e('Time start', 'book-appointment-online'); ?>" data-ampm='false' rel='hh:mm' value='' size='30'>
                                    <input type="text" class="at-time" name="oz_23" id="oz_ras_end" placeholder="<?php _e('Time end', 'book-appointment-online'); ?>" data-ampm='false' rel='hh:mm' value='' size='30'>
                                    <div class="add-date-time button button-primary button-large"><span class="dashicons dashicons-plus"></span> <?php _e('Add', 'book-appointment-online'); ?></div>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <div data-graphs="oz_plav" class="plav_grafh hide">
                    <div class="plav_time_text">
                        <b></b>
                        <span><?php _e('Edit', 'book-appointment-online'); ?></span>
                    </div>
                    <div class="plav_grafh_block hide">
                        <p><?php _e('Shift work', 'book-appointment-online'); ?></p>
                        <select id="oz_rab1" name="oz_rab1">
                            <?php $i=0; while ($i < 7) : $i++; ?>
                              <option value="<?php echo $i; ?>"><?php echo ($i == 7) ? __('Week', 'book-appointment-online') : $i; ?></option>
                            <?php endwhile; ?>
                        </select>
                                    <?php _e('across', 'book-appointment-online'); ?>
                        <select id="oz_rab2" name="oz_rab2">
                            <?php $i=0; while ($i < 7) : $i++; ?>
                               <option value="<?php echo $i; ?>"><?php echo ($i == 7) ? __('Week', 'book-appointment-online') : $i; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="oz_flex_container">
                            <label class="width33fl">
                                <input type="text" class="at-date" name="oz_first_day" id="oz_first_day" placeholder="<?php _e('Date of any first shift', 'book-appointment-online'); ?>" rel="dd.mm.yy" value="" size="30">
                            </label>
                            <label class="width33fl">
                                <input type="text" class="at-time" name="oz_231" id="oz_ras_start1" placeholder="<?php _e('Time start', 'book-appointment-online'); ?>" data-ampm='false' rel='hh:mm' value='' size='30' />
                            </label>
                            <label class="width33fl">
                                <input type="text" class="at-time" name="oz_231" id="oz_ras_end1" placeholder="<?php _e('Time end', 'book-appointment-online'); ?>" data-ampm='false' rel='hh:mm' value='' size='30' />
                            </label>
                            <div class="add-date-time button button-primary button-large"><span class="dashicons dashicons-plus"></span> <?php _e('Add', 'book-appointment-online'); ?></div>
                        </div>
                    </div>
                </div>
                <div data-graphs="oz_custom" class="custom_grafh hide">
                <div class="oz_flex_container oz_direction_row">
                    <div class="width50fl">
                        <?php 
                        $rasp = isset($_GET['post']) ? get_post_meta((int) ($_GET['post']), 'oz_raspis', true) : '';
                        if ($rasp && strpos($rasp,'days') != false) :
                        $days = $rasp;
                        else :
                        $days = '[]';
                        endif;
                        ?>
                        <div class="oz_custom_grafh oz_datepicker" data-chosen='<?php echo $days; ?>'></div>
                    </div>
                    <div class="width50fl">
                        <div class="oz_flex_container">
                            <div class="oz_days">
                                <ul>
                                    <li data-chosen='{"days":[], "time":{"start":"", "end":""}}' class="custom_adding hide">
                                        Days: <span class="for_days"></span>
                                        <br>
                                        Time:
                                        <span class="oz_time" data-type="start" data-time=""></span> - <span data-type="end" class="oz_time" data-time=""></span>									
                                        <span class="remove-days oz_close_right oz_tag-remove dashicons dashicons-no-alt"></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="oz_custom_time_wrap hide">
                                <div class="oz_px5">Time:</div>
                                <div class="oz_flex oz_wrap oz_w100">
                                <label class="width50fl">
                                    <input type="text" class="at-time1 oz_time_custom" name="oz_2312" id="oz_ras_start_custom" placeholder="<?php _e('Time start', 'book-appointment-online'); ?>" data-ampm='false' rel='hh:mm' value='' size='30' />
                                </label>
                                <label class="width50fl">
                                    <input type="text" class="at-time1 oz_time_custom" name="oz_2312" id="oz_ras_end_custom" placeholder="<?php _e('Time end', 'book-appointment-online'); ?>" data-ampm='false' rel='hh:mm' value='' size='30' />
                                </label>
                                    <div class="oz_p5">
                                        <div class="add-custom-date button button-primary button-large"><span class="dashicons dashicons-plus"></span> <?php _e('Add', 'book-appointment-online'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                (function( $ ) {
                pId = postId;
                function refreshDatePicker() {
                    addingDates = []
                    $('.oz_days ul li:not(.custom_adding)').remove()
                    $('.custom_adding').attr('data-chosen', JSON.stringify({"days":[], "time":{"start":"", "end":""}}))
                    $('.custom_adding').addClass('hide').find('.for_days').html('')
                    $('.oz_custom_time_wrap').addClass('hide')
                    $('.at-time1').val('')
                    $('.custom_adding').find('.oz_time').attr('data-time', '').html('')
                    let chosen = [];
                    let dates = JSON.parse($('.oz_custom_grafh').attr('data-chosen')).reduce(function(result,obj,ind) { result = result.concat(obj.days);  return result; }, []);
                    $.each(JSON.parse($('.oz_custom_grafh').attr('data-chosen')),
                        function(index, dates) {
                            
                            var dateString = '';
                            $.each(dates.days, function(index, date) {
                                dateString += '<span class="oz_tag" data-day="'+date+'">'+moment(date, 'DD.MM.YYYY').format(oz_vars.dateFormat)+'</span> ';
                            })
                            
                            $('.oz_days ul').prepend('<li>'+
                            'Days: '+dateString+
                                        '<br>'+
                                        'Time: '+
                                        '<span class="oz_time" data-type="start" data-time="'+dates.time.start+'">'+dates.time.start+'</span> - <span data-type="end" class="oz_time" data-time="'+dates.time.end+'">'+dates.time.end+'</span>'+									
                            '<span class="remove-days oz_close_right oz_tag-remove dashicons dashicons-no-alt"></span></li>')
                        }
                    );
                    $( ".oz_custom_grafh.oz_datepicker" ).datepicker({
                        minDate: 0,
                        dateFormat: 'dd.mm.yy',
                        multidate: true,
                        onSelect: function(dateText,inst) {
                            if (dates && dates.indexOf(dateText) > -1) {
                                if ($('.oz_days li span[data-day="'+dateText+'"]').length && jQuery('.oz_custom_time_wrap').hasClass('hide')) {
                                $('.oz_days li').removeClass('active')
                                $('.oz_days li span[data-day="'+dateText+'"]').parent().addClass('active')
                                //var start = $('.oz_days li span[data-day="'+dateText+'"]').parent().find('.oz_time[data-type="start"]').attr('data-time')
                                //var end = $('.oz_days li span[data-day="'+dateText+'"]').parent().find('.oz_time[data-type="end"]').attr('data-time')
                                //$('#oz_ras_start_custom').val(start);
                                //$('#oz_ras_end_custom').val(end);
                                }
                                else {
                                dates = dates.filter(function(val) { return val != dateText});
                                    if (typeof addingDates != 'undefined') {
                                        addingDates = addingDates.filter(function(val) { return val != dateText});
                                        addingDates  = addingDates.sort((a,b) => moment(a, 'DD.MM.YYYY') - moment(b, 'DD.MM.YYYY'))
                                            $('.custom_adding').find('.for_days').html('')
                                            $.each(addingDates, function(i, d) {
                                                $('.custom_adding').find('.for_days').append('<span class="oz_tag" data-day="'+d+'">'+moment(d, 'DD.MM.YYYY').format(oz_vars.dateFormat)+'</span>')
                                            })
                                    }
                                }
                            }
                            else {
                                addingDates = typeof addingDates == 'undefined' || !addingDates.length ? [] : addingDates; 
                                dates.push(dateText);
                                addingDates.push(dateText)
                                addingDates  = addingDates.sort((a,b) => moment(a, 'DD.MM.YYYY') - moment(b, 'DD.MM.YYYY'))
                                var choosing = JSON.parse($('.custom_adding').attr('data-chosen'))
                                choosing['days'] = addingDates
                                $('.custom_adding').attr('data-chosen', JSON.stringify(choosing))
                                $('.custom_adding, .oz_custom_time_wrap').removeClass('hide')
                                $('.custom_adding').find('.for_days').html('')
                                $.each(addingDates, function(i, d) {
                                    $('.custom_adding').find('.for_days').append('<span class="oz_tag" data-day="'+d+'">'+moment(d, 'DD.MM.YYYY').format(oz_vars.dateFormat)+'</span>')
                                })
                                //.find('.for_days').text(addingDates.join(', '))
                            }
                            $(this).attr('data-chosen', JSON.stringify(dates));
                        },
                        beforeShowDay: function(date){
                            var date = moment(date).format('DD.MM.YYYY');
                            //let raspis = JSON.parse($(this).attr('data-chosen'));
                            let active = dates.indexOf(date) > -1 ? 'active' : '';
                            return [ 1, active, ];
                        }
                    });
                    }
                    $(document).ready(function() {
                    
                    refreshDatePicker();
                    
                    if ($('.at-time1').length) {
                        let stepMin = (typeof oz_vars.timeslot !== 'undefined') ? parseInt(oz_vars.timeslot) : 15
                            stepMin = $('[name="oz_ind_timeslot"]').val() > 0 ? parseInt($('[name="oz_ind_timeslot"]').val()) : stepMin
                    $('.at-time1').timepicker({
                        controlType: 'select',
                        stepMinute: stepMin,
                        oneLine: true,
                        onSelect: function (datetimeText, datepickerInstance) {
                            if ($(this).attr('id') == 'oz_ras_start_custom') {
                            var se = parseInt($(this).val().split(':')[0]);
                            $('#oz_ras_end_custom').timepicker('option', 'hourMin', se);
                            $('.custom_adding').find('.oz_time[data-type="start"]').attr('data-time', $(this).val()).text($(this).val())
                            var choosing = JSON.parse($('.custom_adding').attr('data-chosen'))
                            choosing['time']['start'] = $(this).val()
                            $('.custom_adding').attr('data-chosen', JSON.stringify(choosing))
                            }
                            else {
                                $('.custom_adding').find('.oz_time[data-type="end"]').attr('data-time', $(this).val()).text($(this).val())
                                var choosing = JSON.parse($('.custom_adding').attr('data-chosen'))
                                choosing['time']['end'] = $(this).val()
                                $('.custom_adding').attr('data-chosen', JSON.stringify(choosing))
                            }
                        }
                        });
                    }
                    
                    $('.add-custom-date').click(function() {
                        var choosing = JSON.parse($('.custom_adding').attr('data-chosen'))
                        if (choosing.time.start == '') {
                            $('#oz_ras_start_custom').addClass('oz_req')
                            setTimeout(function() {
                                $('.at-time1').removeClass('oz_req')
                            },2000)
                        }
                        else if (choosing.time.end == '') {
                            $('#oz_ras_end_custom').addClass('oz_req')
                            setTimeout(function() {
                                $('.at-time1').removeClass('oz_req')
                            },2000)
                        }
                        else {
                            var raspis = []
                            $('.oz_days ul li').each(function(i,dates) {
                                let day = []
                                $(this).find('[data-day]').each(function(i,d) {
                                    day.push($(this).attr('data-day'))
                                })
                                raspis.push({
                                    days: day,
                                    time: {
                                        start: $(this).find('.oz_time[data-type="start"]').attr('data-time'),
                                        end: $(this).find('.oz_time[data-type="end"]').attr('data-time')
                                    },
                                    pId
                                })
                            })
                            $( ".oz_custom_grafh.oz_datepicker" ).datepicker('destroy')
                            $('#oz_raspis').val(JSON.stringify(raspis))
                            $('.oz_custom_grafh').attr('data-chosen', JSON.stringify(raspis))
                            refreshDatePicker()
                        }
                    })
                    
                    $('body').on('click', '.remove-days', function() {
                        let parent = $(this).parent()
                        if (!parent.hasClass('custom_adding')) {
                        parent.remove()
                        }
                        else {
                            $('.custom_adding, .oz_custom_time_wrap').addClass('hide')
                        }
                        var raspis = []
                            $('.oz_days ul li:not(.custom_adding)').each(function(i,dates) {
                                let day = []
                                $(this).find('[data-day]').each(function(i,d) {
                                    day.push($(this).attr('data-day'))
                                })
                                raspis.push({
                                    days: day,
                                    time: {
                                        start: $(this).find('.oz_time[data-type="start"]').attr('data-time'),
                                        end: $(this).find('.oz_time[data-type="end"]').attr('data-time')
                                    },
                                    pId
                                })
                            })
                        $( ".oz_custom_grafh.oz_datepicker" ).datepicker('destroy')
                        $('#oz_raspis').val(JSON.stringify(raspis))
                        $('.oz_custom_grafh').attr('data-chosen', JSON.stringify(raspis))
                        console.log(raspis)
                        refreshDatePicker()
                    })
                    
                    
    
                    });
                 })(jQuery);
                </script>
                </div>
                </div>
    <style>
    
    .worktime {
           border-collapse: collapse;
    }
    
    .warning {
        border:1px solid red !important;
    }
    
    .hide {
        display:none;
    }
    </style>
            <?php
        }
    }

    public function book_oz_servListSelect($arg) {
        global $post;
        if ($arg == 'book_oz_worktime' ) {
        ?>
    <table id="book_oz_servListSelect" class="form-table">
        <tr>
            <td class='at-field'>
                <div class='at-label'>
                    <label for='oz_re_timerange'><?php _e('Provides services', 'book-appointment-online'); ?></label>
                </div>
                <?php $cur = get_post_meta($post->ID,'oz_book_provides_services',true); ?>
                <select class="oz_book_select_block at-posts-select-exclude" id="oz_book_provides_services" name="oz_book_provides_services" onchange="oz_book_changeSelect(this.value);">
                    <option value="all" 	<?php selected($cur, 'all' ); ?>><?php _e('All services', 'book-appointment-online'); ?></option>
                    <option value="exclude" <?php selected($cur, 'exclude' ); ?>><?php _e('Exclude services below', 'book-appointment-online'); ?></option>
                    <option value="include" <?php selected($cur, 'include' ); ?>><?php _e('Include services below', 'book-appointment-online'); ?></option>
                </select>
            </td>
        </tr>
    </table>
        <?php
        }
    }

    public function book_oz_servListSelectSave(){ 
        global $post;
        if(isset($_POST["oz_book_provides_services"])){
            $prov = sanitize_text_field($_POST['oz_book_provides_services']);
            update_post_meta($post->ID, 'oz_book_provides_services', $prov);
        }
    }
}