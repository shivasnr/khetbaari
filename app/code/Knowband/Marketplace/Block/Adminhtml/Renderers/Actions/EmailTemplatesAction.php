<?php

namespace Knowband\Marketplace\Block\Adminhtml\Renderers\Actions;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;

class EmailTemplatesAction extends AbstractRenderer {

    /**
     * @var UrlInterface
     */
    protected $_frameworkUrlInterface;

    public function render(DataObject $row) {
        $data = $row->getData();
        $html = '';
        $redirectUrl = $this->getUrl('mpadmin/marketplace/editEmailTemplate', ['template_id' => $data['template_id']]);
        $html .= '<a class="btn btn-primary" style="margin:5px;" href="'.$redirectUrl.'">' . __('Edit Template') . '</a>';
        return $html;
    }

}