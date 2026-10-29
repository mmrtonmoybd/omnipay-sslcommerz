<?php

namespace Omnipay\SslCommerz\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractRequest as BaseAbstractRequest;

/**
 * Base request for all SSLCommerz API calls.
 */
abstract class AbstractRequest extends BaseAbstractRequest
{
    const LIVE_ENDPOINT = 'https://securepay.sslcommerz.com';
    const SANDBOX_ENDPOINT = 'https://sandbox.sslcommerz.com';

    const SESSION_PATH = '/gwprocess/v4/api.php';
    const VALIDATION_PATH = '/validator/api/validationserverAPI.php';
    const MERCHANT_API_PATH = '/validator/api/merchantTransIDvalidationAPI.php';

    public function getStoreId()
    {
        return $this->getParameter('storeId');
    }

    public function setStoreId($value)
    {
        return $this->setParameter('storeId', $value);
    }

    public function getStorePassword()
    {
        return $this->getParameter('storePassword');
    }

    public function setStorePassword($value)
    {
        return $this->setParameter('storePassword', $value);
    }

    /**
     * @return string
     */
    public function getBaseEndpoint()
    {
        return $this->getTestMode() ? self::SANDBOX_ENDPOINT : self::LIVE_ENDPOINT;
    }

    /**
     * @return array
     */
    protected function getCredentials()
    {
        return [
            'store_id' => $this->getStoreId(),
            'store_passwd' => $this->getStorePassword(),
        ];
    }

    /**
     * Send a GET request to an SSLCommerz API path and decode the JSON body.
     *
     * @param string $path
     * @param array  $query
     * @return array
     * @throws InvalidResponseException
     */
    protected function sendGet($path, array $query)
    {
        $url = $this->getBaseEndpoint() . $path . '?' . http_build_query($query, '', '&');

        $httpResponse = $this->httpClient->request('GET', $url, ['Accept' => 'application/json']);

        return $this->decode((string) $httpResponse->getBody());
    }

    /**
     * Send a form-encoded POST request to an SSLCommerz API path.
     *
     * @param string $path
     * @param array  $data
     * @return array
     * @throws InvalidResponseException
     */
    protected function sendPost($path, array $data)
    {
        $httpResponse = $this->httpClient->request(
            'POST',
            $this->getBaseEndpoint() . $path,
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
            http_build_query($data, '', '&')
        );

        return $this->decode((string) $httpResponse->getBody());
    }

    /**
     * @param string $body
     * @return array
     * @throws InvalidResponseException
     */
    protected function decode($body)
    {
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new InvalidResponseException('Invalid response from SSLCommerz: ' . substr($body, 0, 255));
        }

        return $decoded;
    }
}
