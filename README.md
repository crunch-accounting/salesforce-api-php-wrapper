# SalesForce Library

Warning : Work in progess

###Aim

Call The SalesForce API (v37.0 at this time) in order to call API functionnalities easily.
The main advantage is that you don't have to be preoccuped by the token, the library maintains it.
Also, you are authentificated correctly to make a CLI application.

### Install
 ```$ composer require akeneo-salesforce/salesforce-api```

### Documentation

#### Usage

##### The client

```
$client = new Akeneo\SalesForce\Connector\SalesForceClient(
    $myUsername,
    $myPassword,
    $myClientId,
    $myClientSecret,
    $myLoginUrl,
    new GuzzleHttp\Client(),
    new Akeneo\SalesForce\Authentification\AccessTokenGenerator()
);

```

#### Usage in Symfony

This bundle can be easily integrated to a Symfony2 project,
you just have to declare two services:

First declare your parameters in your parameters.yml
 * sales_force.username
 * sales_force.password
 * sales_force.client_id
 * sales_force.client_secret
 * sales_force.login_url (For example: 'https://login.salesforce.com/')

```
services:
    akeneo_sales_force.authentification.token_generator:
        class: Akeneo\SalesForce\Authentification\AccessTokenGenerator

    akeneo_sales_force.connector.client:
        class: Akeneo\SalesForce\Connector\SalesForceClient
        arguments:
            - "%sales_force.username%"
            - "%sales_force.password%"
            - "%sales_force.client_id%"
            - "%sales_force.client_secret%"
            - "%sales_force.login_url%"
            - "@guzzle.client"
            - "@akeneo_sales_force.authentification.token_generator"
```

And then use your client service like any other service.

#### Make a query

A QueryBuilder is given to make [SOQL](https://developer.salesforce.com/docs/atlas.en-us.202.0.soql_sosl.meta/soql_sosl/) query.

See available functionnalities into the class itself.

Example
```
$queryBuilder = new Akeneo\SalesForce\Query\QueryBuilder();

$queryBuilder
            ->select('Id')
            ->from('Account')
            ->where($queryBuilder->getNotEqualCondition('Name', ':nameId'))
            ->setParameter('nameId', 'AccountPlop')
        ;

$client->search($queryBuilder->getQuery());

```
### Next

- Fit the old repo structure -> [Thanks to Crunch](https://github.com/crunch-accounting/salesforce-api-php-wrapper)
- Make tests with PHP Spec

### More

This library is made with <3 by [Akeneo](https://www.akeneo.com/)

Do not hesitate to contribute.

Maintained by Anaël CHARDAN.
