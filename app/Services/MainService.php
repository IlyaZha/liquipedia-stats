<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\Client;
use App\Models\Tournament;
use App\Models\Score;

/**
 * Class MainService
 * @package App\Services\MainService
 */
class MainService
{

    public const DOMAIN = 'https://liquipedia.net';

    /**
     * @var Client
     */
    private $client;

    /**
     * MainService constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $tournamentsUrl
     * @return string[]
     */
    public function getTournamentsList(string $tournamentsUrl): array
    {
        $content = $this->client->get($tournamentsUrl);
        preg_match_all('@Tournament .+>.+/span>.+href="(.*)"@msU', $content, $matches);

        return array_map(function ($url) {
            return self::DOMAIN . $url;
        }, $matches[1]);
    }
}
