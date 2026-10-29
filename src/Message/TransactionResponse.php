<?php

namespace Omnipay\SslCommerz\Message;

/**
 * Response holding a single SSLCommerz transaction record (Validation API,
 * transaction query, or posted success/fail/cancel/IPN data).
 */
class TransactionResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return in_array(strtoupper((string) $this->getStatus()), ['VALID', 'VALIDATED'], true);
    }

    public function isPending()
    {
        return in_array(strtoupper((string) $this->getStatus()), ['PENDING', 'UNATTEMPTED'], true);
    }

    public function isCancelled()
    {
        return strtoupper((string) $this->getStatus()) === 'CANCELLED';
    }

    /**
     * True when the transaction was already validated before (status VALIDATED).
     *
     * @return bool
     */
    public function isAlreadyValidated()
    {
        return strtoupper((string) $this->getStatus()) === 'VALIDATED';
    }

    /**
     * SSLCommerz marks suspicious transactions with risk_level = 1. These
     * should be held until the customer is verified.
     *
     * @return bool
     */
    public function isRisky()
    {
        return (string) $this->getValue('risk_level') === '1';
    }

    public function getRiskLevel()
    {
        return $this->getValue('risk_level');
    }

    public function getRiskTitle()
    {
        return $this->getValue('risk_title');
    }

    /**
     * The SSLCommerz validation id (val_id).
     */
    public function getTransactionReference()
    {
        return $this->getValue('val_id');
    }

    public function getValId()
    {
        return $this->getValue('val_id');
    }

    /**
     * Your own transaction id (tran_id).
     */
    public function getTransactionId()
    {
        return $this->getValue('tran_id');
    }

    /**
     * Bank transaction id. Required for refunds.
     */
    public function getBankTransactionId()
    {
        return $this->getValue('bank_tran_id');
    }

    public function getSessionKey()
    {
        return $this->getValue('sessionkey');
    }

    /**
     * Amount in BDT.
     */
    public function getAmount()
    {
        return $this->getValue('amount');
    }

    /**
     * Amount after SSLCommerz fees.
     */
    public function getStoreAmount()
    {
        return $this->getValue('store_amount');
    }

    /**
     * Currency the payment was requested in.
     */
    public function getCurrency()
    {
        return $this->getValue('currency_type', $this->getValue('currency'));
    }

    /**
     * Amount in the requested currency.
     */
    public function getCurrencyAmount()
    {
        return $this->getValue('currency_amount');
    }

    public function getCardType()
    {
        return $this->getValue('card_type');
    }

    public function getCardNumber()
    {
        return $this->getValue('card_no');
    }

    public function getCardBrand()
    {
        return $this->getValue('card_brand');
    }

    public function getCardIssuer()
    {
        return $this->getValue('card_issuer');
    }

    public function getTransactionDate()
    {
        return $this->getValue('tran_date');
    }
}
