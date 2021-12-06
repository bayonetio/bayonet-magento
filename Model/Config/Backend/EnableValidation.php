<?php

namespace Bayonet\BayonetAntiFraud\Model\Config\Backend;

use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Bayonet\BayonetAntiFraud\Helper\GetData;

/**
 * Class ApiModeValidation
 *
 * Validates the API mode value before updating it to the core_config_data table
 */
class EnableValidation extends \Magento\Framework\App\Config\Value
{
    protected $getHelper;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        GetData $getHelper
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection
        );
        $this->getHelper = $getHelper;
    }

    /**
     * Checks that both of the live API keys are already saved in the
     * core_config_data before updating the API mode if the desired mode is the
     * live mode
     */
    public function beforeSave()
    {
        $enabled = $this->getValue();
        $bayoLiveKey = $this->getHelper->getConfigValue('bayonet_live_key');
        $jsLiveKey = $this->getHelper->getConfigValue('js_live_key');

        if (1 === (int)$enabled && (isset($bayoLiveKey) && isset($jsLiveKey))) {
            parent::beforeSave();
        } elseif (1 === (int)$enabled && (!isset($bayoLiveKey) || !isset($jsLiveKey))) {
                throw new \Magento\Framework\Exception\ValidatorException(__(
                    'Cannot enable the module with no pair of API keys saved. Please save your pair of API keys first'
                ));
        }
    }
}
