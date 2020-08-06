<?php

namespace Bayonet\BayonetAntiFraud\Model\Config\Backend;

use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Bayonet\BayonetAntiFraud\Api\RequestHelper;
use \Bayonet\BayonetAntiFraud\Helper\Data;

/**
 * Class KeyValidation
 *
 * Validates a key before saving it to the core_config_data table
 */
class KeyValidation extends \Magento\Framework\App\Config\Value
{
    protected $requestHelper;
    protected $dataHelper;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        RequestHelper $requestHelper,
        Data $dataHelper
    )
    {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection
        );
        $this->requestHelper = $requestHelper;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Performs the corresponding validations of the API key before saving
     * to the core_config_data table
     */
    public function beforeSave()
    {
        $apiKey = $this->getValue();
        $label = $this->getData('field_config/label');
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
                if (isset($response->reason_code) && intval($response->reason_code) !== 101) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'Invalid '.$label.'. Please check your key and try again'
                    ));
                } elseif (isset($response->reason_code) && intval($response->reason_code) === 101) {
                    $this->setValue(($this->getValue()));
                    parent::beforeSave();
                } elseif (!isset($response->reason_code)) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'An error ocurred while validating the '.$label.'. Please try again'
                    ));
                }
            } else if (strpos($label, 'Fingerprint') !== false) {
                $requestBody['auth']['jsKey'] = $apiKey;
                $response = $this->requestHelper->deviceFingerprint($requestBody);

                if (isset($response->reasonCode) && intval($response->reasonCode) !== 51) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'Invalid '.$label.'. Please check your key and try again'
                    ));
                } elseif (isset($response->reasonCode) && intval($response->reasonCode) === 51) {
                    $this->setValue(($this->getValue()));
                    parent::beforeSave();
                } elseif (!isset($response->reasonCode)) {
                    throw new \Magento\Framework\Exception\ValidatorException(__(
                        'An error ocurred while validating the '.$label.'. Please try again'
                    ));
                }
            }
        } elseif (!empty($apiKey) && '**********' === $apiKey) { // when the merchant doesn't modify an existing key
            $currentApiKey = $this->dataHelper->getGeneralConfig($fieldId);

            if (strlen($currentApiKey) === 0) { // to avoid trying to trick the module entering a '**********' string
                throw new \Magento\Framework\Exception\ValidatorException(__(
                    'Invalid '.$label.'. Please check your key and try again'
                ));
            } else {
                $this->setValue($currentApiKey);
                parent::beforeSave();
            }
        } elseif (empty($apiKey) && strpos($label, 'Live') !== false) {
            $currentApiMode = $this->dataHelper->getGeneralConfig('api_mode');

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
