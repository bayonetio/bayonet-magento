<?php

namespace Bayonet\BayonetAntiFraud\Block;

use \Magento\Framework\View\Element\Template\Context;
use \Magento\Framework\View\Element\Template;
use \Bayonet\BayonetAntiFraud\Helper\DirectQuery;

/**
 * Block class used to get data in the Fingerprint template
 */
class Fingerprint extends Template
{
    protected $_isScopePrivate = false;
    protected $directQuery;
    protected $httpContext;

    public function __construct(
        Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        DirectQuery $directQuery,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->directQuery = $directQuery;
        $this->httpContext = $httpContext;
    }

    /**
     * Gets the controller URL
     * 
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('device/fingerprint/index');
    }

    /**
     * Gets the current value of the 'enable' configuration field
     * 
     * @return int
     */
    public function getEnabled()
    {
        return intval($this->directQuery->customQuery('core_config_data', 'value', array('path' => 'bayonetantifraud_general/general/enable')));
    }

    /**
     * Gets the API key in order to generate the fingerprint token
     * It checks whether the the API mode is in live or sandbox in order
     * to return the corresponding key
     * 
     * @return string
     */
    public function getApiKey()
    {
        $apiMode = intval($this->directQuery->customQuery('core_config_data', 'value', array('path' => 'bayonetantifraud_general/general/api_mode')));
        $apiKey = $apiMode === 1 ? $this->directQuery->customQuery('core_config_data', 'value', array('path' => 'bayonetantifraud_general/general/js_live_key')) : $this->directQuery->customQuery('core_config_data', 'value', array('path' => 'bayonetantifraud_general/general/js_sandbox_key'));
        
        return $apiKey;
    }

    /**
     * Gets a bool that indicates whether a customer is logged in or not
     * 
     * @return bool
     */
    public function getCustomerIsLoggedIn()
    {
    	return (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * Gets the ID of the logged in customer
     * 
     * @return string
     */
    public function getCustomerId()
    {
    	return $this->httpContext->getValue('customer_id');
    }
}
