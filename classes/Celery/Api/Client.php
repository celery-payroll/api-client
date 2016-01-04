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
define("CAPI_EMAIL_EXISTS", 5);
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

// Succes
define("CAPI_URL_AVAILABLE", 12);
define("CAPI_ACCOUNT_CREATED", 13);
define("CAPI_SUCCESS", 14);
define("CAPI_COMPANY_MOVED", 18);

// Methods
define("CAPI_CMD_AUTH", "authenticate");
define("CAPI_CMD_URL", "url");
define("CAPI_CMD_ACCOUNT", "account");
define("CAPI_CMD_COMPANY", "company");
define("CAPI_CMD_PRICE", "price");
define("CAPI_CMD_SERVICE", "service");

// Config
define("CAPI_URL", "https://api.celerypayroll.com/"); // Include the trailing '/'

 /**
  * Client API class
  */
class Client
{
    private $restObject;
    private $response;
    private $user;
    private $pass;
    private $url;

    private static $token = null;
    private static $instance = null;

    /**
     * Constructor
     * @param string $strUser API Username
     * @param string $strPass API Password
     * @param string $strUrl API Url
     *
     * @return void
     */
    private function __construct($user = null, $pass = null, $url = null)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->url = (is_null($url)) ? CAPI_URL : $url;
    }

    private function authenticate()
    {
        if (empty(self::$token)) {
            if (!empty($this->user) && !empty($this->pass)) {
                $this->restObject = new RestRequest(
                    $this->url . CAPI_CMD_AUTH,
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
     *
     * @return void
     * @throws \InvalidArgumentException
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
     * @return Client Singleton instance of Client
     * @throws \Exception
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
    public static function getInstance($user = null, $pass = null, $url = null)
    {
        /* Get the singleton instance for this class */
        return self::$instance;
    }

    public function createAccount($name, $email, $affiliate, $language = "nl")
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . CAPI_CMD_ACCOUNT,
            "POST",
            array(
                "token" => self::$token,
                "name" => $name,
                "email" => $email,
                "language" => $language,
                "affiliate" => $affiliate
            )
        );
        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result;
    }

    public function moveCompany($companyToken, $accountToken)
    {
        $this->authenticate();
        $this->restObject = new RestRequest(
            $this->url . CAPI_CMD_COMPANY,
            "POST",
            array(
                "token" => self::$token,
                "company" => $companyToken,
                "account" => $accountToken
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
            $this->url . CAPI_CMD_ACCOUNT,
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
            $this->url . CAPI_CMD_ACCOUNT,
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

    public function checkUrl($url)
    {
        $blnReturn = false;
        $this->restObject = new RestRequest(
            $this->url . CAPI_CMD_URL,
            "POST",
            array(
                "url" => $url
            )
        );

        $this->restObject->execute();
        $this->parseResponse();

        if (property_exists($this->response, "result")) {
            $blnReturn = ($this->response->result->code == CAPI_URL_AVAILABLE);
        }

        return $blnReturn;
    }

    public function getPrice($intCompanies, $intEmployees)
    {
        $blnReturn = false;
        $this->restObject = new RestRequest(
            $this->url . CAPI_CMD_PRICE,
            "POST",
            array(
                "companies" => $intCompanies,
                "employees" => $intEmployees,
            )
        );

        $this->restObject->execute();
        $this->parseResponse();

        return $this->response->result->message;
    }

    public function call($strMethod)
    {
        $blnReturn = false;

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
