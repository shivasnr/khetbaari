<?php
namespace Knowband\Marketplace\Block\Adminhtml\Grid;

/**
 *
 * @author      Knowband Team
 */

class EmailTemplates extends \Magento\Backend\Block\Widget\Grid\Extended implements
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
        \Magento\Framework\App\ResourceConnection $resource,
        \Knowband\Marketplace\Model\Emailtemplates $mpEmailTemplates,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_resource = $resource;
        $this->_storeManager = $context->getStoreManager();
        $this->mp_emailTemplates = $mpEmailTemplates;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setId('emailTemplateGrid');
        $this->setDefaultSort('template_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Email Templates');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Email Templates');
    }

    protected function _prepareCollection() 
    {
        $collection = $this->mp_emailTemplates->getCollection();
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns() {
        
        $this->addColumn('template_id', [
            'header' => __('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'template_id',
        ]);

        $this->addColumn('template_subject', [
            'header' => __('Template Subject'),
            'align' => 'left',
            'type' => 'text',
            'width' => '200px',
            'index' => 'template_subject',
            'filter' => false,
            'sortable' => false
        ]);

        $this->addColumn('template_description', [
            'header' => __('Template Description'),
            'align' => 'left',
            'type' => 'text',
            'width' => '160px',
            'index' => 'template_description',
            'filter' => false,
            'sortable' => false
        ]);

        $this->addColumn('template_type', [
            'header' => __('Template Type'),
            'align' => 'left',
            'type' => 'text',
            'width' => '100px',
            'index' => 'template_type',
            'filter' => false,
            'sortable' => false
        ]);

        $this->addColumn('created_at', [
            'header' => __('Date Added'),
            'align' => 'left',
            'width' => '160px',
            'type' => 'datetime',
            'index' => 'created_at',
        ]);

        $this->addColumn('updated_at', [
            'header' => __('Date Updated'),
            'align' => 'left',
            'width' => '160px',
            'type' => 'datetime',
            'index' => 'updated_at',
        ]);


        $this->addColumn('action',[
                'header'    => __('Actions'),
                'type'      => 'action',
                'width'     => '150px',
                'getter'    => 'getTemplateId',
                'renderer'  => 'Knowband\Marketplace\Block\Adminhtml\Renderers\Actions\EmailTemplatesAction',
                'filter'    => false,
                'sortable'  => false
        ]);
        return parent::_prepareColumns();
    }

    public function getRowUrl($row){
        return false;
    }

    public function getGridUrl() {
        return $this->getUrl('mpadmin/marketplace/EmailTemplatesAjax', ['_current' => true]);
    }

}
