<?php

namespace Bayonet\BayonetAntiFraud\Model;

use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;

/**
 * Defines the Model for the Bayonet Blocklist table
 */
class BayonetBlocklist extends AbstractModel
{
    const CACHE_TAG = 'bayonet_antifraud_blocklist';
    protected $_cacheTag = 'bayonet_antifraud_blocklist';
    protected $_eventPrefix = 'bayonet_antifraud_blocklist';

    const API_MODE_SANDBOX = 0;
    const API_MODE_LIVE = 1;
    const ADDED_LIST = 1;
    const NOT_ADDED_LIST = 0;

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
        $this->_init(\Bayonet\BayonetAntiFraud\Model\ResourceModel\BayonetBlocklist::class);
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

    /**
     * Prepares available API modes for the listing grid
     *
     * @return array
     */
    public function getAvailableApiModes()
    {
        return [self::API_MODE_SANDBOX => __('Sandbox (test)'), self::API_MODE_LIVE => __('Live (production)')];
    }

    /**
     * Prepares available list statuses for the listing in the grid
     *
     * @return array
     */
    public function getAvailableListStatuses()
    {
        return [self::ADDED_LIST => __('Added'), self::NOT_ADDED_LIST=> __('Not added')];
    }
}
