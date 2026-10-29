<?php

namespace Omnipay\SslCommerz;

use Omnipay\Common\AbstractGateway;

/**
 * SSLCommerz Gateway
 *
 * SSLCommerz is the largest payment gateway aggregator in Bangladesh. It
 * supports local and international cards, internet banking and mobile
 * financial services such as bKash, Nagad, Rocket and Upay through a single
 * hosted checkout page.
 *
 * Example:
 *
 * <code>
 *   $gateway = Omnipay::create('SslCommerz');
 *   $gateway->initialize([
 *       'storeId'       => 'your_store_id',
 *       'storePassword' => 'your_store_password',
 *       'testMode'      => true,
 *   ]);
 *
 *   $response = $gateway->purchase([
 *       'amount'        => '100.00',
 *       'currency'      => 'BDT',
 *       'transactionId' => 'ORDER-1001',
 *       'returnUrl'     => 'https://example.com/payment/success',
 *       'failUrl'       => 'https://example.com/payment/fail',
 *       'cancelUrl'     => 'https://example.com/payment/cancel',
 *       'notifyUrl'     => 'https://example.com/payment/ipn',
 *       'card'          => [...],
 *   ])->send();
 *
 *   if ($response->isRedirect()) {
 *       $response->redirect();
 *   }
 * </code>
 *
 * @link https://developer.sslcommerz.com/doc/v4/
 */
class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'SSLCommerz';
    }

    public function getDefaultParameters()
    {
        return [
            'storeId' => '',
            'storePassword' => '',
            'testMode' => false,
        ];
    }

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->getParameter('storeId');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setStoreId($value)
    {
        return $this->setParameter('storeId', $value);
    }

    /**
     * @return string
     */
    public function getStorePassword()
    {
        return $this->getParameter('storePassword');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setStorePassword($value)
    {
        return $this->setParameter('storePassword', $value);
    }

    /**
     * Create a hosted checkout session and get the redirect URL.
     *
     * @param array $options
     * @return \Omnipay\SslCommerz\Message\PurchaseRequest
     */
    public function purchase(array $options = [])
    {
        return $this->createRequest('\Omnipay\SslCommerz\Message\PurchaseRequest', $options);
    }

    /**
     * Handle the customer returning to success_url / fail_url / cancel_url
     * (or the server-to-server IPN). Verifies the signature and validates the
     * payment with the SSLCommerz Validation API.
     *
     * @param array $options
     * @return \Omnipay\SslCommerz\Message\CompletePurchaseRequest
     */
    public function completePurchase(array $options = [])
    {
        return $this->createRequest('\Omnipay\SslCommerz\Message\CompletePurchaseRequest', $options);
    }

    /**
     * Parse an IPN (Instant Payment Notification) request.
     *
     * @param array $options
     * @return \Omnipay\SslCommerz\Message\AcceptNotificationRequest
     */
    public function acceptNotification(array $options = [])
    {
        return $this->createRequest('\Omnipay\SslCommerz\Message\AcceptNotificationRequest', $options);
    }

    /**
     * Validate a payment by its val_id using the Validation API.
     *
     * @param array $options
     * @return \Omnipay\SslCommerz\Message\ValidateRequest
     */
    public function validateTransaction(array $options = [])
    {
        return $this->createRequest('\Omnipay\SslCommerz\Message\ValidateRequest', $options);
    }

    /**
     * Query a transaction by transactionId (tran_id) or
     * transactionReference (sessionkey).
     *
     * @param array $options
     * @return \Omnipay\SslCommerz\Message\FetchTransactionRequest
     */
    public function fetchTransaction(array $options = [])
    {
        return $this->createRequest('\Omnipay\SslCommerz\Message\FetchTransactionRequest', $options);
    }

    /**
     * Initiate a (full or partial) refund using the bank_tran_id.
     *
     * @param array $options
     * @return \Omnipay\SslCommerz\Message\RefundRequest
     */
    public function refund(array $options = [])
    {
        return $this->createRequest('\Omnipay\SslCommerz\Message\RefundRequest', $options);
    }

    /**
     * Query the status of a refund by its refund_ref_id.
     *
     * @param array $options
     * @return \Omnipay\SslCommerz\Message\FetchRefundRequest
     */
    public function fetchRefund(array $options = [])
    {
        return $this->createRequest('\Omnipay\SslCommerz\Message\FetchRefundRequest', $options);
    }
}
