<?php
/**
 * Plugin Name: DR Simple Shortcodes
 * Description: Easy and fast creation of custom shortcodes
 * Version: 0.5
 * Plugin URI: https://github.com/DrAnyKey/dr-simple-shortcodes-wp/
 *
 * Author: Digital Rising
 * Author URI: https://digital-rising.ru/
 *
 * Text Domain: dr-simple-shortcodes
 * Domain Path: /languages
 */

__( 'DR Simple Shortcodes', 'dr-simple-shortcodes' );
__( 'Easy and fast creation of custom shortcodes', 'dr-simple-shortcodes' );

add_action('plugins_loaded', function(){
    load_plugin_textdomain( 'dr-simple-shortcodes', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
} );


if ( ! class_exists('DrSimpleShortcodes' ) ) {

class DrSimpleShortcodes{

    private $options;
    private $used_shortcodes;

    function __construct() {
        add_action('admin_menu', array( $this, 'add_page')); // Add the "Shortcodes" page to the admin panel
        add_action('admin_init', array( $this, 'settings')); // Add shortcodes to this page
        add_action('admin_enqueue_scripts', function(){wp_enqueue_style(  'drss', plugin_dir_url( __FILE__ ) .'admin.css' );}, 99 );

        $this->options = get_option( 'dr_simple_shortcodes_option' );

        $this->used_shortcodes = array();
        global $shortcode_tags;
        foreach ( $shortcode_tags as $key => $value ) {
            array_push($this->used_shortcodes, $key);
        }

        if ( $this->options ) { // Create shortcodes
            foreach ( $this->options as $shortcode => $value ) {
                add_shortcode($shortcode, function ($atts, $content, $tag) {
                    return html_entity_decode(get_option('dr_simple_shortcodes_option')[$tag]);
                });
            }
        }
    }

    function add_page(){
        add_menu_page(
            __('Simple Shortcodes', 'dr-simple-shortcodes'), // Page title in admin panel
            __('Shortcodes', 'dr-simple-shortcodes'),        // Page name in admin panel in the left menu
            'manage_options',                                // That's how it should be
            'dr_simple_shortcodes',                          // Slug
            array( $this, 'show_page'),                      // Function that displays the contents of the page
            'dashicons-shortcode'                            // Icon in the left menu
        );
    }

    function show_page(){
    ?>
        <div class="wrap">
            <h2><?= get_admin_page_title() ?></h2>
            <p class="used-shortcodes"><?= __('Used shortcodes', 'dr-simple-shortcodes') ?>: &nbsp; &nbsp;
                <?= implode(" , ", $this->used_shortcodes) ?></p>
            <input type="hidden" id="used-shortcodes" value="<?= implode("&", $this->used_shortcodes) ?>">
            <strong class="table-description">shortcode</strong>
            <strong class="table-description"><?= __('Value', 'dr-simple-shortcodes') ?></strong>
            <form action="options.php" method="POST" id="sc-form">
                <?php
                settings_fields( 'dr_simple_shortcodes_group' );     // Hidden protective fields
                do_settings_sections( 'dr_simple_shortcodes_page' ); // Section with the options
                ?>
                <div id="sc-error"></div>
                <input type="text" id="sc-name"><input type="text" id="sc-value"><i class="add" title="<?= __('Add', 'dr-simple-shortcodes') ?>">+</i>
                <p class="submit"><input type="button" id="sc-submit" class="button button-primary" value="<?= __('Save Changes', 'dr-simple-shortcodes') ?>"></p>
            </form>
        </div>
        <script>
            const scName  = document.getElementById('sc-name' ),
                  scValue = document.getElementById('sc-value')

            function validate( name ) {
                const scError        = document.getElementById('sc-error' )
                let   usedShortcodes = document.getElementById('used-shortcodes' ).value.toLowerCase().split('&')

                scError.innerHTML = ''

                if ( name.replace(/\s+/g, '') == '' ) {
                    scError.innerHTML = '<?= __('Input the name of the shortcode', 'dr-simple-shortcodes') ?>'
                    return false
                }

                if ( /[^a-z0-9-_]/i.test(name) ) {
                    scError.innerHTML = '<?= __('The shortcode name can only contain Latin letters, numbers, minus signs and underscores', 'dr-simple-shortcodes') ?>'
                    return false
                }

                if ( /^[-_]/.test(name) || /[-_]$/.test(name) ) {
                    scError.innerHTML = '<?= __('The shortcode name must begin and end with a Latin letter or number', 'dr-simple-shortcodes') ?>'
                    return false
                }

                for ( shortcode of document.querySelectorAll('#wpbody-content .form-table tr th') ) {
                    usedShortcodes.push(shortcode.innerHTML.toLowerCase())
                }

                if ( usedShortcodes.includes(name.toLowerCase()) ) {
                    scError.innerHTML = '<?= __('A shortcode with this name already exists (shortcode names are case-insensitive)', 'dr-simple-shortcodes') ?>'
                    return false
                }

                return true
            }

            function add() {
                if ( validate( scName.value ) ) {
                    if ( ! document.querySelector('#wpbody-content table') ) {
                        const table = document.createElement('table'),
                              tbody = document.createElement('tbody')

                        table.appendChild(tbody)
                        table.className = 'form-table'
                        table.setAttribute('role', 'presentation')
                        scName.parentNode.insertBefore(table, scName)
                    }

                    document.querySelector('#wpbody-content tbody').innerHTML +=
                        '<tr>' +
                            '<th scope="row">' + scName.value + '</th>' +
                            '<td><input type="text" name="dr_simple_shortcodes_option[' + scName.value + ']" ' +
                                     'value="' + String(scValue.value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') + '">' +
                                  '<i class="remove" title="<?= __('Remove', 'dr-simple-shortcodes') ?>">–</i>' +
                            '</td>' +
                        '</tr>'

                    scName.value  = ''
                    scValue.value = ''

                    return true
                } else {
                    return false
                }
            }

            document.querySelector('#wpbody-content i.add').addEventListener('click', add)

            document.addEventListener('click', function( e ){
                const target = e.target.closest('#wpbody-content i.remove')
                if ( target ) target.closest('tr').remove()
            })

            document.getElementById('sc-submit' ).addEventListener('click', function() {
                if ( ( scName.value == '' && scValue.value == '' ) || add() ) {
                    document.getElementById('sc-form').submit()
                }
            })
        </script>
    <?php
    }

    function settings() {
        // Parameters: $option_group, $option_name, $validate_callback
        register_setting('dr_simple_shortcodes_group', 'dr_simple_shortcodes_option', array( $this, 'validate'));

        // Parameters: $id, $title, $callback, $page
        add_settings_section('dr_simple_shortcodes_section_id', '', '', 'dr_simple_shortcodes_page');

        if ( $this->options ) {
            foreach ( $this->options as $shortcode => $value ) {
                // Parameters: $id, $title, $callback, $page, $section, $args
                add_settings_field($shortcode, $shortcode, array( $this, 'show_field'), 'dr_simple_shortcodes_page', 'dr_simple_shortcodes_section_id', $shortcode);
            }
        }
    }

    function show_field( $shortcode ) {
        echo '<input type="text" name="dr_simple_shortcodes_option[' . $shortcode . ']" value="' . $this->options[$shortcode] . '"><i class="remove" title="' . __('Remove', 'dr-simple-shortcodes') . '">–</i>';
    }

    function validate( $options ) { // Data validation and conversion
        if ( $options ) {
            foreach ( $options as & $val ) {
                $val = htmlentities($val, ENT_QUOTES, "UTF-8");
            }
        }
        return $options;
    }
}

new DrSimpleShortcodes();
}