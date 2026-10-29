<?php

namespace Omnipay\SslCommerz\Message;

/**
 * SSLCommerz Refund Response
 *
 * status is "success" when the refund was accepted, "processing" when it is
 * already queued, or "failed".
 */
class RefundResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return $this->getValue('APIConnect') === 'DONE' && strtolower((string) $this->getStatus()) === 'success';
    }

    public function isPending()
    {
        return $this->getValue('APIConnect') === 'DONE' && strtolower((string) $this->getStatus()) === 'processing';
    }

    /**
     * The SSLCommerz refund_ref_id. Use it with fetchRefund().
     */
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
        return $this->getValue('trans_id', $this->getValue('tran_id'));
    }
}
