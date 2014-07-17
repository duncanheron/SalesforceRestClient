### Salesforce Rest API
A php class to interact with the Salesforce REST API<br /><br />
Requirements:<br />
Because I find using curl cumbersome I chose to use Guzzle to make my REST requests.

### The obvious
```php
use Salesforce\SalesforceBundle\Entity\SalesforceRestClient;
$restClient = new SalesforceRestClient(
	$config,
    new Guzzle\Http\Client
);
```

### Describing a Salesforce object
```php
$object = $restClient->describeObject('Account');

if ($object) {
    print '<pre>' . $object. '</pre>';
}
```  

### Retrieving multiple records
```php
$soql = 'SELECT Name, Id from Account LIMIT 10';
$records = $restClient->getRecords($soql);

foreach ($records as $record) {
    print 'Name : ' . $record->Name;
}
```

### Retrieving a single record
```php
$soql = 'SELECT Name, Id from Account LIMIT 1';
$record = $restClient->getRecord($soql);

if ($record) {
    print 'Name : ' . $record->Name;
}
```

### Creating a record
```php
$data = array(
			'LastName' => 'Heron',
			'FirstName' => 'Duncan'
			);

$insertContact = $restClient->createRecord('Contact',$data);

if($insertContact->success) {
	print 'Record created: ' . $insertContact->id;
}
```

### Updating a record
```php
$recordId = 'SOMESALESFORCECONTACTID';
$data = array(
			'LastName' => 'Heron',
			'FirstName' => 'Duncan'
			);

$updateContact = $restClient->updateRecord($recordId,'Contact',$data);

```  


More examples in <a href="http://github.com/duncanheron/SalesforceRestClient/blob/master/sf-index.php">index</a> file.

  

### Author
```
Duncan Heron
```
