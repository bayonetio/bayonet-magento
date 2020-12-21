<?php

namespace Bayonet\BayonetAntiFraud\Helper\Order;

use \Magento\Quote\Api\CartRepositoryInterface;
use \Bayonet\BayonetAntiFraud\Helper\DirectQuery;
use \Bayonet\BayonetAntiFraud\Model\BayonetFingerprintFactory;

/**
 * Helper class to manage the data of an order object and get the necessary
 * data in the required format for a Bayonet API request
 */
class OrderHelper
{
    protected $order;
    protected $directQuery;
    protected $bayonetFingerprintFactory;
    protected $quoteRepository;

    public function __construct(
        DirectQuery $directQuery,
        BayonetFingerprintFactory $bayonetFingerprintFactory,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->directQuery = $directQuery;
        $this->bayonetFingerprintFactory = $bayonetFingerprintFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Sets the current order to be managed
     *
     * @param \Magento\Sales\Model\Order $processingOrder
     */
    public function setOrder($processingOrder)
    {
        $this->order = $processingOrder;
    }

    /**
     * Gets the current order being managed
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Generates the request body for an order object, depending on the
     * request type, the body will be generated for either a backfill
     * order or a consulting order
     *
     * @param string $requestType
     * @return array
     */
    public function generateRequestBody($requestType)
    {
        $requestBody = [
            'channel' => 'ecommerce',
            'order_id' => $this->getOrder()->getQuoteId(),
            'consumer_internal_id' => $this->getOrder()->getCustomerId(),
            'consumer_name' => $this->getCustomerName(),
            'email' => $this->getOrder()->getCustomerEmail(),
            'telephone' => $this->getTelephone(),
            'billing_address' => $this->getBillingAddress(),
            'shipping_address' => $this->getShippingAddress(),
            'products' => $this->getProducts(),
            'currency_code' => $this->getOrder()->getOrderCurrencyCode(),
            'transaction_amount' => number_format((float)$this->getOrder()->getGrandTotal(), 1, '.', ''),
            'transaction_time' => $this->getTransactionTime($requestType),
        ];

        $paymentDetails = $this->getPaymentDetails($this->getOrder()->getPayment());

        foreach ($paymentDetails as $key => $value) {
            $requestBody[$key] = $value;
        }

        if ($requestType === 'consulting') {
            $bayonetFingerprint = $this->bayonetFingerprintFactory->create();
            $fingerprintToken = $bayonetFingerprint->load($this->getOrder()->getCustomerId(), 'customer_id');
            if (!empty($fingerprintToken->getData())) {
                $requestBody['bayonet_fingerprint_token'] = $fingerprintToken->getData('fingerprint_token');
                $resetTokenData = [
                    'fingerprint_id' => $fingerprintToken->getData('fingerprint_id'),
                    'fingerprint_token' => null
                ];
                $bayonetFingerprint->setData($resetTokenData);
                $bayonetFingerprint->save();
            }
        }

        if ($requestType === 'backfill') {
            $transactionStatus = $this->getTransactionStatus();
            $requestBody['transaction_status'] = $transactionStatus;
        }

        return $requestBody;
    }

    /**
     * Gets the customer name of an order object
     *
     * @return string
     */
    protected function getCustomerName()
    {
        return $this->getOrder()->getCustomerFirstname().' '.$this->getOrder()->getCustomerLastname();
    }

    /**
     * Generates an address in the required format for the Bayonet API request using
     * the provided address object
     *
     * @param array $address
     * @return array
     */
    protected function generateAddress($address)
    {
        if (!$address) {
            return null;
        }

        $generatedAddress = [];
        $street = $address->getStreet();
        $generatedAddress['line_1'] = ($street !== null && array_key_exists('0', $street)) ? $street['0'] : null;
        $generatedAddress['line_2'] = ($street !== null && array_key_exists('1', $street)) ? $street['1'] : null;
        $generatedAddress['city'] = $address->getCity();
        $generatedAddress['state'] = $address->getRegion();
        $generatedAddress['country'] = $this->convertCountryCode($address->getCountryId());
        $generatedAddress['zip_code'] = $address->getPostCode();

        return $generatedAddress;
    }

    /**
     * Gets the billing address of an order object
     */
    protected function getBillingAddress()
    {
        $billingAddress = $this->getOrder()->getBillingAddress();

        return $this->generateAddress($billingAddress);
    }

    /**
     * Gets the shipping address of an order object
     */
    protected function getShippingAddress()
    {
        $shippingAddress = $this->getOrder()->getShippingAddress();

        return $this->generateAddress($shippingAddress);
    }

    /**
     * Gets the telephone (if exists) out of the billing address,
     * it also removes any special characters from the string
     */
    protected function getTelephone()
    {
        $telephoneData = $this->getOrder()->getBillingAddress()->getTelephone();
        $telephoneForRequest = $telephoneData !== null ? preg_replace("/[^0-9]/", "", $telephoneData) : null;

        return $telephoneForRequest;
    }

    /**
     * Gets the products of an order object and adds them to an array.
     * Each product contains its ID, name & price
     */
    protected function getProducts()
    {
        $orderItems = $this->getOrder()->getAllItems();
        $products_list = [];

        foreach ($orderItems as $item) {
            $products_list[] = [
                "product_id" => $item->getProductId(),
                "product_name" => $item->getName(),
                "product_price" => number_format((float)$item->getPrice(), 1, '.', '')
            ];
        }

        return $products_list;
    }

    /**
     * Gets the payment gateway code of an order/quote
     *
     * @param array $paymentData
     * @return string
     */
    protected function getPaymentGateway($paymentData)
    {
        $paymentGateway = $paymentData->getMethodInstance()->getCode();

        return $paymentGateway;
    }

    /**
     * Gets the payment details of an order/quote.
     * It will first check if the method is offline or paypal, if none
     * of this is true, it will call the definePaymentDetails function
     * to try and map the payment details
     *
     * @param array $paymentData
     * @return array
     */
    protected function getPaymentDetails($paymentData)
    {
        $paymentDetails = [];

        if ($paymentData->getMethodInstance()->isOffline()) {
            $paymentDetails['payment_method'] = 'offline';
            $paymentDetails['payment_gateway'] = $this->getPaymentGateway($paymentData);
        } elseif (strpos(strtolower($this->getPaymentGateway($paymentData)), 'paypal') !== false) {
            $paymentDetails['payment_method'] = 'paypal';
            $paymentDetails['payment_gateway'] = 'paypal';
        } else {
            $paymentDetails = $this->definePaymentDetails($paymentData);
        }

        return $paymentDetails;
    }

    /**
     * Defines the payment details to decide how to map them.
     * It first checks if the payment gateway is a gateway that the
     * module handles and then calls either the checkForCard or
     * mapPaymentDetails function depending on whether the gateway
     * accepts cards or not
     *
     * @param array $paymentData
     * @return array
     */
    private function definePaymentDetails($paymentData)
    {
        $paymentDetails = [];
        $paymentGateway = $this->getPaymentGateway($paymentData);

        // if the payment gateway has been removed from the store
        if (strpos($paymentGateway, 'substitution') !== false) {
            $paymentGateway = $this->directQuery->getPaymentGateway($this->getOrder()->getId());
        }

        if (strpos($paymentGateway, 'conekta') !== false) {
            $paymentDetails = $this->checkForCard($paymentGateway, 'conekta');
        } elseif (strpos($paymentGateway, 'openpay') !== false) {
            $paymentDetails = $this->checkForCard($paymentGateway, 'openpay');
        } elseif (strpos($paymentGateway, 'braintree') !== false) {
            $paymentDetails = $this->mapPaymentDetails('braintree');
        } elseif (strpos($paymentGateway, 'stripe') !== false) {
            $paymentDetails = $this->checkForCard($paymentGateway, 'stripe');
        } elseif (strpos($paymentGateway, 'mercadopago') !== false) {
            $paymentDetails = $this->mapPaymentDetails('mercadopago');
        }

        return $paymentDetails;
    }

    /**
     * Checks if the payment method of the provided payment gateway
     * is using card to then call the mapPaymentDetails and get the
     * corresponding payment details
     *
     * @param string $paymentGatewayCode
     * @param string $gateway
     * @return array
     */
    private function checkForCard($paymentGatewayCode, $gateway)
    {
        $paymentDetails = [];
        $ccCodes = ['cc', 'card', 'tarjeta'];

        if (strpos($paymentGatewayCode, 'stripe') !== false) {
            if (array_key_exists('save_card', $this->getOrder()->getPayment()->getAdditionalInformation())) {
                $paymentDetails = $this->mapPaymentDetails($gateway);
            } elseif (strpos($paymentGatewayCode, 'oxxo') !== false) {
                $paymentDetails['payment_method'] = 'offline';
                $paymentDetails['payment_gateway'] = $paymentGatewayCode;
            }
        } else {
            foreach ($ccCodes as $code) {
                if (strpos(strtolower($paymentGatewayCode), $code) !== false) {
                    $paymentDetails = $this->mapPaymentDetails($gateway);
                    break;
                }
            }
        }

        return $paymentDetails;
    }

    /**
     * Maps the payment details of an order/quote depending on the
     * provided gateway.
     * Only conekta stores both the bin & last 4 digits of the cards, thus,
     * it is the only one having the 'card_bin' & 'card_last_4' details;
     * openpay, braintree, stripe and mercadopago will be handled as tokenized card
     *
     * @param string $paymentGateway
     * @return array
     */
    private function mapPaymentDetails($paymentGateway)
    {
        $paymentDetails = [];

        switch ($paymentGateway) {
            case 'conekta':
                $paymentDetails['payment_gateway'] = 'conekta';
                $paymentDetails['payment_method'] = 'card';
                $paymentDetails['card_bin'] = $this->getOrder()->getPayment()->getAdditionalInformation()['additional_data']['cc_bin'];
                $paymentDetails['card_last_4'] = $this->getOrder()->getPayment()->getAdditionalInformation()['additional_data']['cc_last_4'];
                break;
            case 'openpay':
                $paymentDetails['payment_gateway'] = 'openpay';
                $paymentDetails['payment_method'] = 'tokenized_card';
                break;
            case 'braintree':
                $paymentDetails['payment_gateway'] = 'braintree';
                $paymentDetails['payment_method'] = 'tokenized_card';
                break;
            case 'stripe':
                $paymentDetails['payment_gateway'] = 'stripe';
                $paymentDetails['payment_method'] = 'tokenized_card';
                break;
            case 'mercadopago':
                $paymentDetails['payment_gateway'] = 'mercadopago';
                $paymentDetails['payment_method'] = 'tokenized_card';
                break;
        }

        return $paymentDetails;
    }

    /**
     * Gets the status of an order. This is done by mapping the
     * order's state to a valid transaction status for the API
     *
     * @return string
     */
    public function getTransactionStatus()
    {
        $orderState = $this->getOrder()->getState();
        $successStates = ['processing', 'complete', 'closed'];
        $pendingStates = ['new', 'pending', 'pending_payment', 'holded'];

        foreach ($pendingStates as $state) {
            if ($orderState === $state) {
                return 'pending';
            }
        }

        foreach ($successStates as $state) {
            if ($orderState === $state) {
                return 'success';
            }
        }

        if ($orderState === 'canceled') {
            return 'cancelled';
        }

        if ($orderState === 'payment_review') {
            $orderStatus = $this->getOrder()->getStatus();
            if (strpos($orderStatus, 'fraud') !== false) {
                return 'suspected_fraud';
            }

            return 'pending';
        }
    }

    /**
     * Gets the transaction time of an order.
     * The time retrieved will depend on the request type, if the
     * generated request body is for a backfill order, then it will
     * retrieve the 'created_at' value, meanwhile, if the request body is
     * for a consulting request, it will retrieve the 'updated_at' value.
     * The retrieved date will be translated to a Unix timestamp
     *
     * @param string $requestType
     * @return string
     */
    protected function getTransactionTime($requestType)
    {
        if ($requestType === 'consulting') {
            $quote = $this->quoteRepository->get($this->getOrder()->getQuoteId());
            return strtotime($quote->getData('updated_at'));
        } elseif ($requestType === 'backfill') {
            return strtotime($this->getOrder()->getData('created_at'));
        }
    }

    /**
     * Converts the country codes to 3-letter ISO codes
     * https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
     *
     * @param string $country
     * @return string
     */
    protected function convertCountryCode($country)
    {
        $countries = [
            'AF' => 'AFG', //Afghanistan
            'AX' => 'ALA', //&#197;land Islands
            'AL' => 'ALB', //Albania
            'DZ' => 'DZA', //Algeria
            'AS' => 'ASM', //American Samoa
            'AD' => 'AND', //Andorra
            'AO' => 'AGO', //Angola
            'AI' => 'AIA', //Anguilla
            'AQ' => 'ATA', //Antarctica
            'AG' => 'ATG', //Antigua and Barbuda
            'AR' => 'ARG', //Argentina
            'AM' => 'ARM', //Armenia
            'AW' => 'ABW', //Aruba
            'AU' => 'AUS', //Australia
            'AT' => 'AUT', //Austria
            'AZ' => 'AZE', //Azerbaijan
            'BS' => 'BHS', //Bahamas
            'BH' => 'BHR', //Bahrain
            'BD' => 'BGD', //Bangladesh
            'BB' => 'BRB', //Barbados
            'BY' => 'BLR', //Belarus
            'BE' => 'BEL', //Belgium
            'BZ' => 'BLZ', //Belize
            'BJ' => 'BEN', //Benin
            'BM' => 'BMU', //Bermuda
            'BT' => 'BTN', //Bhutan
            'BO' => 'BOL', //Bolivia
            'BQ' => 'BES', //Bonaire, Saint Estatius and Saba
            'BA' => 'BIH', //Bosnia and Herzegovina
            'BW' => 'BWA', //Botswana
            'BV' => 'BVT', //Bouvet Islands
            'BR' => 'BRA', //Brazil
            'IO' => 'IOT', //British Indian Ocean Territory
            'BN' => 'BRN', //Brunei
            'BG' => 'BGR', //Bulgaria
            'BF' => 'BFA', //Burkina Faso
            'BI' => 'BDI', //Burundi
            'KH' => 'KHM', //Cambodia
            'CM' => 'CMR', //Cameroon
            'CA' => 'CAN', //Canada
            'CV' => 'CPV', //Cape Verde
            'KY' => 'CYM', //Cayman Islands
            'CF' => 'CAF', //Central African Republic
            'TD' => 'TCD', //Chad
            'CL' => 'CHL', //Chile
            'CN' => 'CHN', //China
            'CX' => 'CXR', //Christmas Island
            'CC' => 'CCK', //Cocos (Keeling) Islands
            'CO' => 'COL', //Colombia
            'KM' => 'COM', //Comoros
            'CG' => 'COG', //Congo
            'CD' => 'COD', //Congo, Democratic Republic of the
            'CK' => 'COK', //Cook Islands
            'CR' => 'CRI', //Costa Rica
            'CI' => 'CIV', //Côte d\'Ivoire
            'HR' => 'HRV', //Croatia
            'CU' => 'CUB', //Cuba
            'CW' => 'CUW', //Curaçao
            'CY' => 'CYP', //Cyprus
            'CZ' => 'CZE', //Czech Republic
            'DK' => 'DNK', //Denmark
            'DJ' => 'DJI', //Djibouti
            'DM' => 'DMA', //Dominica
            'DO' => 'DOM', //Dominican Republic
            'EC' => 'ECU', //Ecuador
            'EG' => 'EGY', //Egypt
            'SV' => 'SLV', //El Salvador
            'GQ' => 'GNQ', //Equatorial Guinea
            'ER' => 'ERI', //Eritrea
            'EE' => 'EST', //Estonia
            'ET' => 'ETH', //Ethiopia
            'FK' => 'FLK', //Falkland Islands
            'FO' => 'FRO', //Faroe Islands
            'FJ' => 'FIJ', //Fiji
            'FI' => 'FIN', //Finland
            'FR' => 'FRA', //France
            'GF' => 'GUF', //French Guiana
            'PF' => 'PYF', //French Polynesia
            'TF' => 'ATF', //French Southern Territories
            'GA' => 'GAB', //Gabon
            'GM' => 'GMB', //Gambia
            'GE' => 'GEO', //Georgia
            'DE' => 'DEU', //Germany
            'GH' => 'GHA', //Ghana
            'GI' => 'GIB', //Gibraltar
            'GR' => 'GRC', //Greece
            'GL' => 'GRL', //Greenland
            'GD' => 'GRD', //Grenada
            'GP' => 'GLP', //Guadeloupe
            'GU' => 'GUM', //Guam
            'GT' => 'GTM', //Guatemala
            'GG' => 'GGY', //Guernsey
            'GN' => 'GIN', //Guinea
            'GW' => 'GNB', //Guinea-Bissau
            'GY' => 'GUY', //Guyana
            'HT' => 'HTI', //Haiti
            'HM' => 'HMD', //Heard Island and McDonald Islands
            'VA' => 'VAT', //Holy See (Vatican City State)
            'HN' => 'HND', //Honduras
            'HK' => 'HKG', //Hong Kong
            'HU' => 'HUN', //Hungary
            'IS' => 'ISL', //Iceland
            'IN' => 'IND', //India
            'ID' => 'IDN', //Indonesia
            'IR' => 'IRN', //Iran
            'IQ' => 'IRQ', //Iraq
            'IE' => 'IRL', //Republic of Ireland
            'IM' => 'IMN', //Isle of Man
            'IL' => 'ISR', //Israel
            'IT' => 'ITA', //Italy
            'JM' => 'JAM', //Jamaica
            'JP' => 'JPN', //Japan
            'JE' => 'JEY', //Jersey
            'JO' => 'JOR', //Jordan
            'KZ' => 'KAZ', //Kazakhstan
            'KE' => 'KEN', //Kenya
            'KI' => 'KIR', //Kiribati
            'KP' => 'PRK', //Korea, Democratic People\'s Republic of
            'KR' => 'KOR', //Korea, Republic of (South)
            'KW' => 'KWT', //Kuwait
            'KG' => 'KGZ', //Kyrgyzstan
            'LA' => 'LAO', //Laos
            'LV' => 'LVA', //Latvia
            'LB' => 'LBN', //Lebanon
            'LS' => 'LSO', //Lesotho
            'LR' => 'LBR', //Liberia
            'LY' => 'LBY', //Libya
            'LI' => 'LIE', //Liechtenstein
            'LT' => 'LTU', //Lithuania
            'LU' => 'LUX', //Luxembourg
            'MO' => 'MAC', //Macao S.A.R., China
            'MK' => 'MKD', //Macedonia
            'MG' => 'MDG', //Madagascar
            'MW' => 'MWI', //Malawi
            'MY' => 'MYS', //Malaysia
            'MV' => 'MDV', //Maldives
            'ML' => 'MLI', //Mali
            'MT' => 'MLT', //Malta
            'MH' => 'MHL', //Marshall Islands
            'MQ' => 'MTQ', //Martinique
            'MR' => 'MRT', //Mauritania
            'MU' => 'MUS', //Mauritius
            'YT' => 'MYT', //Mayotte
            'MX' => 'MEX', //Mexico
            'FM' => 'FSM', //Micronesia
            'MD' => 'MDA', //Moldova
            'MC' => 'MCO', //Monaco
            'MN' => 'MNG', //Mongolia
            'ME' => 'MNE', //Montenegro
            'MS' => 'MSR', //Montserrat
            'MA' => 'MAR', //Morocco
            'MZ' => 'MOZ', //Mozambique
            'MM' => 'MMR', //Myanmar
            'NA' => 'NAM', //Namibia
            'NR' => 'NRU', //Nauru
            'NP' => 'NPL', //Nepal
            'NL' => 'NLD', //Netherlands
            'AN' => 'ANT', //Netherlands Antilles
            'NC' => 'NCL', //New Caledonia
            'NZ' => 'NZL', //New Zealand
            'NI' => 'NIC', //Nicaragua
            'NE' => 'NER', //Niger
            'NG' => 'NGA', //Nigeria
            'NU' => 'NIU', //Niue
            'NF' => 'NFK', //Norfolk Island
            'MP' => 'MNP', //Northern Mariana Islands
            'NO' => 'NOR', //Norway
            'OM' => 'OMN', //Oman
            'PK' => 'PAK', //Pakistan
            'PW' => 'PLW', //Palau
            'PS' => 'PSE', //Palestinian Territory
            'PA' => 'PAN', //Panama
            'PG' => 'PNG', //Papua New Guinea
            'PY' => 'PRY', //Paraguay
            'PE' => 'PER', //Peru
            'PH' => 'PHL', //Philippines
            'PN' => 'PCN', //Pitcairn
            'PL' => 'POL', //Poland
            'PT' => 'PRT', //Portugal
            'PR' => 'PRI', //Puerto Rico
            'QA' => 'QAT', //Qatar
            'RE' => 'REU', //Reunion
            'RO' => 'ROU', //Romania
            'RU' => 'RUS', //Russia
            'RW' => 'RWA', //Rwanda
            'BL' => 'BLM', //Saint Barth&eacute;lemy
            'SH' => 'SHN', //Saint Helena
            'KN' => 'KNA', //Saint Kitts and Nevis
            'LC' => 'LCA', //Saint Lucia
            'MF' => 'MAF', //Saint Martin (French part)
            'SX' => 'SXM', //Sint Maarten / Saint Matin (Dutch part)
            'PM' => 'SPM', //Saint Pierre and Miquelon
            'VC' => 'VCT', //Saint Vincent and the Grenadines
            'WS' => 'WSM', //Samoa
            'SM' => 'SMR', //San Marino
            'ST' => 'STP', //S&atilde;o Tom&eacute; and Pr&iacute;ncipe
            'SA' => 'SAU', //Saudi Arabia
            'SN' => 'SEN', //Senegal
            'RS' => 'SRB', //Serbia
            'SC' => 'SYC', //Seychelles
            'SL' => 'SLE', //Sierra Leone
            'SG' => 'SGP', //Singapore
            'SK' => 'SVK', //Slovakia
            'SI' => 'SVN', //Slovenia
            'SB' => 'SLB', //Solomon Islands
            'SO' => 'SOM', //Somalia
            'ZA' => 'ZAF', //South Africa
            'GS' => 'SGS', //South Georgia/Sandwich Islands
            'SS' => 'SSD', //South Sudan
            'ES' => 'ESP', //Spain
            'LK' => 'LKA', //Sri Lanka
            'SD' => 'SDN', //Sudan
            'SR' => 'SUR', //Suriname
            'SJ' => 'SJM', //Svalbard and Jan Mayen
            'SZ' => 'SWZ', //Swaziland
            'SE' => 'SWE', //Sweden
            'CH' => 'CHE', //Switzerland
            'SY' => 'SYR', //Syria
            'TW' => 'TWN', //Taiwan
            'TJ' => 'TJK', //Tajikistan
            'TZ' => 'TZA', //Tanzania
            'TH' => 'THA', //Thailand
            'TL' => 'TLS', //Timor-Leste
            'TG' => 'TGO', //Togo
            'TK' => 'TKL', //Tokelau
            'TO' => 'TON', //Tonga
            'TT' => 'TTO', //Trinidad and Tobago
            'TN' => 'TUN', //Tunisia
            'TR' => 'TUR', //Turkey
            'TM' => 'TKM', //Turkmenistan
            'TC' => 'TCA', //Turks and Caicos Islands
            'TV' => 'TUV', //Tuvalu
            'UG' => 'UGA', //Uganda
            'UA' => 'UKR', //Ukraine
            'AE' => 'ARE', //United Arab Emirates
            'GB' => 'GBR', //United Kingdom
            'US' => 'USA', //United States
            'UM' => 'UMI', //United States Minor Outlying Islands
            'UY' => 'URY', //Uruguay
            'UZ' => 'UZB', //Uzbekistan
            'VU' => 'VUT', //Vanuatu
            'VE' => 'VEN', //Venezuela
            'VN' => 'VNM', //Vietnam
            'VG' => 'VGB', //Virgin Islands, British
            'VI' => 'VIR', //Virgin Island, U.S.
            'WF' => 'WLF', //Wallis and Futuna
            'EH' => 'ESH', //Western Sahara
            'YE' => 'YEM', //Yemen
            'ZM' => 'ZMB', //Zambia
            'ZW' => 'ZWE', //Zimbabwe
        ];
        $iso_code = isset($countries[$country]) ? $countries[$country] : $country;

        return $iso_code;
    }
}
