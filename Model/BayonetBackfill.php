<?php

namespace Bayonet\BayonetAntiFraud\Model;

use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;

/**
 * Defines the Model for the Backfill table
 */
class BayonetBackfill extends AbstractModel {

    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Defines resource model
     */
    protected function _construct()
    {
        $this->_init('Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetBackfill');
    }
}
