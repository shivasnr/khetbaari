<?php
namespace Knowband\Marketplace\Block\Adminhtml\Grid;

/**
 *
 * @author      Knowband Team
 */

class OrderList extends \Magento\Backend\Block\Widget\Grid\Extended implements
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
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $salesOrderItemCollection,
        \Knowband\Marketplace\Helper\GridAction $gridActionHelper,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Store\Model\Website $website,
        \Magento\Store\Model\Store $store,
        \Magento\Framework\ObjectManagerInterface $objectManager, 
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->mp_gridActionHelper = $gridActionHelper;
        $this->_backendHelper = $backendHelper;
        $this->_salesOrderItemCollection = $salesOrderItemCollection;
        $this->_store = $store;
        $this->_website = $website;
        $this->_objectManager = $objectManager;
        
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setId('sellerOrderListGrid');
        $this->setDefaultSort('order_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Seller Order List');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Seller Order List');
    }
    
    protected function _prepareCollection()
    {       
        $itemsCollection = $this->_salesOrderItemCollection->create();
        $itemsCollection->join(['order' => $itemsCollection->getTable('sales_order')], "main_table.order_id=order.entity_id");

        if ($this->getRequest()->getParam('id')) {
            $itemsCollection->getSelect()
                    ->join(
                        ['seller_product' => $itemsCollection->getTable("vss_mp_product_to_seller")], 
                        "(main_table.product_id = seller_product.product_id and seller_product.seller_id=" . $this->getRequest()->getParam('id') . ")",
                        []
                );
        }
        $itemsCollection->addAttributeToFilter('main_table.parent_item_id', ['null' => true]);
        
        $storeids = [];
        $store_id = '';
        if (strlen($code = $this->getRequest()->getParam('store'))) {
            $scope = "stores";
            $store_id = $this->_store->load($code)->getId();
        } else if (strlen($code = $this->getRequest()->getParam('website'))) {
            $scope = "websites";
            $storeids = $this->_website->load($code)->getStoreIds();
            $website_id = $this->_website->load($code)->getDefaultGroup()
                    ->getDefaultStoreId();
            $store_id = $website_id;
        } else {
            $scope = "default";
            $store_id = 0;
        }

        $storeids = implode(',', $storeids);
        if ($scope != 'default' && $scope) {
            if ($scope == 'stores') {
                $itemsCollection->addFieldToFilter('main_table.store_id', ['eq' => (int) $store_id]);
            } else if ($scope == 'websites') {
                $itemsCollection->addFieldToFilter('main_table.store_id', ['IN' => $storeids]);
            }
        }

        $colums = [
            "main_table.*",
            "order.created_at as order_create_date",
            "main_table.store_id as order_store_id",
            "SUM(main_table.qty_ordered - main_table.qty_canceled) as main_table.qty_ordered",
            "SUM(main_table.row_total_incl_tax) as row_total_incl_tax",
            "order.status as status",
            "SUM(main_table.discount_amount) as discount_amount",
            "CONCAT_WS(' ', `order`.`customer_firstname`, `order`.`customer_middlename`, `order`.`customer_lastname`) AS full_name"
        ];

        $itemsCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS)
                ->columns($colums);

        $itemsCollection->getSelect()->group("main_table.order_id");

        $this->setCollection($itemsCollection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns() {
        
        $this->addColumn('order_id', [
            'header'        => __('ID'),
            'align'         => 'right',
            'width'         => '50px',
            'type'          => 'number',
            'index'         => 'order_id'
        ]);

        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn('order_store_id', [
                'header'        => __('Purchased From (Store)'),
                'index'         => 'order_store_id',
                'type'          => 'store',
                'filter'        => false,
                'store_view'    => true,
                'display_deleted' => false,
            ]);
        }

        $this->addColumn('order_create_date', [
            'header'        => __('Purchased On'),
            'index'         => 'order_create_date',
            'type' => 'datetime'
        ]);

        $this->addColumn('full_name', [
            'header'        => __('Customer'),
            'index'         => 'full_name',
            'filter_condition_callback' => [$this, '_nameFilter'],
        ]);

        $this->addColumn('qty_ordered', [
            'header' => __('Quantity'),
            'index' => 'qty_ordered',
            'type'  => 'number',
            'align' => 'right',
            'filter_condition_callback' => [$this, '_qtyFilter'],
        ]);

        $this->addColumn('custom_row_total',[
            'header' => __('Total'),
            'index' => 'custom_row_total',
            'type'  => 'number',
            'filter' => false,
            'renderer'  => 'Knowband\Marketplace\Block\Adminhtml\Renderers\Actions\OrderTotal',
            'align'         => 'right',
        ]);

        $this->addColumn('status', [
            'header' => __('Status'),
            'index' => 'status',
            'align' => 'left',
            'type'  => 'options',
            'options' => $this->_objectManager->get("\Magento\Sales\Model\Order\Config")->getStatuses(),
        ]);
        
        $this->addColumn('action',[
            'header'    => __('Actions'),
            'type'      => 'action',
            'renderer'  => 'Knowband\Marketplace\Block\Adminhtml\Renderers\Actions\OrderActionBtn',
            'filter'    => false,
            'sortable'  => false
        ]);
        return parent::_prepareColumns();
    }
    
    protected function _nameFilter($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $this->getCollection()->getSelect()->where(
                "`order`.`customer_firstname` like ?
			OR `order`.`customer_middlename` like ?
			OR `order`.`customer_lastname` like ?"
                , "%$value%");


        return $this;
    }

    protected function _qtyFilter($collection, $column) {
        if (!$column->getFilter()->getValue()) {
            return $this;
        } else {
            $value = $column->getFilter()->getValue();
            if (!is_array($value) || count($value) == 0) {
                return $this;
            } else {
                $query = '';
                if (isset($value['from']) && isset($value['to'])) {
                    $query .= 'qty_ordered >= ' . $value['from'] . ' AND qty_ordered <= ' . $value['to'];
                } else if (isset($value['from'])) {
                    $query .= 'qty_ordered >= ' . (int) $value['from'];
                } else if (isset($value['to'])) {
                    $query .= 'qty_ordered <= ' . (int) $value['to'];
                }
                $this->getCollection()->getSelect()->where($query);
            }
        }
        return $this;
    }

    public function getRowUrl($row){
        return false;
    }

    public function getGridUrl() {
        return $this->getUrl('mpadmin/marketplace/orderListAjax', ['_current' => true, 'id' => $this->getRequest()->getParam('id')]);
    }
    
}
