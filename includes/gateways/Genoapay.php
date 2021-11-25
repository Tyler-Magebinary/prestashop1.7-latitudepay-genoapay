<?php
/**
 * Class Genoapay
 *  @author    Latitude Finance
 *  @copyright Latitude Finance
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use phpseclib\Crypt\Hash;
use Raven_Compat;

class Genoapay extends BinaryPay
{
    public const API_VERSION = 'v3';

    public const TOKEN_ENDPOINT = 'token';
    public const PURCHASE_ENDPOINT = 'sale/online';
    public const PURCHASE_STATUS_ENDPOINT = 'sale/pos';
    public const CONFIGURATON_ENDPOINT= 'configuration';

    public const STATUS_SUCCESSFUL = 200;
    public const STATUS_INVALID = 400;
    public const STATUS_ACCESS_DENIED = 403;
    public const STATUS_INTERNAL_SERVER_ERROR = 500;

    /**
     * @var boolean
     */
    protected $_debug = true;

    public function __construct($credential = array())
    {
        parent::__construct($credential);
        $this->setConfig(
            array(
                'api-error-status' => array(
                    BinaryPay_Variable::STATUS_DECLINED,
                    BinaryPay_Variable::STATUS_BLOCKED,
                    BinaryPay_Variable::STATUS_FAILED,
                    BinaryPay_Variable::STATUS_INPROGRESS
                ),
                'api-success-status'  => array(
                    BinaryPay_Variable::STATUS_SUCCESSFUL
                ),
                'http-success-status'  => array(200),
                'api-error-message-field' => 'error'
            )
        );

        $this->getToken();
    }

    public function getHeader()
    {
        $headers = [];
        $headers[] = "api-version: " . self::API_VERSION;

        if ($this->getConfig('request-content-type') == 'json') {
            $headers[] = "Content-Type: application/com.genoapay.ecom-v3.0+json";
            $headers[] = "Accept: application/com.genoapay.ecom-v3.0+json";
        }

        $headers[] = "Authorization: " . $this->getAuth();
        return $headers;
    }

    public function getAuth()
    {
        if (!$this->_issets(array(BinaryPay_Variable::USERNAME, BinaryPay_Variable::PASSWORD), $this->getConfig())) {
            throw new BinaryPay_Exception('HTTP ERROR: Cannot set authentication header');
        }

        if ($this->getConfig('authToken')) {
            $encodedAuth = 'Bearer ' . $this->getConfig('authToken');
        } else {
            $authString = $this->getConfig(BinaryPay_Variable::USERNAME) . ':' . $this->getConfig(BinaryPay_Variable::PASSWORD);
            $encodedAuth = 'Basic ' . mb_convert_encoding($authString, 'utf8');
        }
        return $encodedAuth;
    }

    /**
     * getToken
     * @return [type]
     */
    public function getToken()
    {
        $url = $this->getApiUrl() . self::API_VERSION . DIRECTORY_SEPARATOR . self::TOKEN_ENDPOINT;

        if (!$this->getConfig('authToken')) {
            $this->setConfig(
                array(
                    'method'                => 'post',
                    'request-content-type'  => 'json',
                    'response-content-type' => 'json',
                    'api-success-status'    => 'authToken',
                    'url'                   => $url,
                    'request'               => []
                )
            );
            $this->setConfig($this->query());
        }
    }

    /**
       * @description main function to query API.
       * @param  array  request body
       * @return array  returns API response
       */

    public function getApiUrl()
    {
        switch ($this->getConfig(BinaryPay_Variable::ENVIRONMENT)) {
            case 'production':
                $url = 'https://api.genoapay.com/';
                break;
            case 'sandbox':
            case 'development':
            default:
                $url = 'https://api.uat.genoapay.com/';
                break;
        }

        return $url;
    }

    /**
     * getPurchaseUrl
     */
    public function getPurchaseUrl()
    {
        return $this->getApiUrl() . self::API_VERSION . DIRECTORY_SEPARATOR . self::PURCHASE_ENDPOINT;
    }

    /**
     * getRefundUrl
     * @return string
     */
    public function getRefundUrl($token)
    {
        return $this->getApiUrl() . self::API_VERSION .DIRECTORY_SEPARATOR . 'sale' . DIRECTORY_SEPARATOR . $token . DIRECTORY_SEPARATOR . 'refund';
    }

    /**
     * getPurchaseStatusUrl
     * @return string
     */
    public function getPurchaseStatusUrl($token)
    {
        return $this->getApiUrl() . self::API_VERSION .DIRECTORY_SEPARATOR . self::PURCHASE_STATUS_ENDPOINT . DIRECTORY_SEPARATOR . $token . DIRECTORY_SEPARATOR . 'status';
    }

    /**
     * getConfigurationUrl
     * @return string
     */
    public function getConfigurationUrl()
    {
        return $this->getApiUrl() . self::API_VERSION . DIRECTORY_SEPARATOR . self::CONFIGURATON_ENDPOINT;
    }

    /**
     * creates a full array signature of a valid gateway request
     * @return array gateway request signature format
     */
    public function createSignature()
    {
        return array_merge(
            array(
                BinaryPay_Variable::ENVIRONMENT,
                BinaryPay_Variable::USERNAME,
                BinaryPay_Variable::PASSWORD,
                BinaryPay_Variable::AMOUNT,
                BinaryPay_Variable::REFERENCE,
                'returnUrls',
                'totalAmount',
                'billingAddress',
                'customer',
                'shippingAddress',
                BinaryPay_Variable::TAX_AMOUNT,
                BinaryPay_Variable::PRODUCTS,
                BinaryPay_Variable::CURRENCY,
                BinaryPay_Variable::REASON,
                BinaryPay_Variable::SHIPPING_LINES
            ),
            parent::createSignature()
        );
    }

    /**
     * Get configuration back from Latitude Finance API
     * @return array
     */
    public function configuration(array $args = array())
    {
        $url = $this->getConfigurationUrl();
        $request = array();

        $this->setConfig(
            array(
                'method'                => 'get',
                'request-content-type'  => 'json',
                'response-content-type' => 'json',
                'api-success-status'    => 'name',
                'url'                   => $url,
                'request'               => $request
            )
        );

        return $this->query();
    }

    /**
     * Pass in purchase payment info as below:
     * TODO: Cannot support address in customer for now, since the array structure
     * @param  array
     * @return array
     */
    public function purchase(array $args = array())
    {
        $url = $this->getPurchaseUrl();
        $request = array(
            'totalAmount' => array(
                'amount'        => round($args[BinaryPay_Variable::AMOUNT], 2),
                'currency'      => $args[BinaryPay_Variable::CURRENCY]
            ),
            'returnUrls' => array(
                'successUrl'    => $args[BinaryPay_Variable::RETURN_URL],
                'failUrl'       => $args[BinaryPay_Variable::RETURN_URL]
            ),
            "reference"         => $args[BinaryPay_Variable::REFERENCE],
            "customer" => [
                "mobileNumber"  => $args[BinaryPay_Variable::MOBILENUMBER],
                "firstName"     => $args[BinaryPay_Variable::FIRSTNAME],
                "surname"       => $args[BinaryPay_Variable::SURNAME],
                "email"         => $args[BinaryPay_Variable::EMAIL]
            ],
            "shippingAddress" => [
                "addressLine1"  => $args[BinaryPay_Variable::SHIPPING_ADDRESS],
                "suburb"        => $args[BinaryPay_Variable::SHIPPING_SUBURB],
                "cityTown"      => $args[BinaryPay_Variable::SHIPPING_CITY],
                "postcode"      => $args[BinaryPay_Variable::SHIPPING_POSTCODE],
                "countryCode"   => $args[BinaryPay_Variable::SHIPPING_COUNTRY_CODE]
            ],
            "billingAddress" => [
                "addressLine1"  => $args[BinaryPay_Variable::BILLING_ADDRESS],
                "suburb"        => $args[BinaryPay_Variable::BILLING_SUBURB],
                "cityTown"      => $args[BinaryPay_Variable::BILLING_CITY],
                "postcode"      => $args[BinaryPay_Variable::BILLING_POSTCODE],
                "countryCode"   => $args[BinaryPay_Variable::BILLING_COUNTRY_CODE]
            ],
            "products" => $args[BinaryPay_Variable::PRODUCTS],
            "taxAmount" => [
                "amount" => round($args[BinaryPay_Variable::TAX_AMOUNT], 2),
                "currency" => $args[BinaryPay_Variable::CURRENCY]
            ],
            "shippingLines" => $args[BinaryPay_Variable::SHIPPING_LINES]
        );

        // signature
        $signature = $this->hash(
            $this->base64Encode($this->recursiveImplode($request, '', true)),
            $this->getConfig('password'),
            'sha256'
        );

        // Clean implode buffer
        $this->gluedString = '';

        $this->setConfig(
            array(
                'method'                => 'post',
                'request-content-type'  => 'json',
                'response-content-type' => 'json',
                'api-success-status'    => 'token',
                'url'                   => $url . '?signature=' . $signature,
                'request'               => $request
            )
        );

        return $this->query();
    }

    /**
     * refund request
     * @param  array $args
     * @return array
     */
    public function refund($args)
    {
        $token = $args[BinaryPay_Variable::PURCHASE_TOKEN];

        $request = [
            'amount' => [
                'amount'    => round($args[BinaryPay_Variable::AMOUNT], 2),
                'currency'  => $args[BinaryPay_Variable::CURRENCY]
            ],
            'reason'        => $args[BinaryPay_Variable::REASON],
            'reference'     => $args[BinaryPay_Variable::REFERENCE]
        ];

        // Clean implode buffer
        $this->gluedString = '';

        $this->setConfig(array(
            'method'                => 'post',
            'request-content-type'  => 'json',
            'response-content-type' => 'json',
            'api-success-status'    => 'refundId',
            'url'                   => $this->getRefundUrl($token) . '?signature=' .
                $this->hash(
                    $this->base64Encode($this->recursiveImplode($request, '', true)),
                    $args[BinaryPay_Variable::PASSWORD],
                    'sha256'
                ),
            'request'               => $request
        ));
        return $this->query();
    }

    /**
     * retrieve
     * @param  array  $args
     * @return array
     */
    public function retrieve(array $args)
    {
        $this->setConfig(array(
            'method'                => 'get',
            'request-content-type'  => 'json',
            'response-content-type' => 'json',
            'api-success-status'    => 'status',
            'url'                   => $this->getPurchaseStatusUrl($args[BinaryPay_Variable::PURCHASE_TOKEN]),
            'request'               => []
        ));

        return $this->query();
    }

    /**
     * Convert string to base64 code
     * @param $string
     * @return array|false|string|string[]|null
     */
    private function base64Encode($string)
    {
        $base64 = mb_convert_encoding($string, 'base64');
        return str_replace("\r\n", "", $base64);
    }

    /**
     * Encrypt string using SHA256 algo
     * @param $data
     * @param $key
     * @param string $algo
     * @return false|string
     */
    private function hash($data, $key, $algo = MHASH_SHA256)
    {
        return mhash($algo, $data, $key);
    }
}
