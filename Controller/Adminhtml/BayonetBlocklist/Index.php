<?php

namespace Bayonet\BayonetAntiFraud\Controller\Adminhtml\BayonetBlocklist;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;

/**
 * Index class for the Bayonet Blocklist menu element inside the
 * Bayonet menu in the admin dashboard
 */
class Index extends Action
{
    protected $resultPageFactory = false;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('Bayonet Anti-Fraud Blocklist/Whitelist')));

        return $resultPage;
    }
}
