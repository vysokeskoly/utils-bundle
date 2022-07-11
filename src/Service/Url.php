<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use function Safe\parse_url;

class Url
{
    /**
     * @param array|object $params
     */
    public static function buildQuery($params): string
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param string $url base URL (can already have parameters)
     * @param array $params key-value query string in array
     */
    public static function buildUrlWithParams(string $url, array $params): string
    {
        if (empty($params)) {
            return $url;
        }

        if (mb_strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }

        return $url . static::buildQuery($params);
    }

    /**
     * Remove parameter from given URL (if it is present)
     */
    public static function removeParam(string $url, string $param): string
    {
        $urlParts = parse_url($url);
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $parameters);
            if (array_key_exists($param, $parameters)) {
                unset($parameters[$param]);

                $outputQueryPart = static::buildQuery($parameters);

                // Replace old query part with adjusted query part
                $url = str_replace($urlParts['query'], $outputQueryPart, $url);
                // Trim ending '?' because of the case when the removed parameter was the only one the url
                $url = rtrim($url, '?');
            }
        }

        return $url;
    }

    public static function getParams(string $url): array
    {
        $queryStringPart = parse_url($url, PHP_URL_QUERY);

        if ($queryStringPart) {
            parse_str($queryStringPart, $queryParams);

            return $queryParams;
        }

        return [];
    }
}
