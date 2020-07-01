<?php
/**
 * Khetbaari
 * Module Hariyo/Theme
 *
 * @category  Khetbaari
 * @package   Hariyo/Theme
 * @author    Chet B. Sunar <chet.sunar@javra.com>
 * @copyright 2020 Khetbaari
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 */
 
namespace Hariyo\Theme\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
 

class AddImageToCategoryMenu implements ObserverInterface
{
    /**
     * @var CategoryRepositoryInterface $categoryRepository
     */
    protected $categoryRepository;

    /**@var \Psr\Log\LoggerInterface $logger */
    protected $logger;
 
    /**
     * AddFirstCategoryImageToTopmenu constructor.
     *
     * @param CategoryRepositoryInterface $categoryRepository repository
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->categoryRepository = $categoryRepository;
    }
 
    /**
     * @param Observer $observer Observer object
     */
    public function execute(Observer $observer)
    {
        $transport = $observer->getTransport();
        $html      = $transport->getHtml();
        $menuTree  = $transport->getMenuTree();
 
        $parentLevel = $menuTree->getLevel();
        $childLevel = $parentLevel === null ? 0 : $parentLevel + 1;
 
        $menuId = $menuTree->getId();

        // add image in submenu
        if ($childLevel == 2 && $this->isCategory($menuId)) {
            $html .= '<li class="category_image" style="float: right;width: 300px;margin-left: 200px;margin-top: -80px !important;"><img src="'.$this->getCategoryImage($menuId).'"/></li>';
        }
 
        $transport->setHtml($html);
    }
 
    /**
     * Retrieves the category image for the corresponding child
     *
     * @param string $categoryId Category composed ID
     *
     * @return string
     */
    protected function getCategoryImage($categoryId)
    {
        $this->logger->info($categoryId);
        $categoryIdElements = explode('-', $categoryId);
        $category           = $this->categoryRepository->get(end($categoryIdElements));
        $categoryName       = $category->getImageUrl();
 
        return $categoryName;
    }
 
    /**
     * Check if current menu element corresponds to a category
     *
     * @param string $menuId Menu element composed ID
     *
     * @return string
     */
    protected function isCategory($menuId)
    {
        $menuId = explode('-', $menuId);
 
        return 'category' == array_shift($menuId);
    }
}