<?php

namespace Akeneo\SalesForce\Connector;

use Akeneo\SalesForce\Authentification\AccessToken;
use Akeneo\SalesForce\Authentification\AccessTokenGenerator;
use Akeneo\SalesForce\Exception\AuthenticationException;
use Akeneo\SalesForce\Exception\DuplicateDetectedException;
use Akeneo\SalesForce\Search\ParameterizedSearchBuilder;
use GuzzleHttp\Client as GuzzleClient;
use Akeneo\SalesForce\Exception\RequestException;

/**
 * @author Anael Chardan <anael.chardan@akeneo.com>
 */
class SalesForceClient
{
    const BASE_API_URL    = '/services/data/v37.0/sobjects';
    const BASE_QUERY_URL  = '/services/data/v37.0/query';
    const BASE_SEARCH_URL = '/services/data/v37.0/parameterizedSearch';

    /**
     * @var string
     */
    protected $salesforceLoginUrl;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var GuzzleClient
     */
    protected $clientGuzzle;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var AccessTokenGenerator
     */
    protected $accessTokenGenerator;

    /**
     * @var AccessToken
     */
    protected $salesForceAccessToken;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        string $username,
        string $password,
        string $clientId,
        string $clientSecret,
        string $salesForceLoginUrl,
        GuzzleClient $guzzleClient,
        AccessTokenGenerator $accessTokenGenerator
    ) {
        $this->username             = $username;
        $this->password             = $password;
        $this->clientId             = $clientId;
        $this->clientSecret         = $clientSecret;
        $this->salesforceLoginUrl   = $salesForceLoginUrl;
        $this->clientGuzzle         = $guzzleClient;
        $this->accessTokenGenerator = $accessTokenGenerator;
    }

    public function search($query = null, $nextUrl = null)
    {
        $url = !empty($nextUrl) ?
            sprintf('%s/%s', $this->getBaseUrl(), $nextUrl) :
            sprintf(
                '%s%s/?q=%s',
                $this->getBaseUrl(),
                static::BASE_QUERY_URL,
                urlencode($query)
            )
        ;

        $response = $this->request(HttpWords::GET, $url, $this->getHeaderWithAuthorization());
        $data     = json_decode($response->getBody(), true);

        $results = $data['records']; // or $data['searchRecords']

        if (!$data['done']) {
            $more_results = $this->search(null, substr($data['nextRecordsUrl'], 1));
            if (!empty($more_results)) {
                $results = array_merge($results, $more_results);
            }
        }

        return $results;
    }

    public function parameterizedSearch(string $query)
    {
        $url = sprintf(
            '%s%s/?q=%s',
            $this->getBaseUrl(),
            static::BASE_SEARCH_URL,
            $query
        );

        $response = $this->request(HttpWords::GET, $url, $this->getHeaderWithAuthorization());
        $data     = json_decode($response->getBody(), true);
        $results = $data['searchRecords'];

        return $results;
    }

    public function getAllRessources()
    {
        $url      = sprintf('%s%s', $this->getBaseUrl(), static::BASE_API_URL);
        $response = $this->request(HttpWords::GET, $url, $this->getHeaderWithAuthorization());

        return json_decode($response->getBody(), true);
    }

    public function getBasicInformation(string $objectName)
    {
        $url      = sprintf('%s%s/%s', $this->getBaseUrl(), static::BASE_API_URL, $objectName);
        $response = $this->request(HttpWords::GET, $url, $this->getHeaderWithAuthorization());

        return json_decode($response->getBody(), true);
    }

    public function getDescribedObject(string $objectName)
    {
        $url      = sprintf(
            '%s%s/%s/describe',
            $this->getBaseUrl(),
            static::BASE_API_URL,
            $objectName
        );
        $response = $this->request(HttpWords::GET, $url, $this->getHeaderWithAuthorization());

        return json_decode($response->getBody(), true);
    }

    public function findById($objectName, $objectId, array $fields = [])
    {
        $url      = sprintf(
            '%s%s/%s/%s?fields=%s',
            $this->getBaseUrl(),
            static::BASE_API_URL,
            $objectName,
            $objectId,
            implode(',', $fields)
        );
        $response = $this->request(HttpWords::GET, $url, $this->getHeaderWithAuthorization());

        return json_decode($response->getBody(), true);
    }

    public function insert($objectName, array $data = [])
    {
        $url      = sprintf('%s%s/%s/', $this->getBaseUrl(), static::BASE_API_URL, $objectName);
        $response = $this->request(HttpWords::POST, $url, $this->getHeaderWithAuthorizationAndData($data));

        return json_decode($response->getBody(), true);
    }

    public function upsertByExternalId(
        string $objectName,
        string $externalIdName,
        string $externalIdValue,
        array $data = []
    ) {
        $url      = sprintf(
            '%s%s/%s/%s/%s',
            $this->getBaseUrl(),
            static::BASE_API_URL,
            $objectName,
            $externalIdName,
            $externalIdValue
        );
        $response = $this->request(HttpWords::PATCH, $url, $this->getHeaderWithAuthorizationAndData($data));

        return json_decode($response->getBody(), true);
    }

    public function update($objectName, $objectId, $data = [])
    {
        $url      = sprintf(
            '%s%s/%s/%s',
            $this->getBaseUrl(),
            static::BASE_API_URL,
            $objectName,
            $objectId
        );
        $response = $this->request(HttpWords::PATCH, $url, $this->getHeaderWithAuthorizationAndData($data));

        return json_decode($response->getBody(), true);
    }

    public function delete($objectName, $objectId)
    {
        $url = sprintf(
            '%s%s/%s/%s',
            $this->getBaseUrl(),
            static::BASE_API_URL,
            $objectName,
            $objectId
        );

        $this->request(HttpWords::DELETE, $url, $this->getHeaderWithAuthorization());

        return true;
    }

    protected function getBaseUrl()
    {
        if ($this->baseUrl == null) {
            $this->setupToken();
        }

        return $this->baseUrl;
    }

    /**
     * @param AccessToken $accessToken
     */
    public function setAccessToken(AccessToken $accessToken)
    {
        $this->salesForceAccessToken = $accessToken;
        $this->baseUrl               = $accessToken->getApiUrl();
    }

    protected function getHeaderWithAuthorizationAndData(array $data = [])
    {
        return [
            HttpWords::HEADERS => $this->getHeaderJsonAndAuthorization(),
            HttpWords::BODY    => json_encode($data),
        ];
    }

    protected function getHeaderWithAuthorization()
    {
        return [HttpWords::HEADERS => $this->getAuthorizationField()];
    }

    protected function getHeaderJsonAndAuthorization()
    {
        return array_merge([HttpWords::CONTENT_TYPE => HttpWords::APPLICATION_JSON], $this->getAuthorizationField());
    }

    protected function getAuthorizationField()
    {
        $this->setupToken();

        $value = sprintf('%s %s', HttpWords::BEARER, $this->salesForceAccessToken->getAccessToken());

        return [HttpWords::AUTHORIZATION => $value];
    }

    /**
     * Refresh an existing access token.
     *
     * @throws \Exception
     *
     * @return AccessToken
     */
    protected function refreshToken()
    {
        $url = sprintf(
            '%s%s',
            $this->salesforceLoginUrl,
            SalesForceWords::TOKEN_ENDPOINT
        );

        $postData = [
            SalesForceWords::GRANT_TYPE    => SalesForceWords::REFRESH_TOKEN,
            SalesForceWords::CLIENT_ID     => $this->clientId,
            SalesForceWords::CLIENT_SECRET => $this->clientSecret,
            SalesForceWords::REFRESH_TOKEN => $this->salesForceAccessToken->getRefreshToken(),
        ];

        $response = $this->request(HttpWords::POST, $url, [SalesForceWords::FORM_PARAMS => $postData]);

        $update = json_decode($response->getBody(), true);
        $this->salesForceAccessToken->updateFromSalesforceRefresh($update);
        $this->baseUrl = $this->salesForceAccessToken->getApiUrl();

        return $this->salesForceAccessToken;
    }

    /**
     * @return AccessToken
     */
    protected function setupToken()
    {
        if ($this->salesForceAccessToken === null) {
            $this->authenticate();

            return;
        }

        if ($this->salesForceAccessToken->needsRefresh()) {
            $this->salesForceAccessToken = $this->refreshToken();
        }
    }

    protected function authenticate()
    {
        $url = sprintf(
            '%s%s',
            $this->salesforceLoginUrl,
            SalesForceWords::TOKEN_ENDPOINT
        );

        $postData = [
            SalesForceWords::GRANT_TYPE    => SalesForceWords::PASSWORD,
            SalesForceWords::CLIENT_ID     => $this->clientId,
            SalesForceWords::CLIENT_SECRET => $this->clientSecret,
            SalesForceWords::USERNAME      => $this->username,
            SalesForceWords::PASSWORD      => $this->password,
        ];

        $response = $this->request(HttpWords::POST, $url, [SalesForceWords::FORM_PARAMS => $postData]);

        $this->salesForceAccessToken = $this
            ->accessTokenGenerator
            ->createFromSalesforceResponse(json_decode($response->getBody(), true))
        ;

        $this->baseUrl = $this->salesForceAccessToken->getApiUrl();
    }

    /**
     * @param string $imageUrl
     *
     * @return mixed
     * @throws AuthenticationException
     * @throws \Exception
     */
    public function downloadImageFromUrl(string $imageUrl)
    {
        return $this->request(HttpWords::GET, $imageUrl, $this->getHeaderWithAuthorization());
    }


    /**
     * @param string $method
     * @param string $url
     * @param string $data
     *
     * @throws AuthenticationException
     * @throws \Exception
     *
     * @return mixed
     */
    protected function request($method, $url, $data)
    {
        try {
            $response = $this->clientGuzzle->$method($url, $data);

            return $response;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->getResponse() === null) {
                throw $e;
            }

            $error = json_decode($e->getResponse()->getBody(), true);

            //If its an auth error convert to an auth exception
            if (isset($error[0])
                && isset($error[0]['errorCode'])
                && isset($error[0]['message'])
                && $e->getResponse()->getStatusCode() == 401
            ) {
                throw new AuthenticationException($error[0]['errorCode'], $error[0]['message']);
            }

            //Invalid data sent to salesforce
            if (isset($error[0])
                && isset($error[0]['errorCode'])
                && $e->getResponse()->getStatusCode() == 400
                && $error[0]['errorCode'] == 'DUPLICATES_DETECTED'
            ) {
                throw new DuplicateDetectedException($error, $e->getRequest()->getUri());
            }

            throw new RequestException($e->getMessage(), (string) $e->getResponse()->getBody());
        }
    }
}
