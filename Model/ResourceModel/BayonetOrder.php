<?php

namespace Bayonet\BayonetAntiFraud\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Defines the Resource Model for the Bayonet Orders table
 */
class BayonetOrder extends AbstractDb
{
    protected $_isPkAutoIncrement = false; // if not added, no rows can be inserted

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('bayonet_antifraud_orders', 'bayonet_id');
    }
}
