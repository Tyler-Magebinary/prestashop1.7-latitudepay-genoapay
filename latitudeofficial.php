<?php
/**
 * This checks for the existence of an always-existing PrestaShop constant (its version number),
 * and if it does not exist, it stops the module from loading.
 * The sole purpose of this is to prevent malicious visitors to load this file directly.
 *  @author    Latitude Finance
 *  @copyright Latitude Finance
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once(dirname(__FILE__) . '/includes/autoload.php');
require_once(dirname(__FILE__).'/helpers/OrderHelper.php');
require_once(dirname(__FILE__).'/models/LatitudeRefundTransaction.php');

class latitudeofficial extends PaymentModule
{
    /**
     * @var string
     */
    public const GENOAPAY_PAYMENT_METHOD_CODE = 'Genoapay';

    /**
     * @var string
     */
    public const LATITUDE_PAYMENT_METHOD_CODE = 'Latitudepay';

    /**
     * @var array
     */
    public const ALLOWED_PAYMENT_GATEWAYS= [self::GENOAPAY_PAYMENT_METHOD_CODE, self::LATITUDE_PAYMENT_METHOD_CODE];

    /**
     * @var string
     */
    public const ENVIRONMENT_DEVELOPMENT = 'development';

    /**
     * @var string
     */
    public const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * @var string
     */
    public const PRODUCT_LPAY = 'LPAY';

    /**
     * @var string
     */
    public const PRODUCT_LPAYPLUS = 'LPAYPLUS';

    /**
     * @var string
     */
    public const PRODUCT_CO_PRESENTMENT = 'LPAY,LPAYPLUS';

    /**
     * @var string
     */
    public const PRODUCT_GPAY = 'GPAY';

    /**
     * @var string
     */
    public const PAYMENT_TERM_6MONTHS = '6';

    /**
     * @var string
     */
    public const PAYMENT_TERM_12MONTHS = '12';

    /**
     * @var string
     */
    public const PAYMENT_TERM_18MONTHS = '18';

    /**
     * @var string
     */
    public const PAYMENT_TERM_24MONTHS = '24';

    /**
     * @var string
     */
    public const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * @var string
     */
    public const ENVIRONMENT = 'LATITUDE_FINANCE_ENVIRONMENT';

    /**
     * @var string
     */
    public const LATITUDE_FINANCE_PRODUCT = 'LATITUDE_FINANCE_PRODUCT';

    /**
     * @var string
     */
    public const LATITUDE_FINANCE_PAYMENT_TERMS = 'LATITUDE_FINANCE_PAYMENT_TERMS';

    /**
     * @var string - The data would be fetch from the API
     */
    public const LATITUDE_FINANCE_TITLE = 'LATITUDE_FINANCE_TITLE';

    /**
     * @var string
     */
    public const LATITUDE_FINANCE_DESCRIPTION = 'LATITUDE_FINANCE_DESCRIPTION';

    /**
     * @var string
     */
    public const LATITUDE_FINANCE_MIN_ORDER_TOTAL = 'LATITUDE_FINANCE_MIN_ORDER_TOTAL';

    /**
     * @var string
     */
    public const LATITUDE_FINANCE_MAX_ORDER_TOTAL = 'LATITUDE_FINANCE_MAX_ORDER_TOTAL';

    /**
     * @var boolean
     */
    public const LATITUDE_FINANCE_DEBUG_MODE = 'LATITUDE_FINANCE_DEBUG_MODE';

    /**
     * @var string
     */
    public const LATITUDE_FINANCE_PUBLIC_KEY = 'LATITUDE_FINANCE_PUBLIC_KEY';

    /**
     * @var string
     */
    public const LATITUDE_FINANCE_PRIVATE_KEY = 'LATITUDE_FINANCE_PRIVATE_KEY';

    /**
     * @var string
     */
    public const LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY = 'LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY';

    /**
     * @var string
     */
    public const LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY = 'LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY';

    /**
     * @var string
     */
    public const LATITUDE_FINANCE_IMAGES_API_URL = 'LATITUDE_FINANCE_IMAGES_API_URL';

    /**
     * @var string
     */
    public const DEFAULT_IMAGES_API_URL = 'https://images.latitudepayapps.com/';

    /**
     * @var string
     */
    public const DEFAULT_IMAGES_API_VERSION = 'v2';

    /**
     * @var string
     */
    protected $_html = '';

    /**
     * @var string
     */
    public $gatewayName = '';

    /**
     * @var object
     */
    public $gateway = '';

    /**
     * List of hooks needed in this module
     * @var array
     */
    public $hooks = array(
        'paymentOptions',
        'displayProductPriceBlock',
        'displayPaymentReturn',
        'displayTop',
        'displayAdminOrderTabContent',
        'actionOrderSlipAdd',
        'displayBackOfficeHeader',
        'displayExpressCheckout'
    );

    /**
     * latitudeofficial constructor.
     */
    public function __construct()
    {
        /**
         * The value MUST be the name of the module's folder.
         * @var string
         */
        $this->name = 'latitudeofficial';

        /**
         * The title for the section that shall contain this module in PrestaShop's back office modules list.
         * payments_gateways => Payments & Gateways
         * @var string
         */
        $this->tab = 'payments_gateways';

        $this->version = '1.2.1';
        $this->author = 'Latitude Financial Services';

        /**
         * Indicates whether to load the module's class when displaying the "Modules" page in the back office.
         * If set at 0, the module will not be loaded, and therefore will spend less resources to generate the "Modules" page.
         * If your module needs to display a warning message in the "Modules" page, then you must set this attribute to 1.
         * @var integer
         */
        $this->need_instance = 0;

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => '1.7.99.99');

        /**
         * Indicates that the module's template files have been built with PrestaShop 1.6's bootstrap tools in mind
         * PrestaShop should not try to wrap the template code for the configuration screen
         * (if there is one) with helper tags.
         * @var boolean
         */
        $this->bootstrap = true;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->configuration = [];

        // Calling the parent constuctor method must be done after the creation of the $this->name variable and before any use of the $this->l() translation method.
        parent::__construct();
        // If the module is not enabled or installed then do not initialize
        if (!Module::isInstalled('latitudeofficial') || !Module::isEnabled('latitudeofficial')) {
            return;
        }

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
        if (Tools::getValue(self::ENVIRONMENT, Configuration::get(self::ENVIRONMENT))) {
            $this->_setPaymentGateway();
        }

        $this->displayName = $this->l('Latitude Finance Payment Module');
        $this->description = $this->l('Available to NZ and OZ residents who are 18 years old and over and have a valid debit or credit card.');
        $this->confirmUninstall = $this->l('Are you sure you to uninstall the module?');

        /**
         * Check cURL extension
         */
        if (is_callable('curl_init') === false) {
            $this->errors[] = $this->l('To be able to use this module, please activate cURL (PHP extension).');
        }
    }

    /**
     * Install this module and register the following Hooks:
     *
     * @return bool
     */
    public function install()
    {
        if (!parent::install() || !$this->registerHooks() || !$this->updateDatabase()) {
            return false;
        }

        return true;
    }

    /**
     * Register a list of hooks
     * @return bool
     */
    protected function registerHooks()
    {
        foreach ($this->hooks as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }
        return true;
    }

    /**
     * For displaying error after the redirection from Latitude Finance
     */
    public function hookDisplayTop($params)
    {
        $errorMessage = $params['cookie']->latitude_finance_redirect_error;

        // No Error found, do nothing
        if (!$errorMessage) {
            return;
        }

        $this->context->smarty->assign(array(
            'error_message' => $errorMessage
        ));

        // remove the old error message
        $this->context->cookie->__unset('latitude_finance_redirect_error');

        return $this->display(__FILE__, 'errors_alert.tpl');
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') != $this->name) {
            return;
        }
        $this->context->controller->addJS($this->_path.'views/js/configuration.js');
    }

    /**
     * Uninstall this module and reset all the existing configuration data
     *
     * @return bool
     */
    public function uninstall()
    {
        $result = parent::uninstall();
        return $result
            && Configuration::deleteByName(self::LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_PUBLIC_KEY)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_PRIVATE_KEY)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_TITLE)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_DESCRIPTION)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_MIN_ORDER_TOTAL)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_MAX_ORDER_TOTAL)
            && Configuration::deleteByName(self::ENVIRONMENT)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_PRODUCT)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_PAYMENT_TERMS)
            && Configuration::deleteByName(self::ENVIRONMENT)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_DEBUG_MODE)
            && Configuration::deleteByName(self::LATITUDE_FINANCE_IMAGES_API_URL);
    }

    /**
     * Add refund script to backend template
     * @return null
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('controller') == "AdminOrders" && Tools::getValue('id_order')) {
            $order = new Order((int) Tools::getValue('id_order'));
            $paymentGatewayName = $this->_getGatewayNameByPaymentMethod($order);
            $customRefund = $this->isCustomRefundNeeded(Tools::getValue('id_order'), $paymentGatewayName);
            if ($customRefund) {
                Media::addJsDefL('latitude_refund_js', $this->l('Refund '.$paymentGatewayName));
                $this->context->controller->addJS(_PS_MODULE_DIR_ . $this->name . '/views/js/latitude_order.js');
            }
        }

        return null;
    }

    /**
     * Display order return block
     * @param $params
     * @return string|void
     */
    public function hookDisplayPaymentReturn($params)
    {
        $order = isset($params['order']) ? $params['order'] : null;
        if ($order) {
            /**
             * @var OrderCore $order
             */
            $this->context->smarty->assign(array(
                'currency_code' => Context::getContext()->currency->iso_code,
                'order_total_amount' => round($order->getTotalPaid(), 2),
                'payment_method' => $order->payment,
                'email' => $params['cookie']->email,
                'invoice_date' => $order->invoice_date,
                'order_id' => $order->getUniqReference()
            ));

            return $this->display(__FILE__, 'payment_return.tpl');
        }
    }

    /**
     * @param $params
     * @return false|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookDisplayAdminOrderTabContent($params)
    {
        $id_order = Tools::getValue('id_order');
        $order = new Order((int)$id_order);
        $paymentGatewayName = $this->_getGatewayNameByPaymentMethod($order);
        $customRefund = $this->isCustomRefundNeeded($id_order, $paymentGatewayName);

        $data = array(
            '_id' => $params['cookie']->id_employee,
            '_secert' => $params['cookie']->passwd,
            'order_id' => $id_order
        );

        $url = $this->context->link->getModuleLink(
            $this->name,
            'refund',
            array(),
            Configuration::get('PS_SSL_ENABLED')
        );
        $this->context->smarty->assign(array(
            'payment_gateway_name' => $paymentGatewayName,
            'refund_url' => $url,
            'custom_refund' => $customRefund,
            'query_data' => http_build_query($data),
            'available_amount' => $this->getAvailableRefundAmount($id_order),
            'total_paid' => $order->getTotalPaid(),
            'payment_code' => $order->module
        ));
        return $this->display(__FILE__, 'admin-refund.tpl');
    }

    /**
     * Check if partial refund for current method is selected
     * @param $params
     * @throws BinaryPay_Exception
     */
    public function hookActionOrderSlipAdd($params)
    {
        if (Tools::isSubmit('doPartialRefundLatitude')) {
            $order = $params['order'];
            $amount = (float) Tools::getValue('partialRefundShippingCost', '');
            foreach ($params['productList'] as $product) {
                $amount += $product['amount'];
            }

            $this->_makeRefund($order, $amount);
        }
    }

    public function hookDisplayExpressCheckout($params)
    {
        $cart = isset($params['cart']) ? $params['cart'] : null;
        if ($cart) {
            $price = $cart->getOrderTotal();
            if ($price >= $this->getMinOrderTotal()) {
                $currencyCode = $this->context->currency->iso_code;
                $gatewayName = $this->getPaymentGatewayNameByCurrencyCode($currencyCode);

                if ($gatewayName) {
                    $this->smarty->assign(array(
                        'services' => $this->getServices($gatewayName),
                        'payment_terms' => Configuration::get(self::LATITUDE_FINANCE_PAYMENT_TERMS),
                        'base_dir' => Configuration::get('PS_SSL_ENABLED') ? _PS_BASE_URL_SSL_ : _PS_BASE_URL_ . __PS_BASE_URI__,
                        'current_module_uri' => $this->_path,
                        'images_api_url' => Tools::getValue(self::LATITUDE_FINANCE_IMAGES_API_URL, self::DEFAULT_IMAGES_API_URL),
                        'images_api_version' => self::DEFAULT_IMAGES_API_VERSION,
                        'full_block' => false,
                        'amount' => $price,
                        'gateway_name' => $gatewayName
                    ));

                    return $this->display(__FILE__, 'payment_snippet.tpl');
                }
            }
        }
    }

    /**
     * Check if refund option is needed
     * @param $id_order
     * @param $paymentGatewayName
     * @return bool
     */
    public function isCustomRefundNeeded($id_order, $paymentGatewayName)
    {
        /** @var OrderCore $order */
        $order = new Order($id_order);
        $order_payment = null;
        $payments = OrderPayment::getByOrderId($id_order);

        if (count($payments) > 0) {
            $order_payment = $payments[0];
        }

        if (!$order_payment || $order->getCurrentState() === _PS_OS_REFUND_) {
            return false;
        }

        $refundedAmount = OrderHelper::getTotalRefundedAmount($id_order);
        if ($refundedAmount === $order->getTotalPaid()) {
            return false;
        }

        return $order_payment->payment_method === $paymentGatewayName;
    }

    /**
     * Check if the Latitude Finance API is avaliable for the web server
     * @return boolean
     */
    public function checkApiConnection()
    {
        try {
            $configuration = $this->getConfiguration();
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        if (empty($configuration)) {
            return false;
        }

        return true;
    }

    /**
     * Fetch the configuration from the Latitude Finance API
     * @see  https://api.uat.latitudepay.com/v3/api-doc/index.html#operation/getConfiguration
     * @return array
     * @throws Exception
     */
    public function getConfiguration()
    {
        // initialize payment gateway
        $this->gateway = $gateway = $this->getGateway();

        if (!$gateway) {
            throw new Exception('The payment gateway cannot been initialized.');
        }

        if (empty($this->configuration)) {
            $this->configuration = $gateway->configuration();
        }

        return $this->configuration;
    }

    /**
     * retrieve PostPassword from database
     *
     * @param int $storeId
     *
     * @return array|bool
     */
    public function getCredentials()
    {
        $environment = Tools::getValue(self::ENVIRONMENT, Configuration::get(self::ENVIRONMENT));
        switch ($environment) {
            case self::ENVIRONMENT_SANDBOX:
            case self::ENVIRONMENT_DEVELOPMENT:
                /**
                 * retrieve the correct configuration base on the current public and private key pair
                 */
                $publicKey = Tools::getValue(self::LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY, Configuration::get(self::LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY));
                $privateKey = Tools::getValue(self::LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY, Configuration::get(self::LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY));
                break;
            case self::ENVIRONMENT_PRODUCTION:
                $publicKey = Tools::getValue(self::LATITUDE_FINANCE_PUBLIC_KEY, Configuration::get(self::LATITUDE_FINANCE_PUBLIC_KEY));
                $privateKey = Tools::getValue(self::LATITUDE_FINANCE_PRIVATE_KEY, Configuration::get(self::LATITUDE_FINANCE_PRIVATE_KEY));
                break;
            default:
                $this->warning = 'Failed to get credentials because the environment value is not correct.';
                return false;
        }

        return array(
            'username'      => $publicKey,
            'password'      => $privateKey,
            'environment'   => $environment,
            'accountId'     => ''
        );
    }

    /**
     * Get payment gateway name base on currency code
     * @param null $currencyCode
     * @return string
     * @throws Exception
     */
    public function getPaymentGatewayNameByCurrencyCode($currencyCode = null)
    {
        $countryToCurrencyCode = [
            'NZ' => 'NZD',
            'AU' => 'AUD',
        ];

        /**
         * If the currency object still not initialized then use the country object as the default setting
         */
        if (!$currencyCode) {
            $countryCode = $this->context->country->iso_code;
            if (!isset($countryToCurrencyCode[$countryCode])) {
                throw new Expcetion(sprintf("The country code: %s cannot to map with a supported currency code.", $countryCode));
            }

            $currencyCode = $countryToCurrencyCode[$countryCode];
        }

        $this->gatewayName = $gatewayName = '';
        switch ($currencyCode) {
            case 'AUD':
                $this->gatewayName = $gatewayName = self::LATITUDE_PAYMENT_METHOD_CODE;
                break;
            case 'NZD':
                $this->gatewayName = $gatewayName = self::GENOAPAY_PAYMENT_METHOD_CODE;
                break;
            default:
                throw new Exception("The extension does not support for the current currency for the shop.");
        }

        return $gatewayName;
    }

    /**
     * Get gateway instance
     * @param null $gatewayName
     * @return BinaryPay|false|mixed|object|string|void
     */
    public function getGateway($gatewayName = null)
    {
        $message = '';

        if ($this->gateway instanceof BinaryPay && null === $gatewayName) {
            return $this->gateway;
        }

        if (!$gatewayName) {
            $gatewayName = $this->gatewayName;
        }

        try {
            $className = (isset(explode('_', $gatewayName)[1])) ? Tools::ucfirst(explode('_', $gatewayName)[1]) : Tools::ucfirst($gatewayName);
            // @todo: validate credentials coming back from the account
            if ($className && $credentials = $this->getCredentials()) {
                $this->gateway = BinaryPay::getGateway($className, $credentials);
            }
        } catch (BinaryPay_Exception $e) {
            $message = $e->getMessage();
            $this->errors[] =  $this->l($className .': '. $message);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->errors[] = $this->l($className . ': ' . $message);
        }

        if (!$this->gateway) {
            $messagePrefix = "Message: ";
            $message = $message && $message !== $messagePrefix ? $message : 'The gateway object did not initialized correctly.';
            BinaryPay::log($message, false, 'latitudepay-finance-' . date('Y-m-d') . '.log');
            return false;
        }

        if (Configuration::get(self::LATITUDE_FINANCE_DEBUG_MODE)) {
            $this->gateway->setConfig(['debug' => true]);
        }

        return $this->gateway;
    }

    /**
     * Triggered when user enter the payment step in frontend
     */
    public function hookPaymentOptions($params)
    {
        $cartAmount = $params['cart']->getOrderTotal();
        $currency = new Currency($params['cart']->id_currency);
        $this->gatewayName = $this->getPaymentGatewayNameByCurrencyCode($currency->iso_code);

        if (!$this->active || !$this->isOrderAmountAvailable($cartAmount)) {
            return [];
        }

        if (!$this->checkApiConnection()) {
            $this->context->smarty->assign(array(
                'latitudeError' => $this->l(
                    'No credentials have been provided for Latitude Finance. Please contact the owner of the website.',
                    $this->name
                )
            ));
        }

        $this->smarty->assign(array(
            'gateway_name' => $this->gatewayName,
            'amount' => $cartAmount,
            'full_block' => true,
            'images_api_url' => Tools::getValue(self::LATITUDE_FINANCE_IMAGES_API_URL, self::DEFAULT_IMAGES_API_URL),
            'images_api_version' => self::DEFAULT_IMAGES_API_VERSION,
            'services' => $this->getServices($this->gatewayName),
            'payment_terms' => Configuration::get(self::LATITUDE_FINANCE_PAYMENT_TERMS)
        ));
        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans($this->getPaymentGatewayNameByCurrencyCode($currency->iso_code)))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true))
            ->setLogo($this->getPaymentLogo())
            ->setAdditionalInformation(
                $this->fetch('module:latitudeofficial/views/templates/hook/payment_snippet.tpl')
            );
        return [$newOption];
    }

    /**
     * Get payment method logo URL
     * @return string
     * @throws Exception
     */
    protected function getPaymentLogo()
    {
        switch ($this->gatewayName) {
            case self::GENOAPAY_PAYMENT_METHOD_CODE:
                $paymentLogo =  (Configuration::get('PS_SSL_ENABLED') ? _PS_BASE_URL_SSL_ : _PS_BASE_URL_) . $this->_path . 'views/img/logos/genoapay.svg';
                break;
            case self::LATITUDE_PAYMENT_METHOD_CODE:
                $paymentLogo =  (Configuration::get('PS_SSL_ENABLED') ? _PS_BASE_URL_SSL_ : _PS_BASE_URL_) . $this->_path . 'views/img/logos/latitudepay.svg';
                break;
            default:
                throw new Exception("Failed to get the payment logo from the current gateway name.");
        }

        return $paymentLogo;
    }

    /**
     * Display payment snippet inside product detail page
     * @param $params
     * @return false|string
     * @throws Exception
     */
    public function hookDisplayProductPriceBlock($params)
    {
        if (!isset($params['type']) ||
            $params['type'] !== "weight" ||
            !$this->context->controller instanceof ProductController ||
            !isset($params['hook_origin'])
        ) {
            return "";
        }
        $currency = $this->context->currency;
        /** @var \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray $product */
        $product = $params['product'];
        $price = Tools::ps_round($product->offsetGet('price_amount'), (int)$currency->precision);
        $currencyCode = $this->context->currency->iso_code;
        $gatewayName = $this->getPaymentGatewayNameByCurrencyCode($currencyCode);

        if ($gatewayName && $product->offsetGet('quantity')) {
            $this->smarty->assign(array(
                'services' => Configuration::get(self::LATITUDE_FINANCE_PRODUCT),
                'payment_terms' => Configuration::get(self::LATITUDE_FINANCE_PAYMENT_TERMS),
                'base_dir' => Configuration::get('PS_SSL_ENABLED') ? _PS_BASE_URL_SSL_ : _PS_BASE_URL_ . __PS_BASE_URI__,
                'current_module_uri' => $this->_path,
                'images_api_url' => Tools::getValue(self::LATITUDE_FINANCE_IMAGES_API_URL, self::DEFAULT_IMAGES_API_URL),
                'images_api_version' => self::DEFAULT_IMAGES_API_VERSION,
                'full_block' => false,
                'amount' => $price,
                'gateway_name' => $gatewayName
            ));

            return $this->display(__FILE__, 'payment_snippet.tpl');
        }
    }

    /**
     * Check if order currency is matched with cart currency
     * @param $cart
     * @return bool
     */
    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns a string containing the HTML necessary to
     * generate a configuration screen on the admin
     *
     * @return string
     */
    public function getContent()
    {
        /* Check if SSL is enabled */
        if (!Configuration::get('PS_SSL_ENABLED')) {
            $this->warning = $this->l(
                'You must enable SSL on the store if you want to use this module in production.',
                $this->name
            );
        }

        $output = '';
        $output .= $this->postProcess();
        $output .= $this->renderSettingsForm();
        return $output;
    }

    /**
     * Render backend configuration form
     * @return mixed
     */
    public function renderSettingsForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Title'),
                        'readonly' => true,
                        'disabled' => true,
                        'desc'  => $this->l('This controls the title which the user sees during checkout.'),
                        'name' => self::LATITUDE_FINANCE_TITLE,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Description'),
                        'readonly' => true,
                        'disabled' => true,
                        'desc' => $this->l('This option can be set from your account portal. When the Save Changes button is clicked, this option will update automatically.'),
                        'name' => 'LATITUDE_FINANCE_DESCRIPTION',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Product'),
                        'name' => self::LATITUDE_FINANCE_PRODUCT,
                        'options' => array(
                            'query' => $this->getProducts(),
                            'id' => 'id_option',
                            'name' => 'product',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'multiple' => true,
                        'label' => $this->l('Payment Terms'),
                        'name' => self::LATITUDE_FINANCE_PAYMENT_TERMS,
                        'desc' => $this->l("Please note you can select more than one option by holding the CTRL(On Windows) or COMMAND (On MAC) and clicking on the payment terms you wish to add. The following payment terms will be displayed on your PDP Modal. Please check your contract to see what payment terms can be offered to your customers"),
                        'disabled' => $this->shouldDisplayPaymentTerms() ? false : true,
                        'options' => [
                            'query' => $this->getPaymentTerms(),
                            'id' => 'id_option',
                            'name' => 'payment_term'
                        ]
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Environment'),
                        'name' => self::ENVIRONMENT,
                        'col' => 4,
                        'desc' => $this->l('Sandbox and development are for testing purpose only.'),
                        'options' => array(
                            'query' => $this->getEnvironments(),
                            'id' => 'id_option',
                            'name' => 'environment',
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Production API Key'),
                        'desc'  => $this->l('The Public Key for your Genoapay or Latitudepay account.'),
                        'name' => self::LATITUDE_FINANCE_PUBLIC_KEY,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Production API Secret'),
                        'desc'  => $this->l('The Private Key for your Genoapay or Latitudepay account.'),
                        'name' => self::LATITUDE_FINANCE_PRIVATE_KEY,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sandbox API Key'),
                        'desc'  => $this->l('The Public Key for your Genoapay or Latitudepay sandbox account.'),
                        'name' => self::LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sandbox API Secret'),
                        'desc'  => $this->l('The Private Key for your Genoapay or Latitudepay sandbox account.'),
                        'name' => self::LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Debug Mode'),
                        'hint' => $this->l('Show Detailed Error Messages and API requests/responses in the log file.'),
                        'name' => self::LATITUDE_FINANCE_DEBUG_MODE,
                        'is_bool' => true,
                        'desc' => $this->l('Turn on the debug mode to record every request and response'),
                        'values' => array(
                            array(
                                'id' => 'LATITUDE_FINANCE_DEBUG_MODE_ON',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'LATITUDE_FINANCE_DEBUG_MODE_OFF',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Minimum Order Total'),
                        'desc'  => $this->l('This option can be set from your account portal. When the Save Changes button is clicked, this option will update automatically.'),
                        'readonly' => true,
                        'name' => self::LATITUDE_FINANCE_MIN_ORDER_TOTAL,
                    ),
                    array(
                        'type' => 'hidden',
                        'label' => $this->l('Maximum Order Total'),
                        'desc'  => $this->l('This option can be set from your account portal. When the Save Changes button is clicked, this option will update automatically.'),
                        'readonly' => true,
                        'name' => self::LATITUDE_FINANCE_MAX_ORDER_TOTAL,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Images API URL'),
                        'desc'  => $this->l('The URL of Images API that will be used for display payment snippets and modal.'),
                        'readonly' => false,
                        'name' => self::LATITUDE_FINANCE_IMAGES_API_URL,
                    ),
                ),
                'submit' => array(
                    'name' => 'submitSave',
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSave';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Get available environments
     * @return string[][]
     */
    public function getEnvironments()
    {
        return array(
            array(
                'id_option' => self::ENVIRONMENT_SANDBOX,
                'environment' => 'Sandbox'
            ),
            array(
                'id_option' => self::ENVIRONMENT_PRODUCTION,
                'environment' => 'Production'
            )
        );
    }

    /**
     * Get available product options
     * @return string[][]
     */
    public function getProducts()
    {
        return array(
            array(
                'id_option' => self::PRODUCT_LPAY,
                'product' => 'LatitudePay'
            ),
            array(
                'id_option' => self::PRODUCT_LPAYPLUS,
                'product' => 'LatitudePay+'
            ),
            array(
                'id_option' => self::PRODUCT_CO_PRESENTMENT,
                'product' => 'Co-Presentment (LatitudePay & LatitudePay+)'
            ),
        );
    }

    /**
     * Get availabe payment term options
     * @return string[][]
     */
    public function getPaymentTerms()
    {
        return array(
            array(
                'id_option' => self::PAYMENT_TERM_6MONTHS,
                'payment_term' => '6 months'
            ),
            array(
                'id_option' => self::PAYMENT_TERM_12MONTHS,
                'payment_term' => '12 months'
            ),
            array(
                'id_option' => self::PAYMENT_TERM_18MONTHS,
                'payment_term' => '18 months'
            ),
            array(
                'id_option' => self::PAYMENT_TERM_24MONTHS,
                'payment_term' => '24 months'
            )
        );
    }

    /**
     * Get module configurations
     * @return array
     */
    public function getConfigFieldsValues()
    {
        return array(
            self::LATITUDE_FINANCE_TITLE => Configuration::get(self::LATITUDE_FINANCE_TITLE),
            self::LATITUDE_FINANCE_DESCRIPTION => Configuration::get(self::LATITUDE_FINANCE_DESCRIPTION),
            self::LATITUDE_FINANCE_DEBUG_MODE => Tools::getValue(self::LATITUDE_FINANCE_DEBUG_MODE, Configuration::get(self::LATITUDE_FINANCE_DEBUG_MODE)),
            self::ENVIRONMENT => Tools::getValue(self::ENVIRONMENT, Configuration::get(self::ENVIRONMENT)),
            self::LATITUDE_FINANCE_MIN_ORDER_TOTAL => Configuration::get(self::LATITUDE_FINANCE_MIN_ORDER_TOTAL),
            self::LATITUDE_FINANCE_MAX_ORDER_TOTAL => Configuration::get(self::LATITUDE_FINANCE_MAX_ORDER_TOTAL),
            self::LATITUDE_FINANCE_PUBLIC_KEY => Tools::getValue(self::LATITUDE_FINANCE_PUBLIC_KEY, Configuration::get(self::LATITUDE_FINANCE_PUBLIC_KEY)),
            self::LATITUDE_FINANCE_PRIVATE_KEY => Tools::getValue(self::LATITUDE_FINANCE_PRIVATE_KEY, Configuration::get(self::LATITUDE_FINANCE_PRIVATE_KEY)),
            self::LATITUDE_FINANCE_PRODUCT => Tools::getValue(self::LATITUDE_FINANCE_PRODUCT, Configuration::get(self::LATITUDE_FINANCE_PRODUCT)),
            self::LATITUDE_FINANCE_PAYMENT_TERMS . '[]' => Tools::getValue(self::LATITUDE_FINANCE_PAYMENT_TERMS) ? Tools::getValue(self::LATITUDE_FINANCE_PAYMENT_TERMS) : explode(',', Configuration::get(self::LATITUDE_FINANCE_PAYMENT_TERMS)),
            self::LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY => Tools::getValue(self::LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY, Configuration::get(self::LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY)),
            self::LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY => Tools::getValue(self::LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY, Configuration::get(self::LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY)),
            self::LATITUDE_FINANCE_IMAGES_API_URL => Tools::getValue(self::LATITUDE_FINANCE_IMAGES_API_URL, self::DEFAULT_IMAGES_API_URL),
        );
    }

    /**
     * Store user settings
     * @return mixed
     */
    protected function postProcess()
    {
        try {
            $configuration = $this->getConfiguration();
        } catch (Exception $e) {
            return $this->displayError($e->getMessage());
        }

        if (Tools::isSubmit('submitSave')) {
            // The data fetched from Latitude Finance API
            Configuration::updateValue(self::LATITUDE_FINANCE_TITLE, $this->getConfigData('name', $configuration));
            Configuration::updateValue(self::LATITUDE_FINANCE_DESCRIPTION, $this->getConfigData('description', $configuration));
            Configuration::updateValue(self::LATITUDE_FINANCE_MIN_ORDER_TOTAL, $this->getConfigData('minimumAmount', $configuration, 20));
            // Increase the max order total significantly
            Configuration::updateValue(self::LATITUDE_FINANCE_MAX_ORDER_TOTAL, $this->getConfigData('maximumAmount', $configuration, 1500) * 1000);

            // The values set by the shop owner
            Configuration::updateValue(self::LATITUDE_FINANCE_DEBUG_MODE, Tools::getValue(self::LATITUDE_FINANCE_DEBUG_MODE));
            Configuration::updateValue(self::ENVIRONMENT, Tools::getValue(self::ENVIRONMENT));
            Configuration::updateValue(self::LATITUDE_FINANCE_PUBLIC_KEY, Tools::getValue(self::LATITUDE_FINANCE_PUBLIC_KEY));
            Configuration::updateValue(self::LATITUDE_FINANCE_PRODUCT, Tools::getValue(self::LATITUDE_FINANCE_PRODUCT));

            if ($this->shouldDisplayPaymentTerms()) {
                $paymentTerms = Tools::getValue(self::LATITUDE_FINANCE_PAYMENT_TERMS);
                if (!$paymentTerms || empty($paymentTerms)) {
                    return $this->displayError($this->l('You have to set at least one value for Payment Terms!'));
                }
                Configuration::updateValue(self::LATITUDE_FINANCE_PAYMENT_TERMS, implode(',', $paymentTerms));
            }

            Configuration::updateValue(self::LATITUDE_FINANCE_PRIVATE_KEY, Tools::getValue(self::LATITUDE_FINANCE_PRIVATE_KEY));
            Configuration::updateValue(self::LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY, Tools::getValue(self::LATITUDE_FINANCE_SANDBOX_PUBLIC_KEY));
            Configuration::updateValue(self::LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY, Tools::getValue(self::LATITUDE_FINANCE_SANDBOX_PRIVATE_KEY));

            if (Configuration::updateValue('latitude_offical', (int)Tools::getValue('latitude_offical'))) {
                return $this->displayConfirmation($this->l('Settings updated'));
            } else {
                return $this->displayError($this->l('Confirmation button') . ': ' . $this->l('Invaild choice'));
            }
        }
    }

    /**
     * Get minimum order amount
     * @return mixed|string
     * @throws Exception
     */
    protected function getMinOrderTotal()
    {
        if (!$this->configuration) {
            $this->getConfiguration();
        }
        return $this->getConfigData('minimumAmount', $this->configuration);
    }

    /**
     * Get maximum order amount
     * @return mixed|string
     * @throws Exception
     */
    protected function getMaxOrderTotal()
    {
        if (!$this->configuration) {
            $this->getConfiguration();
        }
        return $this->getConfigData('maximumAmount', $this->configuration);
    }

    /**
     * Check if order total is allowed
     * @param $amount
     * @return bool
     * @throws Exception
     */
    protected function isOrderAmountAvailable($amount)
    {
        if ($amount > $this->getMaxOrderTotal() || $amount < $this->getMinOrderTotal()) {
            return false;
        }
        return true;
    }

    /**
     * Get a configuration value
     * @param $key
     * @param $array
     * @param string $default
     * @return mixed|string
     */
    public function getConfigData($key, $array, $default = '')
    {
        $value = isset($array[$key]) ? $array[$key] : $default;
        return $value;
    }

    /**
     * Get order available refund amount
     * @param $orderId
     * @return false|float
     */
    public static function getAvailableRefundAmount($orderId)
    {
        /** @var OrderCore $order */
        $order = new Order($orderId);
        if ($order->getTotalPaid()) {
            return (round($order->getTotalPaid(), 2) - round(OrderHelper::getTotalRefundedAmount($orderId), 2));
        }
        return false;
    }

    /**
     * Add private message
     * @param $id_order
     * @param $message
     * @return false
     */
    public function _addNewPrivateMessage($id_order, $message)
    {
        if (!(bool) $id_order) {
            return false;
        }

        $new_message = new Message();
        $message = strip_tags($message, '<br>');

        if (!Validate::isCleanHtml($message)) {
            $message = $this->l('Payment message is not valid, please check your module.');
        }

        $new_message->message = $message;
        $new_message->id_order = (int) $id_order;
        $new_message->private = 1;

        return $new_message->add();
    }

    /**
     * Send refund request to payment gateway
     * @param OrderCore $order
     * @param float $amount
     * @param string $reason
     * @return array
     * @throws BinaryPay_Exception
     */
    public function _makeRefund($order, $amount, $reason = "")
    {
        $currencyCode = $this->_getCurrencyCodeByPaymentMethod($order->payment);
        if (!$currencyCode) {
            $currencyCode = self::getOrderCurrencyCode($order->id);
        }
        $gateway = $this->getGateway();
        if (!$gateway) {
            return array(
                "success" => false,
                "message" => "The gateway object did not initialized correctly."
            );
        }
        $payments = $order->getOrderPayments();
        $credentials = $this->getCredentials();
        $availableRefundAmount = $this->getAvailableRefundAmount($order->id);
        if (!$availableRefundAmount || $availableRefundAmount > $order->getTotalPaid()) {
            return array(
                "success" => false,
                "message" => "The order has been refunded already"
            );
        }

        $payment = reset($payments);
        $transactionId = $payment->transaction_id;
        $reference = $payment->order_reference;

        $refund = array(
            BinaryPay_Variable::PURCHASE_TOKEN => $transactionId,
            BinaryPay_Variable::CURRENCY => $currencyCode,
            BinaryPay_Variable::AMOUNT => $amount,
            BinaryPay_Variable::REFERENCE => $reference,
            BinaryPay_Variable::REASON => $reason,
            BinaryPay_Variable::PASSWORD => $credentials['password']
        );

        try {
            if (empty($transactionId)) {
                throw new InvalidArgumentException(sprintf('The transaction ID for order %1$s is blank. A refund cannot 
                be processed unless there is a valid transaction associated with the order.', $order->id));
            }
            $response = $gateway->refund($refund);
            if (Configuration::get(self::LATITUDE_FINANCE_DEBUG_MODE)) {
                if (isset($response['refundId'])) {
                    $message = "Refund ID: ".$response['refundId'];
                    $message .= "\n";
                    $message .= "Refund Date: ".$response['refundDate'];
                    $message .= "\n";
                    $message .= "Amount: ".$amount;
                    $this->_addNewPrivateMessage($order->id, $message);
                } else {
                    $this->_addNewPrivateMessage($order->id, "Response from the gateway: ".json_encode($response));
                }
            }

            if (isset($response['refundId'])) {
                $rTransaction = new LatitudeRefundTransaction();
                $rTransaction->id_refund = $response['refundId'];
                $rTransaction->id_order = $order->id;
                $rTransaction->refund_date = $response['refundDate'];
                $rTransaction->refund_amount = $amount;
                $rTransaction->reference = $response['reference'];
                $rTransaction->commission_amount = $response['commissionAmount'];
                $rTransaction->payment_gateway = $order->payment;
                $rTransaction->save();

                if (self::getAvailableRefundAmount($order->id) !== false && !self::getAvailableRefundAmount($order->id)) {
                    $this->_changeOrderStatus($order->id, (int) Configuration::get('PS_OS_REFUND'));
                }
            }
            return array(
                "success" => true,
                "response" => $response
            );
        } catch (BinaryPay_Exception $e) {
            PrestaShopLogger::addLog($e->getMessage(), 1, null, 'PaymentModule', (int)$order->id, true);
            BinaryPay::log($e->getMessage(), true, 'latitudepay-finance-' . date('Y-m-d') . '.log');
            if (Configuration::get(self::LATITUDE_FINANCE_DEBUG_MODE)) {
                $this->_addNewPrivateMessage($order->id, $e->getMessage());
            }
            return array(
                "success" => false,
                "message" => $e->getMessage()
            );
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Build the correct structure of the product list
     * @param OrderCore $order
     * @return array
     */
    public function getProductList($order)
    {
        $productList = [];
        $orderDetailList = $order->getOrderDetailList();
        foreach ($orderDetailList as $productDetail) {
            $productList[] = $productDetail['id_order_detail'];
        }
        return $productList;
    }

    /**
     * Get order currency code
     * @param $order
     * @return mixed
     */
    public static function getOrderCurrencyCode($order)
    {
        $currencyId = $order->id_currency;
        $currency = new Currency($currencyId);
        return $currency->iso_code;
    }

    /**
     * Get and set the instance's gateway name base on order payment method
     * @param $order
     */
    public function setGatewayNameByPaymentMethod($order)
    {
        if ($gatewayName = $this->_getGatewayNameByPaymentMethod($order)) {
            $this->gatewayName = $gatewayName;
        }
    }

    /**
     * Get gateway name base on payment method
     * @param $order
     * @return false
     */
    protected function _getGatewayNameByPaymentMethod($order)
    {
        if (in_array($order->payment, self::ALLOWED_PAYMENT_GATEWAYS)) {
            return $order->payment;
        }
        return false;
    }

    /**
     * Set payment gateway
     * @throws Exception
     */
    protected function _setPaymentGateway()
    {
        $order = null;
        if (Tools::getValue('id_order')) {
            $order = new Order((int) Tools::getValue('id_order'));
        } elseif (Tools::getValue('query_data')) {
            parse_str(Tools::getValue('query_data'), $queryData);
            $order = new Order((int) $queryData['order_id']);
        }
        if ($order && in_array($order->payment, self::ALLOWED_PAYMENT_GATEWAYS)) {
            $this->getGateway($this->_getGatewayNameByPaymentMethod($order));
        } else {
            if (isset($this->context->currency)) {
                $iso_code = $this->context->currency->iso_code;
            } else {
                $cookie = $this->context->cookie;
                $id_currency = isset($cookie->id_currency) ? $cookie->id_currency : Configuration::get('PS_CURRENCY_DEFAULT');
                $currency = new Currency((int) $id_currency);
                $iso_code = $currency->iso_code;
            }
            $this->getGateway($this->getPaymentGatewayNameByCurrencyCode($iso_code));
        }
    }

    /**
     * Get currency code by payment method name
     * @param $paymentMethod
     * @return false|string
     */
    protected function _getCurrencyCodeByPaymentMethod($paymentMethod)
    {
        switch ($paymentMethod) {
            case self::LATITUDE_PAYMENT_METHOD_CODE:
                return "AUD";
            case self::GENOAPAY_PAYMENT_METHOD_CODE:
                return "NZD";
            default:
                return false;
        }
    }

    /**
     * Update order status
     * @param $orderId
     * @param $statusId
     */
    protected function _changeOrderStatus($orderId, $statusId)
    {
        /** @var OrderHistoryCore $history */
        $history = new OrderHistory();
        $history->id_order = (int) $orderId;
        $history->changeIdOrderState($statusId, $history->id_order);
        $history->addWithemail();
        $history->save();
    }

    /**
     *
     * @return bool
     */
    protected function updateDatabase()
    {
        /* Create Refund transactions table table */
        return Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'latitude_refund_transactions` (
		`id_refund` varchar(100) NOT null,
		`id_order` int(10) NOT null,
		`refund_date` varchar(255) NOT null,
		`reference` varchar(50) NOT null,
		`refund_amount` decimal(20,6) NOT null,
		`commission_amount` decimal(20,6) NOT null,
		`payment_gateway` varchar(50) NOT null,
		PRIMARY KEY (`id_refund`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8');
    }

    /**
     * @return bool
     */
    private function shouldDisplayPaymentTerms()
    {
        return in_array(Tools::getValue(self::LATITUDE_FINANCE_PRODUCT), [
            self::PRODUCT_LPAYPLUS, self::PRODUCT_CO_PRESENTMENT
        ]);
    }

    /**
     * Get configured service
     * @param $gatewayName
     * @return false|string
     */
    private function getServices($gatewayName)
    {
        return $gatewayName === self::GENOAPAY_PAYMENT_METHOD_CODE ? self::PRODUCT_GPAY : Configuration::get(self::LATITUDE_FINANCE_PRODUCT);
    }
}
