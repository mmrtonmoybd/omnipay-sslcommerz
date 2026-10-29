<?php

namespace Omnipay\SslCommerz\Message;

use Omnipay\Common\Message\NotificationInterface;
use Omnipay\SslCommerz\Helper\Signature;

/**
 * SSLCommerz IPN (Instant Payment Notification)
 *
 * Parses the IPN POST body. Always confirm a VALID notification with
 * completePurchase() or validateTransaction() before fulfilling the order; this class
 * only checks the signature.
 */
class AcceptNotificationRequest extends AbstractRequest implements NotificationInterface
{
    public function getData()
    {
        $data = $this->httpRequest->request->all();

        return $data ?: $this->httpRequest->query->all();
    }

    public function sendData($data)
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return Signature::verify($this->getData(), $this->getStorePassword());
    }

    public function getTransactionStatus()
    {
        if (!$this->isValid()) {
            return NotificationInterface::STATUS_FAILED;
        }

        switch (strtoupper((string) $this->getValue('status'))) {
            case 'VALID':
            case 'VALIDATED':
                return NotificationInterface::STATUS_COMPLETED;
            case 'PENDING':
            case 'UNATTEMPTED':
                return NotificationInterface::STATUS_PENDING;
            default:
                return NotificationInterface::STATUS_FAILED;
        }
    }

    /**
     * The val_id.
     */
    public function getTransactionReference()
    {
        return $this->getValue('val_id');
    }

    /**
     * Your own tran_id.
     */
    public function getTransactionId()
    {
        return $this->getValue('tran_id');
    }

    public function getMessage()
    {
        return $this->getValue('error', $this->getValue('status'));
    }

    public function getStatus()
    {
        return $this->getValue('status');
    }

    public function getValue($key, $default = null)
    {
        $data = $this->getData();

        return isset($data[$key]) ? $data[$key] : $default;
    }
}
