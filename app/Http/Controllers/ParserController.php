<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyNameException;
use App\Exceptions\EmptyScoreException;
use App\Models\Tournament;
use App\Services\MainService;
use App\Services\TournamentService;

class ParserController extends Controller
{
    private const TOURNAMENTS_URLS = [
        Tournament::DPC_TYPE_MAJOR => 'https://liquipedia.net/dota2/Dota_Major_Championships',
        Tournament::DPC_TYPE_MINOR => 'https://liquipedia.net/dota2/Dota_Minor_Championships',
        Tournament::TYPE_INTERNATIONAL => 'https://liquipedia.net/dota2/The_International',
    ];

    private const TOURNAMENTS_WITH_WRONG_BRACKETS = [ //todo add to table
        'https://liquipedia.net/dota2/ESL_One/Katowice/2018',
        'https://liquipedia.net/dota2/Dota_2_Asia_Championships/2018',
        'https://liquipedia.net/dota2/ESL_One/Birmingham/2018',
        'https://liquipedia.net/dota2/ESL_One/Genting/2018',
        'https://liquipedia.net/dota2/GESC/Indonesia_Minor/2018',
        'https://liquipedia.net/dota2/GESC/Thailand_Minor/2018',
    ];

    /**
     * @var MainService
     */
    private $mainService;
    /**
     * @var TournamentService
     */
    private $tournamentService;

    /**
     * Handle the incoming request.
     *
     * @param MainService $mainService
     * @param TournamentService $tournamentService
     * @return void
     */
    public function __invoke(
        MainService $mainService,
        TournamentService $tournamentService
    )
    {
        $this->mainService = $mainService;
        $this->tournamentService = $tournamentService;
        foreach (self::TOURNAMENTS_URLS as $tournamentsType => $tournamentUrl) {
            $this->parseByTournamentType($tournamentsType, $tournamentUrl);
        }
    }

    private function parseByTournamentType(string $tournamentType, string $tournamentUrl): void
    {
        $existed = $this->tournamentService->getTournaments();

        $tournamentsList = $this->mainService->getTournamentsList($tournamentUrl);
        $urlsNotExisted = array_filter($tournamentsList, function ($url) use ($existed) {
            $isUrlExisted = $existed->search(function (Tournament $tournament) use ($url) {
                return $tournament->url === $url;
            });

            return $isUrlExisted === false;
        });

        $tournamentService = $this->tournamentService;
        foreach ($urlsNotExisted as $url) {
            if (in_array($url, self::TOURNAMENTS_WITH_WRONG_BRACKETS) === true) {
                echo "Skipped: $url<br>";
                continue;
            };

            try {
                \DB::transaction(function () use ($tournamentService, $url, $tournamentType) {
                    $tournamentService->setData($url, $tournamentType);
                });
            } catch (EmptyNameException | EmptyScoreException $e) {
                echo "Skipped: $url. May be this tournament hasn't been finished yet<br>";
            }
        }
    }
}
