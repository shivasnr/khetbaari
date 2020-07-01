<?php

namespace Knowband\Marketplace\Block\Review\Product;

class ProductList extends \Magento\Framework\View\Element\Template {
    
    private $_default_sort = ['col'=> 'rt.review_id', 'dir' => 'DESC'];
    public $rating_html = '<div class="vss_ratings"><div class="vss_rating_box"><div class="vss_rating_unfilled">★★★★★</div><div class="vss_rating_filled" style="width: {{width}}%;">★★★★★</div></div></div>';
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
            \Magento\Catalog\Model\Product $productModel,
            \Magento\Review\Model\Review $review,
            \Knowband\Marketplace\Helper\Data $mpDataHelper
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        $this->_timezone = $timezone;
        $this->mp_dataHelper = $mpDataHelper;
        $this->_productModel = $productModel;
        $this->_review = $review;
        parent::__construct($context);
        $this->setTemplate('seller_product_review_list.phtml');
    }
    
    public function getFieldId() {
        return 'vssmp_product_review';
    }

    public function getFilters() {
        return [
            ['label' => __('From Date'), 'name' => 'from_date', 'className' => 'form-control has_datepicker', 'type' => 'date', 'values' => ''],
            ['label' => __('To Date'), 'name' => 'to_date', 'className' => 'form-control has_datepicker', 'type' => 'date', 'values' => ''],
//            ['label' => __('Product Name'), 'name' => 'name', 'className' => 'form-control', 'type' => 'text', 'values' => ''],
            ['label' => __('Customer Name'), 'name' => 'nickname', 'className' => 'form-control', 'type' => 'text', 'values' => ''],
        ];
    }

    public function getColumns() {
        return [
            ['label' => __('Id'), 'name' => 'rt.review_id', 'className' => 'vssmp-txt-ryt', 'targets' => 0, 'width' => 50],
            ['label' => __('Posted on'), 'name' => 'rt.created_at', 'className' => '', 'targets' => 1, 'width' => 80],
            ['label' => __('Product Name'), 'name' => 'name', 'className' => '', 'targets' => 2, 'width' => 150],
            ['label' => __('Customer Name'), 'name' => 'rdt.nickname', 'className' => '', 'targets' => 3, 'width' => 100],
            ['label' => __('Review'), 'name' => 'rdt.detail', 'className' => '', 'targets' => 4, 'width' => ''],
            ['label' => __('Status'), 'name' => 'status_id', 'className' => '', 'targets' => 4, 'width' => ''],
            ['label' => __('Rating'), 'name' => 'review_rating', 'className' => '', 'targets' => 5, 'width' => 100]
        ];
    }

    public function getSellerInfo() {
        return $this->_coreRegistry->registry("vssmp_seller_info");
    }

    public function getListUrl() {
        return $this->getFrontUrl('productreview', 'productList');
    }
    
    public function getReviewList() {
        $seller_info = $this->getSellerInfo();
        $post_data = $this->getRequest()->getParams();
        $col_order = $this->mp_dataHelper->getColOrder($post_data);

        $collection = $this->_review->getProductCollection();

        $collection->getSelect()->join(['s2p' => $collection->getTable("vss_mp_product_to_seller")], 'e.entity_id = s2p.product_id');

        $collection->getSelect()->join(['vote' => $collection->getTable("rating_option_vote")], 'rt.review_id = vote.review_id');

        $collection->getSelect()->columns([
            "(SUM(vote.percent)/COUNT(vote.review_id)) as review_rating",
            "rt.created_at as review_posted_at"
        ]);
        $collection->getSelect()->where('s2p.seller_id = ' . (int) $seller_info['entity_id'] . ' AND s2p.website_id = ' . $seller_info['website_id']);
        $filters = $this->getFilters();
        foreach ($filters as $filter) {
            if (isset($post_data[$filter['name']]) && $post_data[$filter['name']] != '') {
                if ($filter['type'] == 'select' || $filter['type'] == 'date') {
                    if ($filter['type'] == 'date' && $filter['name'] == 'from_date') {
                        $formatted_date = date('Y-m-d', strtotime($post_data[$filter['name']]));
                        $collection->getSelect()->where('DATE(rt.created_at) >= "' . $formatted_date . '"');
                    } else if ($filter['type'] == 'date' && $filter['name'] == 'to_date') {
                        $formatted_date = date('Y-m-d', strtotime($post_data[$filter['name']]));
                        $collection->getSelect()->where('DATE(rt.created_at) <= "' . $formatted_date . '"');
                    } else {
                        $collection->getSelect()->where($filter['name'] . ' = "' . $post_data[$filter['name']] * 20);
                    }
                } else if ($filter['type'] == 'text') {
                    $collection->getSelect()->where($filter['name'] . " like '%" . trim($post_data[$filter['name']]) . "%'");
                }
            }
        }

        if (isset($col_order['col']) && $col_order['col'] != '') {
            $collection->setOrder([$col_order['col'] . ' ' . $col_order['dir']]);
        } else {
            $collection->setOrder([$this->_default_sort['col'] . ' ' . $this->_default_sort['dir']]);
        }

        $collection->getSelect()->group('rt.review_id');

        $data = [];
        $countCollection = clone $collection;
        $data['count'] = count($countCollection->getItems());

        $start = 0;
        $limit = $this->mp_dataHelper->getPageLength();
        if ($post_data['start'] > 0) {
            $start = (int) $post_data['start'];
        }
        $collection->getSelect()->limit($limit, $start);
        if ($data['count'] > 0) {
            $modalName = "'" . "vssmp-product-review-view-popup" . "'";
            $collection_arr = $collection->getData();
            foreach ($collection_arr as $item) {
                $rating = round(($item['review_rating']), 2);
                $status = '';
                if($item['status_id'] == \Magento\Review\Model\Review::STATUS_APPROVED){
                    $status = __('Approved');
                } else if($item['status_id'] == \Magento\Review\Model\Review::STATUS_PENDING){
                    $status = __('Pending');
                } else if($item['status_id'] == \Magento\Review\Model\Review::STATUS_NOT_APPROVED){
                    $status = __('Not Approved');
                }
                $read_more_link = '<a class="vssmp-open-map-popup" onclick="openVssSellerProductReviewModal(' . $modalName . ',' . $item['review_id'] . ',' . $item['product_id'] . ')" href="javascript:void(0)" >Read More</a>';
                $product_mod = $this->_productModel->load($item['product_id']);
                $data['collection'][] = [
                    '<a class="vssmp-open-map-popup" onclick="openVssSellerProductReviewModal(' . $modalName . ',' . $item['review_id'] . ',' . $item['product_id'] . ')" href="javascript:void(0)" title="' . __("click to view review") . '">' . $item['review_id'] . '</a>',
                    $this->_timezone->formatDate($item['review_posted_at']),
                    '<a class="vssmp-open-map-popup" target="_blank" href="' . $product_mod->getProductUrl() . '" title="' . __("click to view product") . '">' . $product_mod->getName() . '</a>',
                    $item['nickname'],
                    $this->mp_dataHelper->clipLongText($item['detail'], $read_more_link),
                    $status,
                    str_replace('{{width}}', $rating, $this->rating_html)
                ];
                $product_mod->unsetData();
            }
        } else {
            $data['collection'] = [];
        }
        unset($collection);
        return $data;
    }

}
