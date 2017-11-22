<?php

namespace Akeneo\SalesForce\Authentification;

use Carbon\Carbon;

class AccessTokenGenerator
{
    /**
     * Create an access token from stored json data.
     *
     * @param $text
     *
     * @return AccessToken
     */
    public function createFromJson($text)
    {
        $savedToken = json_decode($text, true);

        $id = $savedToken[TokenFields::ID];

        $dateIssued = Carbon::parse($savedToken[TokenFields::DATE_ISSUED]);

        $dateExpires = Carbon::parse($savedToken[TokenFields::DATE_EXPIRES]);

        $scope = $savedToken[TokenFields::SCOPE];

        $tokenType = $savedToken[TokenFields::TOKEN_TYPE];

        $refreshToken = $savedToken[TokenFields::REFRESH_TOKEN];

        $signature = $savedToken[TokenFields::SIGNATURE];

        $accessToken = $savedToken[TokenFields::ACCESS_TOKEN];

        $apiUrl = $savedToken[TokenFields::API_URL];

        $token = new AccessToken(
            $id,
            $dateIssued,
            $dateExpires,
            $scope,
            $tokenType,
            $refreshToken,
            $signature,
            $accessToken,
            $apiUrl
        );

        return $token;
    }

    /**
     * Create an access token object from the salesforce response data.
     *
     * @param array $salesforceToken
     *
     * @return AccessToken
     */
    public function createFromSalesforceResponse(array $salesforceToken)
    {
        $dateIssued = Carbon::createFromTimestamp((int) ($salesforceToken[TokenFields::ISSUED_AT] / 1000));

        $dateExpires = $dateIssued->copy()->addHour()->subMinutes(5);

        $id = $this->getKeyIfSet($salesforceToken, TokenFields::ID);

        $scope = explode(' ', $this->getKeyIfSet($salesforceToken, TokenFields::SCOPE));

        $refreshToken = $this->getKeyIfSet($salesforceToken, TokenFields::SF_REFRESH_TOKEN);

        $signature = $this->getKeyIfSet($salesforceToken, TokenFields::SIGNATURE);

        $tokenType = $this->getKeyIfSet($salesforceToken, TokenFields::SF_TOKEN_TYPE);

        $accessToken = $salesforceToken[TokenFields::SF_ACCESS_TOKEN];

        $apiUrl = $salesforceToken[TokenFields::SF_INSTANCE_URL];

        $token = new AccessToken(
            $id,
            $dateIssued,
            $dateExpires,
            $scope,
            $tokenType,
            $refreshToken,
            $signature,
            $accessToken,
            $apiUrl
        );

        return $token;
    }

    /**
     * @param array $array
     * @param mixed $key
     */
    private function getKeyIfSet($array, $key)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        return;
    }
}
