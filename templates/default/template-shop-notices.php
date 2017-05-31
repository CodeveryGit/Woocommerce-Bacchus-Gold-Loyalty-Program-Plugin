<?php
if( $this->wbgm_valid_permission()) :
?>
    <div class="wbgm-custom-notices" style="<?php
    if(is_product()) {
        echo 'margin-top: 0!important; margin-bottom: 10px!important';
    }
    ?>">
        <ul class="woocommerce-message wbgm-custom-notices-add wbgm-shop" style="display: none; min-height: 50px;">
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

        <ul class="woocommerce-error wbgm-custom-notices-remove wbgm-shop" style="display: none; min-height: 50px;">
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
<?php endif; ?>