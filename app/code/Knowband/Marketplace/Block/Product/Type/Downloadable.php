<?php

namespace Knowband\Marketplace\Block\Product\Type;

class Downloadable extends \Knowband\Marketplace\Block\Product\Base 
{
    private $_sample_index = 0;
    private $_link_index = 0;
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDatabase,
            \Magento\Downloadable\Helper\File $downloadableFileHelper
    ) {
        $this->_coreRegistry = $registry;
        $this->_setsFactory = $setsFactory;
        $this->_objectManager = $objectManager;
        $this->_scoperConfig = $context->getScopeConfig();
        $this->_coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->_downloadableFileHelper = $downloadableFileHelper;
        
        parent::__construct($context, $setsFactory, $objectManager, $registry);
        $this->setTemplate('product/section/downloadable.phtml');
    }
    
    protected function _prepareLayout() {
        return parent::_prepareLayout();
    }

    public function getSampleIndex() {
        return $this->_sample_index;
    }

    public function getLinkIndex() {
        return $this->_link_index;
    }

    public function getSamplesTitle() {
        return $this->_scopeConfig->getValue(\Magento\Downloadable\Model\Sample::XML_PATH_SAMPLES_TITLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getSampleUsedDefault() {
        return $this->getProduct()->getAttributeDefaultValue('samples_title') === false;
    }

    public function getLinksTitle() {
        return $this->_scoperConfig->getValue(\Magento\Downloadable\Model\Link::XML_PATH_LINKS_TITLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getLinksUsedDefault() {
        return $this->getProduct()->getAttributeDefaultValue('links_title') === false;
    }

    /**
     * Retrieve samples array
     *
     * @return array
     */
    public function getSampleData() {
        $samplesArr = [];
        if ($this->getProduct()->getTypeId() !== \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE) {
            return $samplesArr;
        }
        $samples = $this->getProduct()->getTypeInstance()->getSamples($this->getProduct());
        $fileHelper = $this->_downloadableFileHelper;
        foreach ($samples as $item) {
            $tmpSampleItem = [
                'sample_id' => $item->getId(),
                'title' => $this->escapeHtml($item->getTitle()),
                'sample_url' => $item->getSampleUrl(),
                'sample_type' => $item->getSampleType(),
                'sort_order' => $item->getSortOrder(),
            ];

            $sampleFile = $item->getSampleFile();
            if ($sampleFile) {
                $file = $fileHelper->getFilePath($this->_objectManager->get('\Magento\Downloadable\Model\Sample')->getBasePath(), $sampleFile);

                $fileExist = $fileHelper->ensureFileInFilesystem($file);

                if ($fileExist) {
                    $name = '<a href="' . $this->getUrl(
                        'adminhtml/downloadable_product_edit/sample',
                        ['id' => $item->getId(), '_secure' => true]
                    ) . '">' . $fileHelper->getFileFromPathFile(
                        $sampleFile
                    ) . '</a>';
                    $tmpSampleItem['file_save'] = [
                        [
                            'file' => $sampleFile,
                            'name' => $this->_downloadableFileHelper->getFileFromPathFile($item->getSampleFile()),
                            'size' => $fileHelper->getFileSize($file),
                            'status' => 'old',
                        ],
                    ];
                }
            }

            if ($this->getProduct() && $item->getStoreTitle()) {
                $tmpSampleItem['store_title'] = $item->getStoreTitle();
            }
            $samplesArr[] = new \Magento\Framework\DataObject($tmpSampleItem);
        }
        return $samplesArr;
    }

    /**
     * Return array of links
     *
     * @return array
     */
    public function getLinkData() {
        $linkArr = [];
        if ($this->getProduct()->getTypeId() !== \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE) {
            return $linkArr;
        }
        $links = $this->getProduct()->getTypeInstance()->getLinks($this->getProduct());
        $priceWebsiteScope = $this->getIsPriceWebsiteScope();
        $fileHelper = $this->_downloadableFileHelper;
        foreach ($links as $item) {
            $tmpLinkItem = [
                'link_id' => $item->getId(),
                'title' => $this->escapeHtml($item->getTitle()),
                'price' => $this->getPriceValue($item->getPrice()),
                'number_of_downloads' => $item->getNumberOfDownloads(),
                'is_shareable' => $item->getIsShareable(),
                'link_url' => $item->getLinkUrl(),
                'link_type' => $item->getLinkType(),
                'sample_file' => $item->getSampleFile(),
                'sample_url' => $item->getSampleUrl(),
                'sample_type' => $item->getSampleType(),
                'sort_order' => $item->getSortOrder(),
            ];

            $linkFile = $item->getLinkFile();
            if ($linkFile) {
                $file = $fileHelper->getFilePath($this->_objectManager->get('\Magento\Downloadable\Model\Link')->getBasePath(), $linkFile);

                $fileExist = $fileHelper->ensureFileInFilesystem($file);

                if ($fileExist) {
                    $name = '<a href="' . $this->getUrl(
                        'adminhtml/downloadable_product_edit/link',
                        ['id' => $item->getId(), 'type' => 'link', '_secure' => true]
                    ) . '">' . $fileHelper->getFileFromPathFile(
                        $linkFile
                    ) . '</a>';
                    $tmpLinkItem['file_save'] = [
                        [
                            'file' => $linkFile,
                            'name' => $this->_downloadableFileHelper->getFileFromPathFile($item->getLinkFile()),
                            'size' => $fileHelper->getFileSize($file),
                            'status' => 'old',
                        ],
                    ];
                }
            }

            $sampleFile = $item->getSampleFile();
            if ($sampleFile) {
                $file = $fileHelper->getFilePath($this->_objectManager->get('\Magento\Downloadable\Model\Link')->getBaseSamplePath(), $sampleFile);

                $fileExist = $fileHelper->ensureFileInFilesystem($file);

                if ($fileExist) {
                    $name = '<a href="' . $this->getUrl(
                        'adminhtml/downloadable_product_edit/link',
                        ['id' => $item->getId(), 'type' => 'sample', '_secure' => true]
                    ) . '">' . $fileHelper->getFileFromPathFile(
                        $sampleFile
                    ) . '</a>';
                    $tmpLinkItem['sample_file_save'] = [
                        [
                            'file' => $item->getSampleFile(),
                            'name' => $this->_downloadableFileHelper->getFileFromPathFile($item->getSampleFile()),
                            'size' => $fileHelper->getFileSize($file),
                            'status' => 'old',
                        ],
                    ];
                }
            }

            if ($item->getNumberOfDownloads() == '0') {
                $tmpLinkItem['is_unlimited'] = 1;
            }
            if ($this->getProduct()->getStoreId() && $item->getStoreTitle()) {
                $tmpLinkItem['store_title'] = $item->getStoreTitle();
            }
            if ($this->getProduct()->getStoreId() && $priceWebsiteScope) {
                $tmpLinkItem['website_price'] = $item->getWebsitePrice();
            }
            $linkArr[] = new \Magento\Framework\DataObject($tmpLinkItem);
        }
        return $linkArr;
    }

    public function getPriceValue($value) {
        return number_format($value, 2, null, '');
    }

}
