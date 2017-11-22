<?php

namespace Akeneo\SalesForce\Authentification;

use Carbon\Carbon;

class AccessToken
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var \Carbon\Carbon
     */
    private $dateIssued;

    /**
     * @var \Carbon\Carbon
     */
    private $dateExpires;

    /**
     * @var array
     */
    private $scope;

    /**
     * @var string
     */
    private $tokenType;

    /**
     * @var string
     */
    private $refreshToken;

    /**
     * @var string
     */
    private $signature;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @param string         $id
     * @param \Carbon\Carbon $dateIssued
     * @param \Carbon\Carbon $dateExpires
     * @param array          $scope
     * @param string         $tokenType
     * @param string         $refreshToken
     * @param string         $signature
     * @param string         $accessToken
     * @param string         $apiUrl
     */
    public function __construct(
        $id,
        $dateIssued,
        $dateExpires,
        $scope,
        $tokenType,
        $refreshToken,
        $signature,
        $accessToken,
        $apiUrl
    ) {
        $this->id           = $id;
        $this->dateIssued   = $dateIssued;
        $this->dateExpires  = $dateExpires;
        $this->scope        = $scope;
        $this->tokenType    = $tokenType;
        $this->refreshToken = $refreshToken;
        $this->signature    = $signature;
        $this->accessToken  = $accessToken;
        $this->apiUrl       = $apiUrl;
    }

    public function updateFromSalesforceRefresh(array $salesforceToken)
    {
        $this->dateIssued = Carbon::createFromTimestamp((int) ($salesforceToken[TokenFields::ISSUED_AT] / 1000));

        $this->dateExpires = $this->dateIssued->copy()->addHour()->subMinutes(5);

        $this->signature = $salesforceToken[TokenFields::SIGNATURE];

        $this->accessToken = $salesforceToken[TokenFields::ACCESS_TOKEN];
    }

    /**
     * @return bool
     */
    public function needsRefresh()
    {
        return $this->dateExpires->lt(Carbon::now());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            TokenFields::ID            => $this->id,
            TokenFields::DATE_ISSUED   => $this->dateIssued->format(TokenFields::DATE_ISSUED_FORMAT),
            TokenFields::DATE_EXPIRES  => $this->dateExpires->format(TokenFields::DATE_EXPIRES_FORMAT),
            TokenFields::SCOPE         => $this->scope,
            TokenFields::TOKEN_TYPE    => $this->tokenType,
            TokenFields::REFRESH_TOKEN => $this->refreshToken,
            TokenFields::SIGNATURE     => $this->signature,
            TokenFields::ACCESS_TOKEN  => $this->accessToken,
            TokenFields::API_URL       => $this->apiUrl,
        ];
    }

    /**
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @return Carbon
     */
    public function getDateExpires()
    {
        return $this->dateExpires;
    }

    /**
     * @return Carbon
     */
    public function getDateIssued()
    {
        return $this->dateIssued;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @return array
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }
}
