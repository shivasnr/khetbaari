<?php
namespace Knowband\Marketplace\Block\Adminhtml\Transaction\Grid;

/**
 *
 * @author      Knowband Team
 */

class CategoryEarning extends \Magento\Backend\Block\Widget\Grid\Extended implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected $_coreRegistry = null;

    public $_filters = [];
    
    private $scope = null;
    private $gridParameters;
    
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
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customColFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Knowband\Marketplace\Helper\GridAction $gridActionHelper,
        \Knowband\Marketplace\Helper\Reports $mpReportsHelper,
        \Knowband\Marketplace\Model\Orderitem $mpOrderItemModel,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_resource = $resource;
        $this->_storeManager = $context->getStoreManager();
        $this->mp_gridActionHelper = $gridActionHelper;
        $this->mp_reportsHelper = $mpReportsHelper;
        $this->_customerColFactory = $customColFactory;
        $this->_backendHelper = $backendHelper;
        $this->mp_orderItemModel = $mpOrderItemModel;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setId('categoryEarningListGrid');
        $this->setSaveParametersInSession(true);
        $this->setFilterVisibility(false);
        $this->setUseAjax(true);
        $this->scope = $this->mp_gridActionHelper->getScope();
        $this->gridParameters = $this->getRequest()->getParams();
        
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Category wise Commissions');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Category wise Commissions');
    }
    
    protected function _getStore($storeId = 0){
        return $this->_storeManager->getStore($storeId);
    }

    protected function _prepareCollection() 
    {
        $collection = $this->mp_orderItemModel->getCollection();
        if (isset($this->gridParameters['category_to_filter'])) {
            if ($this->gridParameters['category_to_filter'] != 'all')
                $collection->addFieldToFilter('category_id', (int) $this->gridParameters['category_to_filter']);
        }
        if ($this->scope['scope'] != 'default' && $this->scope) {
            if ($this->scope['scope'] == 'stores') {
                $collection->addFieldToFilter('main_table.store_id', ['eq' => (int) $this->scope['scope_id']]);
            } else if ($this->scope['scope'] == 'websites') {
                $collection->addFieldToFilter('main_table.website_id', ['eq' => (int) $this->scope['scope_id']]);
            }
        }
        
        //mak persist category
        $catNameAttr = $this->mp_gridActionHelper->getCategoryNameAttribute();
        //mak persist category
        $collection->getSelect()
                ->join(['ce1' => $collection->getTable('customer_entity')], 'ce1.entity_id=main_table.seller_id', ['firstname'])
                ->join(['ce2' => $collection->getTable('customer_entity')], 'ce2.entity_id=main_table.seller_id', ['lastname'])
                ->columns(new \Zend_Db_Expr("CONCAT(`ce1`.`firstname`, ' ',`ce2`.`lastname`) AS full_name"))
                ->join(['seller' => $collection->getTable('vss_mp_seller_entity')], 'seller.seller_id=main_table.seller_id')
                ->join(['order_item' => $collection->getTable('sales_order_item')], 'order_item.item_id=main_table.order_item_id', ['(row_total_incl_tax - discount_amount - (row_total_incl_tax - discount_amount)*((main_table.commission)/100)) as row_total_incl_tax', '((row_total_incl_tax - discount_amount)*((main_table.commission)/100)) as total_commission'])
                ->join(['category_name' => $collection->getTable('catalog_category_entity_varchar')], 'main_table.category_id=category_name.entity_id', ['category_name.value as category_title'])
                ->where('category_name.attribute_id=' . $catNameAttr->getAttributeId());
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }
    
    protected function _prepareColumns() {
        $store = $this->_getStore($this->scope['scope_id']);
        $this->addColumn('row_id', [
            'header' => __('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'row_id',
            'filter' => false,
            'sortable' => false
        ]);

        $this->addColumn('full_name', [
            'header' => __('Seller'),
            'align' => 'left',
            'index' => 'full_name',
            'type' => 'text',
            'renderer' => '\Knowband\Marketplace\Block\Adminhtml\Renderers\Link',
            'truncate' => 100,
            'escape' => true,
            'filter' => false,
            'sortable' => false
        ]);
        //mak persist category
        $this->addColumn('category_title', [
            'header' => __('Category'),
            'index' => 'category_title',
            'type' => 'text',
            'escape' => true,
            'filter' => false,
            'sortable' => false
        ]);
        //mak persist category
        $this->addColumn('row_total', [
            'header' => __('Total Earning'),
            'index' => 'row_total_incl_tax',
            'type' => 'price',
            'width' => '180px',
            'truncate' => 100,
            'escape' => true,
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'filter' => false,
            'sortable' => false
        ]);

        $this->addColumn('total_commission', [
            'header' => __('Total Commission'),
            'index' => 'total_commission',
            'type' => 'price',
            'width' => '180px',
            'truncate' => 100,
            'escape' => true,
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'filter' => false,
            'sortable' => false
        ]);
        return parent::_prepareColumns();
    }

    public function getRowUrl($row){
        return false;
    }

    public function getGridUrl() {
        return $this->getUrl('mpadmin/marketplace/categoryEarningAjax', ['_current' => true]);
    }

}
