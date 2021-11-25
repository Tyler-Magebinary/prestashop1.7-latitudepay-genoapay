<?php
/**
 *  Class latitudeofficialpaymentModuleFrontController
 *  @author    Latitude Finance
 *  @copyright Latitude Finance
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class latitudeofficialPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = false;
    public $display_column_left = false;

    /**
     * @var string
     */
    protected $returnUrl = '/module/latitudeofficial/return';

    /**
     * @var boolean
     */
    protected $debug = true;

    /**
     * @var string
     */
    public const DEFAULT_VALUE = 'NO_VALUE';

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $errors = [];

        try {
            $purchaseUrl = $this->getPurchaseUrl();
            Tools::redirect($purchaseUrl);
            return;
        } catch (Exception $e) {
            $errors[] = Tools::displayError($e->getMessage());
            Tools::redirect('index.php?controller=order');
            return;
        }
    }

    /**
     * Get the payment checkout logo by the current currency
     * @return string
     * @throws Exception
     */
    protected function getPaymentCheckoutLogo()
    {
        $currencyCode = $this->context->currency->iso_code;
        switch ($currencyCode) {
            case 'AUD':
                $logo = 'latitudepay.svg';
                break;
            case 'NZD':
                $logo = 'genoapay.svg';
                break;
            default:
                throw new Exception('Unsupported currency code. Please change your currency code to AUD or NZD.');
        }
        return (Configuration::get('PS_SSL_ENABLED') ? _PS_BASE_URL_SSL_ : _PS_BASE_URL_) . $this->module->getPathUri() . 'views/img/logos' . DIRECTORY_SEPARATOR . $logo;
    }

    /**
     * Request the purchase URL by accessing the Latitude Finance API
     * @see https://api.uat.latitudepay.com/v3/api-doc/index.html#operation/createEcommerceSale
     * @return string
     */
    public function getPurchaseUrl()
    {
        try {
            $cookie = $this->getCookie();
            $currency   = $this->context->currency;
            $paymentGatewayName = $this->module->getPaymentGatewayNameByCurrencyCode($currency->iso_code);
            $gateway    = $this->module->getGateway($paymentGatewayName);
            if (!$gateway) {
                $this->errors[] = "Could not get the payment URL from payment gateway";
                return false;
            }

            $reference  = $this->getReferenceNumber();
            // Save the reference for validation when response coming back from
            $cookie->reference = $reference;

            $cart       = $this->context->cart;
            $amount     = $cart->getOrderTotal();
            $customer   = $this->context->customer;
            $address    = new Address($cart->id_address_delivery);

            $payment = array(
                BinaryPay_Variable::REFERENCE                => (string) $reference,
                BinaryPay_Variable::AMOUNT                   => $amount,
                BinaryPay_Variable::CURRENCY                 => $currency->iso_code ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::RETURN_URL               => $this->getReturnUrl(),
                BinaryPay_Variable::MOBILENUMBER             => $address->phone_mobile ?: '0210123456',
                BinaryPay_Variable::EMAIL                    => $customer->email,
                BinaryPay_Variable::FIRSTNAME                => $customer->firstname ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::SURNAME                  => $customer->lastname ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::SHIPPING_ADDRESS         => $this->getFullAddress() ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::SHIPPING_COUNTRY_CODE    => $address->country ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::SHIPPING_POSTCODE        => $address->postcode ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::SHIPPING_SUBURB          => $address->city ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::SHIPPING_CITY            => $address->city ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::BILLING_ADDRESS          => $this->getFullAddress() ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::BILLING_COUNTRY_CODE     => $address->country ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::BILLING_POSTCODE         => $address->postcode ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::BILLING_SUBURB           => $address->city ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::BILLING_CITY             => $address->city ?: self::DEFAULT_VALUE,
                BinaryPay_Variable::TAX_AMOUNT               => $cart->getOrderTotal() - $cart->getOrderTotal(false),
                BinaryPay_Variable::PRODUCTS                 => $this->getQuoteProducts(),
                BinaryPay_Variable::SHIPPING_LINES           => [
                    $this->getShippingData()
                ]
            );

            if ($this->debug) {
                PrestaShopLogger::addLog('latitudeofficialpaymentModuleFrontController::getPurchaseUrl - Function called.' . json_encode($payment), 1, null, 'PaymentModule', (int)$cart->id, true);
            }

            $response = $gateway->purchase($payment);
            $purchaseUrl = $this->module->getConfigData('paymentUrl', $response);
        } catch (BinaryPay_Exception $e) {
            BinaryPay::log($e->getMessage(), true, 'prestashop-latitude-finance.log');
            PrestaShopLogger::addLog($e->getMessage(), 1, null, 'PaymentModule', (int)$cart->id, true);
            $this->errors[] = $e->getMessage();
            Tools::redirect('index.php?controller=order');
            return;
        } catch (Exception $e) {
            $message = $e->getMessage() ?: 'Something massively went wrong. Please try again. If the problem still exists, please contact us';
            PrestaShopLogger::addLog($message, 1, null, 'PaymentModule', (int)$cart->id, true);
            BinaryPay::log($message, true, 'latitudepay-finance-' . date('Y-m-d') . '.log');
            $this->errors[] = $message;
            Tools::redirect('index.php?controller=order');
            return;
        }
        return $purchaseUrl;
    }

    protected function getReturnUrl()
    {
        return $this->context->link->getModuleLink('latitudeofficial', 'return', array(), Configuration::get('PS_SSL_ENABLED'));
    }

    /**
     * This is how prestashop generate the next order referece number
     * @return string
     */
    protected function getReferenceNumber()
    {
        do {
            $reference = Order::generateReference();
        } while (Order::getByReference($reference)->count());
        return $reference;
    }

    /**
     * Since the address have two address lines so this function is made to merge them as a single string
     * @return string
     */
    protected function getFullAddress()
    {
        $addressObject    = new Address($this->context->cart->id_address_delivery);
        $address = $addressObject->address1;
        $address2 = $addressObject->address2;

        if ($address2) {
            $address .= ', ' . $address2;
        }

        return $address;
    }

    /**
     * Build the shipping line array structure base on latitude finance API documentation
     * @see  https://api.uat.latitudepay.com/v3/api-doc/index.html#operation/createEcommerceSale
     * @return array
     */
    protected function getShippingData()
    {
        // handling fee + shipping fee
        $currencyCode = $this->context->currency->iso_code;
        $id_lang = Configuration::get('PS_LANG_DEFAULT');
        $carrier = new Carrier($this->context->cart->id_carrier, $id_lang);
        // Tax rule group 0 is the "No Tax" group
        $taxIncluded = (!property_exists($carrier, 'id_tax_rules_group') || $carrier->id_tax_rules_group === 0) ? 0 : 1;

        $shippingDetail = [
            'carrier' => $carrier->name,
            'price' => [
                'amount' => $this->context->cart->getTotalShippingCost(),
                'currency' => $currencyCode
            ],
            'taxIncluded' => $taxIncluded
        ];
        return $shippingDetail;
    }

    /**
     * Build the products array structure base on latitude finance API documentation
     * @see  https://api.uat.latitudepay.com/v3/api-doc/index.html#operation/createEcommerceSale
     * @return array
     */
    protected function getQuoteProducts()
    {
        $items = $this->context->cart->getProducts();
        $currencyCode = $this->context->currency->iso_code;
        $isTaxIncluded = ($this->context->cart->getOrderTotal() == $this->context->cart->getOrderTotal(false)) ? 0 : 1;

        $products = [];
        foreach ($items as $_item) {
            $_item = (object) $_item;
            $product_line_item = [
                'name'          => htmlspecialchars($_item->name),
                'price' => [
                    'amount'    => round($_item->total_wt, 2),
                    'currency'  => $currencyCode
                ],
                'sku'           => $_item->reference,
                'quantity'      => $_item->quantity,
                'taxIncluded'   => $isTaxIncluded
            ];
            array_push($products, $product_line_item);
        }

        return $products;
    }

    protected function getCookie()
    {
        return $this->context->cookie;
    }

    public function postProcess()
    {
        $this->initContent();
    }
}
