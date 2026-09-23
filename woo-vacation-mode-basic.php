<?php
/**
 * Plugin Name: Woo Vacation Mode Basic
 * Plugin URI: https://github.com/ehstbr/Woo-Vacation-Mode
 * Description: Basic vacation mode for WooCommerce with a configurable full-width notice bar, separate messages, WYSIWYG editors, colors, position, and options to hide purchases.
 * Version: 1.4.0
 * Author: Eduardo Henrique Teixeira
 * Author URI: https://github.com/ehstbr
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: woo-vacation-mode-basic
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Woo_Vacation_Mode_Basic' ) ) {

    class Woo_Vacation_Mode_Basic {
        const OPTION_GROUP = 'wvm_basic';
        const OPTION_KEY   = 'wvm_basic_settings';
        const VERSION      = '1.4.0';

        public function __construct() {
            add_action( 'init', array( $this, 'load_textdomain' ) );
            add_action( 'admin_init', array( $this, 'register_settings' ) );
            add_action( 'admin_menu', array( $this, 'add_settings_page_fallback' ), 99 );

            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_assets' ) );

            add_action( 'wp_footer', array( $this, 'render_notice_bar' ) );
            add_action( 'wp_body_open', array( $this, 'render_notice_bar_top' ) );

            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

            add_filter( 'woocommerce_is_purchasable', array( $this, 'maybe_disable_purchasing' ), 9999, 2 );
            add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'maybe_disable_variation_purchasing' ), 9999, 2 );

            add_action( 'wp', array( $this, 'setup_purchase_hiding_hooks' ) );
            add_action( 'template_redirect', array( $this, 'block_cart_and_checkout' ) );
            add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'block_add_to_cart_validation' ), 9999, 5 );
        }

        public function load_textdomain() {
            load_plugin_textdomain( 'woo-vacation-mode-basic', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }

        public static function get_defaults() {
            return array(
                'enabled'               => 'no',
                'position'              => 'top',
                'message'               => '<p><strong>' . esc_html__( 'We are currently on vacation mode.', 'woo-vacation-mode-basic' ) . '</strong> ' . esc_html__( 'We are not accepting new orders at the moment. We will be back soon.', 'woo-vacation-mode-basic' ) . '</p>',
                'checkout_message'      => __( 'We are temporarily not accepting new orders. Please come back soon.', 'woo-vacation-mode-basic' ),
                'button_text'           => '<strong>' . esc_html__( 'Temporarily unavailable', 'woo-vacation-mode-basic' ) . '</strong>',
                'background_color'      => '#fff3cd',
                'text_color'            => '#664d03',
                'close_button_color'    => '#664d03',
                'hide_buy_buttons'      => 'no',
                'prevent_purchase'      => 'yes',
                'show_every_page'       => 'yes',
                'show_close_button'     => 'yes',
                'dismiss_hours'         => '24',
                'padding_vertical'      => '14',
                'padding_horizontal'    => '18',
                'z_index'               => '99999',

            );
        }

        public static function get_settings() {
            $saved = get_option( self::OPTION_KEY, array() );
            return wp_parse_args( is_array( $saved ) ? $saved : array(), self::get_defaults() );
        }

        public function register_settings() {
            register_setting(
                self::OPTION_GROUP,
                self::OPTION_KEY,
                array( $this, 'sanitize_settings' )
            );

            add_settings_section(
                'wvm_basic_main_section',
                __( 'Store vacation mode', 'woo-vacation-mode-basic' ),
                array( $this, 'render_section_description' ),
                'wvm-basic-settings'
            );

            add_settings_field(
                'enabled',
                __( 'Enable vacation mode', 'woo-vacation-mode-basic' ),
                array( $this, 'render_checkbox_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'   => 'enabled',
                    'label' => __( 'Enable the notice bar and the plugin behavior.', 'woo-vacation-mode-basic' ),
                )
            );

            add_settings_field(
                'position',
                __( 'Bar position', 'woo-vacation-mode-basic' ),
                array( $this, 'render_select_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'     => 'position',
                    'options' => array(
                        'top'    => __( 'Top of the page', 'woo-vacation-mode-basic' ),
                        'bottom' => __( 'Bottom of the page', 'woo-vacation-mode-basic' ),
                    ),
                )
            );

            add_settings_field(
                'message',
                __( 'Notice bar message', 'woo-vacation-mode-basic' ),
                array( $this, 'render_editor_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'     => 'message',
                    'editor_id' => 'wvm_basic_message_editor',
                )
            );

            add_settings_field(
                'checkout_message',
                __( 'Cart and checkout message', 'woo-vacation-mode-basic' ),
                array( $this, 'render_textarea_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'         => 'checkout_message',
                    'rows'        => 4,
                    'description' => __( 'Supports simple HTML like &lt;b&gt;, &lt;strong&gt;, &lt;i&gt;, &lt;em&gt;, &lt;u&gt;, &lt;br&gt;, &lt;p&gt;, and &lt;center&gt;.', 'woo-vacation-mode-basic' ),
                )
            );

            add_settings_field(
                'button_text',
                __( 'Replacement text for purchase buttons', 'woo-vacation-mode-basic' ),
                array( $this, 'render_text_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'         => 'button_text',
                    'placeholder' => __( 'Temporarily unavailable', 'woo-vacation-mode-basic' ),
                    'description' => __( 'Supports simple HTML like &lt;b&gt;, &lt;strong&gt;, &lt;i&gt;, &lt;em&gt;, &lt;u&gt;, &lt;br&gt;, &lt;p&gt;, and &lt;center&gt;.', 'woo-vacation-mode-basic' ),
                )
            );

            add_settings_field(
                'background_color',
                __( 'Background color', 'woo-vacation-mode-basic' ),
                array( $this, 'render_color_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key' => 'background_color',
                )
            );

            add_settings_field(
                'text_color',
                __( 'Text color', 'woo-vacation-mode-basic' ),
                array( $this, 'render_color_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key' => 'text_color',
                )
            );

            add_settings_field(
                'close_button_color',
                __( 'Close button color', 'woo-vacation-mode-basic' ),
                array( $this, 'render_color_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key' => 'close_button_color',
                )
            );

            add_settings_field(
                'hide_buy_buttons',
                __( 'Hide purchase buttons', 'woo-vacation-mode-basic' ),
                array( $this, 'render_checkbox_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'   => 'hide_buy_buttons',
                    'label' => __( 'Hide buttons like "Buy", "Add to cart", and similar purchase controls.', 'woo-vacation-mode-basic' ),
                )
            );

            add_settings_field(
                'prevent_purchase',
                __( 'Block new purchases', 'woo-vacation-mode-basic' ),
                array( $this, 'render_checkbox_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'   => 'prevent_purchase',
                    'label' => __( 'Prevent add to cart and checkout access during vacation mode.', 'woo-vacation-mode-basic' ),
                )
            );

            add_settings_field(
                'show_every_page',
                __( 'Show across the whole site', 'woo-vacation-mode-basic' ),
                array( $this, 'render_checkbox_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'   => 'show_every_page',
                    'label' => __( 'If unchecked, the bar will only be shown on WooCommerce pages.', 'woo-vacation-mode-basic' ),
                )
            );

            add_settings_field(
                'show_close_button',
                __( 'Show close button', 'woo-vacation-mode-basic' ),
                array( $this, 'render_checkbox_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'   => 'show_close_button',
                    'label' => __( 'Display the X button so visitors can dismiss the notice.', 'woo-vacation-mode-basic' ),
                )
            );

            add_settings_field(
                'dismiss_hours',
                __( 'Show again after', 'woo-vacation-mode-basic' ),
                array( $this, 'render_number_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'         => 'dismiss_hours',
                    'min'         => 1,
                    'max'         => 8760,
                    'description' => __( 'Number of hours before the notice is shown again after being closed.', 'woo-vacation-mode-basic' ),
                )
            );

            add_settings_field(
                'padding_vertical',
                __( 'Vertical padding', 'woo-vacation-mode-basic' ),
                array( $this, 'render_number_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'         => 'padding_vertical',
                    'min'         => 6,
                    'max'         => 40,
                    'description' => __( 'Vertical internal spacing in pixels.', 'woo-vacation-mode-basic' ),
                )
            );

            add_settings_field(
                'padding_horizontal',
                __( 'Horizontal padding', 'woo-vacation-mode-basic' ),
                array( $this, 'render_number_field' ),
                'wvm-basic-settings',
                'wvm_basic_main_section',
                array(
                    'key'         => 'padding_horizontal',
                    'min'         => 8,
                    'max'         => 60,
                    'description' => __( 'Horizontal internal spacing in pixels.', 'woo-vacation-mode-basic' ),
                )
            );
        }

        public function sanitize_settings( $input ) {
            $defaults = self::get_defaults();
            $output   = array();

            $output['enabled']            = ( isset( $input['enabled'] ) && 'yes' === $input['enabled'] ) ? 'yes' : 'no';
            $output['position']           = ( isset( $input['position'] ) && in_array( $input['position'], array( 'top', 'bottom' ), true ) ) ? $input['position'] : $defaults['position'];
            $output['message']            = isset( $input['message'] ) ? wp_kses_post( $input['message'] ) : $defaults['message'];
            $allowed_inline_html         = array(
                'b'      => array(),
                'strong' => array(),
                'i'      => array(),
                'em'     => array(),
                'u'      => array(),
                'br'     => array(),
                'span'   => array( 'style' => array(), 'class' => array() ),
                'div'    => array( 'style' => array(), 'class' => array() ),
                'p'      => array( 'style' => array(), 'class' => array() ),
                'center' => array(),
            );

            $output['checkout_message']   = isset( $input['checkout_message'] ) ? wp_kses( $input['checkout_message'], $allowed_inline_html ) : $defaults['checkout_message'];
            $output['button_text']        = isset( $input['button_text'] ) ? wp_kses( $input['button_text'], $allowed_inline_html ) : $defaults['button_text'];
            $output['background_color']   = isset( $input['background_color'] ) ? sanitize_hex_color( $input['background_color'] ) : $defaults['background_color'];
            $output['text_color']         = isset( $input['text_color'] ) ? sanitize_hex_color( $input['text_color'] ) : $defaults['text_color'];
            $output['close_button_color'] = isset( $input['close_button_color'] ) ? sanitize_hex_color( $input['close_button_color'] ) : $defaults['close_button_color'];
            $output['hide_buy_buttons']   = ( isset( $input['hide_buy_buttons'] ) && 'yes' === $input['hide_buy_buttons'] ) ? 'yes' : 'no';
            $output['prevent_purchase']   = ( isset( $input['prevent_purchase'] ) && 'yes' === $input['prevent_purchase'] ) ? 'yes' : 'no';
            $output['show_every_page']    = ( isset( $input['show_every_page'] ) && 'yes' === $input['show_every_page'] ) ? 'yes' : 'no';
            $output['show_close_button']  = ( isset( $input['show_close_button'] ) && 'yes' === $input['show_close_button'] ) ? 'yes' : 'no';
            $output['dismiss_hours']      = isset( $input['dismiss_hours'] ) ? max( 1, min( 8760, absint( $input['dismiss_hours'] ) ) ) : (int) $defaults['dismiss_hours'];
            $output['padding_vertical']   = isset( $input['padding_vertical'] ) ? max( 6, min( 40, absint( $input['padding_vertical'] ) ) ) : (int) $defaults['padding_vertical'];
            $output['padding_horizontal'] = isset( $input['padding_horizontal'] ) ? max( 8, min( 60, absint( $input['padding_horizontal'] ) ) ) : (int) $defaults['padding_horizontal'];
            $output['z_index']            = $defaults['z_index'];

            foreach ( array( 'background_color', 'text_color', 'close_button_color' ) as $color_key ) {
                if ( empty( $output[ $color_key ] ) ) {
                    $output[ $color_key ] = $defaults[ $color_key ];
                }
            }

            return $output;
        }

        public function render_section_description() {
            echo '<p>' . esc_html__( 'Configure a simple vacation mode for your WooCommerce store, with a full-width notice bar, separate messages, and an option to block purchases.', 'woo-vacation-mode-basic' ) . '</p>';
        }

        public function render_checkbox_field( $args ) {
            $settings = self::get_settings();
            $key      = $args['key'];
            $checked  = isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
            ?>
            <label>
                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="yes" <?php checked( $checked ); ?> />
                <?php echo isset( $args['label'] ) ? esc_html( $args['label'] ) : ''; ?>
            </label>
            <?php
        }

        public function render_select_field( $args ) {
            $settings = self::get_settings();
            $key      = $args['key'];
            $value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
            ?>
            <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]">
                <?php foreach ( $args['options'] as $option_value => $label ) : ?>
                    <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }

        public function render_color_field( $args ) {
            $settings = self::get_settings();
            $key      = $args['key'];
            $value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
            ?>
            <input type="text" class="wvm-color-field" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( $value ); ?>" />
            <?php
        }

        public function render_text_field( $args ) {
            $settings    = self::get_settings();
            $key         = $args['key'];
            $value       = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
            $placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
            ?>
            <textarea
                class="large-text"
                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
                rows="3"
                placeholder="<?php echo esc_attr( $placeholder ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
            <?php if ( ! empty( $args['description'] ) ) : ?>
                <p class="description"><?php echo wp_kses_post( $args['description'] ); ?></p>
            <?php endif; ?>
            <?php
        }



        public function render_textarea_field( $args ) {
            $settings = self::get_settings();
            $key      = $args['key'];
            $value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
            $rows     = isset( $args['rows'] ) ? absint( $args['rows'] ) : 4;
            ?>
            <textarea
                class="large-text"
                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
                rows="<?php echo esc_attr( $rows ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
            <?php if ( ! empty( $args['description'] ) ) : ?>
                <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
            <?php endif; ?>
            <?php
        }

        public function render_number_field( $args ) {
            $settings = self::get_settings();
            $key      = $args['key'];
            $value    = isset( $settings[ $key ] ) ? absint( $settings[ $key ] ) : 0;
            ?>
            <input type="number"
                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
                value="<?php echo esc_attr( $value ); ?>"
                min="<?php echo esc_attr( $args['min'] ); ?>"
                max="<?php echo esc_attr( $args['max'] ); ?>"
                step="1" />
            <?php if ( ! empty( $args['description'] ) ) : ?>
                <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
            <?php endif; ?>
            <?php
        }

        public function render_editor_field( $args ) {
            $settings  = self::get_settings();
            $key       = $args['key'];
            $value     = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
            $editor_id = isset( $args['editor_id'] ) ? $args['editor_id'] : 'wvm_basic_message_editor';

            wp_editor(
                $value,
                $editor_id,
                array(
                    'textarea_name' => self::OPTION_KEY . '[' . $key . ']',
                    'textarea_rows' => 6,
                    'media_buttons' => false,
                    'teeny'         => false,
                    'quicktags'     => true,
                    'tinymce'       => array(
                        'toolbar1'         => 'formatselect,bold,italic,underline,alignleft,aligncenter,alignright,alignjustify,bullist,numlist,link,unlink,removeformat,undo,redo,fontsizeselect',
                        'toolbar2'         => '',
                        'fontsize_formats' => '12px 13px 14px 15px 16px 18px 20px 24px 28px 32px',
                    ),
                )
            );
        }

        public function add_settings_page_fallback() {
            add_submenu_page(
                'woocommerce',
                __( 'Vacation mode', 'woo-vacation-mode-basic' ),
                __( 'Vacation mode', 'woo-vacation-mode-basic' ),
                'manage_woocommerce',
                'wvm-basic-settings',
                array( $this, 'render_settings_page' )
            );
        }

        public function render_settings_page() {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }
            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Store vacation mode', 'woo-vacation-mode-basic' ); ?></h1>
                <p><?php esc_html_e( 'This plugin creates a cookie-style visual notice, lets you define separate messages, and can block new purchases in WooCommerce.', 'woo-vacation-mode-basic' ); ?></p>
                <form method="post" action="options.php">
                    <?php
                    settings_fields( self::OPTION_GROUP );
                    do_settings_sections( 'wvm-basic-settings' );
                    submit_button();
                    ?>
                </form>
                <hr>
                <div class="wvm-basic-about">
                    <h2><?php esc_html_e( 'About', 'woo-vacation-mode-basic' ); ?></h2>
                    <p><?php esc_html_e( 'Woo Vacation Mode Basic is an open-source plugin developed by Eduardo Henrique Teixeira for simple WooCommerce vacation management.', 'woo-vacation-mode-basic' ); ?></p>
                    <p>
                        <strong><?php esc_html_e( 'Author:', 'woo-vacation-mode-basic' ); ?></strong> Eduardo Henrique Teixeira<br>
                        <strong><?php esc_html_e( 'GitHub:', 'woo-vacation-mode-basic' ); ?></strong>
                        <a href="https://github.com/ehstbr/Woo-Vacation-Mode" target="_blank" rel="noopener noreferrer">github.com/ehstbr/Woo-Vacation-Mode</a><br>
                        <strong><?php esc_html_e( 'License:', 'woo-vacation-mode-basic' ); ?></strong> GPL v3 <?php esc_html_e( 'or later', 'woo-vacation-mode-basic' ); ?>
                    </p>
                </div>
            </div>
            <?php
        }

        public function enqueue_admin_assets( $hook ) {
            if ( false === strpos( (string) $hook, 'wvm-basic-settings' ) ) {
                return;
            }

            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
            wp_add_inline_script(
                'wp-color-picker',
                'jQuery(function($){$(".wvm-color-field").wpColorPicker();});'
            );
        }

        public function enqueue_front_assets() {
            $settings = self::get_settings();
            if ( 'yes' !== $settings['enabled'] ) {
                return;
            }

            if ( 'yes' !== $settings['show_every_page'] && function_exists( 'is_woocommerce' ) ) {
                if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_product() && ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
                    return;
                }
            }

            wp_register_style( 'wvm-basic-front', false, array(), self::VERSION );
            wp_enqueue_style( 'wvm-basic-front' );

            $css = $this->build_front_css( $settings );
            wp_add_inline_style( 'wvm-basic-front', $css );

            wp_register_script( 'wvm-basic-front', '', array(), self::VERSION, true );
            wp_enqueue_script( 'wvm-basic-front' );
            wp_add_inline_script( 'wvm-basic-front', $this->get_front_js() );
        }

        protected function build_front_css( $settings ) {
            $bg      = esc_html( $settings['background_color'] );
            $text    = esc_html( $settings['text_color'] );
            $close   = esc_html( $settings['close_button_color'] );
            $pv      = absint( $settings['padding_vertical'] );
            $ph      = absint( $settings['padding_horizontal'] );
                        $z_index = absint( $settings['z_index'] );

            return "
                .wvm-basic-bar {
                    position: fixed;
                    left: 0;
                    right: 0;
                    background: {$bg};
                    color: {$text};
                    padding: {$pv}px {$ph}px;
                    z-index: {$z_index};
                    box-shadow: 0 2px 12px rgba(0,0,0,.12);
                                        line-height: 1.5;
                }
                .wvm-basic-bar--top { top: 0; }
                .wvm-basic-bar--bottom { bottom: 0; }
                .wvm-basic-bar__inner {
                    width: 100%;
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    justify-content: space-between;
                }
                .wvm-basic-bar__content {
                    flex: 1 1 auto;
                }
                .wvm-basic-bar__content,
                .wvm-basic-bar__content * {
                    color: inherit;
                }
                .wvm-basic-bar__content p {
                    margin: 0 0 .75em;
                }
                .wvm-basic-bar__content p:last-child {
                    margin-bottom: 0;
                }
                .wvm-basic-bar__content h1,
                .wvm-basic-bar__content h2,
                .wvm-basic-bar__content h3,
                .wvm-basic-bar__content h4,
                .wvm-basic-bar__content h5,
                .wvm-basic-bar__content h6 {
                    color: inherit;
                    margin: 0 0 .5em;
                    line-height: 1.25;
                }
                .wvm-basic-bar__content ul,
                .wvm-basic-bar__content ol {
                    margin: 0 0 .75em 1.25em;
                    padding: 0;
                }
                .wvm-basic-bar__content ul:last-child,
                .wvm-basic-bar__content ol:last-child {
                    margin-bottom: 0;
                }
                .wvm-basic-bar__content a {
                    color: inherit;
                    text-decoration: underline;
                }
                .wvm-basic-bar__close {
                    appearance: none;
                    border: 0;
                    background: transparent;
                    color: {$close};
                    font-size: 24px;
                    line-height: 1;
                    cursor: pointer;
                    padding: 0 4px;
                    flex: 0 0 auto;
                }
                body.admin-bar .wvm-basic-bar--top {
                    top: 32px;
                }
                @media (max-width: 782px) {
                    body.admin-bar .wvm-basic-bar--top {
                        top: 46px;
                    }
                    .wvm-basic-bar__inner {
                        align-items: flex-start;
                    }
                }
                .wvm-basic-hide-cart .single_add_to_cart_button,
                .wvm-basic-hide-cart .add_to_cart_button,
                .wvm-basic-hide-cart .ajax_add_to_cart,
                .wvm-basic-hide-cart .wc-block-components-product-button,
                .wvm-basic-hide-cart .wc-block-cart__submit-button,
                .wvm-basic-hide-cart .checkout-button,
                .wvm-basic-hide-cart .wp-block-button.wc-block-components-product-button,
                .wvm-basic-hide-cart form.cart,
                .wvm-basic-hide-cart .quantity {
                    display: none !important;
                }
                .wvm-basic-replacement-text {
                    display: inline-block;
                    margin-top: 8px;
                    font-weight: 600;
                    opacity: .9;
                }
                .wvm-basic-replacement-text--loop {
                    margin-top: 10px;
                }
                .wvm-basic-replacement-text--single {
                    margin-top: 12px;
                }
            ";
        }

        protected function get_front_js() {
            $settings      = self::get_settings();
            $dismiss_hours = isset( $settings['dismiss_hours'] ) ? max( 1, absint( $settings['dismiss_hours'] ) ) : 24;

            return "
                (function(){
                    var key = 'wvm-basic-dismissed-until';
                    var dismissMs = " . ( $dismiss_hours * 60 * 60 * 1000 ) . ";
                    function ready(fn){
                        if(document.readyState !== 'loading'){ fn(); }
                        else { document.addEventListener('DOMContentLoaded', fn); }
                    }
                    ready(function(){
                        try {
                            var storedUntil = parseInt(localStorage.getItem(key) || '0', 10);
                            if (storedUntil && storedUntil > Date.now()) {
                                document.querySelectorAll('.wvm-basic-bar').forEach(function(el){ el.style.display = 'none'; });
                            } else if (storedUntil) {
                                localStorage.removeItem(key);
                            }
                        } catch(e) {}
                        document.querySelectorAll('.wvm-basic-bar__close').forEach(function(btn){
                            btn.addEventListener('click', function(){
                                var bar = btn.closest('.wvm-basic-bar');
                                if(bar){ bar.style.display = 'none'; }
                                try { localStorage.setItem(key, String(Date.now() + dismissMs)); } catch(e) {}
                            });
                        });
                    });
                })();
            ";
        }

        protected function should_render_bar() {
            $settings = self::get_settings();
            if ( 'yes' !== $settings['enabled'] ) {
                return false;
            }

            if ( is_admin() ) {
                return false;
            }

            if ( 'yes' !== $settings['show_every_page'] && function_exists( 'is_woocommerce' ) ) {
                if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_product() && ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
                    return false;
                }
            }

            return true;
        }

        public function render_notice_bar_top() {
            $settings = self::get_settings();
            if ( 'top' !== $settings['position'] ) {
                return;
            }
            $this->render_bar_markup();
        }

        public function render_notice_bar() {
            $settings = self::get_settings();
            if ( 'bottom' !== $settings['position'] ) {
                return;
            }
            $this->render_bar_markup();
        }

        protected function render_bar_markup() {
            if ( ! $this->should_render_bar() ) {
                return;
            }

            $settings = self::get_settings();
            $position = ( 'bottom' === $settings['position'] ) ? 'bottom' : 'top';
            ?>
            <div class="wvm-basic-bar wvm-basic-bar--<?php echo esc_attr( $position ); ?>" role="region" aria-label="<?php echo esc_attr__( 'Store notice', 'woo-vacation-mode-basic' ); ?>">
                <div class="wvm-basic-bar__inner">
                    <div class="wvm-basic-bar__content"><?php echo wp_kses_post( wpautop( $settings['message'] ) ); ?></div>
                    <?php if ( 'yes' === $settings['show_close_button'] ) : ?>
                        <button type="button" class="wvm-basic-bar__close" aria-label="<?php echo esc_attr__( 'Close notice', 'woo-vacation-mode-basic' ); ?>">&times;</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }

        public function setup_purchase_hiding_hooks() {
            $settings = self::get_settings();
            if ( 'yes' !== $settings['enabled'] || 'yes' !== $settings['hide_buy_buttons'] ) {
                return;
            }

            add_filter( 'body_class', array( $this, 'add_body_class_hide_cart' ) );
            add_action( 'wp_head', array( $this, 'inject_fallback_button_hide_css' ) );

            remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

            add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_loop_replacement_text' ), 10 );
            add_action( 'woocommerce_single_product_summary', array( $this, 'render_single_replacement_text' ), 30 );
        }


        public function render_loop_replacement_text() {
            $text = $this->get_button_replacement_text();
            if ( '' === $text ) {
                return;
            }
            echo '<span class="wvm-basic-replacement-text wvm-basic-replacement-text--loop">' . wp_kses_post( $text ) . '</span>';
        }

        public function render_single_replacement_text() {
            $text = $this->get_button_replacement_text();
            if ( '' === $text ) {
                return;
            }
            echo '<p class="wvm-basic-replacement-text wvm-basic-replacement-text--single">' . wp_kses_post( $text ) . '</p>';
        }

        public function add_body_class_hide_cart( $classes ) {
            $classes[] = 'wvm-basic-hide-cart';
            return $classes;
        }

        public function inject_fallback_button_hide_css() {
            echo '<style>.wvm-basic-hide-cart .single_add_to_cart_button, .wvm-basic-hide-cart .add_to_cart_button, .wvm-basic-hide-cart form.cart, .wvm-basic-hide-cart .quantity{display:none!important;}</style>';
        }

        protected function is_vacation_purchase_block_enabled() {
            $settings = self::get_settings();
            return ( 'yes' === $settings['enabled'] && 'yes' === $settings['prevent_purchase'] );
        }

        public function maybe_disable_purchasing( $purchasable, $product ) {
            if ( $this->is_vacation_purchase_block_enabled() ) {
                return false;
            }
            return $purchasable;
        }

        public function maybe_disable_variation_purchasing( $purchasable, $product ) {
            if ( $this->is_vacation_purchase_block_enabled() ) {
                return false;
            }
            return $purchasable;
        }

        public function block_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
            if ( ! $this->is_vacation_purchase_block_enabled() ) {
                return $passed;
            }

            wc_add_notice( $this->get_checkout_message_html(), 'error' );
            return false;
        }

        public function block_cart_and_checkout() {
            if ( ! $this->is_vacation_purchase_block_enabled() || ! function_exists( 'is_cart' ) ) {
                return;
            }

            if ( is_checkout() || is_cart() ) {
                wc_add_notice( $this->get_checkout_message_html(), 'notice' );
            }
        }

        protected function get_plain_message() {
            $settings = self::get_settings();
            $plain    = trim( wp_strip_all_tags( $settings['message'] ) );
            if ( '' === $plain ) {
                $plain = __( 'The store is temporarily in vacation mode and is not accepting new orders.', 'woo-vacation-mode-basic' );
            }
            return $plain;
        }

        protected function get_checkout_plain_message() {
            $settings = self::get_settings();
            $plain    = trim( wp_strip_all_tags( $settings['checkout_message'] ) );
            if ( '' === $plain ) {
                $plain = __( 'The store is temporarily in vacation mode and is not accepting new orders.', 'woo-vacation-mode-basic' );
            }
            return $plain;
        }

        protected function get_checkout_message_html() {
            $settings = self::get_settings();
            $html     = isset( $settings['checkout_message'] ) ? trim( $settings['checkout_message'] ) : '';
            if ( '' === $html ) {
                $html = $this->get_checkout_plain_message();
            }
            return wp_kses_post( $html );
        }

        protected function get_button_replacement_text() {
            $settings = self::get_settings();
            $text     = isset( $settings['button_text'] ) ? trim( wp_strip_all_tags( $settings['button_text'] ) ) : '';
            if ( '' === $text ) {
                $text = __( 'Temporarily unavailable', 'woo-vacation-mode-basic' );
            }
            return $text;
        }

        public function plugin_action_links( $links ) {
            $url = admin_url( 'admin.php?page=wvm-basic-settings' );
            array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'woo-vacation-mode-basic' ) . '</a>' );
            return $links;
        }
    }

    new Woo_Vacation_Mode_Basic();
}
