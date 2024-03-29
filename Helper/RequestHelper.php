<?php

namespace Bayonet\BayonetAntiFraud\Helper;

use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Bayonet\BayonetAntiFraud\Helper\GetData;

/**
 * Contains the required functions to perform request to the Bayonet/Fingerprint API
 */
class RequestHelper
{
    protected $getHelper;

    public function __construct(GetData $getHelper)
    {
        $this->getHelper = $getHelper;
    }

    /////////////////////// ORDERS ENDPOINTS ////////////////////////////

    /**
     * Performs a consulting request to the Bayonet API
     *
     * @param array $requestBody
     * @return array
     */
    public function consulting($requestBody)
    {
        $consultingResponse = $this->request('sigma/consult', $requestBody, 'bayonet');

        return $consultingResponse;
    }

    /**
     * Performs a feedback historical request to the Bayonet API
     *
     * @param array $requestBody
     * @return array
     */
    public function feedbackHistorical($requestBody)
    {
        $historicalResponse = $this->request('sigma/feedback-historical', $requestBody, 'bayonet');

        return $historicalResponse;
    }

    /**
     * Performs an update transaction request to the Bayonet API
     *
     * @param array $requestBody
     * @return array
     */
    public function updateTransaction($requestBody)
    {
        $updateResponse = $this->request('sigma/update-transaction', $requestBody, 'bayonet');

        return $updateResponse;
    }

    /////////////////////// FINGERPRINT ENDPOINTS ////////////////////////////

    /**
     * Performs a request to the Fingerprint API
     * Used only to validate fingerprint API keys
     *
     * @param string $requestBody
     * @return array
     */
    public function deviceFingerprint($requestBody)
    {
        $deviceFingerprintResponse = $this->request('', $requestBody, 'js');

        return $deviceFingerprintResponse;
    }

    /////////////////////// LISTS ENDPOINTS ////////////////////////////

    /**
     * Defines "whitelist/add" as the call to be executed in the request method
     *
     * @param array $requestBody
     * @return array
     */
    public function addWhitelist($requestBody)
    {
        $listResponse = $this->request('sigma/labels/whitelist/add', $requestBody, 'bayonet');

        return $listResponse;
    }

    /**
     * Defines "whitelist/remove" as the call to be executed in the request method
     *
     * @param array $requestBody
     * @return array
     */
    public function removeWhitelist($requestBody)
    {
        $listResponse = $this->request('sigma/labels/whitelist/remove', $requestBody, 'bayonet');

        return $listResponse;
    }

    /**
     * Defines "block/add" as the call to be executed in the request method
     *
     * @param array $requestBody
     * @return array
     */
    public function addBlocklist($requestBody)
    {
        $listResponse = $this->request('sigma/labels/block/add', $requestBody, 'bayonet');

        return $listResponse;
    }

    /**
     * Defines "block/remove" as the call to be executed in the request method
     *
     * @param array $requestBody
     * @return array
     */
    public function removeBlocklist($requestBody)
    {
        $listResponse = $this->request('sigma/labels/block/remove', $requestBody, 'bayonet');

        return $listResponse;
    }

    // REQUEST FUNCTIONS //

    /**
     * Performs a request to the defined endpoint in the parameters, using the
     * provided request body.
     * The api parameters is used to know whether it is a request to the Bayonet API
     * or the fingerprint API.
     */
    private function request($endpoint, $requestBody, $api)
    {
        $apiVersion = $this->getHelper->getConfigValue('api_version');
        $requestJson = json_encode($requestBody);
        $requestUrl = strcmp($api, 'bayonet') === 0 ? 'https://api.bayonet.io/'.$apiVersion.'/'.$endpoint :
            'https://fingerprinting.bayonet.io/v2/generate-fingerprint-token';
        $ch = curl_init($requestUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $response = json_decode($response);
        curl_close($ch);

        return $response;
    }
}
