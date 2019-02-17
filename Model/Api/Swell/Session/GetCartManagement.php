<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Session;

class GetCartManagement implements \Yotpo\Loyalty\Api\Swell\Session\GetCartManagementInterface
{

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Yotpo\Loyalty\Helper\Schema
     */
    protected $_yotpoSchemaHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * {@inheritdoc}
     */
    public function getGetCart()
    {
        try {
            $quote = $this->_checkoutSession->getQuote();
            return $this->_yotpoHelper->jsonEncode($this->_yotpoSchemaHelper->quoteSchemaPrepare($quote));
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo API - GetCart - ERROR] " . $e->getMessage() . "\n" . print_r($e, true), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => true
            ]);
        }

        return $this->_yotpoHelper->jsonEncode([]);
    }
}
