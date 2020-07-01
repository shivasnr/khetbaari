var vssmp_validation_types = ['int', 'decimal', 'date', 'datetime', 'text', 'varchar'];
var validation_regex = {
    text: /^(\s*([a-zA-Z])*\s*)*$/,
    varchar: /^\s*([a-zA-Z0-9\-\_\s])*\s*$/,
    static: /^\s*([a-zA-Z0-9])*\s*$/,
    numeric: /^[0-9]*$/,
    decimal: /^[0-9]*(?:\.\d{1,6})?$/,
    price: /^[0-9]*(?:\.\d{1,6})?$/,
    date: /^(0[1-9]|1[012])\/(0[1-9]|[12][0-9]|3[01])\/[0-9]{4}$/, //yyyy-mm-dd
    datepicker: /^(0[1-9]|1[012])\/(0[1-9]|[12][0-9]|3[01])\/[0-9]{4}$/, //yyyy-mm-dd
    email: /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
    url: /(https?:\/\/(?:www\.|(?!www))[^\s\.]+\.[^\s]{2,}|www\.[^\s]+\.[^\s]{2,})/gi,
    url_key: /^[a-zA-Z]+(\-[a-zA-Z]+)*$/
};
var img_holder = '';
var url_key_reg = /^[a-zA-Z]+(\-[a-zA-Z]+)*$/;
var size_limit = 0;
var vssmp_profile_img_format = ['jpeg', 'jpg', 'png', 'gif'];

var vssmp_data_big_list = null;

require(['jquery'], function (jQuery) {
    jQuery(document).ready(function () {

        jQuery('.vssmp-popper').hover(
                function () {
                    // hover in
                    jQuery(this).find('.vssmp_popper_info').show();
                },
                function () {
                    //hover out
                    jQuery(this).find('.vssmp_popper_info').hide();
                }
        );

        jQuery('#vss_marketplace_page').on('focus', 'input, select, textarea', function () {
            jQuery(this).closest('.form-group').removeClass('has-error');
            jQuery(this).closest('.form-group').find('.help-block').remove();
        });

        jQuery('body').on('change', '.vss_file_upload_field', function () {
            img_holder = jQuery(this).attr('id');
            if (img_holder == 'mplogo_img') {
                size_limit = 200000;
                newLogoSelected = true;
            } else if (img_holder == 'mpbanner_img') {
                size_limit = 500000;
                newBannerSelected = true;
            }

            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = vssmpSellerProfileImageIsLoaded;
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
});


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

function checkSellerProfileMediaUpload(val)
{
    for(var i=0; i<vssmp_profile_img_format.length; i++) {
        var str = vssmp_profile_img_format[i];
        if(val.indexOf(str.toLowerCase()) > -1){
            return true;
        }
    }
    return false;
}


function vssmpSellerProfileImageIsLoaded(e) 
{
    if(checkSellerProfileMediaUpload(e.target.result) && (Number(e.total) < size_limit)){
        var container = '#'+img_holder+'_holder';
        jQuery(container+' .image_error').html("");
        jQuery(container+' .image_error').hide();
        jQuery(container+' .img_checker').val(1);
        jQuery(container+' img').attr('src', e.target.result);
    } else {
        var container = '#'+img_holder+'_holder';
        if(img_holder == 'mplogo_img' && e.total > size_limit) {
            jQuery(container+' .image_error').html(logoSizeExceedingErrorMsg);
        } else if(img_holder == 'mpbanner_img' && e.total > size_limit) {
            jQuery(container+' .image_error').html(bannerSizeExceedingErrorMsg);
        } else {
            jQuery(container+' .image_error').html(fileTypeNotSupportedErrorMsg);
        }
        
        jQuery(container+' .image_error').show();
        jQuery(container+' .img_checker').val(0);
    }
};

function removeLogo()
{
    jQuery('#vssmp_seller_logo_remove').val(1);
    if (currentLogoURL && newLogoSelected) {
        jQuery('#vss_seller_logo').attr("src", currentLogoURL);
        newLogoSelected = false;
    } else {
        jQuery('#vss_seller_logo').attr("src", defaultLogoUrl);
    }
}

function removeBanner()
{
    jQuery('#vssmp_seller_banner_remove').val(1);
    if (currentBannerURL && newBannerSelected) {
        jQuery('#vss_seller_banner').attr("src", currentBannerURL);
        newBannerSelected = false;
    } else {
        jQuery('#vss_seller_banner').attr("src", defaultBannerUrl);
    }
}

function validateVssField(element)
{
    var error = false;
    if (jQuery(element).is('select')) {
        var value = jQuery(element).find('option:selected').val();
    } else {
        var value = jQuery(element).val();
    }
    value = jQuery.trim(value);
    if (jQuery(element).hasClass('required') && value == '') {
        error = true;
    } else if (jQuery(element).hasClass('required') && value != '') {
        if (jQuery(this).attr('validate') != undefined) {
            var key = jQuery(element).attr('validate');
            if (validation_regex[key] != undefined && ((validation_regex[key]).test(value) == false)) {
                error = true;
            }
        }
    } else if (value != '') {
        if (jQuery(element).attr('validate') != undefined) {
            var key = jQuery(element).attr('validate');
            if (validation_regex[key] != undefined && ((validation_regex[key]).test(value) == false)) {
                error = true;
            }
        }
    }
    return error;
}

function submitProfileData()
{
    var error = false;
    jQuery('#sellerProfileForm .box').removeClass('box-danger');
    jQuery('#sellerProfileForm .box-header .tab-error-highlighter').hide();
    jQuery('#sellerProfileForm .has-error').removeClass('has-error');
    jQuery('.kb-error').remove();
    
    jQuery('#sellerProfileForm input[type="text"]').each(function(){
        var field_error = false;
        var value = jQuery(this).val().trim();
        if (jQuery(this).hasClass('required') && value == '') {
            error = true;
            field_error = true;
        } else if (jQuery(this).hasClass('required') && value != '') {
            if (jQuery(this).attr('validate') != undefined) {
                var key = jQuery(this).attr('validate');
                if ((validation_regex[key] != undefined) && ((validation_regex[key]).test(value) == false)) {
                    error = true;
                    field_error = true;
                }
            }
        } else if (value != '') {
            if (jQuery(this).attr('validate') != undefined) {
                var key = jQuery(this).attr('validate');
                if ((validation_regex[key] != undefined) && ((validation_regex[key]).test(value) == false)) {
                    error = true;
                    field_error = true;
                }
            }
        }
        if (field_error) {
            jQuery(this).closest('.form-group').addClass('has-error');
            jQuery(this).closest('.box').addClass('box-danger');
            jQuery(this).closest('.box').find('.tab-error-highlighter').show();
        }
    });
    
    jQuery('#sellerProfileForm textarea').each(function(){
        var field_error = false;
        var value = jQuery(this).val().trim();
        if (jQuery(this).hasClass('required') && value == '') {
            error = true;
            field_error = true;
        } else if (jQuery(this).hasClass('required') && value != '') {
            if (jQuery(this).attr('validate') != undefined) {
                var key = jQuery(this).attr('validate');
                if ((validation_regex[key] != undefined) && ((validation_regex[key]).test(value) == false)) {
                    error = true;
                    field_error = true;
                }
            }
        } else if (value != '') {
            if (jQuery(this).attr('validate') != undefined) {
                var key = jQuery(this).attr('validate');
                if ((validation_regex[key] != undefined) && ((validation_regex[key]).test(value) == false)) {
                    error = true;
                    field_error = true;
                }
            }
        }
        if (field_error) {
            jQuery(this).closest('.form-group').addClass('has-error');
            jQuery(this).closest('.box').addClass('box-danger');
            jQuery(this).closest('.box').find('.tab-error-highlighter').show();
        }
    });
    
    jQuery('#sellerProfileForm .img_checker').each(function(){
        if (jQuery(this).val() == 0) {
            error = true;
            jQuery(this).closest('.box').addClass('box-danger');
            jQuery(this).closest('.box').find('.tab-error-highlighter').show();
        }
    });
    
    jQuery("#sellerProfileForm .vss-share-link").each(function(){
      if(jQuery(this).hasClass('vss-share-link') && jQuery(this).val().trim() != '') {
            var share_url_error = velovalidation.checkUrl(jQuery(this));
            if(share_url_error != true){
            error = true;
            jQuery(this).after('<span class="kb-error" style="color:#dd4b39;">' + share_url_error + "</span>");
            jQuery(this).closest('.form-group').addClass('has-error');
            jQuery(this).closest('.box').addClass('box-danger');
            jQuery(this).closest('.box').find('.tab-error-highlighter').show();
         }
        }
    });
    
    if (!error) {
        jQuery('#sellerProfileForm').submit();
    }
}

function checkSellerPageUrl(e)
{
    var value = jQuery(e).val();
    if(value.trim() != ''){
        new Ajax.Request(check_url_key_path, {
            method:'post',
            parameters: {
                key_url: value,
                isAjax: true
            },
            requestHeaders: {Accept: 'application/json'},
            onSuccess: function(transport) {
                retjson = transport.responseText.evalJSON();
                if(retjson['exist']){
                    jQuery(e).closest('.form-group').addClass('has-error');
                    jQuery(e).closest('.panel').addClass('box-danger');
                    jQuery(e).closest('.panel').find('.box-tools').show();
//                    alert(url_key_exist_msg);  
                }else{
                    jQuery('#sellerProfileForm').submit();
                }
            }
        });
    }else{
        jQuery('#sellerProfileForm').submit();
    }
}

function openVssModal(modal){
    jQuery('#'+modal).show();
}

function closeVssModal(modal){
    jQuery('#'+modal).hide();
    jQuery('#'+modal+' .overlay-wrapper').show();
}

function closeModalAfterReset(modal){
    jQuery('#'+modal+' input[type="text"]').attr('value', '');
    jQuery('#'+modal+' select option').removeAttr('selected');
    jQuery('#'+modal+' textarea').val('');
    jQuery('#'+modal).hide();
}


function validateNupdateMemoQtys(can_submit)
{
    jQuery('#vssmp-new-creditmemo-updatng').show();
    new Ajax.Request(validate_qty_to_creditmemo_url, {
            method:'post',
            parameters: jQuery('form#vss_creditmemo_form').serialize(),
            requestHeaders: {Accept: 'application/json'},
            onSuccess: function(transport) {
                try {
                    if (transport.responseText.isJSON()) {
                        var response = transport.responseText.evalJSON();
//                        alert(response);
                    } else {
                        if (can_submit == true) {
                            jQuery('form#vss_creditmemo_form').submit();
                        } else{
                            jQuery('#creditmemo_summary_footer').html(transport.responseText);    
                        }
                    }
                    jQuery('#vssmp-new-creditmemo-updatng').hide();
                }
                catch (e) {
                    jQuery('#vssmp-new-creditmemo-updatng').hide();
//                    alert(transport.responseText);
                }
            }
    });
}

function submitCreditMemo()
{
    jQuery('#creditmemo_do_offline').val(0);
    validateNupdateMemoQtys(true);
}

function submitCreditMemoOffline()
{
    jQuery('#creditmemo_do_offline').val(1);
    validateNupdateMemoQtys(true);
}

function vssmpShowLengthStatus(element, type)
{
    var limit = 0;
    if(type == 'short'){
        limit = vssmp_short_description_length;
    }else if(type == 'long'){
        limit = vssmp_long_description_length;
    }
    var txt = jQuery(element).val();
    jQuery(element).parent().find('.vss_hint').html(vssmp_word_count_status.replace('%d', parseInt(limit - txt.length)));
};
