<?php

declare(strict_types=1);

namespace PrettyPhp\Curl;

/**
 * SSL/TLS protocol versions for CURL requests
 */
enum SslVersion: int
{
    case DEFAULT = CURL_SSLVERSION_DEFAULT;
    case TLSv1 = CURL_SSLVERSION_TLSv1;
    case TLSv1_0 = CURL_SSLVERSION_TLSv1_0;
    case TLSv1_1 = CURL_SSLVERSION_TLSv1_1;
    case TLSv1_2 = CURL_SSLVERSION_TLSv1_2;
    case TLSv1_3 = CURL_SSLVERSION_TLSv1_3;
    case MAX_DEFAULT = CURL_SSLVERSION_MAX_DEFAULT;
    case MAX_TLSv1_0 = CURL_SSLVERSION_MAX_TLSv1_0;
    case MAX_TLSv1_1 = CURL_SSLVERSION_MAX_TLSv1_1;
    case MAX_TLSv1_2 = CURL_SSLVERSION_MAX_TLSv1_2;
    case MAX_TLSv1_3 = CURL_SSLVERSION_MAX_TLSv1_3;
}
