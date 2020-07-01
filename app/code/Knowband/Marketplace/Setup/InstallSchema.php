<?php 
namespace Knowband\Marketplace\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        
        $table_name = $installer->getTable('vss_mp_seller_entity');
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
        
        
        $table_name = $installer->getTable('vss_mp_product_to_seller');
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
                    CONSTRAINT `FK_SELLER_TO_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('vss_mp_seller_entity')}` (`seller_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_TO_PRODUCT_ID` FOREIGN KEY (`product_id`) REFERENCES `{$installer->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_TO_PRODUCT_WEBSITE` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('store_website')}` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR MAPPING OF PRODUCTS TO SELLER';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('vss_mp_reasons');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `reason_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `reason_type` varchar(255) NOT NULL,
                    `seller_id` int(11) unsigned,
                    `seller_product_id` int(11) unsigned,
                    `seller_review_id` int(11) unsigned,
                    `category_id` int(11) unsigned,
                    `payout_request_id` int(11) unsigned,
                    `reason_text` text NOT NULL,
                    `admin_id` int(11) unsigned,  
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NOT NULL,
                    PRIMARY KEY  (`reason_id`),
                    KEY `FK_REASON_ID` (`reason_id`),
                    KEY `FK_REASON_TO_SELLER_ID` (`seller_id`),
                    KEY `FK_REASON_TO_SELLER_PRODUCT_ID` (`seller_product_id`),
                    KEY `FK_REASON_TO_SELLER_REVIEW_ID` (`seller_review_id`),
                    KEY `FK_REASON_TO_CATEGORY_ID` (`category_id`),
                    KEY `FK_REASON_TO_ADMIN_ID` (`admin_id`),  
                    CONSTRAINT `FK_REASON_TO_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('vss_mp_seller_entity')}` (`seller_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_REASON_TO_SELLER_PRODUCT_ID` FOREIGN KEY (`seller_product_id`) REFERENCES `{$installer->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_REASON_TO_SELLER_REVIEW_ID` FOREIGN KEY (`seller_review_id`) REFERENCES `{$installer->getTable('vss_mp_seller_reviews')}` (`seller_review_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_REASON_TO_CATEGORY_ID` FOREIGN KEY (`category_id`) REFERENCES `{$installer->getTable('catalog_category_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_REASON_TO_ADMIN_ID` FOREIGN KEY (`admin_id`) REFERENCES `{$installer->getTable('admin_user')}` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR SAVING REASONS DATA';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('vss_mp_seller_settings');
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
                    CONSTRAINT `FK_SELLER_SETTINGS_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('vss_mp_seller_entity')}` (`seller_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_SETTINGS_TO_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES `{$installer->getTable('store')}` (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_SETTINGS_TO_WEBSITE_ID` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('store_website')}` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR SAVING SELLER SETTINGS DATA';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('vss_mp_seller_earnings');
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
                    CONSTRAINT `FK_SELLER_EARNINGS_TO_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('vss_mp_seller_entity')}` (`seller_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_EARNINGS_TO_WEBSITE_ID` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('store_website')}` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_EARNINGS_TO_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES `{$installer->getTable('store')}` (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_EARNINGS_TO_ORDER_ID` FOREIGN KEY (`order_id`) REFERENCES `{$installer->getTable('sales_order')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR SELLER EARNINGS DATA';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('vss_mp_seller_transactions');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `row_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `seller_id` int(11) unsigned NOT NULL,
                    `transaction_id` varchar(255) NOT NULL,
                    `website_id` smallint(5) unsigned default NULL,
                    `store_id` smallint(5) unsigned default '0',
                    `type` tinyint(1) unsigned default '1' NOT NULL,
                    `amount` decimal(10,4) NOT NULL,
                    `currency_code` varchar(10) NOT NULL,
                    `comment` text NOT NULL,
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NOT NULL,
                    PRIMARY KEY  (`row_id`),
                    KEY `FK_SELLER_TRANSACTIONS_ROW_ID` (`row_id`),
                    KEY `FK_SELLER_TRANSACTIONS_TO_SELLER_ID` (`seller_id`),
                    CONSTRAINT `FK_SELLER_TRANSACTIONS_TO_WEBSITE_ID` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('store_website')}` (`website_id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `FK_SELLER_TRANSACTIONS_TO_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES `{$installer->getTable('store')}` (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE,  
                    CONSTRAINT `FK_SELLER_TRANSACTIONS_TO_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('vss_mp_seller_entity')}` (`seller_id`) ON DELETE CASCADE ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR SELLER TRANSACTIONS DATA';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('vss_mp_order_item_category');
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
                    CONSTRAINT `FK_SELLER_ORDER_ITEM_TO_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('vss_mp_seller_entity')}` (`seller_id`) ON DELETE CASCADE ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR SELLER ORDER ITEMS DATA';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('vss_mp_track_category_mapping');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `row_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `product_id` int(11) unsigned NOT NULL,
                    `seller_id` int(11) unsigned NOT NULL,
                    `category_id` int(11) unsigned NOT NULL,
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NOT NULL,
                    PRIMARY KEY  (`row_id`),
                    KEY `FK_TRACK_CATEGORY_TO_PRODUCT_ID` (`product_id`),
                    KEY `FK_TRACK_CATEGORY_TO_CUSTOMER_ID` (`seller_id`),
                    KEY `FK_TRACK_CATEGORY_TO_CATEGORY_ID` (`category_id`),
                    CONSTRAINT `FK_TRACK_CATEGORY_TO_CUSTOMER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('customer_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_TRACK_CATEGORY_TO_PRODUCT_ID` FOREIGN KEY (`product_id`) REFERENCES `{$installer->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_TRACK_CATEGORY_TO_CATEGORY_ID` FOREIGN KEY (`category_id`) REFERENCES `{$installer->getTable('catalog_category_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='This table will track the product mapping with category';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('vss_mp_statusaction');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `action` int(11) NOT NULL,
                    `product_id` int(11) unsigned NOT NULL,
                    `seller_id` int(11) unsigned NOT NULL,
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NOT NULL,
                    PRIMARY KEY  (`id`),
                    KEY `FK_STATUS_ACTION_TO_PRODUCT_ID` (`product_id`),
                    KEY `FK_STATUS_ACTION_TO_CUSTOMER_ID` (`seller_id`),  
                    CONSTRAINT `FK_STATUS_ACTION_TO_CUSTOMER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('customer_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `FK_STATUS_ACTION_TO_PRODUCT_ID` FOREIGN KEY (`product_id`) REFERENCES `{$installer->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR SAVING STATUS ACTION DATA';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('vss_mp_seller_shipments');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `seller_shipment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `seller_id` int(11) unsigned NOT NULL,
                    `shipment_id` int(11) unsigned NOT NULL,
                    `created_at` datetime NOT NULL,
                    PRIMARY KEY  (`seller_shipment_id`),
                    KEY `seller_shipment_id` (`seller_shipment_id`),
                    KEY `FK_SELLER_SHIPMENT_TO_SELLER_ID` (`seller_id`),
                    KEY `FK_SELLER_SHIPMENT_TO_SHIPMENT_ID` (`shipment_id`), 
                    CONSTRAINT `FK_SELLER_SHIPMENT_TO_SELLER_ID` FOREIGN KEY (`seller_id`) REFERENCES `{$installer->getTable('vss_mp_seller_entity')}` (`seller_id`) ON DELETE CASCADE,
                    CONSTRAINT `FK_SELLER_SHIPMENT_TO_SHIPMENT_ID` FOREIGN KEY (`shipment_id`) REFERENCES `{$installer->getTable('sales_shipment')}` (`entity_id`) ON DELETE CASCADE
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR TRACK SHIPMENT SELLER WISE';";
            $installer->run($table_script);
        }
        
        $table_name = $installer->getTable('vss_mp_email_templates');
        if ($installer->getConnection()->isTableExists($table_name) != true) {
            $table_script = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                    `template_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `template_subject` varchar(255) NOT NULL,
                    `template_type` varchar(255) NOT NULL,
                    `template_name` varchar(255) NOT NULL,
                    `template_content` text NOT NULL,
                    `template_description` text NOT NULL,
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NOT NULL,
                    PRIMARY KEY  (`template_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='TABLE FOR MARKETPLACE EMAIL TEMPLATES DATA';";
            $installer->run($table_script);
        }
        
        $installer->run("INSERT INTO `{$installer->getTable('vss_mp_email_templates')}` (`template_id`, `template_subject`, `template_type`, `template_name`, `template_content`, `template_description`, `created_at`, `updated_at`) VALUES ('1', 'Marketplace Seller Welcome', 'HTML', 'mp_welcome_seller', '
        <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"#ECF0F1\" background=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/minimal6.png\" style=\"border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;\">
            <tbody>
                <tr>
                    <td class=\"mlTemplateContainer\" align=\"center\">
                        <table align=\"center\" border=\"0\" class=\"mlEmailContainer\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;\">
                            <tbody>
                                <tr>
                                    <td align=\"center\">
                                        <table width=\"640\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\">
                                            <tbody>
                                                <tr>
                                                    <td height=\"30\"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" width=\"640\" class=\"mlContentTable\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"min-width: 640px; width: 640px;\">
                                            <tbody>
                                                <tr>
                                                    <td class=\"mlContentTable\"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55149655\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"></p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55149967\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"center\" class=\"mlContentContainer mlContentImage mlContentHeight\" style=\"padding: 0px 50px 10px 50px;\"><img border=\"0\" src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/ICON.png\" width=\"99\" height=\"99\" class=\"mlContentImage\" style=\"display: block; max-width: 99px;\" /></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150241\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 15px; color: #ffffff; line-height: 25px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 25px; text-align: center;\"><strong>Marketplace Seller Welcome!</strong></p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150427\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <h2 style=\"line-height: 26px; text-decoration: none; font-weight: bold; margin: 0px 0px 10px 0px; font-family: Helvetica; font-size: 20px; color: #000000; text-align: left;\">Hi,</h2>
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px;\">Thank You For Registering as Seller.</p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150501\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td class=\"mlContentContainer\" style=\"padding: 15px 50px 0px 50px;\">
                                                                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-top: 1px solid #d8d8d8;\">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td width=\"100%\" height=\"15px\"></td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148241\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"><strong>Your Details:</strong></p>
                                                                        <p style=\"margin: 0px 0px 20px 0px; line-height: 23px; text-align: center; color: #000;\">Your Email: {{var email}}<br>Your Name: {{var full_name}}</p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148245\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px;\">
                                                                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-top: 1px solid #d8d8d8;\">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td width=\"100%\" height=\"11px\"></td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150703\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px;\">Please feel free to email us with any questions. If you prefer to call with any questions or requests we can be reached at: +xx-xxx xxx xxxx</p>
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px;\">Best regards <br /> <br /> Name <br /> Designation <br /> www.yoursite.com<br /> Ph: +xx-xxx xxx xxxx<br /> Email: support mail</p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148251\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"><span style=\"font-size: 20px;\"><a href=\"#\" style=\"color: #ffffff; text-decoration: none;\">Happy Shopping!</a></span></p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148239\">
                                            <tbody>
                                                <tr>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55153137\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td>
                                                                        <div style=\"text-align: center;\" class=\"html-content\">
                                                                            <ul style=\"display: inline-block; text-align: center; list-style-type: none;\">
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/FB.png\" /> </a></li>
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/TUMBLER.png\" /> </a></li>
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/PINTEREST.png\" /> </a></li>
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/TWITTER.png\" /> </a></li>
                                                                            </ul>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55152047\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\"></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table width=\"640\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"min-width: 640px; width: 640px;\">
                                            <tbody>
                                                <tr>
                                                    <td height=\"30\"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
        ','This template is used when a new customer registers as seller on the store. Note that the account is not yet approved due to which customer/seller has only limited access to the seller account.', NOW(), NOW());");

        $installer->run("INSERT INTO `{$installer->getTable('vss_mp_email_templates')}` (`template_id`, `template_subject`, `template_type`, `template_name`, `template_content`, `template_description`, `created_at`, `updated_at`) VALUES ('4', 'Marketplace Seller Registration Notification', 'HTML', 'mp_seller_registration_notification_admin', '
        <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"#ECF0F1\" background=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/minimal6.png\" style=\"border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;\">
            <tbody>
                <tr>
                    <td class=\"mlTemplateContainer\" align=\"center\">
                        <table align=\"center\" border=\"0\" class=\"mlEmailContainer\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;\">
                            <tbody>
                                <tr>
                                    <td align=\"center\">
                                        <table width=\"640\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\">
                                            <tbody>
                                                <tr>
                                                    <td height=\"30\"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" width=\"640\" class=\"mlContentTable\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"min-width: 640px; width: 640px;\">
                                            <tbody>
                                                <tr>
                                                    <td class=\"mlContentTable\"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55149655\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"></p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55149967\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"center\" class=\"mlContentContainer mlContentImage mlContentHeight\" style=\"padding: 0px 50px 10px 50px;\"><img border=\"0\" src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/ICON.png\" width=\"99\" height=\"99\" class=\"mlContentImage\" style=\"display: block; max-width: 99px;\" /></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150241\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 15px; color: #ffffff; line-height: 25px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 25px; text-align: center;\"><strong>Marketplace Seller Registration Notification!</strong></p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150427\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <h2 style=\"line-height: 26px; text-decoration: none; font-weight: bold; margin: 0px 0px 10px 0px; font-family: Helvetica; font-size: 20px; color: #000000; text-align: left;\">Hi Admin,</h2>
                                                                        <p><span>A customer just registered as a Seller on your website.</span></p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150501\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td class=\"mlContentContainer\" style=\"padding: 15px 50px 0px 50px;\">
                                                                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-top: 1px solid #d8d8d8;\">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td width=\"100%\" height=\"15px\"></td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148241\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"><strong>Customer Details:</strong></p>
                                                                        <p style=\"margin: 0px 0px 20px 0px; line-height: 23px; text-align: center; color: #000;\">Email: {{var customer_email}}<br />Name: {{var full_name}}</p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148245\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px;\">
                                                                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-top: 1px solid #d8d8d8;\">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td width=\"100%\" height=\"11px\"></td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150703\">
                                            <tbody>
                                                <tr>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148251\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"><span style=\"font-size: 20px;\"><a href=\"#\" style=\"color: #ffffff; text-decoration: none;\">Happy Shopping!</a></span></p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148239\">
                                            <tbody>
                                                <tr>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55153137\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td>
                                                                        <div style=\"text-align: center;\" class=\"html-content\">
                                                                            <ul style=\"display: inline-block; text-align: center; list-style-type: none;\">
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/FB.png\" /> </a></li>
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/TUMBLER.png\" /> </a></li>
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/PINTEREST.png\" /> </a></li>
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/TWITTER.png\" /> </a></li>
                                                                            </ul>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55152047\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\"></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table width=\"640\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"min-width: 640px; width: 640px;\">
                                            <tbody>
                                                <tr>
                                                    <td height=\"30\"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
        ','This template is used to notify the admin about the new registration of a customer as seller.', NOW(), NOW())");

        
        $installer->run("INSERT INTO `{$installer->getTable('vss_mp_email_templates')}` (`template_id`, `template_subject`, `template_type`, `template_name`, `template_content`, `template_description`, `created_at`, `updated_at`) VALUES ('11', 'Your Product has been Deleted from {{store_name}}', 'HTML', 'mp_product_delete_notification', '
        <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"#ECF0F1\" background=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/minimal6.png\" style=\"border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;\">
            <tbody>
                <tr>
                    <td class=\"mlTemplateContainer\" align=\"center\">
                        <table align=\"center\" border=\"0\" class=\"mlEmailContainer\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;\">
                            <tbody>
                                <tr>
                                    <td align=\"center\">
                                        <table width=\"640\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\">
                                            <tbody>
                                                <tr>
                                                    <td height=\"30\"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" width=\"640\" class=\"mlContentTable\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"min-width: 640px; width: 640px;\">
                                            <tbody>
                                                <tr>
                                                    <td class=\"mlContentTable\"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55149655\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"></p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55149967\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"center\" class=\"mlContentContainer mlContentImage mlContentHeight\" style=\"padding: 0px 50px 10px 50px;\"><img border=\"0\" src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/ICON.png\" width=\"99\" height=\"99\" class=\"mlContentImage\" style=\"display: block; max-width: 99px;\" /></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150241\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 15px; color: #ffffff; line-height: 25px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 25px; text-align: center;\"><strong>Your Product has been Deleted!</strong></p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150427\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <h2 style=\"line-height: 26px; text-decoration: none; font-weight: bold; margin: 0px 0px 10px 0px; font-family: Helvetica; font-size: 20px; color: #000000; text-align: left;\">Hi There,</h2>
                                                                        <p>Your product has been deleted from the store.</p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150501\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td class=\"mlContentContainer\" style=\"padding: 15px 50px 0px 50px;\">
                                                                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-top: 1px solid #d8d8d8;\">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td width=\"100%\" height=\"15px\"></td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148241\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"><strong>Reason:</strong></p>
                                                                        <p style=\"margin: 0px 0px 20px 0px; line-height: 23px; text-align: center; color: #000;\">{{var reason}}</p>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"><strong>Product Details:</strong></p>
                                                                        <p style=\"margin: 0px 0px 20px 0px; line-height: 23px; text-align: center; color: #000;\">Product Name: {{var product_name}}<br />SKU: {{var product_sku}}<br />Price: {{var product_price}}<br />Stock: {{var product_qty}}</p>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"><strong>Your Details on Store:</strong></p>
                                                                        <p style=\"margin: 0px 0px 20px 0px; line-height: 23px; text-align: center; color: #000;\">Store: {{var shop_title}}<br />Name: {{var seller_name}}<br />Email: {{var seller_email}}<br />Contact: {{var seller_contact}}</p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148245\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td class=\"mlContentContainer\" style=\"padding: 0px 50px 0px 50px;\">
                                                                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-top: 1px solid #d8d8d8;\">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td width=\"100%\" height=\"11px\"></td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55150703\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px;\">Please feel free to email us with any questions. If you prefer to call with any questions or requests we can be reached at: +xx-xxx xxx xxxx</p>
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px;\">Best regards <br /> <br />Name <br />Designation <br />www.yoursite.com<br />Ph: +xx-xxx xxx xxxx<br />Email: support mail</p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#8b67bb\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #8b67bb; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148251\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#8b67bb\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #8b67bb; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\">
                                                                        <p style=\"margin: 0px 0px 10px 0px; line-height: 23px; text-align: center;\"><span style=\"font-size: 20px;\"><a href=\"#\" style=\"color: #ffffff; text-decoration: none;\">Happy Shopping!</a></span></p>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55148239\">
                                            <tbody>
                                                <tr>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55153137\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td>
                                                                        <div style=\"text-align: center;\" class=\"html-content\">
                                                                            <ul style=\"display: inline-block; text-align: center; list-style-type: none;\">
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/FB.png\" /> </a></li>
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/TUMBLER.png\" /> </a></li>
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/PINTEREST.png\" /> </a></li>
                                                                                <li style=\"display: inline-block;\"><a href=\"#\"> <img src=\"https://ps.knowband.com/demo6/16/modules/spinwheel/views/img/admin/email/TWITTER.png\" /> </a></li>
                                                                            </ul>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align=\"center\" border=\"0\" bgcolor=\"#FFFFFF\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" style=\"background: #FFFFFF; min-width: 640px; width: 640px;\" width=\"640\" id=\"ml-block-55152047\">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table width=\"640\" class=\"mlContentTable\" bgcolor=\"#FFFFFF\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"background: #FFFFFF; width: 640px;\">
                                                            <tbody>
                                                                <tr>
                                                                    <td align=\"left\" class=\"mlContentContainer\" style=\"padding: 15px 50px 5px 50px; font-family: Helvetica; font-size: 14px; color: #7f8c8d; line-height: 23px;\"></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table width=\"640\" class=\"mlContentTable\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"min-width: 640px; width: 640px;\">
                                            <tbody>
                                                <tr>
                                                    <td height=\"30\"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
        ','This template is used to notify the seller about the deletion of the product added by the seller.', NOW(), NOW());");
    }
}
