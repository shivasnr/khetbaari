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
 */
namespace Hariyo\Theme\Rewrite\Block\Html;
 
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

class Topmenu extends \Magento\Theme\Block\Html\Topmenu
{
    /**
     * Recursively generates top menu html from data that is specified in $menuTree
     *
     * @param Node   $menuTree          menu tree
     * @param string $childrenWrapClass children wrap class
     * @param int    $limit             limit
     * @param array  $colBrakes         column brakes
     * @return string
     *
     * @SuppressWarnings(PHPMD)
     */
    protected function _getHtml(
        Node $menuTree,
        $childrenWrapClass,
        $limit,
        $colBrakes = []
    ) {
        $html = parent::_getHtml($menuTree, $childrenWrapClass, $limit, $colBrakes = []);
 
        $transportObject = new DataObject(['html' => $html, 'menu_tree' => $menuTree]);
        $this->_eventManager->dispatch(
            'hariyo_topmenu_node_gethtml_after',
            ['menu' => $this->_menu, 'transport' => $transportObject]
        );
 
        $html = $transportObject->getHtml();
 
        return $html;
    }
}