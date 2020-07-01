<?php

namespace Knowband\Marketplace\Block\Product\Section;

class Image extends \Knowband\Marketplace\Block\Product\Base {
    
    private $_img_format = ['jpeg', 'png', 'jpg', 'gif'];
	
    private $_image_values = [];
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->_coreRegistry = $registry;
        $this->_setsFactory = $setsFactory;
        $this->_objectManager = $objectManager;
        $this->_jsonHelper = $jsonHelper;
        
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/section/image.phtml');
        
        $product = $this->getProduct();
        foreach ($this->getImageTypes() as $key => $val) {
            if ($product && $product->getData($key) && $product->getData($key) != '') {
                $this->_image_values[$key] = $product->getData($key);
            } else {
                $this->_image_values[$key] = $this->_objectManager->get("Knowband\Marketplace\Helper\Uploader")->getNoImageText();
            }
        }
    }
    
    public function getImageTypes() {
        $media_attribs = [
            'image' => ['label' => __('Base Image'), 'field' => 'product[image]'],
            'small_image' => ['label' => __('Small Image'), 'field' => 'product[small_image]'],
            'thumbnail' => ['label' => __('Thumbnail'), 'field' => 'product[thumbnail]']
        ];
        return $media_attribs;
    }

    public function getHtmlId() {
        return 'vssmp_media_gallery_content';
    }

    public function getJsObjectName() {
        return $this->getHtmlId() . 'JsObject';
    }

    public function getImageTypeJson() {
        return $this->_jsonHelper->jsonEncode($this->getImageTypes());
    }

    public function getImageFormat() {
        return $this->_img_format;
    }

    public function getImages() {
        return $this->getProduct()->getMediaGalleryImages();
    }

    public function getImagesValue($image) {
        foreach ($this->getImageTypes() as $key => $val) {
            if (isset($this->_image_values[$key]) && $image['file'] == $this->_image_values[$key]) {
                $image[$key] = 1;
            } else {
                $image[$key] = 0;
            }
        }
        return $image;
    }

}
