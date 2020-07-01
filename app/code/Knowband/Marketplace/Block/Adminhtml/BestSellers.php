<?php

namespace Knowband\Marketplace\Block\Adminhtml;

class BestSellers extends \Magento\Backend\Block\Template {

    const DEFAULT_SECTION_BLOCK = 'Magento\Config\Block\System\Config\Form';
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Knowband\Marketplace\Helper\Data $mpDataHelper,
        \Knowband\Marketplace\Model\Seller $mpSellerModel,
        array $data = []
    ) {
        $this->mp_dataHelper = $mpDataHelper;
        $this->mp_sellerModel = $mpSellerModel;
        parent::__construct($context, $data);
    }

    protected function _construct() {
        $this->_controller = 'adminhtml_marketplace';
        $this->_blockGroup = 'Knowband_Marketplace';
        parent::_construct();
    }
    
    protected function _prepareLayout()
    {
        $this->_formBlockName = self::DEFAULT_SECTION_BLOCK;
        $this->getToolbar()->addChild(
            'save_button', 'Magento\Backend\Block\Widget\Button', [
                'id' => 'show-report',
                'label' => __('Show Report'),
                'class' => 'save primary',
                'onclick' => 'filterFormSubmit()',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save', 'target' => '#vss_marketplace_view']],
                ]
            ]
        );
        
        $block = $this->getLayout()->createBlock($this->_formBlockName);
        $this->setChild('form', $block);
        return parent::_prepareLayout();
    } 

    public function getCategories() {
        return $this->mp_dataHelper->getCategoriesArray();
    }

    public function getSellers() {
        $collection = $this->mp_sellerModel->getCollection()
                ->addFieldToFilter('seller_approved', ['eq' => '1'])
                ->addFieldToFilter('seller_enabled', ['eq' => '1']);
        $collection->getSelect()
                ->reset(\Zend_Db_Select::COLUMNS)
                ->columns(['seller_id', 'shop_title']);
        $result = $collection->getData();
        unset($collection);
        return $result;
    }

    public function formPersists() {
        return $this->getRequest()->getParams();
    }
    
    public function getCategoryDropDownHtml($categories){
        return $this->mp_dataHelper->getCategoryDropDownHtml($categories);
    }

}


