<?php

namespace Bayonet\BayonetAntiFraud\Controller\Fingerprint;

use \Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\ResponseInterface;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Bayonet\BayonetAntiFraud\Model\BayonetFingerprintFactory;

/**
 * Controller class for the Device Fingerprint
 */
class Index extends Action
{
    protected $resultJsonFactory;
    protected $bayonetFingerprintFactory;

	public function __construct(
		Context $context,
        JsonFactory $resultJsonFactory,
        BayonetFingerprintFactory $bayonetFingerprintFactory
	)
	{
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->bayonetFingerprintFactory = $bayonetFingerprintFactory;
	}

    /**
     * Executes the corresponding validations to add the generated fingerprint
     * token to a customer, checking if a new row must be added to the table,
     * or just update an existing one
     * 
     * @return json
     */
	public function execute()
	{
        $post = $this->getRequest()->getPost();
        $bayonetFingerprint = $this->bayonetFingerprintFactory->create();
        $currentToken = $bayonetFingerprint->load($post['customer'], 'customer_id');

        try {
            $data = null;

            if (empty($currentToken->getData())) {
                $data = array(
                    'customer_id' => $post['customer'],
                    'fingerprint_token' => $post['fingerprint']
                );
            } else {
                $data = array(
                    'fingerprint_id' => $currentToken->getData('fingerprint_id'),
                    'fingerprint_token' => $post['fingerprint']
                );
            }
            $bayonetFingerprint->setData($data);
            $bayonetFingerprint->save();
        } catch (Exception $e) {
            return;
        }
        $result = $this->resultJsonFactory->create();
        $resultData = [
            'result' => true,
        ];

        return $result->setData($resultData);
	}
}
