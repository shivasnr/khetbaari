<?php

namespace Knowband\Marketplace\Block\Adminhtml\Renderers\Actions;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;

class ProductReviewAction extends AbstractRenderer {

    /**
     * @var UrlInterface
     */
    protected $_frameworkUrlInterface;

    public function render(DataObject $row) {
        $data = $row->getData();
        $html = '';
//        $editProfileUrl = $this->getUrl('*/index/addProfile', array('profile_id' => $data['id_gs_profiles']));
//        $deleteProfileUrl = $this->getUrl('*/index/deleteProfile', array('profile_id' => $data['id_gs_profiles']));
//        $html .= '<a class="btn btn-primary" style="margin:5px;" href="'.$editProfileUrl.'">' . __('Edit') . '</a>';
//        $html .= '&nbsp;&nbsp;';
//        $html .= '<a class="btn-delete btn btn-danger" style="margin:5px;" data-href = "'.$deleteProfileUrl.'">' . __('Delete') . '</a>';
        return $html;
    }

}