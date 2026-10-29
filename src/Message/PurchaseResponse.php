<?php

namespace Omnipay\SslCommerz\Message;

use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * SSLCommerz Purchase Response
 *
 * Never successful by itself: on SUCCESS the customer must be redirected to
 * the hosted payment page.
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function isSuccessful()
    {
        return false;
    }

    public function isRedirect()
    {
        return strtoupper((string) $this->getStatus()) === 'SUCCESS' && $this->getRedirectUrl() !== null;
    }

    public function getRedirectUrl()
    {
        if (!empty($this->data['GatewayPageURL'])) {
            return $this->data['GatewayPageURL'];
        }

        return null;
    }

    public function getRedirectMethod()
    {
        return 'GET';
    }

    public function getRedirectData()
    {
        return [];
    }

    /**
     * The SSLCommerz session key.
     */
    public function getTransactionReference()
    {
        return $this->getValue('sessionkey');
    }

    public function getSessionKey()
    {
        return $this->getValue('sessionkey');
    }

    /**
     * Payment gateways available for this store (visa, master, mobilebanking...).
     *
     * @return array
     */
    public function getGateways()
    {
        return $this->getValue('gw', []);
    }

    /**
     * Gateway-specific direct URLs (e.g. to skip the SSLCommerz selection page).
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        return $this->getValue('desc', []);
    }
}
