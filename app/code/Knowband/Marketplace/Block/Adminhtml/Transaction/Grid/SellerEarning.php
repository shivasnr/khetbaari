<?php
namespace Knowband\Marketplace\Block\Adminhtml\Transaction\Grid;

/**
 *
 * @author      Knowband Team
 */

class SellerEarning extends \Magento\Backend\Block\Widget\Grid\Extended implements
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
        \Knowband\Marketplace\Model\Earnings $mpEarningsModel,
        \Knowband\Marketplace\Model\Transactions $mpTransactionModel,
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
        $this->mp_earningsModel = $mpEarningsModel;
        $this->mp_transactionModel = $mpTransactionModel;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setId('sellerEarningListGrid');
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
        return __('Seller wise Commissions');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Seller wise Commissions');
    }
    
    protected function _getStore($storeId = 0){
        return $this->_storeManager->getStore($storeId);
    }

    protected function _prepareCollection() 
    {
        if ($this->getRequest()->getParam('filter')) {
            $filters = $this->_backendHelper->prepareFilterString($this->getRequest()->getParam('filter'));
        }
        $scope = $this->scope;

        $earning_coll = $this->mp_earningsModel->getCollection();
        $earning_coll->getSelect()->join(['o' => $earning_coll->getTable('sales_order')], 'o.entity_id = main_table.order_id');
        $earning_coll->addFieldToFilter('o.status', ['nin' => $this->mp_reportsHelper->getOrderNotInclude()]);
        if ($scope['scope'] == 'websites') {
            $earning_coll->addFieldToFilter('main_table.website_id', ['eq' => $scope['scope_id']]);
        } else if ($scope['scope'] == 'stores') {
            $earning_coll->addFieldToFilter('main_table.store_id', ['eq' => $scope['scope_id']]);
        }
        $earning_coll->getSelect()->reset(\Zend_Db_Select::COLUMNS)
                ->columns([
                    "main_table.seller_id",
                    "SUM(main_table.product_count) as product_count",
                    "SUM(main_table.total_earning) as total_earning",
                    "SUM(main_table.seller_earning) as seller_earning",
                    "SUM(main_table.admin_comission) as admin_comission",
                ])
                ->group("main_table.seller_id");

        $transaction_coll = $this->mp_transactionModel->getCollection();
        if ($scope['scope'] == 'websites') {
            $transaction_coll->addFieldToFilter('website_id', ['eq' => $scope['scope_id']]);
        } else if ($scope['scope'] == 'stores') {
            $transaction_coll->addFieldToFilter('store_id', ['eq' => $scope['scope_id']]);
        }
        $transaction_coll->getSelect()->reset(\Zend_Db_Select::COLUMNS)
                ->columns(["seller_id", "SUM(amount) as paid_amount"])
                ->group("seller_id");

        $collection = $this->_customerColFactory->create()
                ->addNameToSelect()
                ->addAttributeToSelect('email')
                ->addAttributeToSelect('created_at');

        $collection->getSelect()->join(['s2c' => $collection->getTable('vss_mp_seller_entity')], 'e.entity_id = s2c.seller_id');
        $collection->getSelect()->joinLeft(['er' => new \Zend_Db_Expr('(' . $earning_coll->getSelect() . ')')], "er.seller_id = s2c.seller_id");
        $collection->getSelect()->joinLeft(['st' => new \Zend_Db_Expr('(' . $transaction_coll->getSelect() . ')')], "st.seller_id = s2c.seller_id");

        if ($scope['scope'] == 'websites') {
            $collection->addFieldToFilter('e1.website_id', ['eq' => $scope['scope_id']]);
        } else if ($scope['scope'] == 'stores') {
            $collection->addFieldToFilter('e1.store_id', ['eq' => $scope['scope_id']]);
        }

        $collection->getSelect()
                ->reset(\Zend_Db_Select::COLUMNS)
                ->columns([
                    "e.email as email",
                    "s2c.contact_number",
                    "s2c.seller_id as seller_id",
                    "er.product_count",
                    "er.total_earning",
                    "er.seller_earning",
                    "er.admin_comission",
                    "st.paid_amount",
                    "(IF(er.seller_earning IS NOT NULL,er.seller_earning,0) - IF(st.paid_amount IS NOT NULL,st.paid_amount,0)) as balance"]);
        
        $collection->getSelect()
			->columns(new \Zend_Db_Expr("CONCAT(`e`.`firstname`, ' ',`e`.`lastname`) AS name"));
        $collection->getSelect()->group('s2c.seller_id');

        if (isset($filters)) {
            $collection = $this->setFilter($collection, $filters);
        }
        $collection = $this->sortCollection($collection);
        unset($earning_coll);
        unset($transaction_coll);
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }
    
    protected function _addColumnFilterToCollection($column) {
        return $this;
    }

    protected function setFilter(&$collection, $filters) {
        foreach ($this->getColumns() as $column) {
            foreach ($filters as $key => $value) {
                if ($key == $column->getIndex()) {
                    $collection = $this->mp_gridActionHelper->setAdminGridFilter($collection, $column, $this->getAliasCol($column->getIndex()), $value);
                }
            }
        }
        return $collection;
    }
    
    protected function sortCollection(&$collection) {
        $params = $this->getRequest()->getParams();
        if (isset($params['sort'])) {
            $collection->getSelect()->order($this->getAliasCol($params['sort']) . ' ' . $params['dir']);
        }
        return $collection;
    }

    protected function _prepareColumns() {
        $store = $this->_getStore();

        $this->addColumn('seller_id', [
            'header' => __('ID'),
            'align' => 'right',
            'index' => 'seller_id'
        ]);

        $this->addColumn('name', [
            'header' => __('Name'),
            'align' => 'left',
            'index' => 'name',
            'type' => 'text',
            'width' => '250px',
            'truncate' => 100,
            'escape' => true,
            'filter' => false
        ]);

        $this->addColumn('email', [
            'header' => __('Email'),
            'align' => 'left',
            'index' => 'email',
            'type' => 'text',
            'width' => '250px',
            'truncate' => 100,
            'escape' => true,
        ]);

        $this->addColumn('contact_number', [
            'header'        => __('Telephone'),
            'align'         => 'left',
            'index'         => 'contact_number',
            'type'          => 'text',
            'escape'        => true,
        ]);

        $this->addColumn('product_count', [
            'header' => __('Total Products Sold'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'product_count',
            'renderer' => '\Knowband\Marketplace\Block\Adminhtml\Renderers\Blank',
        ]);

        $this->addColumn('total_earning', [
            'header' => __('Total'),
            'align' => 'right',
            'index' => 'total_earning',
            'type' => 'currency',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'renderer' => '\Knowband\Marketplace\Block\Adminhtml\Renderers\Blank',
        ]);

        $this->addColumn('seller_earning', [
            'header' => __('Seller Earning'),
            'align' => 'right',
            'index' => 'seller_earning',
            'type' => 'currency',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'renderer' => '\Knowband\Marketplace\Block\Adminhtml\Renderers\Blank',
        ]);

        $this->addColumn('admin_comission', [
            'header' => __('Your Earnings'),
            'align' => 'right',
            'index' => 'admin_comission',
            'type' => 'currency',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'renderer' => '\Knowband\Marketplace\Block\Adminhtml\Renderers\Blank',
        ]);

        $this->addColumn('paid_amount', [
            'header' => __('Amount Transfer'),
            'align' => 'right',
            'index' => 'paid_amount',
            'type' => 'currency',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'renderer' => '\Knowband\Marketplace\Block\Adminhtml\Renderers\Blank',
        ]);

        $this->addColumn('balance', [
            'header' => __('Balance'),
            'align' => 'right',
            'index' => 'balance',
            'type' => 'currency',
            'filter' => false,
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'renderer' => '\Knowband\Marketplace\Block\Adminhtml\Renderers\Blank',
        ]);

        $this->addColumn('action', [
            'header' => __('Transaction Action'),
            'type' => 'action',
            'width' => '200px',
            'getter' => 'getSellerId',
            'renderer' => '\Knowband\Marketplace\Block\Adminhtml\Renderers\Actions\SellerCommissionActionBtn',
            'filter' => false,
            'sortable' => false
        ]);

        return parent::_prepareColumns();
    }

    public function getRowUrl($row){
        return false;
    }

    public function getGridUrl() {
        return $this->getUrl('mpadmin/marketplace/sellerEarningAjax', ['_current' => true]);
    }
	
    private function getAliasCol($col) {
        switch ($col) {
            case 'email':
                return 'e.email';
            case 'seller_id':
                return 's2c.seller_id';
            case 'product_count':
                return 'er.product_count';
            case 'total_earning':
                return 'er.total_earning';
            case 'seller_earning':
                return 'er.seller_earning';
            case 'admin_comission':
                return 'er.admin_comission';
            case 'paid_amount':
                return 'st.paid_amount';
            default :
                return $col;
        }
    }

}
