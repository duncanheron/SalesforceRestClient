<?php
use Salesforce\SalesforceClient;

$config = array(
    'consumerKey' => 'KEY',
    'consumerSecret' => 'SECRET',
    'redirectUri' => 'OAUTHURL',
    'sfUsername' => 'SALESFORCEUSER',
    'sfPassword' => 'SALESFORCEPASS'
);

$sfClient = new SalesforceRestClient(
    $config,
    new Guzzle\Http\Client
);

$accounts = $restAPI->getRecords('SELECT Name, Id from Account LIMIT 10');

foreach ($accounts as $record) {
    print 'Name : ';
    print htmlspecialchars($record->Name);
    print ' - ';
    print htmlspecialchars($record->Id);
    print '<br/>';
    print "\n";
}

$contacts = $restAPI->getRecords('SELECT FirstName,LastName, Id from Contact LIMIT 10');

foreach ($contacts as $contact) {
    print 'Name : ';
    print htmlspecialchars($contact->FirstName);
    print ' ';
    print htmlspecialchars($contact->LastName);
    print ' - ';
    print htmlspecialchars($contact->Id);
    print '<br/>';
    print "\n";
}
