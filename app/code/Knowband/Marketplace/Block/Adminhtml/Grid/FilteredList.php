<?php
namespace Knowband\Marketplace\Block\Adminhtml\Grid;

/**
 *
 * @author      Knowband Team
 */

class FilteredList extends \Magento\Backend\Block\Widget\Grid\Extended implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected $_coreRegistry = null;
    
    private $gridParameters;
    private $defaultPeriod = 'year';

    public $_filters = [];
    
    private $scope = ['scope' => 'default', 'scope_id' => 0];
    
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
        \Magento\Catalog\Model\Category $categoryModel,
        \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory $bestSellersColFactory,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Store\Model\Website $website,
        \Magento\Store\Model\Store $store,
        \Knowband\Marketplace\Helper\Product $mpProductHelper,
        \Knowband\Marketplace\Helper\GridAction $gridActionHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_resource = $resource;
        $this->_storeManager = $context->getStoreManager();
        $this->_categoryModel = $categoryModel;
        $this->_bestSellersColFactory = $bestSellersColFactory;
        $this->mp_gridActionHelper = $gridActionHelper;
        $this->mp_productHelper = $mpProductHelper;
        $this->_store = $store;
        $this->_website = $website;
        $this->_backendHelper = $backendHelper;
        $this->_localeDate = $context->getLocaleDate();
        
        parent::__construct($context, $backendHelper, $data);
        
        $this->gridParameters = $this->getRequest()->getParams();
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setId('bestSellerReportGrid');
        $this->setFilterVisibility(false);
//        $this->setDefaultSort('entity_id');
//        $this->setDefaultDir('desc');
//        $this->setSaveParametersInSession(true);
//        $this->setUseAjax(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Seller Best Selling Product List');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Seller Best Selling Product List');
    }
    
    protected function _getStore($storeId = 0){
        return $this->_storeManager->getStore($storeId);
    }
    
    protected function getPeriodType() {
        if(isset($this->gridParameters['period_type']))
            return $this->gridParameters['period_type'];
        else
            return $this->defaultPeriod;
    }

    protected function _prepareCollection()
    {
        $bestSellerCollection = $this->_bestSellersColFactory->create();
        $tableName = $bestSellerCollection->getTable('sales_bestsellers_aggregated_yearly');
        $storeFilter = '';
        $storeFilterString = '';
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
        
        $this->scope['scope'] = $scope;
        $this->scope['scope_id'] = $store_id;

        $storeids = implode(',', $storeids);
        if ($scope == 'stores') {
            $storeFilter = "store_id=" . $store_id;
        } else if ($scope == 'websites') {
            $storeFilter = "store_id in (" . $storeids . ")";
        }
        if (isset($this->gridParameters['period_type'])) {
            if ($storeFilter != '') {
                $storeFilterString = 'and ' . $tableName . '.' . $storeFilter;
            }
            if ($this->gridParameters['period_type'] == 'day') {
                $bestSellerCollection->setPeriod('day');
                $tableName = $bestSellerCollection->getTable('sales_bestsellers_aggregated_daily');
            } else if ($this->gridParameters['period_type'] == 'month') {
                $bestSellerCollection->setPeriod('month');
                $tableName = $bestSellerCollection->getTable('sales_bestsellers_aggregated_monthly');
            } else if ($this->gridParameters['period_type'] == 'year') {
                $bestSellerCollection->setPeriod('year');
                $tableName = $bestSellerCollection->getTable('sales_bestsellers_aggregated_yearly');
            }

            if (isset($this->gridParameters['from']) && isset($this->gridParameters['to'])) {
                if (!empty($this->gridParameters['from']) && !empty($this->gridParameters['to'])) {
                    $from_date = new \DateTime($this->gridParameters['from']);
                    $from_date = $this->_localeDate->convertConfigTimeToUtc($from_date->format('Y-m-d 00:00:00'));
//                    $to_date = $this->_localeDate->date($this->gridParameters['to'], \Zend_Date::DATE_SHORT);
                    $to_date = new \DateTime($this->gridParameters['to']);
                    $to_date = $this->_localeDate->convertConfigTimeToUtc($to_date->format('Y-m-d 00:00:00'));
                    $bestSellerCollection->setDateRange($from_date, $to_date);
                } else if (!empty($this->gridParameters['from'])) {
                    $from_date = new \DateTime($this->gridParameters['from']);
                    $from_date = $this->_localeDate->convertConfigTimeToUtc($from_date->format('Y-m-d 00:00:00'));
                    $bestSellerCollection->setDateRange($from_date);
                } else if (!empty($this->gridParameters['to'])) {
                    $to_date = new \DateTime($this->gridParameters['to']);
                    $to_date = $this->_localeDate->convertConfigTimeToUtc($to_date->format('Y-m-d 00:00:00'));
                    $bestSellerCollection->setDateRange(null, $to_date);
                }
            }
            $pro_string = '';
            $condition = '';
            if (isset($this->gridParameters['category_to_filter']) && isset($this->gridParameters['seller_to_filter'])) {
                if ($this->gridParameters['category_to_filter'] != 'all' && $this->gridParameters['seller_to_filter'] != 'all') {
                    $category_id = $this->gridParameters['category_to_filter'];
                    $sellerId = $this->gridParameters['seller_to_filter'];
                    $sellerProducts = implode(',', $this->mp_productHelper->getSellerEnabledProducts($sellerId));
                    $products = $this->_categoryModel->load($category_id)
                            ->getProductCollection()
                            ->addAttributeToFilter('status', 1);
                    $products->getSelect()->where('e.entity_id in (' . $sellerProducts . ')');
                    $prod = [];
                    foreach ($products as $pro) {
                        $prod[] = $pro->getEntityId();
                    }
                    $pro_string = implode(',', $prod);
                    $condition = "(" . $tableName . ".product_id in ('" . $pro_string . "'))";
                } else if ($this->gridParameters['category_to_filter'] == 'all' && $this->gridParameters['seller_to_filter'] != 'all') {
                    $sellerId = $this->gridParameters['seller_to_filter'];
                    $pro_string = implode(',', $this->mp_productHelper->getSellerEnabledProducts($sellerId));
                    $condition = "(" . $tableName . ".product_id in ('" . $pro_string . "'))";
                } else if ($this->gridParameters['seller_to_filter'] == 'all' && $this->gridParameters['category_to_filter'] != 'all') {
                    $category_id = $this->gridParameters['category_to_filter'];
                    $products = $this->_categoryModel->load($category_id)
                            ->getProductCollection()
                            ->addAttributeToFilter('status', 1);
                    $prod = [];
                    foreach ($products as $pro) {
                        $prod[] = $pro->getEntityId();
                    }
                    $pro_string = implode(',', $prod);
                    $condition = "(" . $tableName . ".product_id in ('" . $pro_string . "'))";
                } else
                    $condition = "(" . $tableName . ".product_id = seller.product_id)";

                $bestSellerCollection->getSelect()->join(['seller' => $bestSellerCollection->getTable('vss_mp_product_to_seller')], $condition . " " . $storeFilter, []);
            } else
                $bestSellerCollection->getSelect()->join(['seller' => $bestSellerCollection->getTable('vss_mp_product_to_seller')], "(" . $tableName . ".product_id = seller.product_id) " . $storeFilterString, []);
        }
        else {
            if ($storeFilter != '') {
                $storeFilterString = 'and ' . $tableName . '.' . $storeFilter;
            }
            $bestSellerCollection->getSelect()->join(['seller' => $bestSellerCollection->getTable('vss_mp_product_to_seller')], "(" . $tableName . ".product_id = seller.product_id) " . $storeFilterString, []);
        }
        $this->setCollection($bestSellerCollection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns() {
        
        $store = $this->_getStore($this->scope['scope_id']);
        $this->addColumn('period', [
            'header'    => __('Interval'),
            'index'	=> 'period',
            'filter'    => false,
            'sortable'  => false,
            'period_type'   => $this->getPeriodType(),
            'renderer'      => 'Magento\Reports\Block\Adminhtml\Sales\Grid\Column\Renderer\Date',
            'totals_label'  => __('Total'),
            'html_decorators' => ['nobr'],
            'header_css_class' => 'col-period',
            'column_css_class' => 'col-period'
        ]);

        $this->addColumn('qty_ordered', [
            'header'    => __('Order Quantity'),            
            'type'	=> 'number',
            'index'	=> 'qty_ordered',
            'total'     => 'sum',
            'filter'    => false,
            'sortable'  => false,
            'header_css_class' => 'col-qty',
            'column_css_class' => 'col-qty'
        ]);
        
        $this->addColumn('product_id', [
            'header'    => __('Product ID'),
            'type'	=> 'number',
            'index'	=> 'product_id',
            'filter'    => false,
            'sortable'  => false,
            'header_css_class' => 'col-product',
            'column_css_class' => 'col-product'
        ]);
        
        $this->addColumn('product_name', [
            'header'    => __('Product Name'),
            'type'	=> 'string',
            'index'	=> 'product_name',
            'filter'    => false,
            'sortable'  => false,
            'header_css_class' => 'col-product',
            'column_css_class' => 'col-product'
        ]);
        
        $this->addColumn('product_price', [
            'header'        => __('Product Price'),
            'type'	    => 'price',
            'index'	    => 'product_price',
            'currency_code' => $store->getBaseCurrency()->getCode(),
            'filter'        => false,
            'sortable'      => false,
            'header_css_class' => 'col-product',
            'column_css_class' => 'col-product'
        ]);

        return parent::_prepareColumns();
    }
    
//    public function getRowUrl($row){
//        return false;
//    }

//    public function getGridUrl() {
//        if ($this->getRequest()->getParam('id')) {
//            return $this->getUrl('mpadmin/marketplace/sellerProductListAjax', ['_current'=>true, 'id' => $this->getRequest()->getParam('id')]);
//        }else{
//            return $this->getUrl('mpadmin/marketplace/sellerProductListAjax', ['_current'=>true]);
//        }
//    }
    
}
