<?php
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
    private $pest;

    /**
     * Setup rest auth for web app to salesforce
     * Creates sessions but nothing returned
     * @param [string]  $consumerKey
     * @param [string]  $consumerSecret
     * @param [string]  $redirectUri
     * @param [string]  $sfUsername
     * @param [string]  $sfPassword
     * @param boolean $sandbox
     */
    public function __construct($consumerKey, $consumerSecret, $redirectUri, $sfUsername, $sfPassword, $sfApiId, $sandbox = false) {

        $this->setLoginBaseUrl();
        $this->setConsumerKey($consumerKey);
        $this->setConsumerSecret($consumerSecret);
        $this->setRedirectUri($redirectUri);
        $this->setSfUsername($sfUsername);
        $this->setSfPassword($sfPassword);
        $this->setApiUserId($sfApiId);

        // create a rest object - Pest in this case
        $this->getRest();
        
        if(! $this->isAuthorized()) {
            $this->authUsertoSalesforce();
        } else {
            $this->setAccessToken($_SESSION['access_token']);
            $this->setInstanceUrl($_SESSION['instance_url']);
        }
    }

    /**
     * Set the url based on wether the salesforce installation is in sandbox or not
     * @param boolean $sandbox
     */
    public function setLoginBaseUrl()
    {
        if(! $sandbox) {
            $this->loginBaseUrl = 'https://test.salesforce.com';
        }
        else {
            $this->loginBaseUrl = 'https://login.salesforce.com';   
        }
        
    }

    public function setApiUserId($apiUserId)
    {
        $this->apiUserId = $apiUserId;
    }

    public function getApiUserId()
    {
        return $this->apiUserId;
    }

    public function setConsumerKey($key)
    {
        $this->consumerKey = $key;
    }

    public function setConsumerSecret($secret)
    {
        $this->consumerSecret = $secret;
    }

    public function setRedirectUri($url)
    {
        $this->redirectUri = $url;
    }

    public function setSfUsername($user)
    {
        $this->sfUsername = $user;
    }

    public function setSfPassword($pass)
    {
        $this->sfPassword = $pass;
    }

    public function getInstanceUrl()
    {
        return $this->instanceUrl;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setInstanceUrl($url)
    {
        $this->instanceUrl = $url;
    }

    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * get the access token from salesforce which allows rest calls to be made
     * sessions and object variables configured
     */
    public function authUsertoSalesforce()
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
            $thing = $this->pest->post($postQueryUrl, $data, $headers);
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
        $this->refreshRest();

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
     * Used for inital auth and access token
     */
    public function getRest()
    {
        $url = $this->loginBaseUrl;
        if (! $this->pest) {
            include_once $_SERVER['DOCUMENT_ROOT'] . "/pest/Pest.php";
            $this->pest = new Pest($url);
        }
        return $this->pest;
    }

    /**
     * used after auth as object base url changes
     */
    public function refreshRest()
    {
        if(!$this->getInstanceUrl())
            die("no instance url found");

        $url = $this->getInstanceUrl();
        include_once $_SERVER['DOCUMENT_ROOT'] . "/pest/Pest.php";
        $this->pestQuery = new Pest($url);
        
        return $this->pestQuery;
    }

    /**
     * Return records from salesforce
     * @param  string $soql
     * @return recordSet object
     */
    public function getRecords($soql, $singleRecord = false) {

        $this->refreshRest();
        $data = array('q' => $soql);

        $headers = array('Authorization' => 'OAuth '. $this->getAccessToken());

        try {
            $thing = $this->pestQuery->get('/services/data/v20.0/query', $data, $headers);
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
        return $record[0];
    }

    /**
     * creates a new record in Salesforce
     * @param  string $sfObject
     * @param  array $data
     * @return response obj
     */
    public function createRecord($sfObject,$data) {

        $this->refreshRest();

        $data = json_encode($data);

        $headers = array(
                        'Authorization' => 'OAuth '. $this->getAccessToken(),
                        "Content-type" => "application/json"
                        );

        try {
            $thing = $this->pestQuery->post('/services/data/v20.0/sobjects/'.$sfObject."/", $data, $headers);
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

        $this->refreshRest();

        $data = json_encode($data);

        $headers = array(
                        'Authorization' => 'OAuth '. $this->getAccessToken(),
                        "Content-type" => "application/json"
                        );

        try {
            $thing = $this->pestQuery->patch('/services/data/v20.0/sobjects/'.$sfObject."/".$id."/", $data, $headers);
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
        $this->refreshRest();

        $headers = array(
                        'Authorization' => 'OAuth '. $this->getAccessToken(),
                        "Content-type" => "application/json"
                        );

        try {
            $thing = $this->pestQuery->get('/services/data/v20.0/sobjects/'.$sfObject."/describe/", $data, $headers);
        } catch (Exception $e) {            
            echo $e->getMessage();
        }

        $response = json_decode($thing);

        bk_debug($response);
    }

}