<?php
/**
 * Handle all frontend operation
 *
 * @package  woocommerce-bacchus-gold-member
 * @subpackage lib
 * @author Yevgen <yevgen.slyuzkin@gmail.com>
 * @version 0.0.0
 */
class WBGM_Frontend
{
    /** @var boolean Denote if WBGM is enabled */
    protected $_wbgm_enabled;

    /** @var integer Number of gift items allowed */
    protected $_wbgm_gifts_allowed;

    /** @var array Gift products */
    protected $_wbgm_products;

    /** @var integer Minimum number of items in cart for gift */
    protected $_minimum_qty;

    /** @var string Free gift type */
    protected $_wbgm_type;

    /** @var double gold member balance */
    protected $_wbgm_balance;

    /** @var boolean Denotes if there is a valid criteria */
    protected $_wbgm_criteria;

    /** @var float price of product on current price */
    public $wbgm_cur_price;

    /** @var array of new gifts */
    protected $_wbgm_added_gifts;

    /** @var array of other events */
    protected $_wbgm_info_gifts;

    /** @var array of del gifts */
    protected $_wbgm_deleted_gifts;

    /** @var bool of show new gifts [false = showed]*/
    protected $_wbgm_flag_show_gifts;

    /** @var bool of item add to cart*/
    protected $_wbgm_is_add_to_cart;

    /** @var bool of item removed from cart*/
    protected $_wbgm_is_removed;

    /** @var bool of item removed from cart*/
    protected $_wbgm_qty_update;

    /**
     * Constructor
     *
     * @see  get_option()
     * @since  0.0.0
     */
    public function __construct()
    {
        global $wbgm_qty_update;

        $this->_wbgm_type = 'global';
        $this->_minimum_qty = 1;

        /*$this->_wbgm_enabled = WBGM_Settings_Helper::get( $this->_wbgm_type . '_enabled', true, 'global_options' );*/
        $this->_wbgm_criteria = false;
        $this->_wbgm_gifts_allowed = 1;
        $this->_wbgm_added_gifts = array();
        $this->_wbgm_info_gifts = array();
        $this->_wbgm_deleted_gifts = array();
        if ($wbgm_qty_update !== 0) {
            $this->_wbgm_qty_update = $wbgm_qty_update;
        } else {
            $this->_wbgm_qty_update = 0;
        }

        $this->_wbgm_is_removed = false;
        $this->_wbgm_is_add_to_cart = false;

        self::__init_cookie();
        //Add hooks and filters
        self::__init();
    }

    /**
     * Add require hooks and filters
     *
     * @see  add_action()
     * @since  0.0.0
     * @access private
     */
    private function __init()
    {
        /*  Add free gifts ajax callback */
        add_action( 'wp_ajax_wbgm_add_bonus_gifts', array( $this, 'wbgm_ajax_add_bonus_gifts' ) );
        add_action( 'wp_ajax_nopriv_wbgm_add_bonus_gifts', array( $this, 'wbgm_ajax_add_bonus_gifts' ) );

        /* Update front-end */
        add_filter('woocommerce_add_to_cart_fragments', array( $this, 'wbgm_ajax_update_total'));
        add_filter('wbgm_validate', array( $this, 'validate_gifts'));

        /* Flags Add and Remove*/
        add_action('wbgm_after_add_bonus', array( $this, 'wbgm_is_add_to_cart'));
        add_action('woocommerce_cart_item_removed', array( $this, 'wbgm_is_removed'));

        add_action( 'init', array( $this, 'wbgm_gold_status_activate' ) );

        /* Display gifts in frontend */
        add_action( 'wp_init', array( $this, '__init_style' ) );
        add_action( 'wp_head', array( $this, 'wbgm_protect' ), 100 );
        add_action( 'wp_head', array( $this, 'shop_script' ) );
        add_action( 'wp_head', array( $this, 'product_script' ) );
        add_action( 'wp_head', array( $this, 'validate_gifts' ) );
        add_action( 'wp_head', array( $this, 'wbgm_gold_page_status' ) );
        add_action( 'wp_footer', array( $this, 'wbgm_gold_page_status' ) );
        add_action( 'wp_head', array( $this, 'wbgm_gold_member_balance' ) );;
        add_action( 'wp_head', array( $this, 'wbgm_check_cart' ) );
        add_action( 'wp_head', array( $this, 'wbgm_ajax_update_total' ) );/*
        add_action( 'wp_head', array( $this, 'wbgm_custom_notice' ) );*/
        add_action( 'wp_head', array( $this, 'init_style' ) );

        add_action( 'wp_head', array( $this, 'wbgm_gold_page' ) );
        add_action( 'wp_head', array( $this, 'wbgm_product_page' ) );

        /*add_action( 'wp_footer', array( $this, 'wbgm_gold_member_balance2' ) );*/
        /*add_action( 'woocommerce_cart_updated', array( $this, 'wbgm_gold_member_balance2' ));
        add_action( 'woocommerce_cart_updated', array( $this, 'wbgm_gold_member_balance' ) );
        add_action( 'woocommerce_cart_updated', array( $this, 'wbgm_update' ) );*/

        add_action( 'gform_pre_submission', array( $this, 'wbgm_gold_page' ), 10, 2 );

        /*add_filter( 'wbgm_top_info', array( $this, 'wbgm_add_info' ), 10, 2 );
        add_filter( 'wbgm_logo', array( $this, 'wbgm_add_logo' ), 10, 2 );*/
        add_filter( 'wbgm_balance', array( $this, 'wbgm_show_balance' ), 10, 2 );

        add_action('gform_post_submission', array( $this, 'wbgm_gold_page'), 10, 2);/*
        add_action('gform_post_submission', array( $this, 'wbgm_ajax_update_total'), 10, 2);*/

        /* Do not allow user to update quantity of gift items */
        add_filter( 'woocommerce_is_sold_individually', array( $this, 'wbgm_disallow_qty_update' ), 10, 2 );

        /* Remove gifts when main item is removed */
        /*add_action( 'woocommerce_cart_item_removed', array( $this, 'wbgm_item_removed' ), 10, 2 );*/

        /* Notice Without Reloading page *//*
        add_action( 'woocommerce_before_single_product', array( $this, 'wbgm_print_shop_notices_field' ), 10 );*/
        add_action( 'woocommerce_before_single_product', array( $this, 'wbgm_print_notices_field' ), 10 );
        add_action( 'woocommerce_before_shop_loop', array( $this, 'wbgm_print_shop_notices_field' ), 100 );
        /*add_filter( 'wbgm_before_product', array( $this, 'wbgm_print_notices_field' ), 10 );*/
        /*add_action( 'woocommerce_after_shop_loop_item', array( $this, 'wbgm_add_gold_btn' ), 10, 1 );*/
        add_action( 'wbgm_before_product_in_list', array( $this, 'wbgm_add_gold_btn' ), 10, 1 );
        add_action( 'wc_after_price_single_product', array( $this, 'wbgm_add_gold_btn_for_single'), 10, 1);
        //add_action( 'wc_after_price_single_product_custom', array( $this, 'wbgm_add_gold_btn'), 10, 1);

    }

    /**
     * Add require and enqueue script
     *
     * @see  wp_register_script(), wp_enqueue_script
     * @since  0.0.0
     * @access private
     */
    private function __init_cookie()
    {
        /*  Add js cookie  */
        wp_enqueue_script( 'wbgm-js-cookie-script', plugins_url( '/js/js.cookie.js', dirname( __FILE__ ) ));
    }

    /**
     * Add require and enqueue script for /shop/ page
     *
     * @since  0.0.0
     * @access public
     */
    public function shop_script()
    {
        if(is_shop() && !is_product()) {
            /*  Add js shop  */
            wp_enqueue_script( 'wbgm-js-shop-script', plugins_url( '//templates/default/wbgm-shop.js', dirname( __FILE__ ) ));
        }
    }

    /**
     * Add require and enqueue script for single page
     *
     * @since  0.0.0
     * @access public
     */
    public function product_script()
    {
        if(is_product()) {
            /*  Add js shop  */
            wp_enqueue_script( 'wbgm-js-product-script', plugins_url( '//templates/default/wbgm-product.js', dirname( __FILE__ ) ));
        }
    }

    /**
     * Add require and enqueue style
     *
     * @see  wp_register_script(), wp_enqueue_script
     * @since  0.0.0
     * @access public
     */
    public function init_style()
    {
        /*  Add js cookie  */
        wp_enqueue_style('wbgm-template-styles', plugins_url('/templates/default/wbgm-default.css', dirname(__FILE__)), NULL, true);
        wp_enqueue_style('wbgm-core-styles', plugins_url('/css/wbgm-styles.css', dirname(__FILE__)));
    }

    /**
     * Protection of user data by redirection custom posts to /wbgm_error/ or to home(if need)
     *
     * @since  0.0.0
     * @access public
     */
    public function wbgm_protect()
    {
        if( (strpos(urlencode(get_permalink()), urlencode('/wbgm_gmember_list/') ) )  ) {
            wp_redirect( 404 );
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            get_template_part( 404 ); exit();
        }
    }

    /**
     * Overwrite default settings with actual settings
     *
     * @since  0.0.0
     * @access private
     *
     * @return void
     */
    private function __get_actual_settings()
    {
        $total = WBGM_Product_Helper::get_main_product_count();
        if( 1 == $total ) {
            //single gift
            $post_id = $this->__get_post_id();
            if( empty($post_id) ) {
                return;
            }

            $wbgm_enabled = get_post_meta( $post_id, '_wbgm_single_gift_enabled', true );
            if( (bool) $wbgm_enabled ) {
                $this->_wbgm_type = 'single_gift';
                $this->_wbgm_enabled = $wbgm_enabled;
                $this->_wbgm_criteria = true;
                $this->_wbgm_gifts_allowed = get_post_meta( $post_id, '_wbgm_single_gift_allowed', true );
                $this->_wbgm_products = get_post_meta( $post_id, '_wbgm_single_gift_products', true );

                return;
            }
        }

        return $this->__hook_global_settings();
    }

    /**
     * Fetch actual product id
     *
     * @since  0.0.0
     * @access private
     *
     * @return integer|null
     */
    private function __get_post_id()
    {
        $post_id = null;
        foreach( WC()->cart->cart_contents as $key => $content ) {
            $is_gift_product = ($content['plugin'] == 'wbgm');
            if( ! $is_gift_product ) {
                return $content['product_id'];
            }
        }

        return $post_id;
    }

    /**
     * Change flag if add to cart item
     *
     * @since  0.0.0
     * @access public
     *
     * @return null
     */
    public function wbgm_is_add_to_cart()
    {
        $this->_wbgm_is_add_to_cart = true;
    }

    /**
     * Fetch actual product id
     *
     * @since  0.0.0
     * @access public
     *
     * @return null
     */
    public function wbgm_is_removed()
    {
        $this->_wbgm_is_removed = true;
    }

    /**
     * Hook global settings to actual settings
     *
     * @since  0.0.0
     * @access private
     *
     * @return void
     */
    private function __hook_global_settings()
    {
        //look for global settings
        $_wbgm_global_settings = WBGM_Settings_Helper::get( '', false, 'global_settings', false );
        if( empty($_wbgm_global_settings) ) {
            return;
        }

        foreach( $_wbgm_global_settings as $setting ) {
            $gift_criteria = $setting['condition'];
            $criteria = WBGM_Criteria_Helper::parse_criteria( $gift_criteria );
            if( $criteria ) {
                $this->__set_actual_values( $setting );
                return;
            }
        }
    }

    /**
     * Set required values
     *
     * @since  0.0.0
     * @access private
     *
     * @return void
     */
    private function __set_actual_values( $setting )
    {
        $this->_wbgm_criteria = true;
        $this->_wbgm_gifts_allowed = $setting['num_allowed'];
        $this->_wbgm_products = ! empty( $setting['items'] ) ? array_unique( $setting['items'] ) : array();
    }

    /**
     * Create notice for changing by product. NOT USED
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_print_notices( )
    {
        if( $this->wbgm_valid_permission() ) {

            if (!is_product()) {
                return;
            }
            global $post;
            $product_id = $post->ID;
            $_product = wc_get_product($product_id);
            $link = $_product->post->guid;
            $title = $_product->post->post_title;
            $so_congrat = $this->wbgm_get_setting('so_congrat', '{Y} x {title} wurde als Gold Artikel hinzugefügt.');
            $so_congrat = str_replace(
                '{Y}',
                $this->wbgm_bonus_qty($product_id),
                $so_congrat);
            $so_congrat = str_replace(
                '{title}',
                '<a href="' . $link . '">' . $title . '</a>',
                $so_congrat);
            $this->_wbgm_added_gifts[$product_id] = ['message' => $so_congrat, 'type' => 'success'];
            wc_get_template( plugins_url('/template/default/notices/[$notice_type].php', dirname(__FILE__)), array(
                'messages' => $this->_wbgm_added_gifts
            ) );
        }
    }

    /**
     * Return a setting
     *
     * @since  0.0.0
     * @access public
     *
     *
     * @param  string $setting_key name of variable in setting
     * @param  string $default_value default value for undefined variable in setting
     * @return string
     */
    public function wbgm_get_setting( $setting_key, $default_value )
    {
        $value_of_setting = WBGM_Settings_Helper::get( $setting_key, false, 'global_options' );
        if( false == $value_of_setting ) {
            $value_of_setting = WBGM_Common_Helper::translate( $default_value );
        }
        return $value_of_setting;
    }

    /**
     * Create field for notices.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_print_notices_field( )
    {
        if( $this->wbgm_valid_permission(1) ) {
            global $post;

            $product_sku = new WC_Product($post->ID);
            $sku = $product_sku->get_sku();
            if ($sku == 339341) {
                return;
            }

            include(PLUGIN_DIR . 'templates/default/template-single-product-notices.php');

            echo '<div id="wbgm-script-notice-remove" style="display: none;"></div>';
            echo '<div id="wbgm-script-notice-add" style="display: none;"></div>';
            echo '<div id="wbgm-script-notice-shop" style="display: none;"></div>';
        }
    }

    /**
     * Create field for notices.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_print_shop_notices_field( )
    {
        if( $this->wbgm_valid_permission(1) ) {
            global $post;

            $product_sku = new WC_Product($post->ID);
            $sku = $product_sku->get_sku();

            include(PLUGIN_DIR . 'templates/default/template-shop-notices.php');

            echo '<div id="wbgm-script-notice-remove" style="display: none;"></div>';
            echo '<div id="wbgm-script-notice-add" style="display: none;"></div>';
            echo '<div id="wbgm-script-notice-shop" style="display: none;"></div>';
        }
    }

    /**
     * Create notice for changing by product.
     *
     * @since  0.0.0
     * @access public
     *
     * @param  int $product_id noticed item id
     * @param  int $quantity quantity changed of item
     * @param  string $notice_type type of changing
     *
     * @return void
     */
    public function create_notice( $product_id, $quantity, $notice_type )
    {
        $_product = wc_get_product($product_id);
        $link = $_product->post->guid;
        $title = $_product->post->post_title;
        switch ($notice_type) {
            case 'delete':
                $so_deleted_gift = $this->wbgm_get_setting('so_deleted_gift', '{Y} x {title} wurde(n) aus dem Warenkorb entfernt.');
                $so_deleted_gift = str_replace(
                    '{Y}',
                    $quantity,
                    $so_deleted_gift);
                $so_deleted_gift = str_replace(
                    '{title}',
                    '<a href="' . $link . '">' . $title . '</a>',
                    $so_deleted_gift);
                $this->_wbgm_deleted_gifts[$product_id] = ['message' => $so_deleted_gift, 'type' => 'error'];
                $this->wbgm_custom_notice();
                break;
            case 'add':
                /*
				$so_congrat = $this->wbgm_get_setting('so_congrat', '{Y} x {title} wurde als Gold Artikel hinzugefügt.');
                if($so_congrat) {
                    $so_congrat = '{Y} x {title} wurde als Gold Artikel hinzugefügt.';
                }
                $so_congrat = str_replace(
                    '{Y}',
                    $quantity,
                    $so_congrat);
                $so_congrat = str_replace(
                    '{title}',
                    '<a href="' . $link . '">' . $title . '</a>',
                    $so_congrat);
                $this->_wbgm_added_gifts[$product_id] = ['message' => $so_congrat, 'type' => 'success'];
                $this->wbgm_custom_notice();*/
                break;
            default:
        }
    }

    /**
     * Display custom_notice about gifts.
     *
     * @since 0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_custom_notice()
    {

        $so_congrat_enabled = $this->wbgm_get_setting('so_congrat_enabled', false);
        $so_deleted_gift_enabled = $this->wbgm_get_setting('so_deleted_gift_enabled', false);
        $so_congrat_save_money_enabled = $this->wbgm_get_setting('so_congrat_save_money_enabled', false);

        if( true/*is_cart()*/ ) {
            if ($so_congrat_enabled) {
                foreach ($this->_wbgm_added_gifts as $key => $added_item) {
                    wc_add_notice($added_item['message'], $added_item['type']);
                }
            }
        }
        if( true/*is_cart()*/ ) {
            if ($so_deleted_gift_enabled) {
                foreach ($this->_wbgm_deleted_gifts as $key => $deleted_item) {
                    wc_add_notice($deleted_item['message'], $deleted_item['type']);
                }
            }
        }

        /**
         * BUG: with wc_add_notice($message, 'notice');
         * FIXED: in wbgm-styles.css
         * .woocommerce-info {
         *        display: block!important;}
         *
         */
        if( is_cart() ) {
            if( $so_congrat_save_money_enabled ) {
                foreach ($this->_wbgm_info_gifts as $key => $info_item) {
                    wc_add_notice($info_item['message'], $info_item['type']);
                }
            }
        }
        /** Clear arrays*/
        $this->_wbgm_added_gifts = array();
        $this->_wbgm_deleted_gifts = array();
        $this->_wbgm_info_gifts = array();
    }

    /**
     * Add free item to cart.
     *
     * @since  0.0.0
     * @access public
     * @param  int $product_id noticed item id
     *
     * @return string
     */
    public function wbgm_return_notice_add($product_id)
    {
        $_product = wc_get_product($product_id);
        $link = $_product->post->guid;
        $title = $_product->post->post_title;

        $so_congrat = $this->wbgm_get_setting('so_congrat', '{Y} x {title} wurde als Gold Artikel hinzugefügt.');

        $so_congrat = str_replace(
            '{Y}',
            1,
            $so_congrat);
        $so_congrat = str_replace(
            '{title}',
            '<a href="' . $link . '">' . $title . '</a>',
            $so_congrat);
        $this->_wbgm_added_gifts[$product_id] = ['message' => $so_congrat, 'type' => 'success'];


        $this->wbgm_custom_notice();
    }


    /**
     * Add free item to cart.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_ajax_add_bonus_gifts()
    {
        $is_added = false;
        $product_id = $_POST['wbgm_free_items'];

        foreach ( WC()->cart->get_cart() as $key => $content ) {
            if ( $content->product_id === $product_id ){
                $is_gift_product = ($content['plugin'] == 'wbgm');
                if( $is_gift_product ) {
                    WC()->cart->set_quantity( $content->product_id, $content->quantity + 1 );
                    $is_added = true;

                    $this->wbgm_return_notice_add($content->product_id);
                    $this->create_notice($content->product_id, $content->quantity + 1, 'add');
                }
            }
        }



        remove_filter( 'woocommerce_is_sold_individually', array( $this, 'wbgm_disallow_qty_update' ), 10);
        self::__get_actual_settings();
        $_price = $_POST['cur_price'];
        $this->wbgm_gold_member_balance();
        if ( $this->_wbgm_balance > $_price ) {
            $free_product = wbgm_Product_Helper::create_gift_variation( $product_id );
            wbgm_Product_Helper::add_free_product_to_cart( $product_id, $free_product );
            $this->create_notice($product_id, 1, 'add');
        } else {
            //wc_add_wp_error_notices('Your balance is low');
        }
        add_filter( 'woocommerce_is_sold_individually', array( $this, 'wbgm_disallow_qty_update' ), 10, 2 );
        do_action('wbgm_after_add_bonus');
    }

    /**
     * Update frontend elements.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_check_cart()
    {
        if( $this->wbgm_valid_permission() ) {
            if (!is_cart()) {
                return;
            }

        }
    }

    /**
     * Recount gold member balance.
     *
     * @since  0.0.0
     * @access public
     *
     * @return double
     */
    public function wbgm_gold_member_balance()
    {
        global $post;
        if( $this->wbgm_valid_permission()) {


            if (is_product()) {
                $this->wbgm_cur_price = get_post_meta($post->ID, '_regular_price', true);
            }

            $total_free_cost = 0.0;
            $total_cost = WC()->cart->subtotal;
            foreach (WC()->cart->get_cart() as $key => $item) {
                if ($item['plugin'] == 'wbgm') {
                    $_product = wc_get_product($item['product_id']);
                    $total_free_cost += doubleval($_product->get_price()) * $item['quantity'];
                }
            }
            $this->_wbgm_balance = $total_cost * 0.1 - $total_free_cost;

            return $this->_wbgm_balance;
        }
    }

    /**
     * Recount gold member balance.
     *
     * @since  0.0.0
     * @access public
     *
     * @return string
     */
    public function wbgm_show_balance()
    {
        $this->wbgm_gold_member_balance();
        setlocale(LC_MONETARY, 'de_DE');
        return money_format('%.2n', $this->_wbgm_balance);
    }

    /**
     * Recount gold member balance.
     *
     * @since  0.0.0
     * @access public
     *
     * @return boolean
     */
    public function wbgm_is_show_bonus_add_btn()
    {
        $this->wbgm_gold_member_balance();

        if ( floatval( $this->wbgm_cur_price ) <= floatval( $this->wbgm_gold_member_balance() )) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Recount gold member balance and return if it negative.
     *
     * @since  0.0.0
     * @access public
     *
     * @return boolean
     */
    public function wbgm_is_balance_negative()
    {
        $this->wbgm_gold_member_balance();

        if ( 0 > floatval( $this->wbgm_gold_member_balance() )) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Disallow qty update in gift products.
     *
     * @since  0.0.0
     * @access public
     *
     * @param  boolean $return  Is return product
     * @param  object $product Product object
     *
     * @return integer|boolean
     */
    public function wbgm_disallow_qty_update( $return, $product )
    {
        if( ! $this->wbgm_valid_permission()) {
            return $return;
        }
        if(! is_cart()) {
            return $return;
        }

        $is_plugin_wbgm = false;
        foreach (WC()->cart->get_cart() as $key => $item){
            if( $product->variation_id == $item->variation_id) {
                if( !($item['plugin'] == 'wbgm') ) {
                    $is_plugin_wbgm = true;
                }
            }
        }

        if( $is_plugin_wbgm ) {
            return $return;
        } else {
            return 1;
        }
    }

    /**
     * Remove all gifts when main item is removed.
     *
     * @since  0.0.0
     * @access public
     *
     * @param  string $cart_item_key Removed item key
     * @param  object $cart          Cart object
     *
     * @return void
     */
    public function wbgm_item_removed( $cart_item_key, $cart )
    {
        //no need to process further if qty is zero
        if( empty($cart->cart_contents) ) {
            return;
        }

        //check if removed item is a variation or main product
        $removed_item = $cart->removed_cart_contents[ $cart_item_key ];
        if( ! empty($removed_item['variation_id']) ) {
            return;
        }

        if( 'global' == $this->_wbgm_type && 0 == WBGM_Product_Helper::get_main_product_count() ) {
            foreach( $cart->cart_contents as $key => $content ) {
                $this->create_notice(
                    $content['product_id'],
                    $content['quantity'],
                    'delete'
                );
                WC()->cart->remove_cart_item( $key );
            }
        }
    }

    /**
     * Remove gifts if the criteria is invalid.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function validate_gifts()
    {
        if( $this->wbgm_valid_permission()) {
            if (!$this->__gift_item_in_cart()) {
                return;
            }
            $this->wbgm_gold_member_balance();
            if ( $this->_wbgm_balance < 0.0 ) {
                $this->__remove_gift_products();
            }

            self::__get_actual_settings();
        } else {
            if ($this->__gift_item_in_cart()) {
                $this->__remove_gift_products();
            }
        }
    }

    /**
     * Set notice text.
     *
     * @since  0.0.0
     * @access private
     *
     * @return void
     */
    private function __set_notice_text()
    {
        $noticeText = $this->wbgm_get_setting('invalid_condition_text', 'Gift items removed as gift criteria isnt fulfilled');

        WBGM_Common_Helper::fixed_notice( $noticeText );
    }

    /**
     * Validate single gift condition.
     *
     * @since  0.0.0
     * @access protected
     *
     * @return boolean
     */
    protected function _validate_single_gift_condition()
    {
        if( 'single_gift' !== $this->_wbgm_type ) {
            return false;
        }

        $total_items_in_cart = WBGM_Product_Helper::get_main_product_count();
        if( 1 !== $total_items_in_cart ) {
            return false;
        }

        return $this->__remove_gift_products();
    }

    /**
     * Remove gifts products.
     *
     * @since  0.0.0
     * @access private
     *
     * @return boolean
     */
    private function __remove_gift_products()
    {
        $removed = false;
        foreach( WC()->cart->get_cart() as $key => $content ) {
            if( $content['plugin'] == 'wbgm' ) {
                $this->create_notice(
                    $content['product_id'],
                    $content['quantity'],
                    'delete'
                );
                WC()->cart->remove_cart_item( $key );
                $removed = true;
            }
        }


        return $removed;
    }

    /**
     * Display gift popup in frontend.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function display_gifts()
    {
        if( $this->wbgm_valid_permission(1)) {
            if (!is_cart()) {
                return;
            }

            if ($this->__gift_item_in_cart()) {
                return;

            }

            self::__get_actual_settings();

            //check gift criteria
            if (!$this->_check_global_gift_criteria()) {
                return;
            }

            //enqueue required styles for this page

            $items = WBGM_Product_Helper::get_cart_products();
            if ($items['count'] >= $this->_minimum_qty) {
                $this->_show_gifts();
            }
        }
    }

    /**
     * Verify gold status on gold page in frontend.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_product_page()
    {
        if (!is_product()) {
            return;
        }

        $this->wbgm_is_sku_in_cart();
    }
    /**
     * Verify gold status on gold page in frontend.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_gold_page($form = null)
    {
        if( ! is_page('gold') ) {
            return;
        }
        $is_valid = false;
        if( $this->wbgm_valid_permission(1)) {
            if (! ($form == null)) {
                if ($_SERVER['REQUEST_METHOD'] == "POST")
                {
                    if( intval(urlencode($_POST['input_1'])) ) {
                        $is_valid = $this->wbgm_check_number(urlencode($_POST['input_1']), urlencode($_POST['input_10']));
                    } else {
                        $is_valid = $this->wbgm_check_email(urlencode($_POST['input_4']), urlencode($_POST['input_10']));
                    }
                    if( !$is_valid ) {
                        $_POST['input_6'] = '';
                    } else {
                        $_POST['input_6'] = '123';
                    }
                }
            }
        }
    }

    /**
     * Prepare free gold product.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_prepare_free_product()
    {
        if( $this->wbgm_valid_permission(1)) {

            /*enqueue required styles for this page*/
            include(PLUGIN_DIR . 'templates/default/template-default.php');
        }
    }

    /**
     * Display gold status in frontend.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_gold_page_status()
    {
        if( $this->wbgm_valid_permission()) {
            $logo_img = plugins_url('/templates/images/Bacchus_Gold_Logo.png', dirname(__FILE__));
            /*enqueue required styles for this page*/
            wp_enqueue_style( 'wbgm-template-styles', plugins_url( '/templates/default/wbgm-default.css', dirname( __FILE__ ) ) );

            include( PLUGIN_DIR . 'templates/default/template-gold-status.php' );
            echo '<script>jQuery(".tb-left").html(\'<div class="tb-text"><div class="wbgm-info-top">Ihr Bacchus Gold Guthaben für diese Bestellung beträgt <a href="/shop/?min_price=0&max_price=' . $this->_wbgm_balance . '"><span class="wbgm-top-balance btn"> ' . apply_filters('wbgm_balance','') . ' </span></a><a href="/gold"><span class="glyphicon glyphicon-info-sign wbgm-info-sign" style="color: rgba(235, 181, 102, 1);"></span></a></div></div>\');</script>';
        }
    }

    /**
     * Display gold members add-to-cart btn in frontend.
     *
     * @since  0.0.0
     * @access public
     *
     * @return void
     */
    public function wbgm_add_btn_add_to_cart_gold()
    {
        if( $this->wbgm_valid_permission()) {
            apply_filters('wbgm_filter_gold_member_add_to_cart', '');
        }
    }

    /**
     * Display logo in frontend.
     *
     * @since  0.0.0
     * @access public
     *
     * @return string
     */
    public function wbgm_add_logo()
    {
        if( $this->wbgm_valid_permission()) {
            return '<div id="wbgm-logo" style="width:80px; display: inline-block;"><a href="/gold"><img src="' . plugins_url('/templates/images/Bacchus_Gold_Logo.png', dirname(__FILE__)) . '"></a></div>';
        }
        return '';
    }

    /**
     * Display logo in frontend.
     *
     * @since  0.0.0
     * @access public
     *
     * @return string
     */
    public function wbgm_add_info()
    {
        if( $this->wbgm_valid_permission()) {

            return '<div class="wbgm-info-top">Ihr Bacchus Gold Guthaben für diese Bestellung beträgt <a href="/shop/?min_price=0&max_price=' . $this->_wbgm_balance . '"><span class="wbgm-top-balance btn"> ' . apply_filters('wbgm_balance', '') . ' </span></a><a href="/gold"><span class="glyphicon glyphicon-info-sign wbgm-info-sign" style="color: rgba(235, 181, 102, 1);"></span></a></div>';
        }
        return 'Ihre Genuss-Reise durch die Welt der Weine';
    }

    /**
     * Check the data from form (Number way).
     *
     * @since  0.0.0
     * @access private
     *
     * @param int $number custom number (Bacchus Gold)
     * @param string $postal postal code of member
     * @return boolean
     */
    private function wbgm_check_number( $number, $postal ) {

        if (strlen($number) > 10) {
            $number = substr($number, strlen($number) - 10);
        }

        $count_of_adds_zero = 10 - strlen($number);
        while($count_of_adds_zero > 0) {
            $number = '0' . $number;
            $count_of_adds_zero--;
        }
        $is_valid = false;
        foreach ( get_posts( array('numberposts' => 1000000, 'post_type' => 'wbgm_gmember_list' )) as $post_key => $post_value ) {
            $ac_info = explode('|', $post_value->post_content);
            if ( $this->wbgm_valid_date($ac_info[8]) ) {
                if( trim($number) == urlencode($ac_info[0]) ) {

                    if( trim($postal) == urlencode($ac_info[4]) ) {
                        setcookie( 'wbgm_ac_info', $post_value->post_content, 0, '/' );
                        $is_valid = true;
                    }
                }
            }

        }
        return $is_valid;
    }

    /**
     * Check the data from form (Email way).
     *
     * @since  0.0.0
     * @access private
     *
     * @param int $email custom number (Bacchus Gold)
     * @param string $postal postal code of member
     * @return boolean
     */
    private function wbgm_check_email( $email, $postal ) {
        $is_valid = false;
        foreach ( get_posts( array('numberposts' => 1000000, 'post_type' => 'wbgm_gmember_list' ))  as $post_key => $post_value ) {
            $ac_info = explode('|', $post_value->post_content);
            if ($this->wbgm_valid_date($ac_info[8]) && trim($email) == urlencode($ac_info[6]) && trim($postal) == urlencode($ac_info[4])) {
                setcookie('wbgm_ac_info', $post_value->post_content, 0, '/');
                $is_valid = true;
            }
        }
        return $is_valid;
    }

    /**
     * Validate date with current date.
     *
     * @since 0.0.0
     * @access public
     *
     * @param int $valid_date date for valid
     * @return boolean
     */
    public function wbgm_valid_date($valid_date)
    {
        $today = date("Ymd");
        if( $today < $valid_date ) {
            return true;
        }
        return false;
    }

    /**
     * Validate permission for access to functionality.
     *
     * @since 0.0.0
     * @access public
     *
     * @return boolean
     */
    public function wbgm_is_sku_in_cart()
    {
        $cart = WC()->cart;
        if ($cart){
            foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
                $product = new WC_Product($cart_item['product_id']);
                $sku = $product->get_sku();
                if($sku == 339341) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Validate permission for access to functionality.
     *
     * @since 0.0.0
     * @access public
     *
     * @param int $type type of check permission
     * @return boolean
     */
    public function wbgm_valid_permission($type = 0)
    {
        switch ($type){
            case 0:/*Gold cart*/
                if( $this->wbgm_get_setting('global_enabled', false) ) {
                    if( isset($_COOKIE['wbgm_ac_info']) ) {
                        return true;
                    }
                    if( $this->wbgm_is_sku_in_cart() ) {
                        return true;
                    }
                }
                return false;
                break;
            case 1:/*Gold plugin*/
                if( $this->wbgm_get_setting('global_enabled', false) ) {
                    return true;
                }
                return false;
                break;
            case 777:/*Debug mode*/
                if($_REQUEST['debug'] == true) {
                    if( $this->wbgm_get_setting('global_enabled', false) ) {
                        if( isset($_COOKIE['wbgm_ac_info']) ) {
                            return true;
                        }
                        if( $this->wbgm_is_sku_in_cart() ) {
                            return true;
                        }
                    }
                }
                return false;
                break;
            default:
                return false;
        }
    }

    /**
     * Display gifts.
     *
     * @since 0.0.0
     * @access public
     *
     * @return void
     */
    protected function _show_gifts()
    {
        if ( is_product() ) {
        }

        if( ! $this->_wbgm_enabled ) {
            return;
        }

        if( empty($this->_wbgm_products) ) {
            return;
        }

        if( $this->wbgm_valid_permission()) {
            $wbgm_free_products = array();
            foreach ($this->_wbgm_products as $product) {
                $wbgm_free_products[] = WBGM_Product_Helper::get_product_details($product);
            }

            include(PLUGIN_DIR . 'templates/default/template-default.php');
        }
    }

    /**
     * Check if global gift condition is satisfied.
     *
     * @since 0.0.0
     * @access public
     *
     * @return boolean
     */
    protected function _check_global_gift_criteria()
    {
        if( 'single_gift' === $this->_wbgm_type ) {
            return true;
        }

        $gift_criteria = $this->wbgm_get_setting('global_gift_criteria', false);
        if( empty($gift_criteria) ) {
            return true;
        }

        return WBGM_Criteria_Helper::parse_criteria( $gift_criteria );
    }

    /**
     * Check if there is already gift item in the cart
     *
     * @since  0.0.0
     * @access public
     *
     * @param array $post current post
     * @return void
     */
    public function wbgm_add_gold_btn($post)
    {
        include(PLUGIN_DIR . 'templates/default/template-default.php');
    }

    /**
     * Check if there is already gift item in the cart
     *
     * @since  0.0.0
     * @access public
     *
     * @param array $post current post
     * @return void
     */
    public function wbgm_add_gold_btn_for_single($post)
    {
        $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
        $actual_link = "http://" . $_SERVER['HTTP_HOST'] . $uri_parts[0];
        $actual_link_s = "https://" . $_SERVER['HTTP_HOST'] . $uri_parts[0];
        $post_link = get_permalink($post->ID);
        if ($actual_link_s == $post_link || $actual_link == $post_link) {
            include(PLUGIN_DIR . 'templates/default/template-default.php');
        }
    }

    /**
     * Check if there is already gift item in the cart
     *
     * @since  0.0.0
     * @access public
     *
     * @param array $post current post
     * @return void
     */
    public function wbgm_show_gold_member_bnt($post)
    {
        //$product = new WC_Product($post->ID);
        $product = wc_get_product($post->ID);
        $sku = $product->get_sku();
        if($product->is_in_stock()) {
            if (!($sku == 339341)) :
                $btn_adding_item_text = $this->wbgm_get_setting('btn_adding_to_cart_text', 'Bonus-Artikel wird hinzugefügt');
                ?>

                <div class="<?php echo $post->ID ?>"
                     style="display: block; margin-top: 15px; block; position: relative; bottom: 0;">
                    <button type="button" data-loading-text="<?php echo $btn_adding_item_text; ?>"
                            class="wbgm-gold-member-add-to-cart btn btn-warning"
                            style="padding: 20px; font-weight: 700;">
                        <?php
                        echo $this->wbgm_get_setting('btn_add_bonus_item_text', 'ALS BONUS-ARTIKEL FESTLEGEN');
                        ?>
                    </button>
                </div>
            <?php endif; ?>

            <?php
        }
    }

    /**
     * Check if there is already gift item in the cart
     *
     * @since  0.0.0
     * @access private
     *
     * @return boolean
     */
    private function __gift_item_in_cart()
    {
        $cart = WC()->cart->get_cart();
        if ( count( $cart ) < 0 ) {
            return false;
        }

        foreach ( $cart as $cart_item_key => $values ) {
            $product = $values['data'];
            if($values['plugin'] == 'wbgm') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if there is already gift item in the cart
     *
     * @since  0.0.0
     * @access public
     *
     * @return int
     */
    public function wbgm_bonus_qty($product_id, $is_remove = false)	{
         /*
        printf('Session:' . $_SESSION);
        global $wbgm_qty_update;*/
        if($is_remove) {
            return $_SESSION[$product_id];
        } else {
            foreach( WC()->cart->get_cart() as $key => $content ) {
                if ( $content['product_id'] == $product_id ){
                    $is_gift_product = ($content['plugin'] == 'wbgm');
                    if( $is_gift_product ) {
                        $_SESSION[$product_id] = $content['quantity'];
                        return  $content['quantity'];
                    }
                }
            }
            return 0;
        }
    }

    /**
     * Check if there is already gift item in the cart
     *
     * @since  0.0.0
     * @access public
     *
     * @return int
     */
    public function wbgm_get_shop_qty_lists()	{

        $wbgm_script  = '<div id="wbgm-script-notice-shop"><script>';

       /* foreach( WC()->cart->get_cart() as $key => $content ) {
            if ($content['plugin'] == 'wbgm') {
                $wbgm_script .= 'jQuery(".wbgm-custom-notices-add.wbgm-shop span.' . $content['product_id'] . '").html( "' . $content['quantity'] . '");';
            }
        }*/

        $wbgm_script .= ' ;var listIdQty = new Object(); var thisClassName = "";';
        foreach( WC()->cart->get_cart() as $key => $content ) {
            if ($content['plugin'] == 'wbgm') {
                $wbgm_script .= ' ;listIdQty[' . $content['product_id'] . '] = ' . $content['quantity'] . ';';
            }
        }

        $wbgm_script .= 'jQuery(".wbgm-custom-notices-add span").each(function() { var thisClassName = jQuery(this).context.className; if(thisClassName in listIdQty) {jQuery(".wbgm-custom-notices-add span." + thisClassName).html( listIdQty[thisClassName]); } else {} })';/*
        $wbgm_script .= 'jQuery(".wbgm-custom-notices-remove.wbgm-shop span").each(function() { var thisClassName = jQuery(this).context.className; if(!(thisClassName in listIdQty)) {jQuery(".wbgm-custom-notices-remove.wbgm-shop span." + thisClassName).html( listIdQty[thisClassName]); } else { jQuery(".wbgm-custom-notices-remove.wbgm-shop span." + thisClassName).parent().remove() } })';*/
        $wbgm_script  .= '</script></div>';

        return $wbgm_script;
    }
    public function wbgm_gold_status_activate(){
        if($_REQUEST['bacchus-gold'] == 'yes' ){
            $gold_member = get_post(53556);
            $member_content = $gold_member->post_content;
            setcookie('wbgm_ac_info', $member_content, 0, '/');
        } elseif($_REQUEST['bacchus-gold'] == 'no') {
            if(isset($_COOKIE['wbgm_ac_info'])){
                unset($_COOKIE['wbgm_ac_info']);
                setcookie('wbgm_ac_info', null, -1, '/');
            }
        }
    }

    /**
     * Update frontend elements.
     *
     * @since  0.0.0
     * @access public
     *
     * @param array $fragments input fragments
     * @return array
     */
    function wbgm_ajax_update_total($fragments){
        global $post;
        if( $this->wbgm_valid_permission(1)) {
            if( $this->wbgm_valid_permission()) {

                $this->validate_gifts();
                $fragments['.wbgm-info-top'] = $this->wbgm_add_info();
                $fragments['.wbgm-bonus-qty-add'] = '<span class="wbgm-bonus-qty-add">' . $this->wbgm_bonus_qty($post->ID) . '</span>';
                if($this->wbgm_bonus_qty($post->ID, true) != null) {
                    $fragments['.wbgm-bonus-qty-remove'] = '<span class="wbgm-bonus-qty-remove">' . $this->wbgm_bonus_qty($post->ID, true) . '</span>';
                }
                $logo_img = plugins_url('/templates/images/Bacchus_Gold_Logo.png', dirname(__FILE__));
                $fragments['.tb-left>.tb-text'] = '<div class="tb-text"><div class="wbgm-info-top">Ihr Bacchus Gold Guthaben für diese Bestellung beträgt <a href="/shop/?min_price=0&max_price=' . $this->_wbgm_balance . '"><span class="wbgm-top-balance btn"> ' . apply_filters('wbgm_balance', '') . ' </span></a><a href="/gold"><span class="glyphicon glyphicon-info-sign wbgm-info-sign" style="color: rgba(235, 181, 102, 1);"></span></a></div></div>';
                $fragments['.wbgm-info-top'] = $this->wbgm_add_info();

                if ($this->_wbgm_is_add_to_cart) {
                    $fragments['#wbgm-script-notice-add'] = '<div id="wbgm-script-notice-add" style="display: none;"><script>jQuery( ".wbgm-custom-notices-add" ).show(600);</script></div>';
                }
                if ($this->_wbgm_is_removed) {
                    $fragments['#wbgm-script-notice-remove'] = '<div id="wbgm-script-notice-remove" style="display: none;"><script>jQuery( ".wbgm-custom-notices-remove" ).show(600);</script></div>';
                }
                $fragments['#wbgm-debug-field'] = '<div id="wbgm-debug-field" style="display: none;"><script>jQuery(\'#top-bar\').addClass(\'wbgm-gold\');</script></div>';


                if( $this->wbgm_valid_permission(777) ) {
                    $fragments['#wbgm-debug-field'] = '<div id="wbgm-debug-field">' . json_encode($this->_wbgm_is_add_to_cart) . ' : ' . json_encode($this->_wbgm_is_add_to_cart) . '</div>';
                }
                $fragments['#wbgm-scripts'] = '<div id="wbgm-scripts"><script>jQuery( ".wbgm-add-to-cart-bonus-form" ).each( function() { if(jQuery( "input[name=\'cur_price\']" , this ).val() <= ' . $this->_wbgm_balance . ') { jQuery( this ).show(1400);} else {jQuery( this ).hide(1400);}});SWIFT.woocommerce.fullWidthShop();</script></div>';
                $fragments['#wbgm-script-notice-shop'] = $this->wbgm_get_shop_qty_lists();
                if( is_shop() || is_product()) {
                }
                /*if( is_product() ) {
                    if( $this->_wbgm_balance < $this->wbgm_cur_price ) {
                        $fragments['#wbgm-scripts'] = '<div id="wbgm-scripts"><script>jQuery(".wbgm-add-to-cart-bonus-form").hide(1200);</script></div>';
                    } else {
                        $fragments['#wbgm-scripts'] = '<div id="wbgm-scripts"><script>jQuery(".wbgm-add-to-cart-bonus-form").show(1200);</script></div>';
                    }
                } else if( is_shop() ) {
                    $fragments['#wbgm-scripts'] = '<div id="wbgm-scripts"><script>jQuery( ".wbgm-add-to-cart-bonus-form" ).each( function() { if(jQuery( "input[name=\'cur_price\']" , this ).val() <= ' . $this->_wbgm_balance . ') { jQuery( this ).show(1400);} else {jQuery( this ).hide(1400);}});SWIFT.woocommerce.fullWidthShop();</script></div>';
                    $fragments['#wbgm-script-notice-shop'] = $this->wbgm_get_shop_qty_lists();
                }*/

                $fragments['#mobile-logo'] = '<div id="mobile-logo" class="logo-center has-img clearfix" data-anim=""><a href="'. site_url() .'">' .
                    '<img class="standard" src="' . $logo_img . '" alt="Bacchus - Internationale Weine" height="68" width="210">' .
                    '<img class="retina" src="' . $logo_img . '" alt="Bacchus - Internationale Weine" height="68" width="210">' .
                    '<div class="text-logo"></div></a></div>';
                $fragments['#logo'] = '<div id="logo" class="col-sm-4 logo-center has-img clearfix" data-anim=""><a href="'. site_url() .'">' .
                    '<img class="standard" src="' . $logo_img . '" alt="Bacchus - Internationale Weine" height="68" width="200">' .
                    '<img class="retina" src="' . $logo_img . '" alt="Bacchus - Internationale Weine" height="68" width="200">' .
                    '<div class="text-logo"></div></a></div>';
            } else {

                global $logo;
                global $sf_options;
                // Standard Logo
                if ( isset( $sf_options['logo_upload'] ) ) {
                    $logo = $sf_options['logo_upload'];
                }

                $fragments['.wbgm-info-top'] = $this->wbgm_add_info();
                $fragments['#wbgm-debug-field'] = '<div id="wbgm-debug-field" style="display: none;"><script>jQuery(\'#top-bar\').removeClass(\'wbgm-gold\');</script>' . json_encode('') . '</div>';
                $fragments['#wbgm-scripts'] = '<div id="wbgm-scripts"><script>jQuery(".wbgm-add-to-cart-bonus-form").hide(1200);SWIFT.woocommerce.fullWidthShop();jQuery(".wbgm-custom-notices").hide(1200); </script></div>';
                $fragments['#mobile-logo'] = '<div id="mobile-logo" class="logo-center has-img clearfix" data-anim=""><a href="'. site_url() .'">' .
                    '<img class="standard" src="' . $logo["url"] . '" alt="Bacchus - Internationale Weine" height="68" width="210">' .
                    '<img class="retina" src="' . $logo["url"] . '" alt="Bacchus - Internationale Weine" height="68" width="210">' .
                    '<div class="text-logo"></div></a></div>';
                $fragments['#logo'] = '<div id="logo" class="col-sm-4 logo-center has-img clearfix" data-anim=""><a href="'. site_url() .'">' .
                    '<img class="standard" src="' . $logo["url"] . '" alt="Bacchus - Internationale Weine" height="68" width="200">' .
                    '<img class="retina" src="' . $logo["url"] . '" alt="Bacchus - Internationale Weine" height="68" width="200">' .
                    '<div class="text-logo"></div></a></div>';
            }
        } else {
            global $logo;
            global $sf_options;
            // Standard Logo
            if ( isset( $sf_options['logo_upload'] ) ) {
                $logo = $sf_options['logo_upload'];
            }

            $fragments['.wbgm-info-top'] = $this->wbgm_add_info();
            $fragments['#wbgm-debug-field'] = '<div id="wbgm-debug-field" style="display: none;"><script>jQuery(\'#top-bar\').removeClass(\'wbgm-gold\');</script>' . json_encode() . '</div>';
            $fragments['#wbgm-scripts'] = '<div id="wbgm-scripts"><script>jQuery(".wbgm-add-to-cart-bonus-form").hide(1200);SWIFT.woocommerce.fullWidthShop();jQuery(".wbgm-custom-notices").hide(1200); </script></div>';
            $fragments['#mobile-logo'] = '<div id="mobile-logo" class="logo-center has-img clearfix" data-anim=""><a href="https://www.bacchus.de">' .
                '<img class="standard" src="' . $logo["url"] . '" alt="Bacchus - Internationale Weine" height="68" width="210">' .
                '<img class="retina" src="' . $logo["url"] . '" alt="Bacchus - Internationale Weine" height="68" width="210">' .
                '<div class="text-logo"></div></a></div>';
            $fragments['#logo'] = '<div id="logo" class="col-sm-4 logo-center has-img clearfix" data-anim=""><a href="https://www.bacchus.de">' .
                '<img class="standard" src="' . $logo["url"] . '" alt="Bacchus - Internationale Weine" height="68" width="200">' .
                '<img class="retina" src="' . $logo["url"] . '" alt="Bacchus - Internationale Weine" height="68" width="200">' .
                '<div class="text-logo"></div></a></div>';
        }
        /*$fragments['#wbgm-debug-field'] = '<div id="wbgm-debug-field" style="display: none;"><script>console.log(' . json_encode( $this->_wbgm_deleted_gifts ) . ')</script>' . json_encode() . '</div>';
        */return $fragments;
    }

}

/* initialize */
new WBGM_Frontend();
