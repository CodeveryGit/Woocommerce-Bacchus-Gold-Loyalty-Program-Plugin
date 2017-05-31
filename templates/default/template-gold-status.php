<?php
plugins_url( '/templates/images/Bacchus_Gold_Logo.png', dirname( __FILE__ ) );

do_action ('wbgm_prepare_free_product');
?>
<div id="wbgm-debug-field" style="display: none;">
    <script>
        if( <?php echo $this->wbgm_valid_permission() ?> ) {
            jQuery('#top-bar').addClass('wbgm-gold');
        } else {
            jQuery('#top-bar').removeClass('wbgm-gold');
        }
    </script>
</div>

<div id="wbgm-scripts"></div>