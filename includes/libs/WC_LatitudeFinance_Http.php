<?php
/**
* Woocommerce LatitudeFinance Payment Extension
*
* NOTICE OF LICENSE
*
* Copyright 2020 LatitudeFinance
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*   http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*
* @category    LatitudeFinance
* @package     Latitude_Finance
* @author      MageBinary Team
* @copyright   Copyright (c) 2020 LatitudeFinance (https://www.latitudefinancial.com.au/)
* @license     http://www.apache.org/licenses/LICENSE-2.0
*/

class WC_LatitudeFinance_Http
{
    public const HTTP_REQUEST_GET      = 'GET';
    public const HTTP_REQUEST_POST     = 'POST';
    public const HTTP_REQUEST_PUT      = 'PUT';
    public const HTTP_REQUEST_DELETE   = 'DELETE';

    protected $_config;

    public function __construct($config)
    {
        $this->_config = $config;
    }

    public function post($path, $params = null)
    {
        $response = $this->_doRequest(self::HTTP_REQUEST_POST, $path, $params);
        return $response;
    }

    public function put($path, $params = null)
    {
        $response = $this->_doRequest(self::HTTP_REQUEST_PUT, $path, $params);
        return $response;
    }

    public function get($path, $params = null)
    {
        $response = $this->_doRequest(self::HTTP_REQUEST_GET, $path, $params);
        return $response;
    }

    public function delete($path, $params = null)
    {
        $response = $this->_doRequest(self::HTTP_REQUEST_DELETE, $path, $params);
        return $response;
    }

    private function _doRequest($httpVerb, $path, $requestBody = null)
    {
        return $this->_doUrlRequest($httpVerb, $path, $requestBody);
    }

    public function _doUrlRequest($httpVerb, $url, $requestBody = null)
    {
        //TODO: Add debug tag which shows every step of the requests.
        if (is_array($requestBody)) {
            $requestBody = http_build_query($requestBody);
        }

        if ($httpVerb == self::HTTP_REQUEST_GET) {
            $url = trim($url) . '?' .$requestBody;
        }

        $curl = curl_init();
        $headers = $this->_getHeader();
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_ENCODING => "gzip",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'MageBinary BinaryPay API Integration Engine',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $httpVerb,
            CURLOPT_HTTPHEADER => $headers,
            CURLINFO_HEADER_OUT => true
        ));

        if (!empty($requestBody) && $httpVerb != self::HTTP_REQUEST_GET) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
        }

        /* Adding SSL support with setConfig. It must comes with CA string */
        /* @TODO: This might still need some work in the future.           */
        // if (isset($this->_config['ssl']) && isset($this->_config['ssl-ca'])) {
        //     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        //     curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        //     curl_setopt($curl, CURLOPT_CAINFO, $this->_config['ssl-ca']);
        // }
        $response   = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response = array('status' => $httpStatus, 'body' => $response);
        /*TODO: TIDY*/

        $debug = false;
        if (isset($this->_config['debug'])) {
            $debug = $this->_config['debug'];
        }

        if ($debug) {
            $info = "======DEBUG INFO STARTS======\n";
            $info .= "REQUEST:\n";
            $info .= "\n".curl_getinfo($curl, CURLINFO_HEADER_OUT);
            $info .= $requestBody."\n\n";
            $info .= "RESPONSE:\n";
            $info .= json_encode($response) ."\n\n";
            $info .="======DEBUG INFO ENDS========\n\n\n";
            BinaryPay::log($info, true, 'latitudepay-finance-' . date('Y-m-d') . '.log');
        }
        curl_close($curl);
        return $response;
    }

    protected function _getHeader()
    {
        if (!isset($this->_headers)) {
            throw new BinaryPay_Exception('No HTTP headers set');
        }

        return $this->_headers;
    }


    public function setHeader($header)
    {
        if (!isset($this->_headers)) {
            $this->_headers = $header;
        }
    }
}
