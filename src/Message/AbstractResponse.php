<?php

namespace Omnipay\SslCommerz\Message;

use Omnipay\Common\Message\AbstractResponse as BaseAbstractResponse;

/**
 * Base response for SSLCommerz API calls.
 */
abstract class AbstractResponse extends BaseAbstractResponse
{
    /**
     * Get a value from the response data.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getValue($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * Raw SSLCommerz status string.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->getValue('status');
    }

    public function getMessage()
    {
        foreach (['failedreason', 'errorReason', 'error', 'message'] as $key) {
            if (!empty($this->data[$key])) {
                return $this->data[$key];
            }
        }

        return $this->getStatus();
    }
}
