<?php

namespace Knowband\Marketplace\Block\Adminhtml;

class CategoryTree extends \Magento\Framework\View\Element\Template {

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
            \Magento\Backend\Block\Template\Context $context, 
            \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder, 
            \Magento\Framework\ObjectManagerInterface $objectManager, 
            array $data = []
    ) {
        $this->_objectManager = $objectManager;
        $this->formKey = $context->getFormKey();
        parent::__construct($context, $data);
    }

    /**
     * Prepare layout
     *
     * @return this
     */
    public function _prepareLayout() {
        return parent::_prepareLayout();
    }

    public function getCategoriesTree() {
        $categories = $this->_objectManager->create(
                        'Magento\Catalog\Ui\Component\Product\Form\Categories\Options'
                )->toOptionArray();
        return json_encode($categories);
    }

}
