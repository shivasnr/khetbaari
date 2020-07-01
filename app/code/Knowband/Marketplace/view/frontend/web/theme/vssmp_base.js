var vssmp_validation_types = ['int', 'decimal', 'date', 'datetime', 'text', 'varchar'];
var text_reg = /^(\s*([a-zA-Z])*\s*)*$/;
var varchar_reg = /^\s*([a-zA-Z0-9])*\s*$/;
var numeric_reg = /^[0-9]*$/;
var decimal_reg = /^[0-9]*(?:\.\d{1,6})?$/;
var date_reg = /^(0[1-9]|1[012])\/(0[1-9]|[12][0-9]|3[01])\/[0-9]{4}$/; //yyyy-mm-dd
var vssmp_form_field_is_invalid = false;
var email_reg = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
var expression = /[-a-zA-Z0-9@:%_\+.~#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&//=]*)?/gi;
var url_regex = new RegExp(expression);
var img_holder = '';
var size_limit = 0;
var rel_load_first_time = true;

require(['jquery'], function(jQuery){
    jQuery(document).ready(function(){
        if(jQuery('input[type="text"].vssmp_validate_datepicker').length)
        {
            Protoplasm.use('datepicker').transform('input[type="text"].vssmp_validate_datepicker', {dateFormat: 'MM/dd/yyyy'});    
        }

        jQuery('.vssmp-hlyt-inv-field').on('focus', function(){
                jQuery(this).removeClass('vssmp-hlyt-inv-field');
        });

        if(jQuery('ul.vssmp_tabs').length){
            jQuery(".vssmp_tab_content").hide();
            jQuery(".vssmp_tab_content:first").show(); 

            jQuery("ul.vssmp_tabs li").click(function() {
                    jQuery("ul.vssmp_tabs li").removeClass("active");
                    jQuery(this).addClass("active");
                    jQuery(".vssmp_tab_content").hide();
                    var activeTab = jQuery(this).attr("rel"); 
                    jQuery("#"+activeTab).fadeIn(); 
            });    
        }

        if(jQuery('#seller-product-box').length){
            jQuery('.jcarousel').jcarousel();

            jQuery('.jcarousel-control-prev')
                .on('jcarouselcontrol:active', function() {
                    jQuery(this).removeClass('inactive');
                })
                .on('jcarouselcontrol:inactive', function() {
                    jQuery(this).addClass('inactive');
                })
                .jcarouselControl({
                    target: '-=1'
                });

            jQuery('.jcarousel-control-next')
                .on('jcarouselcontrol:active', function() {
                    jQuery(this).removeClass('inactive');
                })
                .on('jcarouselcontrol:inactive', function() {
                    jQuery(this).addClass('inactive');
                })
                .jcarouselControl({
                    target: '+=1'
                });
        }

        if(jQuery('.vssmp-open-map-popup').length){
            jQuery('.vssmp-open-map-popup').on('click', function(){
                jQuery('#vssmp-popup').show();
            });
        }

        jQuery('.map-popup-close').on('click', function(){
            jQuery('#vssmp-popup').hide();
        });

    });
});

function openVssModal(modal){
    jQuery('#'+modal).show();
}

function closeVssModal(modal){
    jQuery('#'+modal).hide();
}

function closeModalAfterReset(modal){
    jQuery('#'+modal+' input[type="text"]').attr('value', '');
    jQuery('#'+modal+' select option').removeAttr('selected');
    jQuery('#'+modal+' textarea').val('');
    jQuery('#'+modal+' .validation_error_msg').remove();
    jQuery('#'+modal).hide();
}

function openSellerReviewModal(seller_id, modal)
{
    jQuery('#vss_seller_id_hidden').val(seller_id);
    jQuery('#'+modal).show();
}

function drawVssmpSellerReviewTable()
{
    seller_product_review_list_dt.fnDraw();
}

function resetReviewTableFilterFields()
{
    jQuery('#seller_product_review_from_date').val('');
    jQuery('#seller_product_review_to_date').val('');
    jQuery('#seller_product_review_product_name').val('');
    jQuery('#seller_product_review_customer_name').val('');
    jQuery('#seller_product_review_rating').val('');
    drawVssmpSellerReviewTable();
}

function fetchSellerProduct()
{    
    var category_id = jQuery('#vss_category_filter').val();
    var sort_as = jQuery('#vss_sorting_filter').val();
    var seller_id = vssmp_seller_id;
    jQuery('#seller-product-list-holder').hide();
    jQuery('#seller-product-list-loader').show();    
    jQuery.ajax({
        url: getSellerProductsUrl,
        type: 'POST',
        dataType: 'json',
        data: {cat_id: category_id,order: sort_as,id: seller_id},
        success: function(response) {
                jQuery('#seller-product-list-holder').html(response);
                jQuery('#seller-product-list-loader').hide();
                jQuery('#seller-product-list-holder').show();
            }
    });
}

function validateReviewForm()
{
    var check = true;
    jQuery('#vssmpSellerReviewSummary, #vssmpSellerReviewDetail, #vssmpSellerReviewerName').removeClass('vssmp-hlyt-inv-field');
    if(jQuery('#vssmpSellerReviewSummary').val() == '')
    {
        jQuery('#vssmpSellerReviewSummary').addClass('vssmp-hlyt-inv-field');
        check = false;
    }
    
    if(jQuery('#vssmpSellerReviewDetail').val() == '')
    {
        jQuery('#vssmpSellerReviewDetail').addClass('vssmp-hlyt-inv-field');
        check = false;
    }
    
    if(jQuery('#vssmpSellerReviewerName').val() == '')
    {
        jQuery('#vssmpSellerReviewerName').addClass('vssmp-hlyt-inv-field');
        check = false;
    }
    if(check){
        //Updated By Dhruw
        jQuery("#vssmp_write_review_button").attr("disabled", "disabled"); 
//        return true;
        jQuery('#sellerProfileForm').submit();
        //Ends
    }
    else
        return false;
}

function confirmSetLocation(msg, url)
{
    var cfm = confirm(msg);
    if (cfm) {
        setLocation(url);
    }
}

function setLocation(url)
{
    location.href = url;
}