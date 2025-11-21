<?php

declare(strict_types=1);

namespace PrettyPhp\Curl;

/**
 * HTTP protocol versions for CURL requests
 */
enum HttpVersion: int
{
    case HTTP_1_0 = CURL_HTTP_VERSION_1_0;
    case HTTP_1_1 = CURL_HTTP_VERSION_1_1;
    case HTTP_2_0 = CURL_HTTP_VERSION_2_0;
    case HTTP_2TLS = CURL_HTTP_VERSION_2TLS;
    case HTTP_2_PRIOR_KNOWLEDGE = CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE;
    case HTTP_3 = CURL_HTTP_VERSION_3;
    case NONE = CURL_HTTP_VERSION_NONE;
}
