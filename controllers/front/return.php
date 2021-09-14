<?php
/**
 * Class latitude_officialreturnModuleFrontController
 *  @author    Latitude Finance
 *  @copyright Latitude Finance
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class latitude_OfficialReturnModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;

    /**
     * @var integer - Order state
     */
    public const PAYMENT_ACCEPECTED = 2;
    public const PAYMENT_ERROR = 8;

    public const PAYMENT_SUCCESS_STATES = [
        'COMPLETED'
    ];

    public const PAYMENT_FAILED_STATES = [
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

        if (Configuration::get(Latitude_Official::LATITUDE_FINANCE_DEBUG_MODE)) {
            $logMessage = "======CALLBACK INFO STARTS======\n";
            $logMessage .= json_encode(Tools::getAllValues(), JSON_PRETTY_PRINT);
            $logMessage .= "\n======CALLBACK INFO ENDS======";
            BinaryPay::log($logMessage, true, 'latitudepay-finance-' . date('Y-m-d') . '.log');
        }

        if (!$this->context->cookie->reference || $this->context->cookie->reference !== $reference) {
            Tools::redirect(Context::getContext()->shop->getBaseURL(true));
        }

        $cart = $this->context->cart;
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
            $this->errors[] = Context::getContext()->getTranslator()->trans("Your purchase order has been cancelled.");
            $this->redirectWithNotifications('index.php?controller=order&step=1');
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        Tools::redirect('index.php?controller=order-confirmation&id_cart='. (int)$cart->id. '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder. '&key=' . $customer->secure_key);
    }

    /**
     * translateErrorMessage
     * @param  string $message
     * @return string
     */
    protected function translateErrorMessage($message)
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
