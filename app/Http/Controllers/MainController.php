<?php

namespace App\Http\Controllers;

use App\Services\MainService;
use App\Services\TournamentService;
use Illuminate\Http\Request;

class MainController extends Controller
{

    const MAJOR_LIST = 'https://liquipedia.net/dota2/Dota_Major_Championships';
    const TOURNAMENTS_WITH_WRONG_BRACKETS = [
        'https://liquipedia.net/dota2/ESL_One/Katowice/2018',
        'https://liquipedia.net/dota2/Dota_2_Asia_Championships/2018',
        'https://liquipedia.net/dota2/ESL_One/Birmingham/2018',
    ];

    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param MainService $mainService
     * @param TournamentService $tournamentService
     * @return void
     */
    public function __invoke(
        Request $request,
        MainService $mainService,
        TournamentService $tournamentService
    )
    {
        /*
         * Получаем список мажоров
         * Обрабатываеем мажоры
         */
        $tournamentsList = $mainService->getTournamentsList(self::MAJOR_LIST);
        foreach ($tournamentsList as $url) {
            if (in_array($url, self::TOURNAMENTS_WITH_WRONG_BRACKETS) === true) {
                echo "Skipped: $url<br>";
                continue;
            }
            \DB::transaction(function () use ($tournamentService, $url) {
                $tournamentService->setData($url);
            });
        }
    }
}
