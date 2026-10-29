<?php

namespace Omnipay\SslCommerz\Message;

use Omnipay\Common\Exception\InvalidRequestException;

/**
 * SSLCommerz Transaction Query
 *
 * Query by your own transactionId (tran_id) or by the SSLCommerz session key
 * (transactionReference, returned by purchase()).
 *
 * @link https://developer.sslcommerz.com/doc/v4/#by-session-id
 * @link https://developer.sslcommerz.com/doc/v4/#by-transaction-id
 */
class FetchTransactionRequest extends AbstractRequest
{
    public function getSessionKey()
    {
        return $this->getParameter('sessionKey');
    }

    public function setSessionKey($value)
    {
        return $this->setParameter('sessionKey', $value);
    }

    public function getData()
    {
        $this->validate('storeId', 'storePassword');

        $sessionKey = $this->getSessionKey() ?: $this->getTransactionReference();

        if ($sessionKey) {
            $query = ['sessionkey' => $sessionKey];
        } elseif ($this->getTransactionId()) {
            $query = ['tran_id' => $this->getTransactionId()];
        } else {
            throw new InvalidRequestException('The transactionId or transactionReference parameter is required');
        }

        return array_merge($query, $this->getCredentials(), ['format' => 'json']);
    }

    public function sendData($data)
    {
        return $this->response = new FetchTransactionResponse($this, $this->sendGet(self::MERCHANT_API_PATH, $data));
    }
}
