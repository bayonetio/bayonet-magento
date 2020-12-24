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
class ApiVersionValidation extends \Magento\Framework\App\Config\Value
{
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null
    ) {
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
    public function beforeSave()
    {
        $apiVersion = $this->getValue();
        $requestBody = [
            "auth" => [
            ]
        ];

        $ch = curl_init('https://api.bayonet.io/'.$apiVersion.'/sigma/consult');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $response = json_decode($response);
        curl_close($ch);
        
        if (!isset($response)) {
            throw new \Magento\Framework\Exception\ValidatorException(__(
                'This API version is invalid, please try again'
            ));
        }

        parent::beforeSave();
    }
}
