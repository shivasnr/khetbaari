jQuery(document).ready(function(){

    jQuery('#vssmp-product-form-inner').on('change', '.vssmp_proccess_max_download', function(){
        if(jQuery(this).is(':checked')){
            jQuery(this).parent().parent().find('input[type="text"]').attr('disabled', true);
        }else{
            jQuery(this).parent().parent().find('input[type="text"]').removeAttr('disabled');
        }
    });
    
    jQuery('#vssmp-product-form-inner').on('change', 'input.file_type_option', function(){
        if (jQuery(this).val() == 'url') {
            jQuery(this).closest('td').find('.downloadable_file_url').addClass('required');
        } else {
            jQuery(this).closest('td').find('.downloadable_file_url').removeClass('required');
        }
    });
    
});


function processSampleIndex()
{
    if(jQuery('#vssmp_download_sample_row_body tr').length == 0){
        vssmp_download_sample_index = 0;
    }
    var tmp = vssmp_download_sample_index;
    vssmp_download_sample_index = vssmp_download_sample_index + 1;
    return tmp;
}

function processLinkIndex()
{
    if(jQuery('#vssmp_download_link_row_body tr').length == 0){
        vssmp_download_link_index = 0;
    }
    var tmp = vssmp_download_link_index;
    vssmp_download_link_index = vssmp_download_link_index + 1;
    return tmp;
}


function createNewDownloadSampleRow(json_data)
{
    var blk_str = sample_row_html;
    
    var sampleIndex = processSampleIndex();    
    blk_str = blk_str.replace(/{{sample_index}}/g, sampleIndex);
    if(json_data != '' && json_data != '{}'){
        var data = json_data;
        blk_str = blk_str.replace(/{{sample_id}}/g, data.sample_id);
        blk_str = blk_str.replace(/{{title}}/g, data.title);
        
        if(data.file_save != undefined){
            blk_str = blk_str.replace(/{{old_file}}/g, JSON.stringify(data.file_save));
            blk_str = blk_str.replace(/{{sample_file_name}}/g, data.file_save[0].name);
        }else{
            blk_str = blk_str.replace(/{{old_file}}/g, '[]');
            blk_str = blk_str.replace(/{{sample_file_name}}/g, '');
        }
        
        
        if(data.sample_type == 'file'){
            blk_str = blk_str.replace(/{{file_selected_status}}/g, 'checked="checked"');
            blk_str = blk_str.replace(/{{url_selected_status}}/g, '');
        }else{
            blk_str = blk_str.replace(/{{file_selected_status}}/g, '');
            blk_str = blk_str.replace(/{{url_selected_status}}/g, 'checked="checked"');    
        }
        blk_str = blk_str.replace(/{{sample_url}}/g, data.sample_url);
        blk_str = blk_str.replace(/{{download_sample_sort_order}}/g, parseInt(data.sort_order));    
    }else{
        blk_str = blk_str.replace(/{{sample_id}}/g, 0);
        blk_str = blk_str.replace(/{{title}}/g, '');
        blk_str = blk_str.replace(/{{old_file}}/g, '[]');
        blk_str = blk_str.replace(/{{file_selected_status}}/g, '');
        blk_str = blk_str.replace(/{{url_selected_status}}/g, 'checked="checked"');
        blk_str = blk_str.replace(/{{sample_url}}/g, '');
        blk_str = blk_str.replace(/{{download_sample_sort_order}}/g, 0);    
    }
   
    var container = '#vssmp_download_sample_row_body';
    
    jQuery(container).append(blk_str);
    
    container += ' #download_sample_row_'+sampleIndex;
    jQuery(container+' .file_type_option:checked').trigger('change');
   
}

function removeDownloadSampleRow(sample_index)
{
    jQuery('#vssmp_download_sample_row_body #download_sample_row_'+sample_index).hide();
    jQuery('#vssmp_download_sample_row_body #download_sample_row_'+sample_index+' input[name="downloadable[sample]['+sample_index+'][is_delete]"]').attr('value', 1);
}

function createNewDownloadLinkRow(json_data)
{
    var blk_str = link_row_html;
    
    var linkIndex = processLinkIndex();
    
    blk_str = blk_str.replace(/{{link_index}}/g, linkIndex);
    
    if(json_data != '' && json_data != '{}'){
        var data = json_data;
        blk_str = blk_str.replace(/{{link_id}}/g, data.link_id);
        blk_str = blk_str.replace(/{{title}}/g, data.title);
        blk_str = blk_str.replace(/{{download_link_price}}/g, data.price);
        blk_str = blk_str.replace(/{{num_of_download}}/g, parseInt(data.number_of_downloads));
        if(data.is_unlimited == 1){
            blk_str = blk_str.replace(/{{is_unlimited}}/g, 'checked="checked"');    
        }else{
            blk_str = blk_str.replace(/{{is_unlimited}}/g, '');  
        }
        
        if(data.sample_type == 'file'){
            blk_str = blk_str.replace(/{{sample_file_selected_status}}/g, 'checked="checked"');
            blk_str = blk_str.replace(/{{sample_url_selected_status}}/g, '');
        }else{
            blk_str = blk_str.replace(/{{sample_file_selected_status}}/g, '');
            blk_str = blk_str.replace(/{{sample_url_selected_status}}/g, 'checked="checked"');
        }
        blk_str = blk_str.replace(/{{sample_url}}/g, data.sample_url);
        
        if(data.sample_file_save != undefined){
            blk_str = blk_str.replace(/{{sample_old_file}}/g, JSON.stringify(data.sample_file_save));
            blk_str = blk_str.replace(/{{sample_file_name}}/g, data.sample_file_save[0].name);
            blk_str = blk_str.replace(/{{sample_file_size}}/g, '('+vssmpComputeSize(data.sample_file_save[0].size)+')');
        }else{
            blk_str = blk_str.replace(/{{sample_old_file}}/g, '[]');
            blk_str = blk_str.replace(/{{sample_file_name}}/g, '');
            blk_str = blk_str.replace(/{{sample_file_size}}/g, '');
        }
        
        if(data.link_type == 'file'){
            blk_str = blk_str.replace(/{{file_selected_status}}/g, 'checked="checked"');
            blk_str = blk_str.replace(/{{url_selected_status}}/g, '');
        }else{
            blk_str = blk_str.replace(/{{file_selected_status}}/g, '');
            blk_str = blk_str.replace(/{{url_selected_status}}/g, 'checked="checked"');
        }
        
        blk_str = blk_str.replace(/{{link_uploader}}/g, '<input name="downloadable[link]['+linkIndex+']" type="file" value="" />');
        
        blk_str = blk_str.replace(/{{link_url}}/g, data.link_url);
        
        if(data.file_save != undefined){
            blk_str = blk_str.replace(/{{old_file}}/g, JSON.stringify(data.file_save));
            blk_str = blk_str.replace(/{{link_file_name}}/g, data.file_save[0].name);
            blk_str = blk_str.replace(/{{link_file_size}}/g, '('+vssmpComputeSize(data.file_save[0].size)+')');
        }else{
            blk_str = blk_str.replace(/{{old_file}}/g, '[]');
            blk_str = blk_str.replace(/{{link_file_name}}/g, '');
            blk_str = blk_str.replace(/{{link_file_size}}/g, '');
        }
        
        blk_str = blk_str.replace(/{{download_link_sort_order}}/g, parseInt(data.sort_order));
        
    }else{
        
        blk_str = blk_str.replace(/{{link_id}}/g, 0);
        blk_str = blk_str.replace(/{{title}}/g, '');
        blk_str = blk_str.replace(/{{download_link_price}}/g, 0);
        blk_str = blk_str.replace(/{{num_of_download}}/g, 0);
        blk_str = blk_str.replace(/{{is_unlimited}}/g, 'checked="checked"');
        blk_str = blk_str.replace(/{{sample_file_name}}/g, '');
        blk_str = blk_str.replace(/{{sample_file_size}}/g, '');
        blk_str = blk_str.replace(/{{link_file_name}}/g, '');
        blk_str = blk_str.replace(/{{link_file_size}}/g, '');
        blk_str = blk_str.replace(/{{sample_old_file}}/g, '[]');
        blk_str = blk_str.replace(/{{sample_file_selected_status}}/g, '');
        blk_str = blk_str.replace(/{{sample_url_selected_status}}/g, 'checked="checked"');
        blk_str = blk_str.replace(/{{sample_url}}/g, '');
        
        blk_str = blk_str.replace(/{{old_file}}/g, '[]');
        blk_str = blk_str.replace(/{{file_selected_status}}/g, 'checked="checked"');
        blk_str = blk_str.replace(/{{url_selected_status}}/g, '');
        
        blk_str = blk_str.replace(/{{link_url}}/g, '');
        blk_str = blk_str.replace(/{{download_link_sort_order}}/g, 0);
        
    }
    
    var container = '#vssmp_download_link_row_body';
    jQuery(container).append(blk_str);
    
    container += ' #download_link_row_'+linkIndex;
    jQuery(container+' .file_type_option:checked').trigger('change');
}

function removeDownloadLinkRow(link_index)
{
    jQuery('#vssmp_download_link_row_body #download_link_row_'+link_index).hide();
    jQuery('#vssmp_download_link_row_body #download_link_row_'+link_index+' input[name="downloadable[link]['+link_index+'][is_delete]"]').attr('value', 1);
}

function vssmpComputeSize(size_in_bytes){
    return parseFloat(size_in_bytes/1000).toFixed(2)+'KB';
};


