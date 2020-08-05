<?php

namespace Bayonet\BayonetAntiFraud\Model\Config\Backend;

use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Bayonet\BayonetAntiFraud\Helper\Data;

/**
 * Class ApiModeValidation
 *
 * Validates the API mode value before updating it to the core_config_data table
 */
class ApiModeValidation extends \Magento\Framework\App\Config\Value
{
    protected $config;
    protected $helperData;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        Data $helperData
    )
    {
        $this->config = $config;
        $this->helperData = $helperData;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection
        );
    }

    /**
     * Checks that both of the live API keys are already saved in the
     * core_config_data before updating the API mode if the desired mode is the
     * live mode
     */
    public function beforeSave() {
        $apiMode = $this->getValue();
        $bayoLiveKey = $this->helperData->getGeneralConfig('bayonet_live_key');
        $jsLiveKey = $this->helperData->getGeneralConfig('js_live_key');

        if (1 === intval($apiMode) && (empty($bayoLiveKey) || empty($jsLiveKey))) {
            throw new \Magento\Framework\Exception\ValidatorException(__(
                'Cannot set the API mode to live (production) with no live (production) API keys saved. Please save your live (production) API keys first.'
            ));
        }

        parent::beforeSave();
    }
}
