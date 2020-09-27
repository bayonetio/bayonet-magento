<?php

namespace Bayonet\BayonetAntiFraud\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        //Adds module's main table
        $tableOrders = $setup->getConnection()->newTable(
            $setup->getTable('bayonet_antifraud_orders')
            )->addColumn(
                'bayonet_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Bayonet ID'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Order ID'
            )->addColumn(
                'bayonet_tracking_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Bayonet Tracking ID'
            )->addColumn(
                'consulting_api',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Consulting API'
            )->addColumn(
                'consulting_api_response',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Consulting API response'
            )->addColumn(
                'feedback_api',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Feedback API'
            )->addColumn(
                'feedback_api_response',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Feedback API response'
            )->addColumn(
                'decision',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Decision'
            )->addColumn(
                'triggered_rules',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                1000,
                ['nullable' => true],
                'Triggered Rules'
            )->addColumn(
                'executed',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'API executed for order'
            )->addColumn(
                'date_added',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Date Added'
            )->setComment("Bayonet Anti-Fraud Orders table");
        $setup->getConnection()->createTable($tableOrders);

        //Adds backfill table
        $tableBackfill = $setup->getConnection()->newTable(
            $setup->getTable('bayonet_antifraud_backfill')
            )->addColumn(
                'backfill_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Backfill ID'
            )->addColumn(
                'backfill_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Backfill Status'
            )->addColumn(
                'processed_orders',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Processed Orders'
            )->addColumn(
                'total_orders',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Total Orders'
            )->addColumn(
                'last_processed_order',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Last Processed Order'
            )->addColumn(
                'last_backfill_order',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Last Backfill Order'
            )->setComment(
                'Bayonet Anti-Fraud Backfill table'
            );
        $setup->getConnection()->createTable($tableBackfill);

        // Adds blocklist table
        $tableBlocklist = $setup->getConnection()->newTable(
            $setup->getTable('bayonet_antifraud_blocklist')
            )->addColumn(
                'blocklist_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Bayonet Blocklist ID'
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Customer ID'
            )->addColumn(
                'email',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Customer Email'
            )->addColumn(
                'whitelist',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Whitelist Status'
            )->addColumn(
                'blocklist',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Blocklist Status'
            )->addColumn(
                'response_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Response Code'
            )->addColumn(
                'response_message',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Response Message'
            )->addColumn(
                'api_mode',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'API Mode'
            )->setComment(
                'Bayonet Anti-Fraud Blocklist/Whitelist table'
            );
        $setup->getConnection()->createTable($tableBlocklist);

        // Adds fingerprint tokens table
        $tableFingerprint = $setup->getConnection()->newTable(
            $setup->getTable('bayonet_antifraud_fingerprint')
            )->addColumn(
                'fingerprint_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Bayonet Fingerprint ID'
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Customer ID'
            )->addColumn(
                'fingerprint_token',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Bayonet Fingerprint Token'
            )->setComment(
                'Bayonet Fingerprint Token table'
            );
        $setup->getConnection()->createTable($tableFingerprint);

        $setup->endSetup();
    }
}