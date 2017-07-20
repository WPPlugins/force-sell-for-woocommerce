<?php
/**
 * Plugin Name: Force Sell for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/force-sell-for-woocommerce/
 * Description: WooCommerce Force Sell plugin allows you to link products to another product, so they are added to the cart together.
 * Version: 1.0.6
 * Author: BeRocket
 * Requires at least: 4.0
 * Author URI: http://berocket.com
 * Text Domain: BeRocket_force_sell_domain
 * Domain Path: /languages/
 */
define( "BeRocket_force_sell_version", '1.0.6' );
define( "BeRocket_force_sell_domain", 'BeRocket_force_sell_domain'); 
define( "force_sell_TEMPLATE_PATH", plugin_dir_path( __FILE__ ) . "templates/" );
load_plugin_textdomain('BeRocket_force_sell_domain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
require_once(plugin_dir_path( __FILE__ ).'includes/admin_notices.php');
require_once(plugin_dir_path( __FILE__ ).'includes/functions.php');
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

/**
 * Class BeRocket_force_sell
 */
class BeRocket_force_sell {

    /**
     * Defaults values
     */
    public static $defaults = array(
        'display_force_sell'        => '1',
        'display_force_sell_linked' => '1',
        'custom_css'                => '',
    );
    public static $values = array(
        'settings_name' => 'br-force_sell-options',
        'option_page'   => 'br-force_sell',
        'premium_slug'  => 'woocommerce-force-sell',
    );
    
    function __construct () {
        register_uninstall_hook(__FILE__, array( __CLASS__, 'deactivation' ) );

        if ( ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) && 
            br_get_woocommerce_version() >= 2.1 ) {
            $options = self::get_option();
            
            add_action ( 'init', array( __CLASS__, 'init' ) );
            add_action ( 'wp_head', array( __CLASS__, 'set_styles' ) );
            add_action ( 'admin_init', array( __CLASS__, 'admin_init' ) );
            add_action ( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
            add_action ( 'admin_menu', array( __CLASS__, 'options' ) );
            add_action( "wp_ajax_br_force_sell_settings_save", array ( __CLASS__, 'save_settings' ) );
            add_action( 'woocommerce_product_options_related', array( __CLASS__, 'product_fields' ) );
            add_action( 'save_post', array( __CLASS__, 'wc_save_product' ) );
            add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'add_to_cart' ), 1, 6 );
            add_action( 'woocommerce_after_cart_item_quantity_update', array( __CLASS__, 'update_quantity'), 1, 2 );
            add_action( 'woocommerce_before_cart_item_quantity_zero', array( __CLASS__, 'update_quantity'), 1, 2 );
            add_filter( 'woocommerce_cart_item_remove_link', array( __CLASS__, 'remove_link'), 10, 2 );
            add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'change_quantity' ), 10, 2 );
            add_action( 'woocommerce_cart_item_removed', array( __CLASS__, 'remove_item' ), 30 );
            add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'remove_item' ) );
            add_action( 'woocommerce_cart_item_restored', array( __CLASS__, 'restore_item' ), 30 );
            add_filter( 'woocommerce_get_cart_item_from_session', array(__CLASS__, 'get_from_session'), 10, 2 );
            add_filter( 'woocommerce_get_item_data', array(__CLASS__, 'cart_item_data'), 10, 3 );
            add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'echo_linked_products' ) );
            add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
            $plugin_base_slug = plugin_basename( __FILE__ );
            add_filter( 'plugin_action_links_' . $plugin_base_slug, array( __CLASS__, 'plugin_action_links' ) );
            add_filter( 'is_berocket_settings_page', array( __CLASS__, 'is_settings_page' ) );
        }
    }
    public static function is_settings_page($settings_page) {
        if( ! empty($_GET['page']) && $_GET['page'] == self::$values[ 'option_page' ] ) {
            $settings_page = true;
        }
        return $settings_page;
    }
    public static function plugin_action_links($links) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page='.self::$values['option_page'] ) . '" title="' . __( 'View Plugin Settings', 'BeRocket_products_label_domain' ) . '">' . __( 'Settings', 'BeRocket_products_label_domain' ) . '</a>',
		);
		return array_merge( $action_links, $links );
    }
    public static function plugin_row_meta($links, $file) {
        $plugin_base_slug = plugin_basename( __FILE__ );
        if ( $file == $plugin_base_slug ) {
			$row_meta = array(
				'docs'    => '<a href="http://berocket.com/docs/plugin/'.self::$values['premium_slug'].'" title="' . __( 'View Plugin Documentation', 'BeRocket_products_label_domain' ) . '" target="_blank">' . __( 'Docs', 'BeRocket_products_label_domain' ) . '</a>',
				'premium'    => '<a href="http://berocket.com/product/'.self::$values['premium_slug'].'" title="' . __( 'View Premium Version Page', 'BeRocket_products_label_domain' ) . '" target="_blank">' . __( 'Premium Version', 'BeRocket_products_label_domain' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}
		return (array) $links;
    }
    public static function init () {
    }
    public static function echo_linked_products() {
        $options = self::get_option();
        if( ! @ $options['display_force_sell'] && ! @ $options['display_force_sell_linked'] ) {
            return;
        }
        global $post;
        $product_id =  $post->ID;
        if( @ $options['display_force_sell'] ) {
            $products = get_post_meta( $product_id, 'berocket_force_sell', true );
            if( empty($products) || ! is_array($products) ) {
                $products = array();
            }
        }
        if( @ $options['display_force_sell_linked'] ) {
            $products_linked = get_post_meta( $product_id, 'berocket_force_sell_linked', true );
            if( empty($products_linked) || ! is_array($products_linked) ) {
                $products_linked = array();
            }
        }
        $is_products = @ $options['display_force_sell'] && ! empty($products) && is_array($products) && count($products) > 0;
        $is_products_linked = @ $options['display_force_sell_linked'] && ! empty($products_linked) && is_array($products_linked) && count($products_linked) > 0;
        
        if( $is_products || $is_products_linked ) {
            echo '<div style="clear:both;"></div>';
            echo '<div class="berocket_linked_products">
                <p>', __('Products that will be added:', 'BeRocket_force_sell_domain'), '</p>';
            if( $is_products ) {
                echo '<ul class="force_sell_list">';
                foreach($products as $product) {
                    $title = get_the_title( $product );
                    echo "<li>{$title}</li>";
                }
                echo '</ul>';
            }
            if( $is_products_linked ) {
                echo '<ul class="force_sell_linked">';
                foreach($products_linked as $product) {
                    $title = get_the_title( $product );
                    echo "<li>{$title}</li>";
                }
                echo '</ul>';
            }
            echo '</div>';
            echo '<div style="clear:both;"></div>';
        }
    }
    public static function product_fields() {
        $product_id = get_the_ID();
        if( ! empty($product_id) ) {
            $products = get_post_meta( $product_id, 'berocket_force_sell', true );
            if( empty($products) || ! is_array($products) ) {
                $products = array();
            }
            $products_linked = get_post_meta( $product_id, 'berocket_force_sell_linked', true );
            if( empty($products_linked) || ! is_array($products_linked) ) {
                $products_linked = array();
            }
        } else {
            $products = array();
            $products_linked = array();
        }
        echo '<div class="options_group berocket_option_group">';
        echo '<label>', __('Linked Products, that can be removed', 'BeRocket_force_sell_domain'), '</label>';
        br_generate_product_selector( array( 
            'option' => $products, 
            'block_name' => 'berocket_force_sell', 
            'name' => 'berocket_force_sell[]' 
        ) );
        echo '</div><div class="options_group berocket_option_group">';
        echo '<label>', __('Linked Products, that can be removed only with this product', 'BeRocket_force_sell_domain'), '</label>';
        br_generate_product_selector( array( 
            'option' => $products_linked, 
            'block_name' => 'berocket_force_sell_linked', 
            'name' => 'berocket_force_sell_linked[]' 
        ) );
        echo '</div>';
    }
    public static function wc_save_product( $product_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( isset( $_POST['berocket_force_sell'] ) ) {
            update_post_meta( $product_id, 'berocket_force_sell', $_POST['berocket_force_sell'] );
        } else {
            delete_post_meta( $product_id, 'berocket_force_sell' );
        }
        if ( isset( $_POST['berocket_force_sell_linked'] ) ) {
            update_post_meta( $product_id, 'berocket_force_sell_linked', $_POST['berocket_force_sell_linked'] );
        } else {
            delete_post_meta( $product_id, 'berocket_force_sell_linked' );
        }
    }
    public static function add_to_cart($cart_item_id, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        global $force_sell_added_products;
        if( empty($force_sell_added_products) ) {
            $force_sell_added_products = array();
        }
        if( in_array($product_id, $force_sell_added_products) ) {
            return;
        }
        if ( isset( WC()->cart->cart_contents[$cart_item_id]['linked_to'] ) ) {
			$linked_to_id = WC()->cart->cart_contents[$cart_item_id]['linked_to'];

			if ( isset( WC()->cart->cart_contents[ $linked_to_id ] ) ) {
				return;
			}
		}
        $options = self::get_option();
        $products = get_post_meta( $product_id, 'berocket_force_sell', true );
        if( empty($products) || ! is_array($products) ) {
            $products = array();
        }
        $products_linked = get_post_meta( $product_id, 'berocket_force_sell_linked', true );
        if( empty($products_linked) || ! is_array($products_linked) ) {
            $products_linked = array();
        }
        foreach($products as $product) {
            $md5_search = WC()->cart->generate_cart_id( $product, '', '', array() );
            $find_item_id = WC()->cart->find_product_in_cart( $md5_search );
            if( $find_item_id ) {
                WC()->cart->set_quantity( $find_item_id, WC()->cart->cart_contents[ $find_item_id ]['quantity'] + $quantity );
            } else {
                $force_sell_added_products[] = $product;
                WC()->cart->add_to_cart( $product, $quantity, '', '', array() );
            }
        }
        foreach($products_linked as $product) {
            $md5_search = WC()->cart->generate_cart_id( $product, '', '', array( 'linked_to' => $cart_item_id ) );
            $find_item_id = WC()->cart->find_product_in_cart( $md5_search );
            if( $find_item_id ) {
                WC()->cart->set_quantity( $find_item_id, WC()->cart->cart_contents[ $find_item_id ]['quantity'] );
            } else {
                $force_sell_added_products[] = $product;
                WC()->cart->add_to_cart( $product, $quantity, '', '', array( 'linked_to' => $cart_item_id ) );
            }
        }
    }
    public static function update_quantity($item_id, $quantity = 0) {
        if( $quantity <= 0 ) {
            $quantity = 0;
        } else {
            $quantity = WC()->cart->cart_contents[$item_id]['quantity'];
        }
        foreach ( WC()->cart->cart_contents as $key => $value ) {
            if( ! empty($value['linked_to']) && $value['linked_to'] == $item_id ) {
                WC()->cart->set_quantity( $key, $quantity );
            }
        }
    }
    public static function remove_link($link, $cart_id) {
        $cart_contents = WC()->cart->cart_contents;
        if( ! empty($cart_contents[$cart_id]['linked_to']) ) {
            $link = '';
        }
        return $link;
    }
    public static function change_quantity($quantity, $item_id) {
        $cart_contents = WC()->cart->cart_contents;
        if( ! empty($cart_contents[$item_id]['linked_to']) ) {
            $quantity = $cart_contents[$item_id]['quantity'];
        }
        return $quantity;
    }
    public static function remove_item($item_id = false) {
        $cart = WC()->cart->get_cart();
        if( ! empty($cart) && is_array($cart) ) {
            foreach ( $cart as $key => $value ) {
                if( ! empty($value['linked_to']) && 
                ( ! array_key_exists( $value['linked_to'], $cart ) || 
                ( $item_id !== false && $item_id == $value['linked_to'] ) ) ) {
                    WC()->cart->remove_cart_item( $key );
                }
                
            }
        }
    }
    public static function restore_item($item_id) {
        foreach ( WC()->cart->removed_cart_contents as $key => $value ) {
            if( ! empty($value['linked_to']) && $value['linked_to'] == $item_id ) {
                WC()->cart->restore_cart_item( $key );
            }
        }
    }
    public static function get_from_session($cart_contents, $session_contents) {
        if( ! empty($session_contents['linked_to']) ) {
            $cart_contents['linked_to'] = $session_contents['linked_to'];
        }
        return $cart_contents;
    }
    public static function cart_item_data($data, $item) {
        
        if( ! empty($item['linked_to']) ) {
            $find_item_id = WC()->cart->find_product_in_cart( $item['linked_to'] );
            $_product = WC()->cart->cart_contents[ $find_item_id ]['data'];
            $_product_post = br_wc_get_product_post($_product);
            $linked_to_name = $_product_post->post_title;
            $data[] = array('key' => 'Linked with', 'value' => $linked_to_name);
        }
        return $data;
    }
    /**
     * Function set styles in wp_head WordPress action
     *
     * @return void
     */
    public static function set_styles () {
        $options = self::get_option();
        echo '<style>'.$options['custom_css'].'</style>';
    }
    /**
     * Load template
     *
     * @access public
     *
     * @param string $name template name
     *
     * @return void
     */
    public static function br_get_template_part( $name = '' ) {
        $template = '';

        // Look in your_child_theme/woocommerce-force_sell/name.php
        if ( $name ) {
            $template = locate_template( "woocommerce-force_sell/{$name}.php" );
        }

        // Get default slug-name.php
        if ( ! $template && $name && file_exists( force_sell_TEMPLATE_PATH . "{$name}.php" ) ) {
            $template = force_sell_TEMPLATE_PATH . "{$name}.php";
        }

        // Allow 3rd party plugin filter template file from their plugin
        $template = apply_filters( 'force_sell_get_template_part', $template, $name );

        if ( $template ) {
            load_template( $template, false );
        }
    }

    public static function admin_enqueue_scripts() {
        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        } else {
            wp_enqueue_style( 'thickbox' );
            wp_enqueue_script( 'media-upload' );
            wp_enqueue_script( 'thickbox' );
        }
    }

    /**
     * Function adding styles/scripts and settings to admin_init WordPress action
     *
     * @access public
     *
     * @return void
     */
    public static function admin_init () {
        wp_enqueue_script("jquery");
        wp_register_style( 'font-awesome', plugins_url( 'css/font-awesome.min.css', __FILE__ ) );
        wp_enqueue_style( 'font-awesome' );
        wp_enqueue_script( 'berocket_force_sell_admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), BeRocket_force_sell_version );
        wp_register_style( 'berocket_force_sell_admin_style', plugins_url( 'css/admin.css', __FILE__ ), "", BeRocket_force_sell_version );
        wp_enqueue_style( 'berocket_force_sell_admin_style' );
        wp_enqueue_script( 'berocket_global_admin', plugins_url( 'js/admin_global.js', __FILE__ ), array( 'jquery' ) );
        wp_localize_script( 'berocket_global_admin', 'berocket_global_admin', array(
            'security' => wp_create_nonce("search-products")
        ) );
    }
    /**
     * Function add options button to admin panel
     *
     * @access public
     *
     * @return void
     */
    public static function options() {
        add_submenu_page( 'woocommerce', __('Force Sell settings', 'BeRocket_force_sell_domain'), __('Force Sell', 'BeRocket_force_sell_domain'), 'manage_options', 'br-force_sell', array(
            __CLASS__,
            'option_form'
        ) );
    }
    /**
     * Function add options form to settings page
     *
     * @access public
     *
     * @return void
     */
    public static function option_form() {
        $plugin_info = get_plugin_data(__FILE__, false, true);
        include force_sell_TEMPLATE_PATH . "settings.php";
    }
    /**
     * Function remove settings from database
     *
     * @return void
     */
    public static function deactivation () {
        delete_option( self::$values['settings_name'] );
    }
    public static function save_settings () {
        if( current_user_can( 'manage_options' ) ) {
            if( isset($_POST[self::$values['settings_name']]) ) {
                update_option( self::$values['settings_name'], self::sanitize_option($_POST[self::$values['settings_name']]) );
                echo json_encode($_POST[self::$values['settings_name']]);
            }
        }
        wp_die();
    }

    public static function sanitize_option( $input ) {
        $default = self::$defaults;
        $result = self::recursive_array_set( $default, $input );
        return $result;
    }
    public static function recursive_array_set( $default, $options ) {
        $result = array();
        foreach( $default as $key => $value ) {
            if( array_key_exists( $key, $options ) ) {
                if( is_array( $value ) ) {
                    if( is_array( $options[$key] ) ) {
                        $result[$key] = self::recursive_array_set( $value, $options[$key] );
                    } else {
                        $result[$key] = self::recursive_array_set( $value, array() );
                    }
                } else {
                    $result[$key] = $options[$key];
                }
            } else {
                if( is_array( $value ) ) {
                    $result[$key] = self::recursive_array_set( $value, array() );
                } else {
                    $result[$key] = '';
                }
            }
        }
        foreach( $options as $key => $value ) {
            if( ! array_key_exists( $key, $result ) ) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    public static function get_option() {
        $options = get_option( self::$values['settings_name'] );
        if ( @ $options && is_array ( $options ) ) {
            $options = array_merge( self::$defaults, $options );
        } else {
            $options = self::$defaults;
        }
        return $options;
    }
}

new BeRocket_force_sell;

berocket_admin_notices::generate_subscribe_notice();
new berocket_admin_notices(array(
    'start' => 1498413376, // timestamp when notice start
    'end'   => 1504223940, // timestamp when notice end
    'name'  => 'name', //notice name must be unique for this time period
    'html'  => 'Only <strong>$10</strong> for <strong>Premium</strong> WooCommerce Load More Products plugin!
        <a class="berocket_button" href="http://berocket.com/product/woocommerce-load-more-products" target="_blank">Buy Now</a>
         &nbsp; <span>Get your <strong class="red">50% discount</strong> and save <strong>$10</strong> today</span>
        ', //text or html code as content of notice
    'righthtml'  => '<a class="berocket_no_thanks">No thanks</a>', //content in the right block, this is default value. This html code must be added to all notices
    'rightwidth'  => 80, //width of right content is static and will be as this value. berocket_no_thanks block is 60px and 20px is additional
    'nothankswidth'  => 60, //berocket_no_thanks width. set to 0 if block doesn't uses. Or set to any other value if uses other text inside berocket_no_thanks
    'contentwidth'  => 400, //width that uses for mediaquery is image_width + contentwidth + rightwidth
    'subscribe'  => false, //add subscribe form to the righthtml
    'priority'  => 10, //priority of notice. 1-5 is main priority and displays on settings page always
    'height'  => 50, //height of notice. image will be scaled
    'repeat'  => false, //repeat notice after some time. time can use any values that accept function strtotime
    'repeatcount'  => 1, //repeat count. how many times notice will be displayed after close
    'image'  => array(
        'local' => plugin_dir_url( __FILE__ ) . 'images/ad_white_on_orange.png', //notice will be used this image directly
    ),
));
