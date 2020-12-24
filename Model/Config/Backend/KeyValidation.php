<?php

namespace Bayonet\BayonetAntiFraud\Model\Config\Backend;

use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Bayonet\BayonetAntiFraud\Helper\RequestHelper;
use \Bayonet\BayonetAntiFraud\Helper\GetData;

/**
 * Class KeyValidation
 *
 * Validates a key before saving it to the core_config_data table
 */
class KeyValidation extends \Magento\Framework\App\Config\Value
{
    protected $requestHelper;
    protected $getHelper;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        RequestHelper $requestHelper,
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
        $this->requestHelper = $requestHelper;
        $this->getHelper = $getHelper;
    }

    /**
     * Performs the corresponding validations of the API key before saving
     * to the core_config_data table
     */
    public function beforeSave()
    {
        $apiKey = $this->getValue();
        $label = $this->translateKeyLabel($this->getData('field_config/label'));
        $fieldId = $this->getData('field_config/id');
        $requestBody = [
            'auth' => []
        ];

        if (!empty($apiKey) && '**********' !== $apiKey) {
            if (strpos($label, 'Bayonet') !== false) {
                $requestBody['auth']['api_key'] = $apiKey;
                $response = $this->requestHelper->consulting($requestBody);

                // if the response from the API was successful but the code is not
                // the one expected, then the API key is not valid and an excepction
                // is thrown, otherwise, the process of saving continues.
                if (isset($response->reason_code) && (int)$response->reason_code !== 101) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'Invalid value for the %1. Please check your key and try again',
                        $label
                    ));
                } elseif (isset($response->reason_code) && (int)$response->reason_code === 101) {
                    $this->setValue(($this->getValue()));
                    parent::beforeSave();
                } elseif (!isset($response->reason_code)) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'An error ocurred while validating the %1. Please try again',
                        $label
                    ));
                }
            } elseif (strpos($label, 'Fingerprint') !== false) {
                $requestBody['auth']['jsKey'] = $apiKey;
                $response = $this->requestHelper->deviceFingerprint($requestBody);

                if (isset($response->reasonCode) && (int)$response->reasonCode !== 51) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'Invalid value for the %1. Please check your key and try again',
                        $label
                    ));
                } elseif (isset($response->reasonCode) && (int)$response->reasonCode === 51) {
                    $this->setValue(($this->getValue()));
                    parent::beforeSave();
                } elseif (!isset($response->reasonCode)) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'An error ocurred while validating the %1. Please try again',
                        $label
                    ));                }
            }
        } elseif (!empty($apiKey) && '**********' === $apiKey) { // when the merchant doesn't modify an existing key
            $currentApiKey = $this->getHelper->getConfigValue($fieldId);

            if (strlen($currentApiKey) === 0) { // to avoid trying to trick the module entering a '**********' string
                throw new \Magento\Framework\Exception\ValidatorException(__(
                    'Invalid value for the %1. Please check your key and try again',
                    $label
                ));
            } else {
                $this->setValue($currentApiKey);
                parent::beforeSave();
            }
        } elseif (empty($apiKey) && strpos($label, 'Live') !== false) {
            $currentApiMode = $this->getHelper->getConfigValue('api_mode');

            if ((int)$currentApiMode === 1) { // to avoid saving an empty live key when the current API mode is set to live
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

    /**
     * Translates the key label (if necessary) to spanish
     *
     * @param string $keyLabel
     * @return string
     */
    private function translateKeyLabel($keyLabel)
    {
        $translatedLabel = '';

        switch ($keyLabel) {
            case 'Bayonet Sandbox (test) Key':
                $translatedLabel = __('Bayonet Sandbox (test) Key');
                break;
            case 'Device Fingerprint Sandbox (test) Key':
                $translatedLabel = __('Device Fingerprint Sandbox (test) Key');
                break;
            case 'Bayonet Live (production) Key':
                $translatedLabel = __('Bayonet Live (production) Key');
                break;
            case 'Device Fingerprint Live (production) Key':
                $translatedLabel = __('Device Fingerprint Live (production) Key');
                break;
        }

        return $translatedLabel;
    }
}
