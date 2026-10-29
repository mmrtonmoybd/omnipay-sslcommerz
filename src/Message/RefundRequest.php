<?php

namespace Omnipay\SslCommerz\Message;

/**
 * SSLCommerz Refund Request
 *
 * transactionReference must be the bank_tran_id from the validation
 * response ($response->getBankTransactionId()).
 *
 * @link https://developer.sslcommerz.com/doc/v4/#initiate-the-refund
 */
class RefundRequest extends AbstractRequest
{
    /**
     * Your own reference for this refund (refe_id).
     */
    public function getRefundReference()
    {
        return $this->getParameter('refundReference');
    }

    public function setRefundReference($value)
    {
        return $this->setParameter('refundReference', $value);
    }

    public function getBankTransactionId()
    {
        return $this->getParameter('bankTransactionId');
    }

    public function setBankTransactionId($value)
    {
        return $this->setParameter('bankTransactionId', $value);
    }

    public function getData()
    {
        $this->validate('storeId', 'storePassword', 'amount');

        $bankTransactionId = $this->getBankTransactionId() ?: $this->getTransactionReference();
        if (!$bankTransactionId) {
            $this->validate('transactionReference');
        }

        $data = [
            'bank_tran_id' => $bankTransactionId,
            'refund_amount' => $this->getAmount(),
            'refund_remarks' => $this->getDescription() ?: 'Refund',
        ];

        if ($this->getRefundReference()) {
            $data['refe_id'] = $this->getRefundReference();
        }

        return array_merge($data, $this->getCredentials(), ['format' => 'json']);
    }

    public function sendData($data)
    {
        return $this->response = new RefundResponse($this, $this->sendGet(self::MERCHANT_API_PATH, $data));
    }
}
