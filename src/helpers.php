<?php

if (!function_exists('base_url')) {
    /**
     * Returns API.BASE_URL, as specified in your .env file.
     *
     * Any URI segments you pass to the function will be added to URL
     */
    function base_url(string $url = ''): string
    {
        $characters = " \t\n\r\0\x0B/";

        return trim($_ENV['B2BXAPI.BASE_URL'], $characters).'/'.trim($url, $characters);
    }
}

if (!function_exists('base2_url')) {
    /**
     * Returns API.BASE_URL, as specified in your .env file.
     *
     * Any URI segments you pass to the function will be added to URL
     */
    function base2_url(string $url = ''): string
    {
        $characters = " \t\n\r\0\x0B/";

        return trim($_ENV['B2BXAPI.BASE2_URL'], $characters).'/'.trim($url, $characters);
    }
}
