<?php

namespace Omnipay\SslCommerz\Message;

/**
 * SSLCommerz Refund Status Query
 *
 * transactionReference (or refundReference) must be the refund_ref_id
 * returned by refund().
 *
 * @link https://developer.sslcommerz.com/doc/v4/#query-the-status-of-a-refund-request
 */
class FetchRefundRequest extends AbstractRequest
{
    public function getRefundReference()
    {
        return $this->getParameter('refundReference');
    }

    public function setRefundReference($value)
    {
        return $this->setParameter('refundReference', $value);
    }

    public function getData()
    {
        $this->validate('storeId', 'storePassword');

        $refundRef = $this->getRefundReference() ?: $this->getTransactionReference();
        if (!$refundRef) {
            $this->validate('transactionReference');
        }

        return array_merge(['refund_ref_id' => $refundRef], $this->getCredentials(), ['format' => 'json']);
    }

    public function sendData($data)
    {
        return $this->response = new FetchRefundResponse($this, $this->sendGet(self::MERCHANT_API_PATH, $data));
    }
}
