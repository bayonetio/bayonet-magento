<?php

namespace Bayonet\BayonetAntiFraud\Plugin;

use \Magento\Customer\Model\Session;
use \Magento\Framework\App\Http\Context;
use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\App\RequestInterface;
use \Closure;

/**
 * Plugin class to make customer data available in the context in order
 * to retrieve it from the frontend
 */
class CustomerSessionContext
{
    protected $customerSession;
    protected $httpContext;

    public function __construct(
    	Session $customerSession,
    	Context $httpContext
    ) {
    	$this->customerSession = $customerSession;
    	$this->httpContext = $httpContext;
    }

    /**
 	* @param \Magento\Framework\App\ActionInterface $subject
 	* @param callable $proceed
 	* @param \Magento\Framework\App\RequestInterface $request
 	* @return mixed
 	*/
    public function aroundDispatch(
    	ActionInterface $subject,
    	Closure $proceed,
    	RequestInterface $request
    ) {
    	$this->httpContext->setValue(
        	'customer_id',
        	$this->customerSession->getCustomerId(),
        	false
    	);

    	$this->httpContext->setValue(
        	'customer_name',
        	$this->customerSession->getCustomer()->getName(),
        	false
    	);

    	$this->httpContext->setValue(
        	'customer_email',
        	$this->customerSession->getCustomer()->getEmail(),
        	false
    	);

    	return $proceed($request);
    }
}
