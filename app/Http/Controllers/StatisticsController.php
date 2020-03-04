<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param StatisticsService $statisticsService
     * @return void
     */
    public function __invoke(
        Request $request,
        StatisticsService $statisticsService
    )
    {
        $statisticsService->setTournamentType(Tournament::DPC_TYPE_MAJOR);

        $statisticsService->getTournamentsCount();
        $statisticsService->getWinnersWRStatisticFirstRound()->display();
        $statisticsService->getWinnersWRStatisticNotFirstRound()->display();
        $statisticsService->getLosersWRStatisticFirstRound()->display();
        $statisticsService->getGrandfinalStatistic()->display();
        $statisticsService->getFallenFromWinnersStatistic()->display();
        $statisticsService->getGrandfinalScoreStatistic();
//        $statisticsService->getTest()->display();
    }
}
