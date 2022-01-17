<?php

namespace Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetOrder;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Defines the Collection for the Bayonet Orders table
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'bayonet_id';
    protected $_eventPrefix = 'bayonet_antifraud_orders_collection';
    protected $_eventObject = 'orders_collection';

    /**
     * Defines the resource model
     */
    protected function _construct()
    {
        $this->_init(
            \Bayonet\BayonetAntiFraud\Model\BayonetOrder::class,
            \Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetOrder::class
        );
    }

    /**
     * Get SQL for get record count.
     * Extra GROUP BY strip added.
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(\Magento\Framework\DB\Select::GROUP);
        return $countSelect;
    }
    /**
     * @param string $valueField
     * @param string $labelField
     * @param array $additional
     * @return array
     */
    protected function _toOptionArray($valueField = 'bayonet_id', $labelField = 'name', $additional = [])
    {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }
}
