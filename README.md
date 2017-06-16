[![Current Version](https://img.shields.io/packagist/v/crunch-accounting/salesforce-api.svg?style=flat-square)](https://packagist.org/packages/crunch-accounting/salesforce-api)
[![License](https://img.shields.io/packagist/l/crunch-accounting/salesforce-api.svg?style=flat-square)](https://packagist.org/packages/crunch-accounting/salesforce-api)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/crunch-accounting/salesforce-api-php-wrapper.svg?style=flat-square)](https://scrutinizer-ci.com/g/crunch-accounting/salesforce-api-php-wrapper/)
[![Travis](https://img.shields.io/travis/crunch-accounting/salesforce-api-php-wrapper.svg?style=flat-square)](https://travis-ci.org/crunch-accounting/salesforce-api-php-wrapper)

# Salesforce PHP Library

A simple library for interacting with the Salesforce REST API.

Methods for setting up a connection, requesting an access token, refreshing the access token, saving the access token, and making calls against the API.


## Getting started

__Installation:__
The package should be installed through composer and locked to a major version
```
composer require crunch-accounting/salesforce-api:~1.0
```

__Creating an oauth token:__
You need to fetch an access token for a user, all followup requests will be performed against this user.

```php
$sfClient = \Crunch\Salesforce\Client::create('https://test.salesforce.com/', 'clientid', 'clientsecret');

if ( ! isset($_GET['code'])) {

    $url = $sfClient->getLoginUrl('http://example.com/sf-login');
    header('Location: '.$url);
    exit();

} else {

    $token = $sfClient->authorizeConfirm($_GET['code'], 'http://example.com/sf-login');
    $tokenGenerator = new \Crunch\Salesforce\AccessTokenGenerator();
    $accessToken = $tokenGenerator->createFromSalesforceResponse($token);
    
    $_SESSION['accessToken'] = $accessToken->toJson();

}

```


__Performing an action:__
Once you have an access token you can perform requests against the API.

```php
$sfClient = \Crunch\Salesforce\Client::create('https://test.salesforce.com/', 'clientid', 'clientsecret');
$tokenGenerator = new \Crunch\Salesforce\AccessTokenGenerator();
$accessToken = $tokenGenerator->createFromJson($_SESSION['accessToken']);
$sfClient->setAccessToken($accessToken);

$results = $sfClient->search('SELECT Name, Email FROM Lead Limit 10');
print_r($results);

```

The token will expire after an hour so you should make sure you're checking the expiry time and refreshing accordingly.

## Setting up the Salesforce client

The client can be configured in two ways, you can call the static create method above passing in the login url and oauth 
details or you can use a configuration object as in the example below. This is useful when you need to resolve 
the client out of an ioc container.
 
The configuration data for the client is passed in through a config file which must implement `\Crunch\Salesforce\ClientConfigInterface`

For example

```php
class SalesforceConfig implements \Crunch\Salesforce\ClientConfigInterface {

    /**
     * @return string
     */
    public function getLoginUrl()
    {
        return 'https://test.salesforce.com/';
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return 'clientid';
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return 'clientsecret';
    }
}

```

A config class is provided and can be used if needed. `\Crunch\Salesforce\ClientConfig`


The Salesforce client can then be instantiated with the config object and an instance of the Guzzle v4 client.

```php
$sfConfig = new SalesforceConfig();
$sfClient = new \Crunch\Salesforce\Client($sfConfig, new GuzzleHttp\Client());

```

## Authentication
Authentication happens via oauth2 and the login url can be generated using the `getLoginUrl` method, you should pass this your return url for the send stage of the oauth process.

```php
$url = $sfClient->getLoginUrl('http://exmaple.com/sf-login');

```

You should redirect the user to this returned url, on completion they will be redirected back with a code in the query string.

The second stage of the authentication can then be completed.

```php
$token = $sfClient->authorizeConfirm($_GET['code'], 'http://exmaple.com/sf-login');

```

The token returned from here is the raw data and can be passed to the access token generator to make an `AccessToken`.


```php
$tokenGenerator = new \Crunch\Salesforce\AccessTokenGenerator();
$accessToken = $tokenGenerator->createFromSalesforceResponse($token);

```

### Storing the access token
This access token should be stored. A method to store this on the file system is provided but this isn't required.

The example above uses the php session to achieve the same result.

The `LocalFileStore` object needs to be instantiated with access to the token generator and a config class which implements `\Crunch\Salesforce\TokenStore\LocalFileConfigInterface`

```php
class SFLocalFileStoreConfig implements \Crunch\Salesforce\TokenStore\LocalFileConfigInterface {

    /**
     * The path where the file will be stored, no trailing slash, must be writable
     *
     * @return string
     */
    public function getFilePath()
    {
        return __DIR__;
    }
}

```

The token store can then be created and used to save the access token to the local file system as well as fetching a previously saved token.

```php
$tokenStore = new \Crunch\Salesforce\TokenStore\LocalFile(new \Crunch\Salesforce\AccessTokenGenerator, new SFLocalFileStoreConfig);

//Save a token
$tokenStore->saveAccessToken($accessToken);

//Fetch a token
$accessToken = $tokenStore->fetchAccessToken();

```

### Refreshing the token
The access token only lasts 1 hour before expiring so you should regularly check its status and refresh it accordingly.

```php
$accessToken = $tokenStore->fetchAccessToken();

if ($accessToken->needsRefresh()) {

	$accessToken = $sfClient->refreshToken();

    $tokenStore->saveAccessToken($accessToken);
}

```

## Making requests

Before making a request you should instantiate the client as above and then assign the access token to it.

```php
$sfConfig = new SalesforceConfig();
$sfClient = new \Crunch\Salesforce\Client($sfConfig, new \GuzzleHttp\Client());

$sfClient->setAccessToken($accessToken);

```

### Performing an SOQL Query
This is a powerful option for performing general queries against your salesforce data.
Simply pass a valid query to the search method and the resulting data will be returned.

```php
$data = $sfClient->search('SELECT Email, Name FROM Lead LIMIT 10');

```

### Fetching a single record
If you know the id and type of a record you can fetch a set of fields from it.

```php
$data = $sfClient->getRecord('Lead', '00WL0000008wVl1MDE', ['name', 'email', 'phone']);

```

### Creating and updating records
The process for creating and updating records is very similar and can be performed as follows.
The createRecord method will return the id of the newly created record.

```php
$data = $sfClient->createRecord('Lead', ['email' => 'foo@example.com', 'Company' => 'New test', 'lastName' => 'John Doe']);

$sfClient->updateRecord('Lead', '00WL0000008wVl1MDE', ['lastName' => 'Steve Jobs']);

```

### Deleting records
Records can be deleted based on their id and type.

```php
$sfClient->deleteRecord('Lead', '00WL0000008wVl1MDE');

```

## Errors
If something goes wrong the library will throw an exception. 

If its an authentication exception such as an expired token this will be as `Crunch\Salesforce\Exceptions\AuthenticationException`,
you can get the exact details using the methods `getMessage` and `getErrorCode`.

All other errors will be `Crunch\Salesforce\Exceptions\RequestException`, the salesforce error will be in the message


```php
try {
    
    $results = $sfClient->search('SELECT Name, Email FROM Lead Limit 10');
    print_r($results);

} catch (\Crunch\Salesforce\Exceptions\RequestException $e) {

    echo $e->getMessage();
    echo $e->getErrorCode();

} catch (\Crunch\Salesforce\Exceptions\AuthenticationException $e) {

    echo $e->getErrorCode();
    
}

```
