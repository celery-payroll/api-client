<?php

namespace Celery\Api;

use Bili\RestRequest;

/**
 * Celery client API
 * This API is used to interact with the RESTful side of Celery.
 *
 * @author Felix Langfeldt // Celery
 * @link http://celerypayroll.com
 * @version 0.3
 *
 * CHANGELOG
 *     0.3        Added affiliates
 *     0.2        Added namespaces and PSRfullness
 *     0.1        First release
 */

// Errors
define("CAPI_EMPTY_ACCOUNT", 1);
define("CAPI_INVALID_ACCOUNT", 2);
define("CAPI_EMPTY_EMAIL", 3);
define("CAPI_INVALID_EMAIL", 4);
define("CAPI_USER_ACCOUNT_EXISTS", 5);
define("CAPI_EMPTY_URL", 6);
define("CAPI_INVALID_URL", 7);
define("CAPI_URL_EXISTS", 8);
define("CAPI_UNAVAILABLE", 9);
define("CAPI_EMPTY_LOGIN", 10);
define("CAPI_INVALID_LOGIN", 11);
define("CAPI_INVALID_AFFILIATE", 15);
define("CAPI_INVALID_SERVICE", 16);
define("CAPI_ACCOUNT_FOUND", 17);
define("CAPI_EMPTY_PARAMETER", 19);
define("CAPI_INVALID_PARAMETER", 20);
define("CAPI_UNKNOWN_ERROR", 21);
define("CAPI_HAS_ACTIVE_TRIAL_ACCOUNT", 22);
define("CAPI_NOT_FOUND", 404);

// Succes
define("CAPI_URL_AVAILABLE", 12);
define("CAPI_ACCOUNT_CREATED", 13);
define("CAPI_ACCOUNT_UPDATED", 22);
define("CAPI_SUCCESS", 14);
define("CAPI_COMPANY_MOVED", 18);
define("CAPI_USER_AVAILABLE", 24);

// Methods
define("CAPI_CMD_AUTH", "authenticate");
define("CAPI_CMD_URL", "url");
define("CAPI_CMD_ACCOUNT", "account");
define("CAPI_CMD_COMPANY", "company");
define("CAPI_CMD_PRICE", "price");
define("CAPI_CMD_SERVICE", "service");
define("CAPI_CMD_ACCOUNT_PRICE", "account/price");
define("CAPI_CMD_ACCOUNT_DISCOUNT", "account/discount");
define("CAPI_CMD_ACCOUNT_INVOICE", "account/invoice");
define("CAPI_CMD_COMPANY_INTEGRATION", "company/integration");
define("CAPI_CMD_ACCOUNT_REMINDERS", "account/reminders");

// Config
define("CAPI_URL", "https://api.celerypayroll.com/"); // Include the trailing '/'

 /**
  * Client API class
  */
class Client
{
    const ERROR_EMPTY_ACCOUNT = 1;
    const ERROR_INVALID_ACCOUNT = 2;
    const ERROR_EMPTY_EMAIL = 3;
    const ERROR_INVALID_EMAIL = 4;
    const ERROR_USER_ACCOUNT_EXISTS = 5;
    const ERROR_EMPTY_URL = 6;
    const ERROR_INVALID_URL = 7;
    const ERROR_URL_EXISTS = 8;
    const ERROR_UNAVAILABLE = 9;
    const ERROR_EMPTY_LOGIN = 10;
    const ERROR_INVALID_LOGIN = 11;
    const ERROR_INVALID_AFFILIATE = 15;
    const ERROR_INVALID_SERVICE = 16;
    const ERROR_EMPTY_PARAMETER = 19;
    const ERROR_INVALID_PARAMETER = 20;
    const ERROR_UNKNOWN_ERROR = 21;
    const ERROR_HAS_ACTIVE_TRIAL_ACCOUNT = 23;
    const ERROR_NOT_FOUND = 404;

    const SUCCESS = 14;
    const SUCCESS_URL_AVAILABLE = 12;
    const SUCCESS_ACCOUNT_CREATED = 13;
    const SUCCESS_ACCOUNT_UPDATED = 22;
    const SUCCESS_ACCOUNT_FOUND = 17;
    const SUCCESS_COMPANY_MOVED = 18;
    const SUCCESS_USER_AVAILABLE = 24;
    const SUCCESS_COMPANY_UPDATED = 25;

    const COMMAND_AUTH = "authenticate";
    const COMMAND_URL = "url";
    const COMMAND_ACCOUNT = "account";
    const COMMAND_COMPANY = "company";
    const COMMAND_SERVICE = "service";
    const COMMAND_ACCOUNT_PRICE = "account/price";
    const COMMAND_ACCOUNT_DISCOUNT = "account/discount";
    const COMMAND_ACCOUNT_INVOICE = "account/invoice";
    const COMMAND_COMPANY_INTEGRATION = "company/integration";
    const COMMAND_ACCOUNT_REMINDERS = "account/reminders";
    const COMMAND_USER_NOTIFICATION = "user/notification";
    const COMMAND_USER_CONTEXT = "user/context";
    const COMMAND_SSO_CONTEXT = "sso/context";

    const API_URL = "https://api.celerypayroll.com/"; // Include the trailing '/'

    /* @var $restObject \Bili\RestRequest */
    private $restObject;
    private $response;
    private $user;
    private $pass;
    private $url;

    private static $token = null;
    private static $instance = null;

    /**
     * Constructor
     * @param string|null $user API Username
     * @param string|null $pass API Password
     * @param string|null $url API Url
     *
     * @return void
     */
    private function __construct($user = null, $pass = null, $url = null)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->url = (is_null($url)) ? static::API_URL : $url;
    }

    private function authenticate()
    {
        if (empty(self::$token)) {
            if (!empty($this->user) && !empty($this->pass)) {
                $this->restObject = new RestRequest(
                    $this->url . static::COMMAND_AUTH,
                    "POST",
                    array(
                        "username" => $this->user,
                        "password" => $this->pass
                    )
                );
                $this->restObject->execute();
                $this->parseResponse();
                $this->setToken($this->response);
            }
        }
    }

    /**
     * Check Response Error
     * Check if the server response contains an error.
     *
     * @param object $objResponse A JSON object
     * @return void
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    private function checkResponseError($objResponse)
    {
        if ($this->isResponse($objResponse)) {
            if (property_exists($objResponse->response, "error")) {
                throw new \InvalidArgumentException(
                    $objResponse->response->error->message,
                    $objResponse->response->error->code
                );
            }
        } else {
            throw new \Exception("Object is not a valid Celery API response.");
        }
    }

    private function parseResponse()
    {
        $strResponse = $this->restObject->getResponseBody();
        $objResponse = json_decode($strResponse);

        $this->checkResponseError($objResponse);
        $this->response = $objResponse->response;
    }

    protected function isResponse($objResponse)
    {
        return property_exists($objResponse, "response");
    }

    /**
     * Create a new instance of the Client class
     *
     * @param string $user API Username
     * @param string $pass API Password
     * @param string|null $url API Url
     * @return Client|null
     */
    public static function singleton($user, $pass, $url = null)
    {
        self::$instance = new Client($user, $pass, $url);

        return self::$instance;
    }

    /**
     * Return a singleton instance of the Client
     *
     * @return Client Singleton instance of Client
     * @throws \Exception
     */
    public static function getInstance()
    {
        /* Get the singleton instance for this class */
        return self::$instance;
    }

    public function createAccount(
        $name,
        $email,
        $affiliate,
        $language = "nl",
        $celeryUser = null,
        $overrideExistingUser = true
    ){
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT,
            "POST",
            array(
                "token" => self::$token,
                "name" => $name,
                "email" => $email,
                "language" => $language,
                "affiliate" => $affiliate,
                "celeryUser" => $celeryUser,
                "overrideUser" => (bool) $overrideExistingUser
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function updateAccount($accountToken, $arrProperties)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT,
            "POST",
            array(
                "token" => self::$token,
                "account" => $accountToken,
                "properties" => $arrProperties
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function updateAccountPrice($accountToken, $arrProperties)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT_PRICE,
            "POST",
            array(
                "token" => self::$token,
                "account" => $accountToken,
                "properties" => $arrProperties
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function updateAccountReminders($accountToken, $arrProperties)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT_REMINDERS,
            "POST",
            array(
                "token" => self::$token,
                "account" => $accountToken,
                "properties" => $arrProperties
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function createAccountDiscount($accountToken, $arrProperties)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT_DISCOUNT,
            "POST",
            array(
                "token" => self::$token,
                "account" => $accountToken,
                "properties" => $arrProperties
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function updateAccountDiscount($accountToken, $discountId, $arrProperties)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT_DISCOUNT,
            "PUT",
            array(
                "token" => self::$token,
                "account" => $accountToken,
                "discount" => $discountId,
                "properties" => $arrProperties
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function deleteAccountDiscount($accountToken, $discountId)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT_DISCOUNT,
            "DELETE",
            array(
                "token" => self::$token,
                "account" => $accountToken,
                "discount" => $discountId
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function updateCompany($accountToken, $companyToken, $arrProperties)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_COMPANY,
            "POST",
            array(
                "token" => self::$token,
                "account" => $accountToken,
                "company" => $companyToken,
                "properties" => $arrProperties
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function updateCompanyIntegration($accountToken, $companyToken, $integrationId, $arrProperties)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_COMPANY_INTEGRATION,
            "PUT",
            array(
                "token" => self::$token,
                "account" => $accountToken,
                "company" => $companyToken,
                "integration" => $integrationId,
                "properties" => $arrProperties
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function createAccountInvoice($accountToken)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT_INVOICE,
            "POST",
            array(
                "token" => self::$token,
                "action" => "create",
                "account" => $accountToken
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function syncAccountInvoice($accountToken, $invoiceToken)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT_INVOICE,
            "POST",
            array(
                "token" => self::$token,
                "action" => "sync",
                "account" => $accountToken,
                "invoice" => $invoiceToken
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function syncAccountInvoices($accountToken)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT_INVOICE,
            "POST",
            array(
                "token" => self::$token,
                "action" => "sync",
                "account" => $accountToken
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function sendAccountInvoice($accountToken, $invoiceToken)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT_INVOICE,
            "POST",
            array(
                "token" => self::$token,
                "action" => "send",
                "account" => $accountToken,
                "invoice" => $invoiceToken
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function moveCompany($originAccountToken, $companyToken, $accountToken)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_COMPANY,
            "POST",
            array(
                "token" => self::$token,
                "account" => $originAccountToken,
                "company" => $companyToken,
                "destination" => $accountToken,
                "action" => "move"
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function getAccount($domain)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT,
            "GET",
            array(
                "token" => self::$token,
                "domain" => $domain
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function searchAccount($email, $language = "nl")
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_ACCOUNT,
            "GET",
            array(
                "token" => self::$token,
                "search" => $email,
                "language" => $language
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function sendNotification(array $arrNotification)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_USER_NOTIFICATION,
            "POST",
            array(
                "token" => self::$token,
                "notification" => $arrNotification
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function impersonateUserContext($intUserContextId, $intUserId)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_USER_CONTEXT,
            "POST",
            array(
                "token" => self::$token,
                "contextId" => $intUserContextId,
                "userId" => $intUserId
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function getSsoContexts($strProviderId)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_SSO_CONTEXT,
            "GET",
            array(
                "token" => self::$token,
                "providerId" => $strProviderId
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function setSsoContexts($strProviderId, $strSource, $arrContexts)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_SSO_CONTEXT,
            "POST",
            array(
                "token" => self::$token,
                "providerId" => $strProviderId,
                "source" => $strSource,
                "data" => $arrContexts
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function deleteSsoContext($strProviderId, $strSource, $intContextId = null)
    {
        $this->authenticate();
        
        $arrParameters = [
            "token" => self::$token,
            "providerId" => $strProviderId,
            "source" => $strSource
        ];

        if (!empty($intContextId)) {
            $arrParameters["contextId"] = $intContextId;
        }

        $this->restObject = new RestRequest(
            $this->url . static::COMMAND_SSO_CONTEXT,
            "DELETE",
            $arrParameters
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function call($strMethod)
    {
        $strUrl = str_replace(".", "/", $strMethod);

        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . $strUrl,
            "GET",
            array(
                "token" => self::$token
            )
        );

        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    // Get/set authentication token
    private function setToken($objResponse)
    {
        if (is_object($objResponse) && property_exists($objResponse, "token")) {
            self::$token = $objResponse->token;
        } else {
            throw new \Exception("Invalid argument passed to setToken(). Object expected.");
        }
    }
}
