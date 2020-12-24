<?php

namespace Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetBlocklist;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Defines the Collection for the Bayonet Blocklist table
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'blocklist_id';
    protected $_eventPrefix = 'bayonet_antifraud_blocklist_collection';
    protected $_eventObject = 'blocklist_collection';

    /**
     * Defines the resource model
     */
    protected function _construct()
    {
        $this->_init(
            \Bayonet\BayonetAntiFraud\Model\BayonetBlocklist::class,
            \Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetBlocklist::class
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
        $countSelect->reset(\Zend_Db_Select::GROUP);
        return $countSelect;
    }
    /**
     * @param string $valueField
     * @param string $labelField
     * @param array $additional
     * @return array
     */
    protected function _toOptionArray($valueField = 'blocklist_id', $labelField = 'name', $additional = [])
    {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }
}
