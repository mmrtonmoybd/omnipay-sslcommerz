<?php

namespace Omnipay\SslCommerz\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\SslCommerz\Helper\Signature;

/**
 * SSLCommerz Complete Purchase Request
 *
 * Use on success_url, fail_url, cancel_url and ipn_url. It:
 *
 *  1. reads the data SSLCommerz POSTed to your URL,
 *  2. verifies the verify_sign hash,
 *  3. for VALID payments, calls the Validation API with val_id,
 *  4. checks tran_id / amount / currency against what you expect.
 *
 * Pass transactionId, amount and currency of your order to enable step 4.
 */
class CompletePurchaseRequest extends AbstractRequest
{
    /**
     * Skip the Validation API call (not recommended).
     */
    public function getSkipValidation()
    {
        return $this->getParameter('skipValidation');
    }

    public function setSkipValidation($value)
    {
        return $this->setParameter('skipValidation', $value);
    }

    /**
     * Raw posted data. Defaults to the current HTTP request body.
     */
    public function getRequestData()
    {
        return $this->getParameter('requestData');
    }

    public function setRequestData(array $value)
    {
        return $this->setParameter('requestData', $value);
    }

    public function getData()
    {
        $this->validate('storeId', 'storePassword');

        $data = $this->getRequestData();
        if (!$data) {
            $data = $this->httpRequest->request->all();
        }
        if (!$data) {
            $data = $this->httpRequest->query->all();
        }
        if (!$data || empty($data['status'])) {
            throw new InvalidRequestException('No SSLCommerz payment data found in the request');
        }

        if (!empty($data['verify_sign']) && !Signature::verify($data, $this->getStorePassword())) {
            throw new InvalidResponseException('Invalid SSLCommerz signature (verify_sign)');
        }

        return $data;
    }

    public function sendData($data)
    {
        $status = strtoupper((string) $data['status']);

        if (in_array($status, ['VALID', 'VALIDATED'], true) && !$this->getSkipValidation()) {
            if (empty($data['val_id'])) {
                throw new InvalidResponseException('Missing val_id in SSLCommerz response');
            }

            $validated = $this->sendGet(self::VALIDATION_PATH, array_merge(
                ['val_id' => $data['val_id']],
                $this->getCredentials(),
                ['v' => 1, 'format' => 'json']
            ));

            // Keep posted fields that the Validation API does not return.
            $data = array_merge($data, $validated);
        }

        $this->assertMatchesOrder($data);

        return $this->response = new CompletePurchaseResponse($this, $data);
    }

    /**
     * @param array $data
     * @throws InvalidResponseException
     */
    protected function assertMatchesOrder(array $data)
    {
        $status = strtoupper((string) $data['status']);
        if (!in_array($status, ['VALID', 'VALIDATED'], true)) {
            return;
        }

        $transactionId = $this->getTransactionId();
        if ($transactionId !== null && isset($data['tran_id'])
            && (string) $data['tran_id'] !== (string) $transactionId
        ) {
            throw new InvalidResponseException('SSLCommerz tran_id does not match the order');
        }

        if ($this->getParameter('amount') === null) {
            return;
        }

        $expected = $this->getAmount();
        $currency = $this->getCurrency();

        if ($currency && !empty($data['currency_type']) && strtoupper($data['currency_type']) === strtoupper($currency)
            && isset($data['currency_amount'])
        ) {
            $paid = $data['currency_amount'];
        } elseif (isset($data['amount'])) {
            $paid = $data['amount'];
        } else {
            return;
        }

        if ($this->formatCurrency($paid) !== $expected) {
            throw new InvalidResponseException('SSLCommerz paid amount does not match the order amount');
        }
    }
}
