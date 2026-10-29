<?php

namespace Omnipay\SslCommerz\Message;

/**
 * SSLCommerz Purchase Request
 *
 * Creates a checkout session. The response contains the GatewayPageURL the
 * customer must be redirected to.
 *
 * @link https://developer.sslcommerz.com/doc/v4/#create-and-get-session
 */
class PurchaseRequest extends AbstractRequest
{
    public function getFailUrl()
    {
        return $this->getParameter('failUrl');
    }

    public function setFailUrl($value)
    {
        return $this->setParameter('failUrl', $value);
    }

    public function getProductName()
    {
        return $this->getParameter('productName');
    }

    public function setProductName($value)
    {
        return $this->setParameter('productName', $value);
    }

    public function getProductCategory()
    {
        return $this->getParameter('productCategory');
    }

    public function setProductCategory($value)
    {
        return $this->setParameter('productCategory', $value);
    }

    /**
     * One of: general, physical-goods, non-physical-goods, airline-tickets,
     * travel-vertical, telecom-vertical.
     */
    public function getProductProfile()
    {
        return $this->getParameter('productProfile');
    }

    public function setProductProfile($value)
    {
        return $this->setParameter('productProfile', $value);
    }

    /**
     * YES, NO, Courier or Airline Tickets.
     */
    public function getShippingMethod()
    {
        return $this->getParameter('shippingMethod');
    }

    public function setShippingMethod($value)
    {
        return $this->setParameter('shippingMethod', $value);
    }

    public function getNumOfItem()
    {
        return $this->getParameter('numOfItem');
    }

    public function setNumOfItem($value)
    {
        return $this->setParameter('numOfItem', $value);
    }

    public function getValueA()
    {
        return $this->getParameter('valueA');
    }

    public function setValueA($value)
    {
        return $this->setParameter('valueA', $value);
    }

    public function getValueB()
    {
        return $this->getParameter('valueB');
    }

    public function setValueB($value)
    {
        return $this->setParameter('valueB', $value);
    }

    public function getValueC()
    {
        return $this->getParameter('valueC');
    }

    public function setValueC($value)
    {
        return $this->setParameter('valueC', $value);
    }

    public function getValueD()
    {
        return $this->getParameter('valueD');
    }

    public function setValueD($value)
    {
        return $this->setParameter('valueD', $value);
    }

    /**
     * Restrict the payment methods shown, e.g. "bkash,nagad" or "visacard".
     */
    public function getMultiCardName()
    {
        return $this->getParameter('multiCardName');
    }

    public function setMultiCardName($value)
    {
        return $this->setParameter('multiCardName', $value);
    }

    public function getAllowedBin()
    {
        return $this->getParameter('allowedBin');
    }

    public function setAllowedBin($value)
    {
        return $this->setParameter('allowedBin', $value);
    }

    public function getEmiOption()
    {
        return $this->getParameter('emiOption');
    }

    public function setEmiOption($value)
    {
        return $this->setParameter('emiOption', $value);
    }

    public function getEmiMaxInstOption()
    {
        return $this->getParameter('emiMaxInstOption');
    }

    public function setEmiMaxInstOption($value)
    {
        return $this->setParameter('emiMaxInstOption', $value);
    }

    public function getEmiSelectedInst()
    {
        return $this->getParameter('emiSelectedInst');
    }

    public function setEmiSelectedInst($value)
    {
        return $this->setParameter('emiSelectedInst', $value);
    }

    public function getEmiAllowOnly()
    {
        return $this->getParameter('emiAllowOnly');
    }

    public function setEmiAllowOnly($value)
    {
        return $this->setParameter('emiAllowOnly', $value);
    }

    /**
     * Extra raw SSLCommerz fields merged into the request (e.g. airline or
     * telecom vertical fields such as hours_till_departure, product_type).
     */
    public function getExtraParameters()
    {
        return $this->getParameter('extraParameters');
    }

    public function setExtraParameters(array $value)
    {
        return $this->setParameter('extraParameters', $value);
    }

    public function getData()
    {
        $this->validate('storeId', 'storePassword', 'amount', 'currency', 'transactionId', 'returnUrl', 'cancelUrl');

        $data = $this->getCredentials();
        $data['total_amount'] = $this->getAmount();
        $data['currency'] = $this->getCurrency();
        $data['tran_id'] = $this->getTransactionId();
        $data['success_url'] = $this->getReturnUrl();
        $data['fail_url'] = $this->getFailUrl() ?: $this->getCancelUrl();
        $data['cancel_url'] = $this->getCancelUrl();

        if ($this->getNotifyUrl()) {
            $data['ipn_url'] = $this->getNotifyUrl();
        }

        $data = array_merge($data, $this->getCustomerData());

        $items = $this->getItems();

        $data['shipping_method'] = $this->getShippingMethod() ?: 'NO';
        $data['product_name'] = $this->getProductName() ?: ($this->getDescription() ?: $this->getItemNames());
        $data['product_category'] = $this->getProductCategory() ?: 'general';
        $data['product_profile'] = $this->getProductProfile() ?: 'general';

        if ($this->getNumOfItem()) {
            $data['num_of_item'] = (int) $this->getNumOfItem();
        } elseif ($items && count($items)) {
            $data['num_of_item'] = count($items);
        }

        if ($items && count($items)) {
            $cart = [];
            foreach ($items as $item) {
                $cart[] = [
                    'product' => $item->getName(),
                    'quantity' => $item->getQuantity(),
                    'amount' => $this->formatCurrency($item->getPrice() * $item->getQuantity()),
                ];
            }
            $data['cart'] = json_encode($cart);
        }

        $optional = [
            'value_a' => $this->getValueA(),
            'value_b' => $this->getValueB(),
            'value_c' => $this->getValueC(),
            'value_d' => $this->getValueD(),
            'multi_card_name' => $this->getMultiCardName(),
            'allowed_bin' => $this->getAllowedBin(),
            'emi_option' => $this->getEmiOption() === null ? null : (int) (bool) $this->getEmiOption(),
            'emi_max_inst_option' => $this->getEmiMaxInstOption(),
            'emi_selected_inst' => $this->getEmiSelectedInst(),
            'emi_allow_only' => $this->getEmiAllowOnly() === null ? null : (int) (bool) $this->getEmiAllowOnly(),
        ];

        foreach ($optional as $key => $value) {
            if ($value !== null && $value !== '') {
                $data[$key] = $value;
            }
        }

        if ($this->getExtraParameters()) {
            $data = array_merge($data, $this->getExtraParameters());
        }

        return $data;
    }

    /**
     * Map the Omnipay CreditCard object onto SSLCommerz customer and
     * shipping fields.
     *
     * @return array
     */
    protected function getCustomerData()
    {
        $card = $this->getCard();
        if (!$card) {
            return [];
        }

        $data = [
            'cus_name' => $card->getBillingName(),
            'cus_email' => $card->getEmail(),
            'cus_add1' => $card->getBillingAddress1(),
            'cus_add2' => $card->getBillingAddress2(),
            'cus_city' => $card->getBillingCity(),
            'cus_state' => $card->getBillingState(),
            'cus_postcode' => $card->getBillingPostcode(),
            'cus_country' => $card->getBillingCountry(),
            'cus_phone' => $card->getBillingPhone(),
            'cus_fax' => $card->getBillingFax(),
        ];

        if ($card->getShippingAddress1()) {
            $data['ship_name'] = $card->getShippingName();
            $data['ship_add1'] = $card->getShippingAddress1();
            $data['ship_add2'] = $card->getShippingAddress2();
            $data['ship_city'] = $card->getShippingCity();
            $data['ship_state'] = $card->getShippingState();
            $data['ship_postcode'] = $card->getShippingPostcode();
            $data['ship_country'] = $card->getShippingCountry();
            $data['ship_phone'] = $card->getShippingPhone();
        }

        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * @return string
     */
    protected function getItemNames()
    {
        $items = $this->getItems();
        if (!$items || !count($items)) {
            return 'Order ' . $this->getTransactionId();
        }

        $names = [];
        foreach ($items as $item) {
            $names[] = $item->getName();
        }

        return substr(implode(', ', $names), 0, 255);
    }

    public function sendData($data)
    {
        return $this->response = new PurchaseResponse($this, $this->sendPost(self::SESSION_PATH, $data));
    }
}
