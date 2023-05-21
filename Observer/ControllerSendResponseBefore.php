<?php

namespace Yotpo\Loyalty\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\Http;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Yotpo\Loyalty\Helper\Data as YotpoLoyaltyHelper;

/**
 * ControllerSendResponseBefore
 */
class ControllerSendResponseBefore implements ObserverInterface
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var YotpoLoyaltyHelper
     */
    private $yotpoHelper;

    /**
     * @method __construct
     * @param  Http               $request
     * @param  CheckoutSession    $checkoutSession
     * @param  CustomerSession    $customerSession
     * @param  YotpoLoyaltyHelper $yotpoHelper
     */
    public function __construct(
        Http $request,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        YotpoLoyaltyHelper $yotpoHelper
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->yotpoHelper = $yotpoHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->yotpoHelper->isEnabled()) {
            try {
                $couponCode = trim((string)$this->request->getParam(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM)) ?:
                    trim((string)$this->customerSession->getData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM));
                $this->yotpoHelper->log("[ControllerSendResponseBefore] URI: " . $this->request->getRequestUri(), "debug");
                $this->yotpoHelper->log("[ControllerSendResponseBefore] COUPON: " . $couponCode . " (" . $this->request->getRequestUri() . ")", "debug");
                if ($couponCode) {
                    $this->customerSession->setData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM, $couponCode);
                    if (($quote = $this->checkoutSession->getQuote())) {
                        $this->yotpoHelper->log("[ControllerSendResponseBefore] Quote ", "debug", [
                            $quote->getId(),
                            $quote->getItemsCount() > 0,
                            $quote->getCouponCode(),
                            $couponCode
                        ]);
                        $quote->collectTotals();
                        if (
                            $quote->getId() &&
                            $quote->getItemsCount() > 0 &&
                            $quote->getCouponCode() === $couponCode
                        ) {
                            $this->customerSession->unsetData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM);
                        } else {
                            $quote->setCouponCode($couponCode)->setTotalsCollectedFlag(false)->collectTotals()->save();
                        }
                    }
                }
                if (($quote = $this->checkoutSession->getQuote()) && $quote->getId()) {
                    $this->yotpoHelper->log("[ControllerSendResponseBefore] COUPON IN CART: " . $quote->getCouponCode(), "debug");
                }
                $this->yotpoHelper->log("[ControllerSendResponseBefore] COUPON IN CUSTOMER SESSION: " . trim((string)$this->customerSession->getData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM)), "debug");
            } catch (\Exception $e) {
                $this->yotpoHelper->log("[Yotpo - ControllerSendResponseBefore - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
    }
}
