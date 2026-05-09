<?php

namespace App\Support;

class Utm
{
    /**
     * Append UTM tracking params to a URL. Existing query string is preserved.
     */
    public static function tag(
        string $url,
        string $source,
        string $medium = 'social',
        ?string $campaign = null,
        ?string $content = null,
    ): string {
        $params = array_filter([
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
            'utm_content' => $content,
        ]);

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($params);
    }
}
