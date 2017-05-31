/**
 * Created by Paul on 27/01/2017.
 */

jQuery(document).ready(function() {

    /*if (jQuery(".wbgm-gold-member-add-to-cart").length) {
        var is_remove_list = false;
        var currentID = jQuery(".wbgm-gold-member-add-to-cart").parent().attr('class').split(/\s+/)[0];

        jQuery(document).on("mouseenter", "li.shopping-bag-item", function () {
            var removedID = 0;
            jQuery("a.remove.remove-product").click(function () {

                var priceItem = jQuery(this).parent();
                var priceP = jQuery("span.woocommerce-Price-amount.amount", priceItem)[0].innerText;
                var classList = jQuery(this).parent().attr('class').split(/\s+/);
                if (classList[2].substr(0, 10) === 'product-id' && !is_remove_list) {
                    removedID = classList[2].substr(11);
                    is_remove_list = true;
                } else {

                    removedID = classList[2].substr(11);
                    //jQuery(classList).each(function(){
                    //console.log(classList[2].substr(11));
                    //});
                }
                if (removedID == currentID) {
                    if ( priceP == '0.00  €' ) {
                        jQuery(".wbgm-custom-notices-remove").show(600);
                        jQuery(".wbgm-custom-notices-add").hide(600);
                    } else {
                        jQuery("a.remove.remove-product").each(function () {
                            var priceItem = jQuery(this).parent();
                            var priceP = jQuery("span.woocommerce-Price-amount.amount", priceItem)[0].innerText;
                            var classList = jQuery(this).parent().attr('class').split(/\s+/);
                            if (classList[2].substr(0, 10) === 'product-id' && !is_remove_list) {
                                removedID = classList[2].substr(11);
                                is_remove_list = true;
                            }
                            if(removedID == currentID && priceP == "0,00 €" ){
                                jQuery(".wbgm-custom-notices-remove").show(600);
                                jQuery(".wbgm-custom-notices-add").hide(600);
                            }
                        });
                    }
                }
            });
        });
    }*/
    if ( jQuery(".wbgm-gold-member-add-to-cart").length ) {
        var is_remove_list = false;
        /*var currentID = jQuery(this).parent().attr('class').split(/\s+/)[0];*/

        jQuery(document).on("mouseenter", "li.shopping-bag-item", function () {
            var removedID = 0;
            var currentID = 0;
            var isExistGift = false;
            // P = Parent; C = Child
            var priceItemP;
            var priceItemC;
            var priceP;
            var priceC;
            var classListP;
            var classListC;
            var template;
            var qty;
            /*asdasdas*/
            var label;
            jQuery("a.remove.remove-product").unbind();
            jQuery("a.remove.remove-product").click(function () {
                var thisRemoveElement = jQuery(this);
                priceItemP = jQuery(this).parent();
                priceP = jQuery("span.woocommerce-Price-amount.amount", priceItemP)[0].innerText;
                classListP = jQuery(this).parent().attr('class').split(/\s+/);
                if (classListP[2].substr(0, 10) === 'product-id' && !is_remove_list) {
                    removedID = classListP[2].substr(11);
                    is_remove_list = true;
                } else {
                    removedID = classListP[2].substr(11);
                }

                jQuery(".wbgm-custom-notices-add span." + removedID).parent().remove();
                if (priceP == '0,00 €') {
                    isExistGift = true;
                    jQuery("a.remove.remove-product").each(function () {
                        priceItemC = jQuery(this).parent();
                        priceC = jQuery("span.woocommerce-Price-amount.amount", priceItemC)[0].innerText;
                        classListC = jQuery(this).parent().attr('class').split(/\s+/);
                        if (classListC[2].substr(0, 10) === 'product-id' && !is_remove_list) {
                            currentID = classListC[2].substr(11);
                            is_remove_list = true;
                        } else {
                            currentID = classListC[2].substr(11);
                        }
                        if (currentID == removedID && priceC == "0,00 €") {
                            qty = jQuery("div.bag-product-quantity", priceItemC)[0].innerHTML;
                            qty = qty.substr(8).trim();
                            label = jQuery("div.bag-product-title", priceItemC)[0].innerHTML;
                            label = label.trim();
                        }
                    });
                } else {
                    jQuery("a.remove.remove-product").each(function () {
                        priceItemC = jQuery(this).parent();
                        priceC = jQuery("span.woocommerce-Price-amount.amount", priceItemC)[0].innerText;
                        classListC = jQuery(this).parent().attr('class').split(/\s+/);
                        if (classListC[2].substr(0, 10) === 'product-id' && !is_remove_list) {
                            currentID = classListC[2].substr(11);
                            is_remove_list = true;
                        } else {
                            currentID = classListC[2].substr(11);
                        }
                        if (currentID == removedID && priceC == "0,00 €") {
                            qty = jQuery("div.bag-product-quantity", priceItemC)[0].innerHTML;
                            qty = qty.substr(8).trim();
                            label = jQuery("div.bag-product-title", priceItemC)[0].innerHTML;
                            label = label.trim();
                            isExistGift = true;
                        }
                    });
                }
                if(qty == '') {
                    qty = '0';
                }
                template = jQuery(".wbgm-shop-notice-remove-template");
                template = template[0].innerHTML;
                template = template.replace('{Y}', '<span class="' + removedID + '">' + qty + '</span>');
                template = template.replace( '<a href="{href}">{label}</a>', label );
                if( isExistGift ) {
                    jQuery(".wbgm-custom-notices-remove span." + removedID).parent().remove();
                    jQuery(".wbgm-custom-notices-remove").append('<li>' + template + '</li>');
                    jQuery(".wbgm-custom-notices-remove").show(600);
                    jQuery(".wbgm-custom-notices-add").hide(600);
                }
            });
        });
    }

    /*jQuery(".wbgm-gold-member-add-to-cart").click(function () {
        jQuery( ".wbgm-custom-notices-add" ).show(600);
        jQuery(".wbgm-custom-notices-remove").hide(600);
    });*/
    jQuery(".wbgm-gold-member-add-to-cart").click(function () {

        var addID = jQuery(this).parent().attr('class').split(/\s+/)[0];
        var template = jQuery(".wbgm-shop-notice-add-template")[0].innerHTML.trim();

        var parentLI = jQuery(jQuery(".wbgm-gold-member-add-to-cart")).parent().parent().parent();
        var label = jQuery(".product_title.entry-title", parentLI)[0].innerHTML;
        var url = jQuery("body")['context']['URL'];
        var qty = 0;
        var isFormating = false;
        var isExist = false;
        label = '<a href="' + url + '">' + label + '</a>';

        jQuery(".bag-contents").each(function () {
            jQuery(this).children().each(function () {
                if( addID == jQuery(this).attr('class').split(/\s+/)[2].substr(11) ) {
                    if( jQuery("span.woocommerce-Price-amount.amount", this)[0].innerText == "0,00 €" ){}
                    qty = jQuery(".bag-product-quantity", this)[0].innerText.substr(8).trim();
                    if(label == undefined) {
                        label = jQuery(".bag-product-title", this)[0].innerHTML.substr(8).trim();
                    }
                    isFormating = true;
                    return false;
                }
            });

            if( isFormating ) {
                return false;
            }
        });
        template = template.replace('{Y}', '<span class="' + addID + '">' + qty + '</span>');
        template = template.replace( '<a href="{href}">{label}</a>', label );

        jQuery("li", ".wbgm-custom-notices-add").each(function () {
            var currentLiContent = jQuery(this)[0].innerHTML.trim();

            if(currentLiContent.substr(currentLiContent.indexOf('x')) == template.substr(template.indexOf('x'))) {
                isExist = true;
            }
        });

        if( !isExist ) {
            jQuery(".wbgm-custom-notices-add").append('<li>' + template + '</li>');
        }

        jQuery( ".wbgm-custom-notices-add" ).show(600);
        jQuery(".wbgm-custom-notices-remove").hide(600);
    });

});