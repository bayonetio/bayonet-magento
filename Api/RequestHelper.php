<?php

namespace Bayonet\BayonetAntiFraud\Api;

/**
 * Class RequestHelper
 *
 * Contains the required functions to perform request to the Bayonet/Fingerprint API
 */
class RequestHelper
{
    /**
     * Performs a consulting request to the Bayonet API
     */
    public function consulting($requestBody)
    {
        $consultingResponse = $this->request('sigma/consult', $requestBody, 'bayonet');

        return $consultingResponse;
    }

    /**
     * Performs a feedback historical request to the Bayonet API
     */
    public function feedbackHistorical($requestBody)
    {
        $historicalResponse = $this->request('sigma/feedback-historical', $requestBody, 'bayonet');

        return $historicalResponse;
    }

    /**
     * Performs a request to the Fingerprint API
     * Used only to validate fingerprint API keys
     */
    public function deviceFingerprint($requestBody)
    {
        $deviceFingerprintResponse = $this->request('', $requestBody, 'js');

        return $deviceFingerprintResponse;
    }

    /**
     * Performs a request to the defined endpoint in the parameters, using the
     * provided request body.
     * The api parameters is used to know whether it is a request to the Bayonet API
     * or the fingerprint API.
     */
    private function request($endpoint, $requestBody, $api)
    {
        $requestJson = json_encode($requestBody);
        $requestUrl = strcmp($api, 'bayonet') === 0 ? 'https://api.bayonet.io/v2/'.$endpoint : 'https://fingerprinting.bayonet.io/v2/generate-fingerprint-token';
        $ch = curl_init($requestUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json')
        );

        $response = curl_exec($ch);
        $response = json_decode($response);
        curl_close($ch);

        return $response;
    }
}