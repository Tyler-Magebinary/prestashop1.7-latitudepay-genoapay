<?php
class latitude_officialreturnModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;

    /**
     * @var integer - Order state
     */
    const PAYMENT_ACCEPECTED = 2;
    const PAYMENT_ERROR = 8;

    const PAYMENT_SUCCESS_STATES = [
        'COMPLETED'
    ];

    const PAYMENT_FAILED_STATES = [
        'UNKNOWN',
        'FAILED'
    ];

    /**
     * [initContent description]
     * @return [type]
     */
    public function initContent()
    {
        parent::initContent();
        // Add the validation
        $reference = Tools::getValue('reference');

        if (!$this->context->cookie->reference || $this->context->cookie->reference !== $reference) {
            Tools::redirect(Context::getContext()->shop->getBaseURL(true));
        }

        $cart = $this->context->cart;
        $response = Tools::getAllValues();
        $responseState = Tools::getValue('result');
        // success
        if (in_array($responseState, self::PAYMENT_SUCCESS_STATES)) {
            $currencyCode = $this->context->currency->iso_code;
            $gatewayName = $this->module->getPaymentGatewayNameByCurrencyCode($currencyCode);
            $this->module->validateOrder(
                $cart->id,
                self::PAYMENT_ACCEPECTED,
                $cart->getOrderTotal(),
                $gatewayName,
                '',
                array(
                    'transaction_id' => Tools::getValue('token')
                )
            );
        } else {
            $message = (is_array($response)) ? json_encode($response) : 'Error response from Latitude Financial services API. The response data cannot be recorded.';
            // record all the FAILED status order
            // just in case we lose the response messages and transaction token ID
            BinaryPay::log($message, true, 'prestashop-latitude-finance.log');
            $this->errors[] = Context::getContext()->getTranslator()->trans("Your purchase order has been cancelled.");
            $this->redirectWithNotifications('index.php?controller=order&step=1');
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer))
            Tools::redirect('index.php?controller=order&step=1');

        Tools::redirect('index.php?controller=order-confirmation&id_cart='. (int)$cart->id. '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder. '&key=' . $customer->secure_key);
    }

    /**
     * translateErrorMessage
     * @param  string $message
     * @return string
     */
    protected function
    translateErrorMessage($message)
    {
        switch ($message) {
            case 'The customer cancelled the purchase.':
                $message =  'Your purchase order has been cancelled.';
                break;
            default:
                // do nothing
                break;
        }
        return $message;
    }
}