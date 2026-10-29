<?php

namespace Omnipay\SslCommerz\Message;

use Omnipay\Common\Message\RequestInterface;

/**
 * SSLCommerz Transaction Query Response
 *
 * A query by tran_id can return several attempts in "element". The response
 * exposes the most relevant one (a VALID/VALIDATED attempt when present)
 * through the TransactionResponse getters; all attempts are available from
 * getTransactions().
 */
class FetchTransactionResponse extends TransactionResponse
{
    /**
     * @var array
     */
    protected $raw;

    public function __construct(RequestInterface $request, $data)
    {
        $this->raw = $data;

        if (isset($data['element']) && is_array($data['element'])) {
            $selected = [];
            foreach ($data['element'] as $element) {
                if (!$selected) {
                    $selected = $element;
                }
                $status = isset($element['status']) ? strtoupper($element['status']) : '';
                if (in_array($status, ['VALID', 'VALIDATED'], true)) {
                    $selected = $element;
                    break;
                }
            }
            $data = array_merge($data, $selected);
        }

        parent::__construct($request, $data);
    }

    public function isSuccessful()
    {
        return $this->getValue('APIConnect', 'DONE') === 'DONE' && parent::isSuccessful();
    }

    /**
     * All transaction attempts returned by a tran_id query.
     *
     * @return array
     */
    public function getTransactions()
    {
        if (isset($this->raw['element']) && is_array($this->raw['element'])) {
            return $this->raw['element'];
        }

        return [$this->raw];
    }

    /**
     * @return int|null
     */
    public function getNumberOfTransactions()
    {
        return isset($this->raw['no_of_trans_found']) ? (int) $this->raw['no_of_trans_found'] : null;
    }

    /**
     * @return array
     */
    public function getRawData()
    {
        return $this->raw;
    }
}
