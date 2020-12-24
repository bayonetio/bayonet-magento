<?php

namespace Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetBackfill;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Defines the Collection for the Backfill table
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'backfill_id';

    /**
     * Defines model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            \Bayonet\BayonetAntiFraud\Model\BayonetBackfill::class,
            \Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetBackfill::class
        );
    }
}
