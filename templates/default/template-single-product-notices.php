<?php
if( $this->wbgm_valid_permission()) :
?>
    <div class="wbgm-custom-notices" style="margin-top: 0!important;">
        <ul class="woocommerce-message wbgm-custom-notices-add" style="display: none; min-height: 50px;">
            <li class="wbgm-shop-notice-add-template" style="display: none;">
                <?php
                $link = '{href}';
                $title = '{label}';

                $so_congrat = $this->wbgm_get_setting('so_congrat', '{Y} x {title} wurde als Gold Artikel hinzugefügt.');
                $so_congrat = str_replace(
                    '{title}',
                    '<a href="' . $link . '">' . $title . '</a>',
                    $so_congrat);
                echo $so_congrat;
                ?>
            </li>
        </ul>

        <ul class="woocommerce-error wbgm-custom-notices-remove" style="display: none; min-height: 50px;">
            <li class="wbgm-shop-notice-remove-template" style="display: none;">
                <?php
                $link = '{href}';
                $title = '{label}';
                $so_deleted_gift = $this->wbgm_get_setting('so_deleted_gift', '{Y} x {title} wurde(n) aus dem Warenkorb entfernt.');
                $so_deleted_gift = str_replace(
                    '{title}',
                    '<a href="' . $link . '">' . $title . '</a>',
                    $so_deleted_gift);
                echo $so_deleted_gift;
                ?>
            </li>
        </ul>
    </div>
<?php endif;
/*
?>
<ul class="woocommerce-message wbgm-custom-notices-add" style="display: none;">
    <li>
        <span class="wbgm-bonus-qty-add"></span>
        <?php
        $product_id = $post->ID;
        $bonus_quantity = '';

        $_product = wc_get_product($product_id);
        $link = $_product->post->guid;
        $title = $_product->post->post_title;

        $so_congrat = $this->wbgm_get_setting('so_congrat', '{Y} x {title} wurde als Gold Artikel hinzugefügt.');
        $so_congrat = str_replace(
            '{Y}',
            $bonus_quantity,
            $so_congrat);
        $so_congrat = str_replace(
            '{title}',
            '<a href="' . $link . '">' . $title . '</a>',
            $so_congrat);
        echo $so_congrat;
        ?>
    </li>
</ul>

<ul class="woocommerce-error wbgm-custom-notices-remove" style="display: none;">
    <li>
        <span class="wbgm-bonus-qty-remove"></span>
        <?php
        $so_deleted_gift = $this->wbgm_get_setting('so_deleted_gift', '{Y} x {title} wurde(n) aus dem Warenkorb entfernt.');
        $so_deleted_gift = str_replace(
            '{Y}',
            $bonus_quantity,
            $so_deleted_gift);
        $so_deleted_gift = str_replace(
            '{title}',
            '<a href="' . $link . '">' . $title . '</a>',
            $so_deleted_gift);
        echo $so_deleted_gift;

        ?>
    </li>
</ul>*/
