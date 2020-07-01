<?php 
namespace Hariyo\Marketplace\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        
        $table_name = $installer->getTable('hariyo_seller_entity');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `seller_entity_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `seller_id` int(11) unsigned NOT NULL,
                    `customer_id` int(11) unsigned NOT NULL,
                    `store_id` smallint(5) unsigned default '0',
                    `website_id` smallint(5) unsigned default NULL,
                    `seller_approved` tinyint(1) unsigned default '0',
                    `seller_enabled` tinyint(1) unsigned default '0',
                    `shop_title` varchar(255),
                    `contact_number` varchar(20),
                    `shop_logo` varchar(510),
                    `shop_banner` varchar(510),
                    `shop_address` varchar(510),
                    `shop_country` varchar(255),
                    `description` text,
                    `meta_keywords` text,
                    `meta_description` text,
                    `return_policy` text,
                    `shipping_policy` text,
                    `payment_info` text,
                    `fb_link` varchar(510),
                    `google_link` varchar(510),
                    `twitter_link` varchar(510),
                    `product_limit` smallint(5) unsigned default '0',
                    `register_limit` smallint(5) unsigned default '1',
                    `page_url_key` varchar(255),
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NOT NULL,
                    PRIMARY KEY  (`seller_entity_id`),
                    KEY `seller_customer_entity_id` (`customer_id`),  
                    KEY `seller_id` (`seller_id`),
                    KEY `FK_SELLER_CUSTOMER_ID` (`customer_id`),
                    KEY `FK_SELLER_ENTITY_STORE` (`store_id`),
                    KEY `FK_SELLER_WEBSITE` (`website_id`),
                    KEY `contact_number` (`contact_number`),
                    CONSTRAINT `FK_SELLER_CUSTOMER_ID` FOREIGN KEY (`customer_id`) REFERENCES `{$installer->getTable('customer_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_ENTITY_STORE` FOREIGN KEY (`store_id`) REFERENCES `{$installer->getTable('store')}` (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_WEBSITE` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('store_website')}` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR DETAILS OF THE SELLERS';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('hariyo_seller_products');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `seller_product_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `seller_id` int(11) unsigned NOT NULL,
                    `product_id` int(11) unsigned NOT NULL,
                    `website_id` smallint(5) unsigned default NULL,  
                    `approved` tinyint(1) unsigned NOT NULL,
                    `approved_date` datetime NOT NULL,
                    `disapproved_date` datetime NOT NULL,
                    `created_at` datetime NOT NULL,
                    PRIMARY KEY  (`seller_product_id`),
                    KEY `seller_product_id` (`seller_product_id`),
                    KEY `FK_SELLER_TO_SELLER_ID` (`seller_id`),
                    KEY `FK_SELLER_TO_PRODUCT_ID` (`product_id`),
                    KEY `FK_SELLER_TO_PRODUCT_WEBSITE` (`website_id`),  
                    CONSTRAINT `FK_SELLER_TO_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('hariyo_seller_entity')}` (`seller_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_TO_PRODUCT_ID` FOREIGN KEY (`product_id`) REFERENCES `{$installer->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_TO_PRODUCT_WEBSITE` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('store_website')}` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR MAPPING OF PRODUCTS TO SELLER';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('hariyo_seller_earnings');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `row_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `seller_id` int(11) unsigned NOT NULL,
                    `website_id` smallint(5) unsigned default NULL,
                    `store_id` smallint(5) unsigned default '0',
                    `order_id` int(11) unsigned NOT NULL,
                    `product_count` smallint(5) unsigned NOT NULL,
                    `currency_code` varchar(10) NOT NULL,
                    `commision_percent` decimal(10,4) NOT NULL,  
                    `total_earning` decimal(10,4) NOT NULL,
                    `seller_earning` decimal(10,4) NOT NULL,
                    `admin_comission` decimal(10,4) NOT NULL,
                    `status` tinyint(1) unsigned default '0',
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NOT NULL,
                    PRIMARY KEY  (`row_id`),
                    KEY `FK_SELLER_EARNINGS_ROW_ID` (`row_id`),
                    KEY `FK_SELLER_EARNINGS_TO_SELLER_ID` (`seller_id`),
                    KEY `FK_SELLER_EARNINGS_TO_WEBSITE_ID` (`website_id`),
                    KEY `FK_SELLER_EARNINGS_TO_STORE_ID` (`store_id`),
                    KEY `FK_SELLER_EARNINGS_TO_ORDER_ID` (`order_id`),
                    CONSTRAINT `FK_SELLER_EARNINGS_TO_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('hariyo_seller_entity')}` (`seller_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_EARNINGS_TO_WEBSITE_ID` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('store_website')}` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_EARNINGS_TO_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES `{$installer->getTable('store')}` (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_EARNINGS_TO_ORDER_ID` FOREIGN KEY (`order_id`) REFERENCES `{$installer->getTable('sales_order')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR SELLER EARNINGS DATA';";
            $installer->run($table_script);
        }

        $table_name = $installer->getTable('hariyo_seller_settings');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `setting_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `store_id` smallint(5) unsigned default '0',
                    `website_id` smallint(5) unsigned default NULL,
                    `seller_id` int(11) unsigned NOT NULL,
                    `field_name` varchar(255) NOT NULL,
                    `field_value` varchar(255) NOT NULL,
                    `use_default` tinyint(1) unsigned default '1',
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NOT NULL,
                    PRIMARY KEY  (`setting_id`),
                    KEY `FK_SELLER_SETTINGS_SELLER_ID` (`seller_id`),
                    KEY `FK_SELLER_SETTINGS_TO_STORE_ID` (`store_id`),
                    KEY `FK_SELLER_SETTINGS_TO_WEBSITE_ID` (`website_id`),
                    CONSTRAINT `FK_SELLER_SETTINGS_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('hariyo_seller_entity')}` (`seller_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_SETTINGS_TO_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES `{$installer->getTable('store')}` (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_SETTINGS_TO_WEBSITE_ID` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('store_website')}` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR SAVING SELLER SETTINGS DATA';";
            $installer->run($table_script);
        }
        
        
        $table_name = $installer->getTable('hariyo_seller_item_orders');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `row_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `seller_id` int(11) unsigned NOT NULL,
                    `order_id` int(11) unsigned NOT NULL,
                    `website_id` smallint(5) unsigned default NULL,
                    `store_id` smallint(5) unsigned default '0',
                    `category_id` int(11) unsigned NOT NULL,
                    `order_item_id` int(10) unsigned NOT NULL,
                    `commission` float unsigned NOT NULL,
                    `consider_for_calculations` tinyint(1) unsigned default '1',
                    `created_at` datetime NOT NULL,
                    PRIMARY KEY  (`row_id`),
                    KEY `FK_SELLER_ORDER_ITEM_ROW_ID` (`row_id`),
                    KEY `FK_SELLER_ORDER_ITEM_TO_SELLER_ID` (`seller_id`),
                    KEY `FK_SELLER_ORDER_ITEM_TO_ORDER_ID` (`order_id`),
                    KEY `FK_SELLER_ORDER_ITEM_TO_WEBSITE_ID` (`website_id`),
                    KEY `FK_SELLER_ORDER_ITEM_TO_STORE_ID` (`store_id`),
                    KEY `FK_SELLER_ORDER_ITEM_TO_CATEGORY_ID` (`category_id`),
                    KEY `FK_SELLER_ORDER_ITEM_TO_ORDER_ITEM_ID` (`order_item_id`),
                    CONSTRAINT `FK_SELLER_ORDER_ITEM_TO_ORDER_ID` FOREIGN KEY (`order_id`) REFERENCES `{$installer->getTable('sales_order')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_ORDER_ITEM_TO_CATEGORY_ID` FOREIGN KEY (`category_id`) REFERENCES `{$installer->getTable('catalog_category_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_ORDER_ITEM_TO_ORDER_ITEM_ID` FOREIGN KEY (`order_item_id`) REFERENCES `{$installer->getTable('sales_order_item')}` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_ORDER_ITEM_TO_WEBSITE_ID` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('store_website')}` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_ORDER_ITEM_TO_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES `{$installer->getTable('store')}` (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE,  
                    CONSTRAINT `FK_SELLER_ORDER_ITEM_TO_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('hariyo_seller_entity')}` (`seller_id`) ON DELETE CASCADE ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR SELLER ORDER ITEMS DATA';";
            $installer->run($table_script);
        }
        
        }
}
