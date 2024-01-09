<?php
/**
 * Plugin Name:      Ultimate Member - Members Directory Custom Search
 * Description:      Extension to Ultimate Member for selecting Members Directory Search fields.
 * Version:          2.1.0
 * Requires PHP:     7.4
 * Author:           Miss Veronica
 * License:          GPL v2 or later
 * License URI:      https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:       https://github.com/MissVeronica
 * Text Domain:      ultimate-member
 * Domain Path:      /languages
 * UM version:       2.8.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Members_Directory_Custom_Search {

    public $all_core_search_fields = array(
                                    'user_login'    => 'user_login',
                                    'user_url'      => 'user_url',
                                    'display_name'  => 'display_name',
                                    'user_email'    => 'user_email',
                                    'user_nicename' => 'user_nicename',
                                );

    public $exclude_field_types = array( 
                                    'password',
                                    'shortcode',
                                    'block',
                                    'file',
                                    'image',
                                    'divider',
                                );

    function __construct( ) {

        add_filter( 'um_general_search_custom_fields',        array( $this, 'general_search_custom_fields' ), 10, 1 );
        add_filter( 'um_member_directory_core_search_fields', array( $this, 'member_directory_core_search_fields' ), 10, 1 );
        add_action( 'add_meta_boxes',                         array( $this, 'add_metabox_directory_custom_search' ), 1 );
        add_action( 'um_before_member_directory_save',        array( $this, 'um_before_member_directory_save_custom_search' ), 10, 1 );

        if ( isset( UM()->classes['admin_metabox'] )) {
            add_action( 'save_post',                          array( UM()->classes['admin_metabox'], 'save_metabox_directory' ), 10, 2 );
        }
    }

    public function add_metabox_directory_custom_search() {

        global $post_id;

        if ( get_post_meta( $post_id, '_um_search', true ) == 1 ) {

            add_meta_box( 'um-admin-form-search-custom', __( 'Search Custom Fields', 'ultimate-member' ), array( $this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
        }
    }

    public function um_before_member_directory_save_custom_search( $post_id ) {

        global $post_id;

        if ( get_post_meta( $post_id, '_um_search', true ) == 1 ) {

            delete_post_meta( $post_id, '_um_custom_search_fields' );
            delete_post_meta( $post_id, '_um_custom_search_core_fields' );
        }
    }

    public function member_directory_core_search_fields( $core_search_fields ) {

        $directory_id = UM()->member_directory()->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) );
        if ( ! empty( $directory_id ) && get_post_meta( $directory_id, '_um_search', true ) == 1 ) {

            $core_search_fields = array_map( 'sanitize_text_field', get_post_meta( $directory_id, '_um_custom_search_core_fields', true ) );
        }

        return $core_search_fields;
    }

    public function general_search_custom_fields( $custom_fields ) {

        $directory_id = UM()->member_directory()->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) );
        if ( ! empty( $directory_id ) && get_post_meta( $directory_id, '_um_search', true ) == 1 ) {

            $custom_fields = array_map( 'sanitize_text_field', get_post_meta( $directory_id, '_um_custom_search_fields', true ) );

            foreach( $custom_fields as $index => $field_key ) {
                $data = UM()->fields()->get_field( $field_key );

                if ( ! um_can_view_field( $data ) ) {
                    unset( $custom_fields[$index] );
                }
            }
        }

        return $custom_fields;
    }

    function load_metabox_directory( $object, $box ) {

        global $post_id;

        if ( get_post_meta( $post_id, '_um_search', true ) == 1 ) {

            $custom_fields = array();

            foreach ( array_keys( UM()->builtin()->all_user_fields ) as $field_key ) {
                if ( empty( $field_key ) ) {
                    continue;
                }

                $data = UM()->fields()->get_field( $field_key );
                if ( isset( $data['type'] ) && ! in_array( $data['type'], $this->exclude_field_types )) {

                    $custom_fields[$field_key] = $field_key;
                    if ( isset( $data['title'] )) {
                        $custom_fields[$field_key] .= ' - ' . $data['title'];
                    }
                }
            }

            foreach( $this->all_core_search_fields as $key => $value ) {

                $data = UM()->fields()->get_field( $key );
                if ( isset( $data['title'] )) {
                    $this->all_core_search_fields[$key] = $value . ' - ' . $data['title'];
                }
            }
?>
            <div class="um-admin-metabox">
<?php
                UM()->admin_forms(
                    array(
                        'class'     => 'um-member-directory-custom-search um-half-column',
                        'prefix_id' => 'um_metadata',
                        'fields'    => array(
                            array(
                                'id'                  => '_um_custom_search_core_fields',
                                'type'                => 'multi_selects',
                                'label'               => __( 'Choose WP core fields to enable for search', 'ultimate-member' ),
                                'tooltip'             => __( 'Select WP core fields to include in the Custom Members Directory Search.', 'ultimate-member' ),
                                'value'               => get_post_meta( $post_id, '_um_custom_search_core_fields', true ),
                                'options'             => $this->all_core_search_fields,
                                'add_text'            => __( 'Add WP Core Search Field', 'ultimate-member' ),
                                'show_default_number' => 0,
                                'conditional'         => array( '_um_search', '=', 1 ),
                            ),

                            array(
                                'id'                  => '_um_custom_search_fields',
                                'type'                => 'multi_selects',
                                'label'               => __( 'Choose UM custom fields to enable for search', 'ultimate-member' ),
                                'tooltip'             => __( 'Select UM custom fields to include in the Custom Members Directory Search.', 'ultimate-member' ),
                                'value'               => get_post_meta( $post_id, '_um_custom_search_fields', true ),
                                'options'             => $custom_fields,
                                'add_text'            => __( 'Add UM Custom Search Field', 'ultimate-member' ),
                                'show_default_number' => 0,
                                'conditional'         => array( '_um_search', '=', 1 ),
                            ),
                        ),
                    )

                )->render_form();
?>
                <div class="clear"></div>
            </div>
<?php
        }
    }

}

new UM_Members_Directory_Custom_Search();
