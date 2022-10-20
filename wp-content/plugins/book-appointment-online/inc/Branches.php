<?php
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.0.9
 */
namespace Ozplugin;
//use Ozplugin\Settings;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Branches {
    function __construct() {
        add_action( 'edited_filial', [$this, 'book_oz_save_filial_custom_fields'], 10, 2 ); 
        add_action( 'create_filial', [$this, 'book_oz_save_filial_custom_fields'], 10, 2 ); 
        add_action( 'filial_edit_form_fields', [$this, 'book_oz_filial_add_custom_fields'], 1, 2 );
        add_filter('filial_edit_form_fields', [$this, 'book_oz_cat_description']);
        add_filter('filial_edit_form_fields', [$this, 'book_oz_cat_description']);
        add_action('admin_head', [$this, 'book_oz_remove_default_category_description']);
        add_action('init', [$this,'register_taxonomy']);

        /**
         * todo why these filters needed?
         */
        remove_filter( 'pre_term_description', 'wp_filter_kses' );
        remove_filter( 'term_description', 'wp_kses_data' );
    }

    public function book_oz_save_filial_custom_fields() {
        if ( isset( $_POST['oz_filial_address'] ) ) { 
            $term_filial_address = sanitize_text_field($_POST['oz_filial_address']);	
            update_term_meta( $term_id, 'oz_filial_address', $term_filial_address );    
        }  
    }

    public function book_oz_filial_add_custom_fields( $term ) {
        $t_id = $term->term_id;
        $term_filial_address = get_term_meta( $t_id, 'oz_filial_address', true ); 
        ?>
    <table class="form-table">
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="description">
                <?php _e('Address', 'book-appointment-online'); ?>
                </label>
            </th>
            <td>
                <input type="text" name="oz_filial_address" id="oz_filial_address" value="<?php echo esc_attr( $term_filial_address ) ? esc_attr( $term_filial_address ) : ''; ?>">
            </td>
        </tr>
    </table>
    
    <?php
    }

    
    public function book_oz_cat_description($tag)
    {
            ?>
                
        <table class="form-table">
        <tr class="form-field">
        <th scope="row" valign="top">
        <label for="description">
        <?php _e('Description', 'book-appointment-online'); ?>
        </label>
        </th>
        <td>

                        <?php
                            $settings = array('wpautop' => true, 'media_buttons' => true, 'quicktags' => true, 'textarea_rows' => '15', 'textarea_name' => 'description' );
                            wp_editor(wp_kses_post($tag->description , ENT_QUOTES, 'UTF-8'), 'filial_description', $settings);
                        ?>                
        <br/>
        <span class="description">
        <?php _e('The description is not prominent by default; however, some themes may show it.', 'book-appointment-online'); ?>
                </span>
                </td>
            </tr>
        </table>

            <?php
    }

    public function book_oz_remove_default_category_description()
    {
        global $current_screen;
        if ( $current_screen && $current_screen->id == 'edit-filial' )
        {
        ?>
            
    <script type="text/javascript">
            jQuery(function($) {
                $('textarea#description').closest('tr.form-field').remove();
            });
            
    </script>
    
        <?php
        }
    }

    public function register_taxonomy() {
        register_taxonomy( 'filial' , 'personal', array(
            'hierarchical'	=> true,
            'labels'	=> array(
                        'name' => __('Branch', 'book-appointment-online'),
                        'singular_name' => __('Branch', 'book-appointment-online'),
                        'search_items' => __('Search branches', 'book-appointment-online'),
                        'all_items' => __('All branches', 'book-appointment-online'),
                        'parent_item' => __('Parent branch', 'book-appointment-online'),
                        'parent_item_colon' => __('Parent branch:', 'book-appointment-online'),
                        'edit_item' => __('Edit branch', 'book-appointment-online'),
                        'update_item' => __('Refresh branch', 'book-appointment-online'),
                        'add_new_item' => __('Add new branch', 'book-appointment-online'),
                        'new_item_name' => __('New branch name', 'book-appointment-online'),
                        'menu_name' => __('Branch', 'book-appointment-online'),
                        'popular_items'	=> __('Popular branches', 'book-appointment-online'),
                        'separate_items_with_commas'	=> __('Branches separated by comma', 'book-appointment-online'),
                        'not_found'	=> __('Branches not found.', 'book-appointment-online'),
                    ),
            'show_ui'	=> true,
            'show_admin_column'	=> true,
            'update_count_callback'	=> '_update_post_term_count',
            'query_var'	=> true,
            'rewrite'	=> array( 'slug' => 'filial' ),
        ) );
    }
}