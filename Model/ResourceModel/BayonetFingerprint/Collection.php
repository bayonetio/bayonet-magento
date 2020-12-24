<?php

namespace Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetFingerprint;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Defines the Collection for the Bayonet Orders table
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'fingerprint_id';
    protected $_eventPrefix = 'bayonet_antifraud_fingerprint_collection';
    protected $_eventObject = 'fingerprint_collection';

    /**
     * Defines the resource model
     */
    protected function _construct()
    {
        $this->_init(
            \Bayonet\BayonetAntiFraud\Model\BayonetFingerprint::class,
            \Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetFingerprint::class
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
    protected function _toOptionArray($valueField = 'fingerprint_id', $labelField = 'name', $additional = [])
    {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }
}
