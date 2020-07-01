<?php
namespace Knowband\Marketplace\Block\Adminhtml\Grid;

/**
 *
 * @author      Knowband Team
 */

class SellerProductList extends \Magento\Backend\Block\Widget\Grid\Extended implements
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
        \Magento\Catalog\Model\Product $productModel,
        \Knowband\Marketplace\Helper\GridAction $gridActionHelper,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_resource = $resource;
        $this->_storeManager = $context->getStoreManager();
        $this->_productModel = $productModel;
        $this->mp_gridActionHelper = $gridActionHelper;
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
        $this->setId('sellerProductListGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('desc');
//        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Seller Product List');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Seller Product List');
    }
    
    protected function _getStore($storeId = 0){
        return $this->_storeManager->getStore($storeId);
    }

    protected function _prepareCollection()
    {
        if ($this->getRequest()->getParam('filter')) {
            $this->_filters = $this->_backendHelper->prepareFilterString($this->getRequest()->getParam('filter'));
        }
        $store = $this->_getStore();
        $collection = $this->_productModel->getCollection()
                        ->addAttributeToSelect('sku')
			->addAttributeToSelect('name')
			->addAttributeToSelect('price');;
        $collection->joinField('qty',
			$collection->getTable('cataloginventory_stock_item'),
			'qty',
			'product_id=entity_id',
			'{{table}}.stock_id=1',
			'left');
        
        $condition = [];
        $scope = $this->mp_gridActionHelper->getScope();
        if ($scope['scope'] != 'default' && $scope) {
            if ($scope['scope'] == 'websites') {
                $condition['website_id'] = (int) $scope['scope_id'];
            }
        }
        $collection->joinField('s2p',
			$collection->getTable('vss_mp_product_to_seller'),
			'*',
			'product_id=entity_id',
			$condition,
			'inner');
        
        $collection->getSelect()->where('(at_s2p.approved = '. \Knowband\Marketplace\Helper\GridAction::APPROVED.' OR at_s2p.approved = '.\Knowband\Marketplace\Helper\GridAction::DISAPPROVED.')');
        if ($this->getRequest()->getParam('id')) {
            $collection->getSelect()->where('at_s2p.seller_id = ' . (int) $this->getRequest()->getParam('id'));
        }
        
        $collection->getSelect()
			->join(['ce1' => $collection->getTable('customer_entity')], 'ce1.entity_id=at_s2p.seller_id', ['firstname'])
			->join(['ce2' => $collection->getTable('customer_entity')], 'ce2.entity_id=at_s2p.seller_id', ['lastname'])
			->columns(new \Zend_Db_Expr("CONCAT(`ce1`.`firstname`, ' ',`ce2`.`lastname`) AS full_name"))
			->join(['seller' => $collection->getTable('vss_mp_seller_entity')], 'seller.seller_id=at_s2p.seller_id',['shop_title']);
        
        if ($scope && $scope['scope'] == 'stores' && $scope['scope_id'] > 0) {
            $collection->joinAttribute(
                    'status', 'catalog_product/status', 'entity_id', null, 'inner', $scope['scope_id']
            );

            $collection->joinAttribute(
                    'visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $scope['scope_id']
            );

            $collection->joinAttribute(
                    'price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId()
            );
        } else {
//            $collection->addAttributeToSelect('price');
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'inner');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        }
        
        $collection->getSelect()->columns(new \Zend_Db_Expr("CONCAT(at_s2p.seller_id, '_',e.entity_id) AS custom_id"));

        if (isset($this->_filters)) {
            $collection = $this->setFilter($collection);
        }
        
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }
    
    protected function _addColumnFilterToCollection($column) {
        return $this;
    }

    protected function setFilter(&$collection) {
        foreach ($this->getColumns() as $column) {
            foreach ($this->_filters as $key => $value) {
                if ($key == $column->getIndex()) {
                    $collection = $this->mp_gridActionHelper->setAdminGridFilter($collection, $column, $this->getAliasCol($column->getIndex()), $value);
                }
            }
        }
        return $collection;
    }

    protected function _prepareColumns() {
        
        $this->addColumn('entity_id', [
            'header'        => __('ID'),
            'align'         => 'right',
            'width'         => '50px',
            'type'          => 'number',
            'index'         => 'entity_id'
        ]);

        $this->addColumn('name', [
            'header'        => __('Product Name'),
            'align'         => 'left',
            'index'         => 'name',
            'type'          => 'text',
            'truncate'      => 100,
            'escape'        => true,
            'filter'        => false
        ]);
        
        $this->addColumn('sku', [
            'header' => __('SKU'),
            'width' => '80px',
            'index' => 'sku',
        ]);
        
        $this->addColumn('full_name', [
            'header'        => __('Seller'),
            'align'         => 'left',
            'index'         => 'full_name',
            'type'          => 'text',
            'renderer'      => 'Knowband\Marketplace\Block\Adminhtml\Renderers\Link',
            'truncate'      => 100,
            'escape'        => true,
            'filter'        => false
        ]);
        
        $this->addColumn('shop_title', [
            'header'        => __('Shop Title'),
            'align'         => 'left',
            'index'         => 'shop_title',
            'type'          => 'text',
            'renderer'      => 'Knowband\Marketplace\Block\Adminhtml\Renderers\Blank',
            'truncate'      => 100,
            'escape'        => true,
            'filter'        => false
        ]);
        
        $this->addColumn('price', [
            'header' => __('Price'),
            'align' => 'right',
            'index' => 'price',
            'type' => 'price',
            'currency_code' => $this->_storeManager->getStore()->getBaseCurrency()->getCode(),
            'escape' => true,
        ]);
        
        $this->addColumn('qty', [
            'header' => __('Quantity'),
            'align' => 'right',
            'index' => 'qty',
            'type' => 'number',
            'escape' => true,
        ]);
        
        $this->addColumn('visibility', [
            'header' => __('Visibility'),
            'width' => '70px',
            'index' => 'visibility',
            'type' => 'options',
            'options' => \Magento\Catalog\Model\Product\Visibility::getOptionArray()
        ]);
        
        $this->addColumn('status', [
            'header' => __('Status'),
            'width' => '70px',
            'index' => 'status',
            'type' => 'options',
            'options' => \Magento\Catalog\Model\Product\Attribute\Source\Status::getOptionArray()
        ]);
        
        $this->addColumn('approved', [
            'header' => __('Approved Status'),
            'align' => 'left',
            'width' => '70px',
            'index' => 'approved',
            'type' => 'options',
            'options' => $this->mp_gridActionHelper->getApprovalOptionArray(),
        ]);

        $this->addColumn('created_at', [
            'header'        => __('Added Date'),
            'align'         => 'left',
            'type'          => 'datetime',
            'index'         => 'created_at'
        ]);

        $this->addColumn('action',[
                'header'    => __('Actions'),
                'type'      => 'action',
                'width'     => '150px',
                'getter'    => 'getProductId',
                'renderer'  => 'Knowband\Marketplace\Block\Adminhtml\Renderers\Actions\Deletebtn',
                'filter'    => false,
                'sortable'  => false
        ]);
        return parent::_prepareColumns();
    }
    
    private function getAliasCol($col) {
        switch ($col) {
            case 'entity_id':
                return 'e.entity_id';
            case 'name':
                return 'e.name';
            case 'sku':
                return 'e.sku';
            case 'status':
                if('at_status.value_id' > 0){
                    return 'at_status.value_id';
                } else {
                    return 'at_status_default.value';
                }
            case 'visibility':
                if('at_visibility.value_id' > 0){
                    return 'at_visibility.value_id';
                } else {
                    return 'at_visibility_default.value';
                }
            case 'price':
                return 'at_price.value';
            case 'qty':
                return 'at_qty.qty';
            case 'shop_title':
                return 'seller.shop_title';
            case 'approved':
                return 'at_s2p.approved';
            case 'created_at':
                return 'at_s2p.created_at';
            default :
                return $col;
        }
    }

    public function getRowUrl($row){
        return false;
    }

    public function getGridUrl() {
        if ($this->getRequest()->getParam('id')) {
            return $this->getUrl('mpadmin/marketplace/sellerProductListAjax', ['_current'=>true, 'id' => $this->getRequest()->getParam('id')]);
        }else{
            return $this->getUrl('mpadmin/marketplace/sellerProductListAjax', ['_current'=>true]);
        }
    }
    
}
