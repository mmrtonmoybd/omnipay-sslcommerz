<?php

namespace Omnipay\SslCommerz\Message;

/**
 * SSLCommerz Order Validation API request.
 *
 * Pass the val_id received on success_url / IPN as transactionReference.
 *
 * @link https://developer.sslcommerz.com/doc/v4/#order-validation-api
 */
class ValidateRequest extends AbstractRequest
{
    public function getData()
    {
        $this->validate('storeId', 'storePassword', 'transactionReference');

        return array_merge(['val_id' => $this->getTransactionReference()], $this->getCredentials(), [
            'v' => 1,
            'format' => 'json',
        ]);
    }

    public function sendData($data)
    {
        return $this->response = new ValidateResponse($this, $this->sendGet(self::VALIDATION_PATH, $data));
    }
}
