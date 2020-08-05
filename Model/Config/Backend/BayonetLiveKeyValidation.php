<?php

namespace Bayonet\BayonetAntiFraud\Model\Config\Backend;

use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Bayonet\BayonetAntiFraud\Helper\KeyValidator;

/**
 * Class BayonetLiveKeyValidation
 *
 * Validates the Bayonet live key before saving it to the core_config_data table
 */
class BayonetLiveKeyValidation extends \Magento\Framework\App\Config\Value
{
    protected $config;
    protected $keyValidator;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        KeyValidator $keyValidator
        )
    {
        $this->config = $config;
        $this->keyValidator = $keyValidator;
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
     * Performs the corresponding validations of the API key before saving
     * to the core_config_data table
     */
    public function beforeSave() {
        $apiKey = $this->getValue();

        if (!empty($apiKey) && '**********' !== $apiKey) {
            $requestBody = [
                'auth' => [
                    'api_key' => $apiKey
                ]
            ];
            $response = $this->keyValidator->validateKey($apiKey, $requestBody, 'bayonet');

            // if the response from the API was successful but the code is not
            // the one expected, then the API key is not valid and an excepction
            // is thrown, otherwise, the process of saving continues.
            if (isset($response->reason_code) && intval($response->reason_code) !== 101) {
                throw new \Magento\Framework\Exception\ValidatorException(__(
                    'Invalid Bayonet live API key. Please check your key and try again'
                ));
            } elseif (isset($response->reason_code) && intval($response->reason_code) === 101) {
                $this->setValue(($this->getValue()));
                parent::beforeSave();
            } elseif (!isset($response->reason_code)) {
                throw new \Magento\Framework\Exception\ValidatorException(__(
                    'An error ocurred while validating the Bayonet live API key. Please try again'
                ));
            }
        } elseif (!empty($apiKey) && '**********' === $apiKey) { // when the merchant doesn't modify an existing key
            $currentApiKey = $this->_config->getValue(
                'bayonetantifraud_general/general/bayonet_live_key',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $this->setValue($currentApiKey);
            parent::beforeSave();
        } elseif (empty($apiKey)) {
            $currentApiMode = $this->_config->getValue(
                'bayonetantifraud_general/general/api_mode',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            if (intval($currentApiMode) === 1) { // to avoid saving an empty live key when the current API mode is set to live
                throw new \Magento\Framework\Exception\ValidatorException(__(
                    'Cannot save an empty live (production) API key when the live (production) mode is enabled'
                ));
            }
        }
    }

    /**
     * Changes the key value (if not empty) to a string of '*'
     */
    public function afterLoad()
    {
        $value = $this->getValue();
        if (!empty($value)) {
            $this->setValue('**********');
        }
        return $this;
    }
}
