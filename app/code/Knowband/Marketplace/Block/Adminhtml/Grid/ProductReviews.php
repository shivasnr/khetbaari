<?php
namespace Knowband\Marketplace\Block\Adminhtml\Grid;

/**
 *
 * @author      Knowband Team
 */

use Magento\Store\Model\Store;
class ProductReviews extends \Magento\Backend\Block\Widget\Grid\Extended implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected $_coreRegistry = null;

    public $_filters = [];
    
    /**
     * @var \Magento\Authorization\Model\ResourceModel\Role\CollectionFactory
     */
    protected $_userRolesFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Authorization\Model\ResourceModel\Role\CollectionFactory $userRolesFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory,
        \Magento\Review\Helper\Data $reviewData,
        \Knowband\Marketplace\Helper\GridAction $gridActionHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_customerFactory = $customerFactory;
        $this->_resource = $resource;
        $this->mp_gridActionHelper = $gridActionHelper;
        $this->_reviewCollectionFactory = $reviewCollectionFactory;
        $this->_reviewData = $reviewData;
        $this->_backendHelper = $backendHelper;
        $this->_storeManager = $context->getStoreManager();
        
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setId('productReviewGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Seller Product Reviews');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Seller Product Reviews');
    }
    
    protected function _prepareCollection()
    {   
        $collection = $this->_reviewCollectionFactory->create();
        $collection->getSelect()->join(
                       ['seller_product' => $collection->getTable("vss_mp_product_to_seller")], 
                        "(entity_pk_value = seller_product.product_id and (seller_product.approved = 0 or seller_product.approved = 1))", 
                        []
                    );

        $collection->getSelect()->joinLeft(['prod' => $collection->getTable('catalog_product_entity')], "entity_pk_value = prod.entity_id", ['sku']);
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $attribute = $objectManager->get('Magento\Catalog\Model\Entity\Attribute');
        
        $prodNameAttrId = $attribute->loadByCode('catalog_product', 'name')->getAttributeId();
        
        $collection->getSelect()->joinLeft(
                ['cpev' => $collection->getTable('catalog_product_entity_varchar')], 
                'cpev.entity_id=prod.entity_id AND cpev.store_id = '.Store::DEFAULT_STORE_ID.' AND cpev.attribute_id='.$prodNameAttrId,
                ['value']
            );
        $scope = $this->mp_gridActionHelper->getScope();
        if ($scope['scope'] == 'websites') {
            $collection->addFieldToFilter('rt.website_id', ['eq' => $scope['scope_id']]);
        }
        $collection->addStoreData();
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns() {
        
        $this->addColumn('review_id', [
		'header'        => __('ID'),
		'align'         => 'right',
		'filter_index'  => 'main_table.review_id',
		'index'         => 'review_id',
	    ]);

	    $this->addColumn('created_at', [
		'header'        => __('Created On'),
		'align'         => 'left',
		'type'          => 'datetime',
		'filter_index'  => 'created_at',
		'index'         => 'created_at',
	    ]);

        if (!$this->_coreRegistry->registry('usePendingFilter')) {
            $this->addColumn('status', [
                'header' => __('Status'),
                'type' => 'options',
                'options' => $this->_reviewData->getReviewStatuses(),
                'filter_index' => 'status_id',
                'index' => 'status_id'
                    ]
            );
        }

        $this->addColumn('title', [
		'header'        => __('Title'),
		'align'         => 'left',
		'filter_index'  => 'rdt.title',
		'index'         => 'title',
		'type'          => 'text',
		'truncate'      => 50,
		'escape'        => true,
	    ]);

	    $this->addColumn('nickname', [
		'header'        => __('Nickname'),
		'align'         => 'left',
		'filter_index'  => 'rdt.nickname',
		'index'         => 'nickname',
		'type'          => 'text',
		'truncate'      => 50,
		'escape'        => true,
	    ]);

	    $this->addColumn('detail', [
		'header'        => __('Review'),
		'align'         => 'left',
		'index'         => 'detail',
		'filter_index'  => 'rdt.detail',
		'type'          => 'text',
		'truncate'      => 50,
		'nl2br'         => true,
		'escape'        => true,
	    ]);

	    /**
	     * Check is single store mode
	     */
            $single_store_mode = $this->_storeManager->isSingleStoreMode();
	    if (!$single_store_mode) {
		$this->addColumn('store_id', [
		    'header'    => __('Visible In'),
		    'index'     => 'stores',
		    'type'      => 'store',
		    'store_view' => true,
                    'filter'    => false,
                    'sortable'  => false
		]);
	    }
            
            $this->addColumn('type', [
                'header' => __('Type'),
                'type' => 'select',
//                'index' => 'type',
                'filter' => '\Knowband\Marketplace\Block\Adminhtml\Renderers\ReviewTypeFilter',
                'renderer' => '\Magento\Review\Block\Adminhtml\Grid\Renderer\Type',
                'sortable' => false
            ]);

	    $this->addColumn('name', [
		'header'    => __('Product Name'),
		'align'     =>'left',
		'type'      => 'text',
		'index'     => 'value',
                'filter_index' => 'cpev.value',
		'escape'    => true
	    ]);

	    $this->addColumn('sku', [
		'header'    => __('Product SKU'),
		'align'     => 'right',
		'type'      => 'text',
		'width'     => '50px',
		'index'     => 'sku',
		'escape'    => true
	    ]);
            
            $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getReviewId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'base' => 'review/product/edit',
                            'params' => [
                                'productId' => $this->getProductId(),
                                'customerId' => $this->getCustomerId(),
                                'ret' => $this->_coreRegistry->registry('usePendingFilter') ? 'pending' : null,
                            ],
                        ],
                        'field' => 'id',
                    ],
                ],
                'filter' => false,
                'sortable' => false
            ]
        );
        return parent::_prepareColumns();
    }

    public function getRowUrl($row){
        return false;
    }

    public function getGridUrl() {
        return $this->getUrl('mpadmin/marketplace/productReviewsAjax', ['_current' => true]);
    }
    
}
