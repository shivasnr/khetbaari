<?php

namespace Knowband\Marketplace\Block\Adminhtml\Renderers;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;

class Link extends AbstractRenderer {

    public function render(DataObject $row) {
        $data = $row->getData();
        $value =  $row->getData($this->getColumn()->getIndex());
        $customer_url = $this->getUrl('customer/index/edit', ['id' => $data['seller_id']]);
        $html = '<div class="vssmp-col_blk">';
        $html .= '<a href="'.$customer_url.'" target="_blank"><span class="title">'.$data['full_name'].'</span></a>';
        $html .= '<div>';
        
        return $html;
    }

}