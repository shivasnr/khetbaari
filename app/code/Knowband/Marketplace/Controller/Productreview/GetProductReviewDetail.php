<?php

namespace Knowband\Marketplace\Controller\Productreview;

use Knowband\Marketplace\Controller\Index\ParentController;
class GetProductReviewDetail extends ParentController {

    protected $mp_resultRawFactory;
    protected $mp_request;
    protected $mp_scopeConfig;
    protected $inlineTranslation;
    protected $mp_transportBuilder;

    public function __construct(
            \Magento\Framework\App\Action\Context $context, 
            \Magento\Framework\App\Request\Http $request, 
            \Magento\Framework\App\Response\Http $response,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\Registry $registry,
            \Magento\Customer\Model\Session $customerSessionModel,
            \Magento\Framework\View\Result\PageFactory $resultRawFactory,
            \Knowband\Marketplace\Helper\Setting $settingHelper,
            \Knowband\Marketplace\Helper\Seller $sellerHelper,
            \Knowband\Marketplace\Helper\Log $logHelper,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
            \Magento\Catalog\Model\Product $productModel,
            \Magento\Review\Model\Review $review,
            \Magento\Review\Model\Rating\Option\Vote $rationOptionVote
    ) {
        parent::__construct($context, $request, $response, $storeManager, $registry, $customerSessionModel, $resultRawFactory, $settingHelper, $sellerHelper);
        $this->mp_request = $request;
        $this->mp_storeManager = $storeManager;
        $this->mp_resultRawFactory = $resultRawFactory;
        $this->mp_settingHelper = $settingHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_productModel = $productModel;
        $this->_review = $review;
        $this->_reviewRatingOptionVote = $rationOptionVote;
        $this->_timezone = $timezone;
        $this->mp_logHelper = $logHelper;
    }

    public function execute() {
        $output = [];
        try {
            $post = $this->mp_request;
            $reviewId = $post->getParam('id');
            $productId = $post->getParam('pro_id');
            $model = $this->_review;
            $collection = $model->getCollection()->addFieldToFilter('detail.review_id', $reviewId);
            $reviewData = $collection->getData();

            $reviewData[0]['created_at'] = $this->_timezone->formatDate($reviewData[0]['created_at']);
            $votesCollection = $this->_reviewRatingOptionVote
                    ->getResourceCollection()
                    ->setReviewFilter($reviewId)
                    ->setStoreFilter($this->mp_storeManager->getStore()->getId())
                    ->load();
            $average = 0;
            $counter = 0;
            $percent = [];
            foreach ($votesCollection as $star) {
                $average += $star->getPercent();
                $percent[$counter] = $star->getPercent();
                $counter++;
            }
            $average = $average / $counter;
            $obj = $this->_productModel;
            $_product = $obj->load($productId);
            $output['reviewData'] = $reviewData;
            $output['ratings'] = $percent;
            $output['average_rating'] = $average;
            
            $output['status'] = '';
            
            if(isset($reviewData[0]['status_id'])){
                if($reviewData[0]['status_id'] == \Magento\Review\Model\Review::STATUS_APPROVED){
                    $output['status'] = __('Approved');
                } else if($reviewData[0]['status_id'] == \Magento\Review\Model\Review::STATUS_PENDING){
                    $output['status'] = __('Pending');
                } else if($reviewData[0]['status_id'] == \Magento\Review\Model\Review::STATUS_NOT_APPROVED){
                    $output['status'] = __('Not Approved');
                }
            }

            $output['product_name'] = $_product->getName();

            unset($collection);
            unset($votesCollection);
            unset($model);
            unset($reviewData);
            unset($obj);
            $_product->unsetData();
        } catch (\Exception $e) {
            $this->mp_logHelper->createFileAndWriteLogData(
                    \Knowband\Marketplace\Helper\Log::INFOTYPEERROR, 'Controller Productreview\GetProductReviewDetail::execute()', $e->getMessage()
                );
            $output = $e->getMessage();
        }
        $result = $this->resultJsonFactory->create();
        return $result->setData($output);
    }
}
