<?php

namespace Knowband\Marketplace\Block\Adminhtml\Renderers\Actions;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;

class Deletebtn extends AbstractRenderer {

    /**
     * @var UrlInterface
     */
    protected $_frameworkUrlInterface;

    public function render(DataObject $row) {
        $data = $row->getData();
        $html = '';

        $edit_url = $this->getUrl('catalog/product/edit', ['id' => $data['product_id']]);
        $html .= "<button type='button' onclick='location.href=" . '"' . $edit_url . '"' . "' class='scalable back' style='margin-right:5px;'>" . __('Edit') . "</button>";

        $html .= '<button type="button" class="scalable save" onclick="openVssmpReasonForm(\'' . $this->getUrl('mpadmin/marketplace/deleteProduct/custom_id/' . $data['seller_id'] . '_' . $data['product_id'] . '/') . '\');" '.'>' . __('Delete') . '</button>';

        return $html;
    }

}