var vssmp_data_big_list = null;
var vssmp_data_big_list1 = null;
var vssmp_data_big_list2 = null;
var vssmp_data_big_list3 = null;

require(['jquery', "Knowband_Marketplace/theme/plugins/DataTables/DataTables-1.10.18/js/jquery.dataTables.min", "Knowband_Marketplace/theme/plugins/iCheck/icheck.min"], function(jQuery){
    jQuery(document).ready(function(){
        if(jQuery('#vssmp_big_table').length){
            var orderStart = [[ 0, "desc" ]];
            if(jQuery('#'+vssmp_field_id+'_list_body').length){
                orderStart = [[ 1, "desc" ]]
            }

            vssmp_data_big_list = jQuery('#vssmp_big_table').dataTable( {
                "pageLength": vssmp_dt_page_length,
                "lengthChange": false,
                "searching": false,
                "processing": true,
                "language": {processing: 'Please wait... Loading Data is in Progress'},
                "serverSide": true,            
                "ajax": {
                        "url": vssmp_list_ajax_url+'?ajax=true&isAjax=true',
                        "type": "POST",
                        "data": function ( param ) {
                            if(filter_params != undefined && filter_params.length){
                                var key = '';
                                for(var i=0; i < filter_params.length; i++){
                                    key = filter_params[i]['name'];
                                    var value = '';
                                    if(jQuery('.vssmp-list-filter #'+key).length){
                                        value = jQuery('.vssmp-list-filter #'+key).val();
                                    }
                                    param[key] = ((value != '') ? value : '');
                                }
                            }
                        },
                        error:       function(xhr,status,error) {
                            console.log(error);
                        }
                    },
                "fnDrawCallback": function( settings ) {
                    if(jQuery('#'+vssmp_field_id+'_list_body input[type="checkbox"].flat-green').length) {
                        jQuery('#'+vssmp_field_id+'_list_body input[type="checkbox"].flat-green').iCheck({
                            checkboxClass: 'icheckbox_flat-green'
                        });    
                    }
                },
                "columnDefs": dt_columns,
                "order": orderStart
            });
        }

        if(jQuery('#'+vssmp_field_id+'_parent_check').length)
        {
            jQuery('#'+vssmp_field_id+'_parent_check').on('change', function(){
                var checkall = true;
                if(!jQuery(this).is(':checked')){
                        checkall = false;    
                }
                if(jQuery('#'+vssmp_field_id+'_list_body tr input[type="checkbox"]').length){
                    jQuery('#'+vssmp_field_id+'_list_body tr input[type="checkbox"]').each(function(){
                        if(checkall){
                            jQuery(this).attr('checked', 'checked');
                        }else{
                            jQuery(this).removeAttr('checked');
                        }
                    });
                }
            });
        }

        if(jQuery('#vssmp_big_table1').length){
            vssmp_data_big_list1 = jQuery('#vssmp_big_table1').dataTable( {
                "pageLength": vssmp_dt_page_length,
                "lengthChange": false,
                "searching": false,
                "processing": true,
                "language": {processing: 'Please wait... Loading Data is in Progress'},
                "serverSide": true,            
                "ajax": {
                        "url": vssmp_list_ajax_url1+'?ajax=true&isAjax=true',
                        "type": "POST",
                        "data": function ( param ) {
                            if(filter_params != undefined && filter_params1.length){
                                var key = '';
                                for(var i=0; i < filter_params1.length; i++){
                                    key = filter_params1[i]['name'];
                                    var value = '';
                                    if(jQuery('.vssmp-list-filter1 #'+key).length){
                                        value = jQuery('.vssmp-list-filter1 #'+key).val();
                                    }
                                    param[key] = ((value != '') ? value : '');
                                }
                            }
                        }
                    },
                "columnDefs": dt_columns1
            });
        }

        //For vacation filters
        if(jQuery('#vssmp_big_table2').length){
            vssmp_data_big_list2 = jQuery('#vssmp_big_table2').dataTable( {
                "pageLength": vssmp_dt_page_length,
                "lengthChange": false,
                "searching": false,
                "processing": true,
                "language": {processing: 'Please wait... Loading Data is in Progress'},
                "serverSide": true,            
                "ajax": {
                        "url": vssmp_list_ajax_url2+'?ajax=true&isAjax=true',
                        "type": "POST",
                        "data": function ( param ) {
                            if(filter_params2 != undefined && filter_params2.length){
                                var key = '';
                                for(var i=0; i < filter_params2.length; i++){
                                    key = filter_params2[i]['name'];
                                    var value = '';
                                    if(jQuery('.vssmp-list-filter2 #'+key).length){
                                        value = jQuery('.vssmp-list-filter2 #'+key).val();
                                    }
                                    param[key] = ((value != '') ? value : '');
                                }
                            }
                        }
                    },
                "columnDefs": dt_columns2
            });
        }

        //for low stock filter
        if(jQuery('#vssmp_big_table3').length){
            vssmp_data_big_list3 = jQuery('#vssmp_big_table3').dataTable( {
                "pageLength": vssmp_dt_page_length,
                "lengthChange": false,
                "searching": false,
                "processing": true,
                "language": {processing: 'Please wait... Loading Data is in Progress'},
                "serverSide": true,            
                "ajax": {
                        "url": vssmp_list_ajax_url3+'?ajax=true&isAjax=true',
                        "type": "POST",
                        "data": function ( param ) {
                            if(filter_params3 != undefined && filter_params3.length){
                                var key = '';
                                for(var i=0; i < filter_params3.length; i++){
                                    key = filter_params3[i]['name'];
                                    var value = '';
                                    if(jQuery('.vssmp-list-filter3 #'+key).length){
                                        value = jQuery('.vssmp-list-filter3 #'+key).val();
                                    }
                                    param[key] = ((value != '') ? value : '');
                                }
                            }
                        }
                    },
                "columnDefs": dt_columns3
            });
        }

        //for payoutrequest filter
        if(jQuery('#vssmp_big_table4').length){
            vssmp_data_big_list4 = jQuery('#vssmp_big_table4').dataTable( {
                "pageLength": vssmp_dt_page_length,
                "lengthChange": false,
                "searching": false,
                "processing": true,
                "language": {processing: 'Please wait... Loading Data is in Progress'},
                "serverSide": true,            
                "ajax": {
                        "url": vssmp_list_ajax_url4+'?ajax=true&isAjax=true',
                        "type": "POST",
                        "data": function ( param ) {
                            if(filter_params4 != undefined && filter_params4.length){
                                var key = '';
                                for(var i=0; i < filter_params4.length; i++){
                                    key = filter_params4[i]['name'];
                                    var value = '';
                                    if(jQuery('.vssmp-list-filter4 #'+key).length){
                                        value = jQuery('.vssmp-list-filter4 #'+key).val();
                                    }
                                    param[key] = ((value != '') ? value : '');
                                }
                            }
                        }
                    },
                "columnDefs": dt_columns4
            });
        }

    });
});

function createParentCheckbox()
{
    return '<input id="'+vssmp_field_id+'_parent_check" type="checkbox" name="'+vssmp_field_id+'_parent_check" value="0" />'
}

function vssmpFilterBigList()
{
    vssmp_data_big_list.fnDraw();
}

function vssmpResetBigList()
{
    jQuery('#'+vssmp_field_id+'_list input[type="text"]').val('');
    jQuery('#'+vssmp_field_id+'_list select option').removeAttr('selected');
    vssmp_data_big_list.fnDraw();
}

function vssmpFilterBigList1()
{
    vssmp_data_big_list1.fnDraw();
}

function vssmpResetBigList1()
{
    jQuery('#'+vssmp_field_id1+'_list input[type="text"]').val('');
    jQuery('#'+vssmp_field_id1+'_list select option').removeAttr('selected');
    vssmp_data_big_list1.fnDraw();
}

function vssmpFilterBigList2()
{
    vssmp_data_big_list2.fnDraw();
}

function vssmpResetBigList2()
{
    jQuery('div#'+vssmp_field_id+'_list input[type="text"]').val('');
    jQuery('div#'+vssmp_field_id+'_list select option').removeAttr('selected');
    vssmp_data_big_list2.fnDraw();
}

function vssmpFilterBigList3()
{
    vssmp_data_big_list3.fnDraw();
}

function vssmpResetBigList3()
{
    jQuery('div#'+vssmp_field_id+'_list input[type="text"]').val('');
    jQuery('div#'+vssmp_field_id+'_list select option').removeAttr('selected');
    vssmp_data_big_list3.fnDraw();
}

function vssmpFilterBigList4()
{
    vssmp_data_big_list4.fnDraw();
}

function vssmpResetBigList4()
{
    jQuery('div#'+vssmp_field_id+'_list input[type="text"]').val('');
    jQuery('div#'+vssmp_field_id+'_list select option').removeAttr('selected');
    vssmp_data_big_list4.fnDraw();
}

function vssmpBulkChangeAction(e)
{
    if(jQuery(e).find('option:selected').attr('id') == 'status'){
        jQuery('#'+vssmp_field_id+'_status_container').css('display', 'inline-block');
    }else{
        jQuery('#'+vssmp_field_id+'_status_container').hide();
    }
}

function vssmpListMassAction(list_id){
    list_id = list_id+'_bulk_action';
    if(jQuery('#'+list_id).val() != ''){
        if(jQuery('#'+vssmp_field_id+'_list_body tr input[type="checkbox"]:checked').length){
            var actionToPerform = confirm("Are you Sure?");
            if (actionToPerform == true) {
                var data = [];
                jQuery('#'+vssmp_field_id+'_list_body tr input[type="checkbox"]:checked').each(function(){
                    data.push(jQuery(this).val());
                });

                jQuery('#'+vssmp_field_id+'_bulk_form').attr('action', jQuery('#'+list_id).val());
                jQuery('#'+vssmp_field_id+'_bulk_form input[name="vssmp_list_item_checked"]').attr('value', JSON.stringify(data));
                
                if(jQuery('#'+vssmp_field_id+'_bulk_form input[name="vssmp_list_action_status"]').length){
                    jQuery('#'+vssmp_field_id+'_bulk_form input[name="vssmp_list_action_status"]').attr('value', jQuery('#'+vssmp_field_id+'_bulk_action_status').val());
                }
                if(jQuery('#'+list_id).find('option:selected').attr('id') == 'status'){
                    jQuery('#'+vssmp_field_id+'_bulk_form').submit();
                }else{
                    openVssModal('vssmp-reason-popup');
                }
                
            } else {
                return false;
            }
        }else{
            alert(vssmp_no_item_checked_msg);
        }    
    }
}

function vssmpGetReasonAndSubmit(rsn_container){
    jQuery('#'+rsn_container).closest('.form-group').find('.help-block').remove();
    jQuery('#'+rsn_container).closest('.form-group').removeClass('has-error');
    var txt = jQuery('#'+rsn_container).val();
    txt = txt.replace(/^\s+|\s+$/g,'');
    if(txt == ''){
        jQuery('#'+rsn_container).parent().append('<span class="help-block">'+empty_field_error+'</span>');
        jQuery('#'+rsn_container).closest('.form-group').addClass('has-error');
    }else if(txt.length < vssmp_rsn_min_chars){
        jQuery('#'+rsn_container).parent().append('<span class="help-block">'+minimum_reason_length_error+'</span>');
        jQuery('#'+rsn_container).closest('.form-group').addClass('has-error');
    }else{
        jQuery('#'+vssmp_field_id+'_bulk_form input[name="vssmp_list_action_reason"]').attr('value', txt);
        jQuery('#'+vssmp_field_id+'_bulk_form').submit();
    }
}



/* for vacation */
var min_char_length = 30;
require(['jquery'], function(jQuery){
    jQuery(document).ready(function(){
        if(jQuery('input[type="text"].vssmp_validate_datepicker').length)
        {        
            Protoplasm.use('datepicker').transform('input[type="text"].vssmp_validate_datepicker', {dateFormat: 'MM/dd/yyyy'});
        }
    });
});
    
function create_new_vacation_event()
{
    jQuery('#vacation_new_form .validation_error_msg').remove();
    jQuery('#vssvm-date-range-error #vacationmode-glob-error').hide();
    var date_reg = /^(0[1-9]|1[012])\/(0[1-9]|[12][0-9]|3[01])\/[0-9]{4}$/;
    var start_date = jQuery('#vacation_start_date').val();
    var end_date = jQuery('#vacation_end_date').val();
    var current_date = new Date();
    var today_month = current_date.getMonth()+1;
    var today_date = current_date.getDay();
    var today_year = current_date.getYear();
    var dt = new Date(today_year, today_month, today_date);

    var error = false;
    if(start_date == ''){
        error = true;
        jQuery('#vacation_start_date').parent().append('<span class="validation_error_msg">'+required_field+'</span>');
    }
    if(end_date == ''){
        error = true;
        jQuery('#vacation_end_date').parent().append('<span class="validation_error_msg">'+required_field+'</span>');
    }

    if(start_date != '' && !date_reg.test(start_date)){
        error = true;
        jQuery('#vacation_start_date').parent().append('<span class="validation_error_msg">'+date_format_invalid+'</span>');
    }

    if(end_date != '' && !date_reg.test(end_date)){
        error = true;
        jQuery('#vacation_end_date').parent().append('<span class="validation_error_msg">'+date_format_invalid+'</span>');
    }

    if(start_date != '' && end_date != ''){
        if(dt > (new Date(end_date).getTime())){
            //error = true;
            //jQuery('#vacation_end_date').parent().append('<span class="validation_error_msg">'+end_future_date_invalid+'</span>');
        }else if((new Date(start_date).getTime()) > (new Date(end_date).getTime())){
            error = true;
            jQuery('#vacation_end_date').parent().append('<span class="validation_error_msg">'+end_date_invalid+'</span>');
        }
    }

    var comment = jQuery('#vacationmode_new_comment').val();
    comment = comment.replace(/^\s+|\s+$/g,'');
    if(comment == ''){
        error = true;
        jQuery('#vacationmode_new_comment').parent().append('<span class="validation_error_msg">'+required_field+'</span>');
    }else if(comment.length < min_char_length){
        error = true;
        jQuery('#vacationmode_new_comment').parent().append('<span class="validation_error_msg">Minimum '+min_char_length+' characters required</span>');
    }

    if(!error){
        jQuery.ajax({
            url: validate_new_event_url + (validate_new_event_url.match(new RegExp('\\?')) ? '&isAjax=true' : '?isAjax=true'),
            type: 'POST',
            data: jQuery('#vacation_new_form').serialize(),
            dataType: 'json',
            beforeSend: function() {
                jQuery('#vacationmode-new-save-progress').css('display','inline-block');
            },
            success: function(json) {
                jQuery('#vacationmode-new-save-progress').hide();
                if(json['redirect']){
                    window.reload();
                }else if(json['error'] != undefined){
                    jQuery('#vssvm-date-range-error #vacationmode-glob-error').html(json['error']);
                    jQuery('#vssvm-date-range-error #vacationmode-glob-error').show();
                }else if(json['success'] != undefined){
                    jQuery('#vacation_new_form').submit();
                }
            }
        });
    }

}

function opencancelVacationForm(key)
{
    jQuery('#vacation_key').val(key);
    jQuery('#vacationmode_cancel_event_popup').show();
}

function cancel_vacation_event()
{
    jQuery('#vacation_cancel_form .validation_error_msg').remove();
    var error = false;
    var comment = jQuery('#vacationmode_cancel_comment').val();
    comment = comment.replace(/^\s+|\s+$/g,'');
    if(comment == ''){
        error = true;
        jQuery('#vacationmode_cancel_comment').parent().append('<span class="validation_error_msg">'+required_field+'</span>');
    }else if(comment.length < min_char_length){
        error = true;
        jQuery('#vacationmode_cancel_comment').parent().append('<span class="validation_error_msg">Minimum '+min_char_length+' characters required</span>');
    }
    
    if(!error){
        var cfrm = confirm('System will cancel this vacation and you cannot revert this back.\nAre you sure?');
        if(cfrm){
            jQuery('#vacationmode-cancel-save-progress').css('display','inline-block');
            jQuery('#vacation_cancel_form').submit();
        }
    }
};
