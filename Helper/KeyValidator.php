<?php

namespace Bayonet\BayonetAntiFraud\Helper;

class KeyValidator
{
    public function validateKey($key, $requestBody, $api)
    {
        $requestUrl = strcmp($api, 'bayonet') === 0 ? 'https://api.bayonet.io/v2/sigma/consult' : 'https://fingerprinting.bayonet.io/v2/generate-fingerprint-token';
        $requestBodyEncoded = json_encode($requestBody);
        $ch = curl_init($requestUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBodyEncoded);
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
