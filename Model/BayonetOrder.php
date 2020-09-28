<?php

namespace Bayonet\BayonetAntiFraud\Model;

use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;

/**
 * Defines the Model for the Bayonet Orders table
 */
class BayonetOrder extends AbstractModel {

    const CACHE_TAG = 'bayonet_antifraud_orders';
    protected $_cacheTag = 'bayonet_antifraud_orders';
    protected $_eventPrefix = 'bayonet_antifraud_orders';

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
        $this->_init('Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetOrder');
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get entity default values
     *
     * @return array
     */
    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}
