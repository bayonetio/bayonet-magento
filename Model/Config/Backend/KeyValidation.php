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
        $invalidBayonet = [ 12, 13, 15 ];
        $invalidJS = [ 12, 15, 16];
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

                // if the response from the API was successful and the code is
                // the one expected the process of saving continues, otherwise,
                // the API key is not valid and an excepction is thrown,
                // otherwise, the process of saving continues.
                if (isset($response->reason_code) && (int)$response->reason_code === 101) {
                    $this->setValue(($this->getValue()));
                    parent::beforeSave();
                } elseif (isset($response->reason_code) && (int)$response->reason_code === 12) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'Invalid value for the %1. Please check your key and try again',
                        $label
                    ));
                } elseif (isset($response->reason_code) && (int)$response->reason_code === 13) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        "%1: Source IP is not valid, please add your IP to the whitelist in Bayonet's console",
                        $label
                    ));
                } elseif (isset($response->reason_code) && (int)$response->reason_code === 15) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        "%1: The key you entered has expired, please generate a new key from Bayonet's console",
                        $label
                    ));
                } elseif (!isset($response->reason_code) || (isset($response->reason_code) &&
                    !in_array((int)$response->reason_code, $invalidBayonet))) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'An error ocurred while validating the %1. Please try again',
                        $label
                    ));
                }
            } elseif (strpos($label, 'Fingerprint') !== false) {
                $requestBody['auth']['jsKey'] = $apiKey;
                $response = $this->requestHelper->deviceFingerprint($requestBody);

                if (isset($response->reasonCode) && (int)$response->reasonCode === 51) {
                    $this->setValue(($this->getValue()));
                    parent::beforeSave();
                } elseif (isset($response->reasonCode) && (int)$response->reasonCode === 12) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'Invalid value for the %1. Please check your key and try again',
                        $label
                    ));
                } elseif (isset($response->reasonCode) && (int)$response->reasonCode === 15) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        "%1: The key you entered has expired, please generate a new key from Bayonet's console",
                        $label
                    ));
                } elseif (isset($response->reasonCode) && (int)$response->reasonCode === 16) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        "%1: Store domain is not registered, please add your store domain to the whitelist in Bayonet's console",
                        $label
                    ));
                } elseif (!isset($response->reasonCode) || (isset($response->reasonCode) &&
                    !in_array((int)$response->reasonCode, $invalidJS))) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'An error ocurred while validating the %1. Please try again',
                        $label
                    ));
                }
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
            $enabled = $this->getHelper->getConfigValue('enable');

            if ((int)$enabled === 1) { // to avoid saving an empty live key when the module is enabled
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
