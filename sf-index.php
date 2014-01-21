<?php
use Salesforce\SalesforceBundle\Entity\SalesforceRestClient;
/**
 * Use within Symfony2 container
 */

// config.yml
services:
    sfrestclient:
        class:        Salesforce\SalesforceBundle\Entity\SalesforceRestClient
        arguments:    ['KEY','SECRET','OAUTHURL','USER','PASS']

// call from bundles
$client = $this->get('sfrestclient');

$dataArray = array(
                    'Flight_arrival_number__c' => $data['Flight_arrival_number__c'],
                    'Flight_arrival_city__c' => $data['Flight_arrival_city__c'],
                );

$results = $client->updateRecord(RECORDID,SALESFORCEOBJECT,$dataQuery);

/**
 * General use
 */
$restAPI = new SalesforceRestClient(
            'KEY',
            'SECRET',
            'OAUTHURL',
            'SALESFORCEUSER',
            'SALESFORCEPASS'
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