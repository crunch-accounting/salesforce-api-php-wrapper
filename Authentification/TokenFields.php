<?php

namespace Akeneo\SalesForce\Authentification;

/**
 * @author Anael Chardan <anael.chardan@akeneo.com>
 */
class TokenFields
{
    const ID                  = 'id';
    const DATE_ISSUED         = 'dateIssued';
    const DATE_ISSUED_FORMAT  = 'Y-m-d H:i:s';
    const DATE_EXPIRES        = 'dateExpires';
    const DATE_EXPIRES_FORMAT = 'Y-m-d H:i:s';
    const SCOPE               = 'scope';
    const TOKEN_TYPE          = 'tokenType';
    const REFRESH_TOKEN       = 'refreshToken';
    const API_URL             = 'apiUrl';
    const ISSUED_AT           = 'issued_at';
    const SIGNATURE           = 'signature';
    const ACCESS_TOKEN        = 'access_token';
    const SF_INSTANCE_URL     = 'instance_url';
    const SF_ID               = 'id';
    const SF_SIGNATURE        = 'signature';
    const SF_REFRESH_TOKEN    = 'refresh_token';
    const SF_SCOPE            = 'scope';
    const SF_TOKEN_TYPE       = 'token_type';
    const SF_ISSUED_AT        = 'issued_at';
    const SF_ACCESS_TOKEN     = 'access_token';
}
