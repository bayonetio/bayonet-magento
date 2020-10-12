<?php

namespace Bayonet\BayonetAntiFraud\Block\Adminhtml\OrderEdit\Tab;

use \Bayonet\BayonetAntiFraud\Helper\DirectQuery;
use \Magento\Backend\Block\Template;
use \Magento\Backend\Block\Widget\Tab\TabInterface;

/**
 * Order custom tab
 */
class View extends Template implements TabInterface
{
    protected $_template = 'tab/view/my_order_info.phtml';
    protected $directQuery;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        DirectQuery $directQuery,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->directQuery = $directQuery;
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * Retrieve order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Bayonet Anti-Fraud');
    }

    /**
     * Gets the Bayonet Tracking ID of an order
     * 
     * @return string
     */
    public function getTrackingId()
    {
        return $this->directQuery->getTrackingId($this->getOrderId());
    }

    /**
     * Gets the Bayonet API response of an order
     * 
     * @return string
     */
    public function getApiResponse()
    {
        return $this->directQuery->getApiResponse($this->getOrderId());
    }

    /**
     * Gets the Decision from the Bayonet API of an order
     * 
     * @return string
     */
    public function getDecision()
    {
        return $this->directQuery->getDecision($this->getOrderId());
    }

    /**
     * Gets the Bayonet API triggered rules (if exist) of an order
     * 
     * @return string
     */
    public function getRules()
    {
        return $this->directQuery->getRules($this->getOrderId());
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Bayonet Anti-Fraud');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
