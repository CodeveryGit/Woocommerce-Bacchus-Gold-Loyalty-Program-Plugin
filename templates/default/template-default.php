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