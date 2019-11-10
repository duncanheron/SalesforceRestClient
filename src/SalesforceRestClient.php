<?php
namespace Salesforce\Client;

use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Client;

class SalesforceRestClient{

    private $consumerKey;
    private $consumerSecret;
    private $redirectUri;
    private $loginBaseUrl;
    private $isAuthorized = 0;
    private $sfUsername;
    private $sfPassword;
    private $apiUserId;
    private $accessToken;
    private $instanceUrl;
    private $client;

    public function __construct(Array $conf, Client $guzzleClient) {

        $this->client = $guzzleClient;

        $this->setLoginBaseUrl($conf['sandbox']);
        $this->setConsumerKey($conf['consumerKey']);
        $this->setConsumerSecret($conf['consumerSecret']);
        $this->setRedirectUri($conf['redirectUri']);
        $this->setSfUsername($conf['sfUsername']);
        $this->setSfPassword($conf['sfPassword']);

        if(! $this->isAuthorized()) {
            $this->authUsertoSalesforce();
        } else {
            $this->setAccessToken($_SESSION['access_token']);
            $this->setInstanceUrl($_SESSION['instance_url']);
        }
    }

    
    /**
     * get the access token from salesforce which allows rest calls to be made
     * sessions and object variables configured
     */
    private function authUsertoSalesforce()
    {
        $postQueryUrl = '/services/oauth2/token';

        $data = array(
            'username' => $this->sfUsername,
            'password' => $this->sfPassword,
            'grant_type' => 'password',
            'client_id' => $this->consumerKey,
            'client_secret' => $this->consumerSecret,
            'redirect_uri' => $this->redirectUri,
        );

        try {
            $thing = $this->client->post($postQueryUrl, $data);
        } catch (Exception $e) {
            unset($_SESSION['access_token']);
            unset($_SESSION['instance_url']);
            echo $e->getMessage();
        }

        $query_request_data = json_decode($thing);
        
        if(! $query_request_data->access_token || ! $query_request_data->instance_url) {
            $this->isAuthorized = 0;
            die("Invalid access token from Salesforce");
        }
        
        $this->setAccessToken($query_request_data->access_token);
        $this->setInstanceUrl($query_request_data->instance_url);

        $_SESSION['access_token'] = $this->getAccessToken();
        $_SESSION['instance_url'] = $this->getInstanceUrl();
    }

    private function isAuthorized()
    {
        if(isset($_SESSION['access_token']) && $_SESSION['instance_url']) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Return records from salesforce
     * @param  string $soql
     * @return recordSet object
     */
    public function getRecords($soql, $singleRecord = false) {

        $data = array('q' => $soql);

        $headers = array('Authorization' => 'OAuth '. $this->getAccessToken());

        try {
            $thing = $this->client->get('/services/data/v29.0/query', $data, $headers);
        } catch (Exception $e) {
            unset($_SESSION['access_token']);
            unset($_SESSION['instance_url']);
            echo $e->getMessage();
        }

        $query_request_data = json_decode($thing);

        $records = $query_request_data->records;

        if($singleRecord) {
            return $records[0];
        }
        else {
            return $records;
        }
        
    }

    /**
     * Return single record from salesforce
     * wrapper for getRecords to only return first array object
     * @param  string $soql
     * @return record object
     */
    public function getRecord($soql) {
        
        $record = $this->getRecords($soql);
        if($record) {
            return $record[0];
        }
        
    }

    /**
     * creates a new record in Salesforce
     * @param  string $sfObject
     * @param  array $data
     * @return response obj
     */
    public function createRecord($sfObject,$data) {

        $data = json_encode($data);

        $headers = array(
                        'Authorization' => 'OAuth '. $this->getAccessToken(),
                        "Content-type" => "application/json"
                        );

        try {
            $thing = $this->client->post('/services/data/v29.0/sobjects/'.$sfObject."/", $data, $headers);
        } catch (Exception $e) {            
            echo $e->getMessage();
        }

        $response = json_decode($thing);

        return $response;
        
    }

    /**
     * update a record in Salesforce
     * @param  string $sfObject
     * @param  array $data
     * @return response obj
     */
    public function updateRecord($id, $sfObject, $data) {

        $data = json_encode($data);

        $headers = array(
                        'Authorization' => 'OAuth '. $this->getAccessToken(),
                        "Content-type" => "application/json"
                        );

        try {
            $thing = $this->client->patch('/services/data/v29.0/sobjects/'.$sfObject."/".$id."/", $data, $headers);
        } catch (Exception $e) {            
            echo $e->getMessage();
        }
        
        $response = json_decode($thing);

        return $response;
        
    }

    /**
     * Prints out object description from salesforce - format bk_debug
     * @param  string $sfObject object name in salesforce
     * @return bk_debug output
     */
    public function describeObject($sfObject)
    {
        $headers = array(
            'Authorization' => 'OAuth '. $this->getAccessToken(),
            "Content-type" => "application/json"
        );

        try {
            $thing = $this->client->get('/services/data/v29.0/sobjects/'.$sfObject."/describe/", false, $headers);
        } catch (Exception $e) {            
            echo $e->getMessage();
        }

        $response = json_decode($thing);

        return $response;
    }

    /**
     * Set the url based on wether the salesforce installation is in sandbox or not
     * @param boolean $sandbox
     */
    private function setLoginBaseUrl($sandbox)
    {
        if(! $sandbox) {
            $this->loginBaseUrl = 'https://test.salesforce.com';
        }
        else {
            $this->loginBaseUrl = 'https://login.salesforce.com';   
        }
    }

    private function setApiUserId($apiUserId)
    {
        $this->apiUserId = $apiUserId;
    }

    private function getApiUserId()
    {
        return $this->apiUserId;
    }

    private function setConsumerKey($key)
    {
        $this->consumerKey = $key;
    }

    private function setConsumerSecret($secret)
    {
        $this->consumerSecret = $secret;
    }

    private function setRedirectUri($url)
    {
        $this->redirectUri = $url;
    }

    private function setSfUsername($user)
    {
        $this->sfUsername = $user;
    }

    private function setSfPassword($pass)
    {
        $this->sfPassword = $pass;
    }

    private function getInstanceUrl()
    {
        return $this->instanceUrl;
    }

    private function getAccessToken()
    {
        return $this->accessToken;
    }

    private function setInstanceUrl($url)
    {
        $this->instanceUrl = $url;
    }

    private function setAccessToken($token)
    {
        $this->accessToken = $token;
    }


}