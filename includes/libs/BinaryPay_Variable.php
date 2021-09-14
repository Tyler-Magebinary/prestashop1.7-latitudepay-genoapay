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

final class BinaryPay_Variable
{
    /**
     *  API All Response Status
     */
    public const STATUS_DECLINED   = 'DECLINED';
    public const STATUS_BLOCKED    = 'BLOCKED';
    public const STATUS_FAILED     = 'FAILED';
    public const STATUS_INPROGRESS = 'INPROGRESS';
    public const STATUS_SUCCESSFUL = 'SUCCESSFUL';
    public const STATUS_AUTHORISED = 'AUTHORISED';
    public const STATUS_SUBMITTED  = 'SUBMITTED';
    public const STATUS_REFUNDED   = 'REFUNDED';
    public const STATUS_NEW        = 'NEW';
    public const STATUS_EXPIRED    = 'EXPIRED';
    public const STATUS_UNKNOWN    = 'UNKNOWN';
    public const STATUS_APPROVED   = 'APPROVED';
    public const STATUS_ERROR      = 'ERROR';
    public const STATUS_PROCESSING = 'IN_PROGRESS';
    public const STATUS_COMPLETED  = 'COMPLETED';

    /**
     *  Account Infomation
     */
    public const ACCOUNTID         = 'accountId';
    public const USERNAME          = 'username';
    public const PASSWORD          = 'password';
    public const ENVIRONMENT       = 'environment';


    public const GATEWAY_PATH      = 'Gateways';

    public const ERROR_SIGNAL      = 'BinaryPay Notice';

    /**
     * API key for binarypay
     */
    public const API_KEY           = 'apiKey';

    /**
     *  Purchase or Refund Amount
     */
    public const AMOUNT            = 'amount';

    /**
     * Currency Type
     */
    public const CURRENCY          = 'currency';

    /**
     * User Agent - user's browser
     */
    public const USER_AGENT        = 'userAgent';

    /**
     * Transaction - an array contains transaction details
     */
    public const TRANSACTION       = 'transaction';

    /**
     * Product Code - for qcard long term finance
     */
    public const PRODUCT_CODE      = 'productCode';

    /**
     * Direct to return URL after purchase
     */
    public const REDIRECT          = 'redirecToUrlResponse';

    /**
     * Process number returned by Qcard API
     */
    public const PROCESS_NO        = "processNo";

    /**
     * Quantity of line items - qcard
     */
    public const QUANTITY          = 'quantity';
    /**
     * ID
     */
    public const IP                = 'ip';

    /**
     *  For all Callback Url
     */
    public const RETURN_URL        = 'returnUrl';

    /**
     *  Purchase or Refund Reference
     */
    public const REFERENCE         = 'reference';

    /**
     *  Purchase or Refund particular
     */
    public const PARTICULAR        = 'particular';

    /**
     *  Card Number
     */
    public const CARD_NUMBER       = 'cardNumber';

    /**
     *  Card Type
     */
    public const CARD_TYPE         = 'cardType';

    /**
     *  Card Expiry
     */
    public const CARD_EXPIRY       = 'cardExpiry';

    /**
     *  Card Holder
     */
    public const CARD_HOLDER       = 'cardHolder';

    /**
     *  Card CSC
     */
    public const CARD_CSC          = 'cardCSC';

    /**
     *  Card Token
     */
    public const CARD_TOKEN        = 'cardToken';

    /**
     *  Token Reference
     */
    public const CARD_TOKEN_REF    = 'tokenReference';

    /**
     *  Origin Transaction Id
     */
    public const ORIGIN_TRANSACTION_ID    = 'originalTransactionId';

    /**
     * Transaction Id
     */
    public const TRANSACTION_ID    = 'transactionId';

    /**
     *  Email
     */
    public const EMAIL             = 'email';

    /**
     * Merchat
     */
    public const MERCHANT          = 'merchant';

    /**
     * Merchant Code
     */
    public const MERCHANT_CODE     = 'merchantCode';

    /**
     * Merchant Url
     */
    public const MERCHANT_URL      = 'merchantUrl';

    /**
     *  Webpayment Merchant Token
     */
    public const MERCHANT_TOKEN    = 'merchantToken';

    /**
     *  Webpayment Store Card
     */
    public const STORE_CARD        = 'storeCard';

    /**
     *  Webpayment Force Store Card
     */
    public const FORCE_STORE_CARD  = 'forceStoreCard';

    /**
     *  Webpayment Display Email
     */
    public const DISPLAY_EMAIL     = 'displayCustomerEmail';

    /**
     *  WebPayment CMD Code
     */
    public const CMD    = 'cmd';

    /**
     * Bank Info
     */
    public const BANK = 'bank';

    public const SKU  = 'sku';

    /**
     * OnlineEFTPOS PayerId
     */
    public const MOBILENUMBER      = 'mobileNumber';

    /**
     * OnlineEFTPOS Description
     */
    public const DESCRIPTION       = 'description';

    /**
     * PayerId Type
     */
    public const PAYMENT_TYPE       = 'paymentType';

    /**
     * Refund Id
     */
    public const REFUND_ID          = 'refundId';

    /**
     * Payment Id
     */
    public const ORDER_ID           = 'orderId';

    /**
     * Bank Id
     */
    public const BANK_ID            = 'bankId';

    /**
     * Term - financenow
     */
    public const TERM               = 'term';

    /**
     * Defterm - financenow
     */
    public const DEFTERM            = 'defterm';

    /**
     * Deposit - financenow
     */
    public const DEPOSIT            = 'deposit';

    /**
     * Rate - financenow
     */
    public const RATE               = 'rate';

    /**
     * Customer name - financenow
     */
    public const CUSTOMER_NAME      = 'customerName';

    /**
     *Customer email - financenow
     */
    public const CUSTOMER_EMAIL     = 'customerEmail';

    /**
     * GENOAPAY
     */
    /**
     * Firstname
     */
    public const FIRSTNAME          = 'firstname';

    /**
     * Surname
     */
    public const SURNAME            = 'surname';

    /**
     * Shipping Address
     */
    public const SHIPPING_ADDRESS   = 'shippingAddress';

    /**
     * Billing Address
     */
    public const BILLING_ADDRESS    = 'billingAddress';

    /**
     * Suburb
     */
    public const SHIPPING_SUBURB             = 'shippingSuburb';

    /**
     * City
     */
    public const SHIPPING_CITY               = 'shippingCity';

    /**
     * Postcode
     */
    public const SHIPPING_POSTCODE           = 'shippingPostcode';

    /**
     * Country Code
     */
    public const SHIPPING_COUNTRY_CODE       = 'shippingCountryCode';

    /**
     * Suburb
     */
    public const BILLING_SUBURB             = 'billingSuburb';

    /**
     * City
     */
    public const BILLING_CITY               = 'billingCity';

    /**
     * Postcode
     */
    public const BILLING_POSTCODE           = 'billingPostcode';

    /**
     * Country Code
     */
    public const BILLING_COUNTRY_CODE       = 'billingCountryCode';

    /**
     * Purchase Token
     */
    public const PURCHASE_TOKEN             = 'purchaseToken';

    /**
     * Tax amount
     */
    public const TAX_AMOUNT                 = 'taxAmount';

    /**
     * Shipping Lines
     */
    public const SHIPPING_LINES             = 'shippingLines';

    /**
     * Product
     */
    public const PRODUCTS                   = 'products';

    /**
     * Refund Reason
     */
    public const REASON                     = 'reason';

    /**
     * FailureURL POLIPAY
     */
    public const FAILURE_URL                = 'FailureURL';

    /**
     * NotificationURL POLIPAY
     */
    public const NOTIFICATION_URL           = 'NotificationURL';

    /**
     * CancellationURL POLIPAY
     */
    public const CANCELLATION_URL           = 'CancellationURL';

    /**
     * MerchantReferenceFormat POLIPAY
     */
    public const REFERENCEFORMAT            = 'ReferenceFormat';
}
