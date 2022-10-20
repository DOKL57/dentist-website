<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 */
namespace Ozplugin;
//use Ozplugin\Settings;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Dashboard {
    
    public function init() {
        add_action( 'admin_footer', [$this,'book_oz_custom_dashboard_widget'] );
    }

    public function book_oz_custom_dashboard_widget() {
        if ( get_current_screen()->base !== 'dashboard' || (get_current_screen()->base == 'dashboard' && !book_oz_user_can())  ) {
            return;
        }
        ?>
        <?php
    $prevDate = date('Y-m-d', strtotime("-30 days"));
    $futDate = date('Y-m-d', strtotime("+30 days"));
        $args = array( 
        'posts_per_page' => -1, 
        'post_type' => 'clients',
        );
        $args = apply_filters('book_oz_get_posts', $args, 'dashboard');
        $clients = get_posts( $args );
        $speci = [];
        $clientsJSON = [];
        foreach ( $clients as $client ) :
        $id = $client->ID;
        $usId = get_post_meta($id,'oz_uslug_set',true);
        $start = date('c',strtotime(get_post_meta($id,'oz_start_date_field_id', true).get_post_meta($id,'oz_time_rot', true)));
        $w_time = apply_filters('book_oz_consoleCalendar_wTime',get_post_meta($usId,'oz_serv_time',true),$usId, false);
        $end = date('c',strtotime($start.'+'.$w_time.' minutes'));
        $tel = get_post_meta($id,'oz_clientPhone',true);
        $email = get_post_meta($id,'oz_clientEmail',true);
        $usId = apply_filters('book_oz_WPML_id', $usId);
        $usl = $usId ? apply_filters('book_oz_consoleCalendar_uslugi',get_the_title($usId),$usId) : '';
        $pers_id = get_post_meta($id,'oz_personal_field_id',true);
        $pers_id = apply_filters('book_oz_WPML_id', $pers_id);
        $pers = $pers_id ? get_the_title($pers_id) : '';
        $url = site_url().'/wp-admin/post.php?post='.$id.'&action=edit';
        $params = array(
        'persId' 		=> $pers_id,
        'title' 	=> $client->post_title ,
        'start' 	=> $start,
        'end'		=> $end,
        'w_time' 	=> $w_time,
        'tel' 		=> $tel,
        'usl' 		=> $usl,
        'url'		=> $url,
        'pers'		=> $pers,
        'email'		=> $email,
        );
        $params = apply_filters('book_oz_dashboard_appointments_JSON',$params,$id);
        $clientsJSON[] = $params;
        
        if (!isset($speci[$pers_id])) {
        $speci[$pers_id] = array(
        'title' => $pers
        );
        }
    endforeach;
    echo '<script>'."\n".'clients = '.json_encode($clientsJSON).'; '."\n".'  var speci = '.json_encode($speci).'; </script>';
        $lan = explode('_',get_locale())[0];
        $ru = (get_locale() == 'ru_RU') ? '-ru' : '';
        $img = '/images/calendar-banner'.$ru.'.jpg';
        if (!Updater::isPro() && !file_exists(OZAPP_PATH.'/assets'.$img)) return;
        $banner = !Updater::isPro() ? '<a target="_blank" href="http://demo.oz-plugin.ru?utm_source=sitebanner&ver='.OZAPP_VER.'" class="oz_ext_banner"><img style="max-width:100%" src="'.OZAPP_URL.'assets'.$img.'?ver='.OZAPP_VER.'" /></a>' : '';
    ?>
        <div id="oz_dashboard_widget_full" class="welcome-panel-white" style="display: none;">
            <div class="welcome-panel-content">
            <?php echo $banner; ?>
                <div class="oz_dashboard_admin_header">
                    <h2><?php _e('Booking calendar', 'book-appointment-online'); ?></h2>
                    <?php do_action('book_oz_dashboard_selects_before'); ?>
                    <?php if (isset($speci) && $speci) : ?>
                    <select class="oz_filter_spec">
                        <option value="0"><?php _e('All Employees', 'book-appointment-online'); ?></option>
                        <?php foreach ($speci as $id => $spec) :
                        if ($spec['title'] && $id) :
                        ?>
                        <option value="<?php echo $id; ?>"><?php echo $spec['title']; ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <?php do_action('book_oz_dashboard_selects_after'); ?>
                </div>
                <p class="about-description"></p>
                <div class="welcome-panel-column-container">
                    <?php do_action('book_oz_dashboard_container'); ?>
                    <div class="oz_widget_calendar"></div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#wpbody-content h1').after($('#oz_dashboard_widget_full').show());
                $('.oz_widget_calendar').fullCalendar({
                    header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,listWeek,agendaDay'
                    },
                    <?php if (isset($lan)) : ?>locale: '<?php echo $lan; ?>',<?php endif; ?>
                    <?php if (get_option('book_oz_time_format')) : ?> timeFormat: '<?php echo get_option('book_oz_time_format'); ?>', <?php endif; ?>
                    defaultView: 'listWeek',
                    editable: false,
                    events: clients, // расписание на главной остановился на этом
                    eventRender: function(event, element) {
                            var title = [event.tel, event.email, event.usl, event.pers].filter(val => val != '').join(', ');
                            $('<div class="fc-ther_info"> '+title+'</div>').insertAfter($(element).find('.fc-title, .fc-list-item-title a'));
                        },
                });

                $('.oz_filter_spec').val(0);
                $('.oz_filter_spec').change(function() {
                    var id = $(this).val();
                    clients_filter = clients;
                    var filter = [];
                    if (id > 0) {
                        $.each(clients, function(i,client) {
                            if (typeof client.persId !== 'undefined' && client.persId == id ) {
                                if ($('.oz_status_dashboard.active').length) {
                                    $('.oz_status_dashboard.active').each(function(k,status) {
                                        var status = $(this).attr('data-status');
                                        if (typeof client.status !== 'undefined' && client.status == status ) {
                                            filter.push(client);
                                            return false;
                                        }
                                    });
                                }
                                else {
                                    filter.push(client);
                                }
                            }
                        });
                        if (filter.length >= 0) {
                            clients_filter = filter;
                        }
                    }
                    else {
                        if ($('.oz_status_dashboard.active').length) {
                            $.each(clients, function(i,client) {
                                $('.oz_status_dashboard.active').each(function(k,status) {
                                    var status = $(this).attr('data-status');
                                    if (typeof client.status !== 'undefined' && client.status == status ) {
                                        filter.push(client);
                                        return false;
                                    }
                                });
                            });
                            clients_filter = filter;
                        }
                    }
                    $('.oz_widget_calendar').fullCalendar('removeEvents');
                    $('.oz_widget_calendar').fullCalendar('addEventSource', clients_filter);
                });
                
                $('.oz_status_dashboard').click(function() {
                    $(this).toggleClass('active');
                    var event= new CustomEvent('oz_onDashStatus');
                    document.addEventListener('oz_onDashStatus',function(){},false);
                    document.dispatchEvent(event);
                    var status = $(this).attr('data-status');
                    clients_filter = clients;
                    var filter = [];
                        $.each(clients, function(i,client) {
                        if ($('.oz_status_dashboard.active').length) {
                        $('.oz_status_dashboard.active').each(function(k,status) {
                            var status = $(this).attr('data-status');
                            if (typeof client.status !== 'undefined' && client.status == status ) {
                                if ($('.oz_filter_spec').val() > 0) {
                                    if (typeof client.persId !== 'undefined' && client.persId == $('.oz_filter_spec').val() ) {
                                    filter.push(client);
                                    return false;
                                    }
                                }
                                else {
                                filter.push(client);
                                }
                            }
                        });
                            }
                            else if (typeof client.persId !== 'undefined' && client.persId == $('.oz_filter_spec').val()) {
                                    filter.push(client);
                            }
                        });
                        if (filter.length >= 0) {
                            clients_filter = filter;
                        }
                        if  ($('.oz_status_dashboard.active').length == 0 && $('.oz_filter_spec').val() == 0 ) {
                                clients_filter = clients;
                            }
                    $('.oz_widget_calendar').fullCalendar('removeEvents');
                    $('.oz_widget_calendar').fullCalendar('addEventSource', clients_filter);
                });
            });
        </script>

    <?php }

}