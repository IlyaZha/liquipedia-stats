<?php

namespace App\Http;

use App\Exceptions\HttpException;

/**
 * Class HttpClient
 * @package App\Http
 */
class HttpClient implements \App\Interfaces\Client
{

    /**
     * @param string $url
     * @return string
     * @throws HttpException
     */
    public function get(string $url): string
    {
        $result = file_get_contents($url);
        if ($result === false) {
            throw new HttpException("Can not get information from $url");
        };

        return $result;
    }
}
