<?php
namespace Knowband\Marketplace\Block\Adminhtml\Grid;

/**
 *
 * @author      Knowband Team
 */

class SellerList extends \Magento\Backend\Block\Widget\Grid\Extended implements
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
        \Knowband\Marketplace\Helper\GridAction $gridActionHelper,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_customerFactory = $customerFactory;
        $this->_resource = $resource;
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
        $this->setId('sellerListGrid');
        $this->setDefaultSort('seller_id');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Seller List');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Seller List');
    }
    
    protected function _prepareCollection()
    {       
        if ($this->getRequest()->getParam('filter')) {
            $this->_filters = $this->_backendHelper->prepareFilterString($this->getRequest()->getParam('filter'));
        }
        $collection = $this->_customerFactory->create()
                ->addNameToSelect()
                ->addAttributeToSelect('email')
                ->addAttributeToSelect('created_at');
        $scope = $this->mp_gridActionHelper->getScope();
        if ($scope['scope'] == 'websites') {
            $collection->getSelect()->join(['seller' => $collection->getTable("vss_mp_seller_entity")],"(e.entity_id = seller.seller_id and seller.seller_approved = 1 and seller.website_id = " . (int) $scope['scope_id'] . ")");
        } else {
            $collection->getSelect()->join(['seller' => $collection->getTable("vss_mp_seller_entity")],"(e.entity_id = seller.seller_id and seller.seller_approved = 1)");
        }
        
        
        if (isset($this->_filters)) {
            $collection = $this->setFilter($collection);
        }
        
        $collection = $this->sortCollection($collection);

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

    protected function sortCollection(&$collection) {
        $params = $this->getRequest()->getParams();
        if (isset($params['sort'])) {
            $collection->getSelect()->order($this->getAliasCol($params['sort']) . ' ' . $params['dir']);
        }
        return $collection;
    }

    protected function _prepareColumns() {
        
        $this->addColumn('entity_id', [
            'header'        => __('ID'),
            'align'         => 'right',
            'width'         => '40px',
            'index'         => 'entity_id'
        ]);

        $this->addColumn('name', [
            'header'        => __('Name'),
            'align'         => 'left',
            'index'         => 'name',
            'type'          => 'text',
            'renderer'      => 'Knowband\Marketplace\Block\Adminhtml\Renderers\SellerLink',
            'width'         => '120px',
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

        $this->addColumn('email', [
            'header'        => __('Email'),
            'align'         => 'left',
            'index'         => 'email',
            'type'          => 'text',
            'truncate'      => 100,
            'escape'        => true
        ]);

        $this->addColumn('seller_approved', [
            'header'        => __('Account Status'),
            'align'         => 'left',
            'type'          => 'options',
            'options'       => $this->mp_gridActionHelper->getApprovalOptionArray(),
            'escape'        => true,
            'index'         => 'seller_approved'
        ]);

        $this->addColumn('seller_enabled',[
                'header'=> __('Seller Status'),
                'align' => 'left',
                'width' => '70px',
                'index'	=> 'seller_enabled',
                'type'  => 'options',
                'options' => $this->mp_gridActionHelper->getStatusOptionArray(),
        ]);

        $this->addColumn('contact_number', [
            'header'        => __('Telephone'),
            'align'         => 'left',
            'index'         => 'contact_number',
            'type'          => 'text',
            'escape'        => true,
        ]);

        $this->addColumn('shop_country', [
            'header'        => __('Country'),
            'align'         => 'left',
            'index'         => 'shop_country',
            'type'          => 'country',
            'escape'        => true,
        ]);

        $this->addColumn('created_at', [
            'header'        => __('Seller Since'),
            'align'         => 'left',
            'width'         => '150px',
            'type'          => 'datetime',
            'index'         => 'created_at'
        ]);

        $this->addColumn('action',[
                'header'    => __('Actions'),
                'type'      => 'action',
                'width'     => '150px',
                'getter'    => 'getSellerId',
                'renderer'  => 'Knowband\Marketplace\Block\Adminhtml\Renderers\Actions\SellerListAction',
                'filter'    => false,
                'sortable'  => false
        ]);
        return parent::_prepareColumns();
    }
    
    private function getAliasCol($col) {
        switch ($col) {
            case 'email':
                return 'e.email';
            case 'entity_id':
                return 'e.entity_id';
            case 'seller':
                return 'seller.shop_title';
            case 'seller_approved':
                return 'seller.seller_approved';
            case 'seller_enabled':
                return 'seller.seller_enabled';
            case 'contact_number':
                return 'seller.contact_number';
            case 'created_at':
                return 'seller.created_at';
            default :
                return $col;
        }
    }

    public function getRowUrl($row){
        return false;
    }

    public function getGridUrl() {
        return $this->getUrl('mpadmin/marketplace/sellerListAjax', ['_current' => true]);
    }
    
}
