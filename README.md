# WooСommerce Bacchus Gold Loyalty Program Plugin
Live url: https://www.bacchus.de/ 

## Idea
WooCommerce giveaway made easy. Best way to offer freebies, gifts or prizes.

My client owns an online and offline shops. He has a loyalty system used offline that works as follows: a customer can pay 19€ and then receives a coupon worth 10% of the order that can be cashed in for an additional product on the same order. For example, a customer has a cart worth 90€. If he is a member of the loyalty club, he will get a coupon for 10% of the order (9€), which can be used to buy an additional product in the shop. The customer can only select wines up 9€ in value. If he selects a wine that is 8€, then he forfeits the 1€ that remains.
So, the goal was to develop a plugin which will allow implement a loyalty system in the online shop.

## Overview
Gift giving is one of the best ways for marketing. This way creates good vibes and your customers will come back more often.

Bacchus Gold Loyalty Program Plugin is a WordPress WooCommerce plugin that makes gift management easy for your woocommerce site. The plugin helps you offer free products or gifts to your customer when they purchase products at your store. Woocommerce Bacchus Gold Member plugin - gives you an edge by allowing you to write your own gift conditions which gives you great control on how you want to provide gifts to your customer.

Woocommerce Bacchus Gold Member Plugin enables the option to provide gifts to your customer. Gift Giving is one of the best ways for marketing. This way creates good vibes and your Customers will come back more often. With Woocommerce Bacchus Gold Member Plugin you can provide gifts to your customer based on single or multiple products.

## Installation
1. Unzip and upload the `woocommerce-bacchus-gold-member` directory to the plugin directory (`/wp-content/plugins/`) or install it from `Plugins->Add New->Upload`.
2. Activate the plugin through the `Plugins` menu in WordPress.
3. That's all you need to do. You will notice Woo Free Gift settings page in the admin.

## Technologies:
* WordPress
* WooCommerce
* PHP

### Plugin information
* Requires at least: 3.8
* Tested up to: 4.5.3
* WC requires at least: 2.3
* WC tested up to: 2.4.6
* Stable tag: 1.1.4
* License: GPLv2 or later

## Code example
**Template:**
```php
<?php
if( $this->wbgm_valid_permission(1)) :
?>
    <form class="wbgm-add-to-cart-bonus-form" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="post" style="display: none;">
        <input type="hidden" name="action" value="wbgm_add_bonus_gifts" />
        <input type="hidden" name="cur_id" value="<?php echo $post->ID; ?>" />
        <input type="hidden" name="cur_price" value="<?php echo get_post_meta( $post->ID, '_price', true); ?>" />
                <div class="wbgm-gift-item" style="display: none">
                    <div class="wbgm-heading">
                        <input type="checkbox" name="wbgm_free_items" class="wbgm-item-<?php echo $post->ID ?> wbgm-checkbox" value="<?php echo $post->ID ?>" checked/>
                        <label for="wbgm-item-<?php echo $post->ID ?>" class="wbgm-title"></label>
                    </div>
                </div>
            <form class="wbgm-actions">
                <?php
                /*do_action('wbgm_show_gold_btn');*/
                $this->wbgm_show_gold_member_bnt( $post );
                ?>
            </form>
    </form>
<?php endif; ?>
```

**Plugin main Class**
```php
<?php
class Woocommerce_Bacchus_Gold_Member
{
	/**
	 * Constructor
	 *
	 * @see  add_action()
	 * @since  0.0.0
	 */
	public function __construct()
	{
		//check if woocommerce plugin is installed and activated
		add_action( 'plugins_loaded', array( $this, 'wbgm_validate_installation' ) );

		//load plugin textdomain
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		//enqueue necessary scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_global_scripts' ) );

		//add action links
        self::__init();
	}


    /**
     * Initialisation
     *
     * @see  add_action()
     * @since  0.0.0
     */

    private function __init() {
        add_action( 'wp_head', array( $this, 'wbgm_gf_activate' ) );
    }

	/**
	 * Plugin activation hook for gravity forms
	 *
	 * @access  public
	 * @since  0.0.0
	 *
	 * @return void
	 */
	public function wbgm_gf_activate()
	{
        add_action('gform_after_submission', array( $this, 'wbgm_bacchus_gold_activation'), 10, 2);
        add_action('gform_post_submission', array( $this, 'wbgm_bacchus_gold_activation'), 10, 2);
        add_action( 'gform_pre_submission',  array( $this, 'pre_submission_handler'), 10, 1 );
	}
```



