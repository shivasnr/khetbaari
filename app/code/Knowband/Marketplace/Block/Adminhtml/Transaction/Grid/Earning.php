<?php
namespace Knowband\Marketplace\Block\Adminhtml\Transaction\Grid;

/**
 *
 * @author      Knowband Team
 */

class Earning extends \Magento\Backend\Block\Widget\Grid\Extended implements
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
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customColFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Knowband\Marketplace\Helper\GridAction $gridActionHelper,
        \Knowband\Marketplace\Helper\Reports $mpReportsHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_resource = $resource;
        $this->_storeManager = $context->getStoreManager();
        $this->mp_gridActionHelper = $gridActionHelper;
        $this->mp_reportsHelper = $mpReportsHelper;
        $this->_customerColFactory = $customColFactory;
        $this->_backendHelper = $backendHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setId('earning_commission_grid');
        $this->setDefaultSort('row_id');
        $this->setDefaultDir('desc');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Commissions');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Commissions');
    }
    
    protected function _getStore($storeId = 0){
        return $this->_storeManager->getStore($storeId);
    }

    protected function _prepareCollection() 
    {
        if ($this->getRequest()->getParam('filter')) {
            $this->_filters = $this->_backendHelper->prepareFilterString($this->getRequest()->getParam('filter'));
        }
        
        $scope = $this->mp_gridActionHelper->getScope();
        
        $collection = $this->_customerColFactory->create()
                ->addNameToSelect()
                ->addAttributeToSelect('email')
                ->addAttributeToSelect('created_at');
        $collection->getSelect()->join(['e1' => $collection->getTable('vss_mp_seller_earnings')], 'e.entity_id = e1.seller_id');
        $collection->getSelect()->join(['s2c' => $collection->getTable('vss_mp_seller_entity')], 'e1.seller_id = s2c.seller_id');
        $collection->getSelect()->join(['o' => $collection->getTable('sales_order')], 'o.entity_id = e1.order_id');
        if ($scope['scope'] == 'websites') {
            $collection->addFieldToFilter('e1.website_id', ['eq' => $scope['scope_id']]);
        } else if ($scope['scope'] == 'stores') {
            $collection->addFieldToFilter('e1.store_id', ['eq' => $scope['scope_id']]);
        }

        $status_con = "o.status NOT IN (";
        foreach ($this->mp_reportsHelper->getOrderNotInclude() as $status) {
            $status_con .= "'" . $status . "',";
        }
        $status_con .= "'')";
        $collection->getSelect()->where(rtrim($status_con, ','));

        $collection->getSelect()
                ->reset(\Zend_Db_Select::COLUMNS)
                ->columns([
                    "e.email as email",
                    "s2c.contact_number",
                    "e1.*"]);

        $collection->getSelect()
			->columns(new \Zend_Db_Expr("CONCAT(`e`.`firstname`, ' ',`e`.`lastname`) AS name"));
        
        if (isset($this->_filters) && !empty($this->_filters)) {
            $collection = $this->setFilter($collection, $this->_filters);
        }
        $collection = $this->sortCollection($collection);
        
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
                    $collection = $this->mp_gridActionHelper->setAdminGridFilter($collection, $column, $column->getIndex(), $value);
                }
            }
        }
        return $collection;
    }

    protected function sortCollection(&$collection) {
        $params = $this->getRequest()->getParams();
        if (isset($params['sort'])) {
            $collection->getSelect()->order($params['sort'] . ' ' . $params['dir']);
        }
        return $collection;
    }

    protected function _prepareColumns() {
//        $store = $this->_getStore();
        $this->addColumn('row_id', [
            'header' => __('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'row_id',
            'filter_index' => '`e1`.`row_id`',
        ]);

        $this->addColumn('name', [
            'header' => __('Name'),
            'align' => 'left',
            'index' => 'name',
            'type' => 'text',
            'width' => '180px',
            'truncate' => 100,
            'escape' => true,
            'filter' => false,
        ]);

        $this->addColumn('email', [
            'header' => __('Email'),
            'align' => 'left',
            'index' => 'email',
            'type' => 'text',
            'width' => '180px',
            'truncate' => 100,
            'escape' => true,
        ]);

        $this->addColumn('contact_number', [
            'header' => __('Telephone'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'contact_number',
            'renderer' => '\Knowband\Marketplace\Block\Adminhtml\Renderers\Blank'
        ]);

        $this->addColumn('product_count', [
            'header' => __('Quantity'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'product_count'
        ]);

        $this->addColumn('total_earning', [
            'header' => __('Total'),
            'align' => 'right',
            'index' => 'total_earning',
            'type' => 'currency',
            'currency_code' => $this->_storeManager->getStore()->getBaseCurrency()->getCode(),
        ]);

        $this->addColumn('commision_percent', [
            'header' => __('Commission (in %)'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'commision_percent'
        ]);

        $this->addColumn('admin_comission', [
            'header' => __('Your Earnings'),
            'align' => 'right',
            'index' => 'admin_comission',
            'type' => 'currency',
            'currency_code' => $this->_storeManager->getStore()->getBaseCurrency()->getCode(),
        ]);
        return parent::_prepareColumns();
    }

    public function getRowUrl($row){
        return false;
    }

    public function getGridUrl() {
        return $this->getUrl('mpadmin/marketplace/transaction', ['_current' => true]);
    }

}
