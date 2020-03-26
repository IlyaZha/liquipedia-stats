<?php

namespace App\Http\Controllers;

use App\Models\Score;
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
        $statisticsService->setTournamentType(Tournament::DPC_TYPE_MINOR);

        $seriesStatistics = [];

        foreach (Score::BRACKET_TYPES as $bracket) {
            for ($i = 1; $i < 8; $i++) {
                $statistic = $statisticsService->getStatisticByBracketAndRound($bracket, $i);

                if ($bracket === Score::BRACKET_TYPE_GRAND_FINAL) {
                    $statistics = $statisticsService->getMatchesCountStatisticBo5Array($bracket, $i);
                    $viewData =  [
                        'bracket' => $bracket,
                        'round' => $i,
                        'wr' => $statistic->percent,
                        'win' => $statistic->count,
                        'total' => $statistic->total,
                    ];
                    for ($j = 0, $total = sizeof($statistics); $j < $total; $j++ ) {
                        $statisticBo5 = $statistics[$j];
                        $viewData["percent3_$j"] = $statisticBo5->percent;
                        $viewData["win3_$j"] = $statisticBo5->count;
                    }
                    $viewData['total_bo5'] = $statisticBo5->total;
                    $seriesStatistics[] = view('statistic_bo5', $viewData);
                } else {
                    $statisticBo3 = $statisticsService->getMatchesCountStatisticBo3($bracket, $i);
                    $seriesStatistics[] = view('statistic_bo3', [
                        'bracket' => $bracket,
                        'round' => $i,
                        'wr' => $statistic->percent,
                        'win' => $statistic->count,
                        'total' => $statistic->total,
                        'percent2_0' => $statisticBo3->percent,
                        'win2_0' => $statisticBo3->count,
                        'total_bo3' => $statisticBo3->total,
                    ]);
                }

            }

        }

        $statisticNotFirstRound = $statisticsService->getWinnersWRStatisticNotFirstRound();
        $statisticFallenFromWinners = $statisticsService->getFallenFromWinnersStatistic();
//        $statisticsService->getGrandfinalScoreStatistic();

        echo view('statistic', [
            'type' => $statisticsService->getTournamentType(),
            'tournamentsCount' => $statisticsService->getTournamentsCount(),
            'statisticsBo3' => $seriesStatistics,
            'wrNotFirstRound' => $statisticNotFirstRound->percent,
            'winNotFirstRound' => $statisticNotFirstRound->count,
            'totalNotFirstRound' => $statisticNotFirstRound->total,
            'wrFallenFromTop' => $statisticFallenFromWinners->percent,
            'winFallenFromTop' => $statisticFallenFromWinners->count,
            'totalFallenFromTop' => $statisticFallenFromWinners->total,
        ]);
    }
}
