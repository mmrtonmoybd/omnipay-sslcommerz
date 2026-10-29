<?php

namespace Omnipay\SslCommerz\Message;

/**
 * SSLCommerz Refund Status Response
 *
 * status is "refunded", "processing" or "cancelled"/"failed".
 */
class FetchRefundResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return $this->getValue('APIConnect') === 'DONE' && strtolower((string) $this->getStatus()) === 'refunded';
    }

    public function isPending()
    {
        return $this->getValue('APIConnect') === 'DONE' && strtolower((string) $this->getStatus()) === 'processing';
    }

    public function isCancelled()
    {
        return strtolower((string) $this->getStatus()) === 'cancelled';
    }

    public function getTransactionReference()
    {
        return $this->getValue('refund_ref_id');
    }

    public function getRefundReference()
    {
        return $this->getValue('refund_ref_id');
    }

    public function getBankTransactionId()
    {
        return $this->getValue('bank_tran_id');
    }

    public function getTransactionId()
    {
        return $this->getValue('tran_id');
    }

    public function getInitiatedOn()
    {
        return $this->getValue('initiated_on');
    }

    public function getRefundedOn()
    {
        return $this->getValue('refunded_on');
    }
}
