<?php

declare(strict_types=1);

namespace App\Services;

use App\Classes\Statistic;
use App\Interfaces\Client;
use App\Models\Score;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Class MainService
 * @package App\Services\MainService
 */
class StatisticsService
{

    /**
     * @var Client
     */
    private $client;
    /**
     * @var Score
     */
    private $score;
    /**
     * @var Statistic
     */
    private $statistic;
    /**
     * @var Tournament
     */
    private $tournament;
    /**
     * @var string
     */
    private $tournamentType;
    /**
     * @var Builder
     */
    private $scoreBuilder;

    /**
     * MainService constructor.
     * @param Client $client
     * @param Score $score
     * @param Tournament $tournament
     * @param Statistic $statistic
     */
    public function __construct(Client $client, Score $score, Tournament $tournament, Statistic $statistic)
    {
        $this->client = $client;
        $this->score = $score;
        $this->tournament = $tournament;
        $this->statistic = $statistic;

    }

    public function getTournamentType(): string
    {
        return $this->tournamentType;
    }

    public function setTournamentType(string $type): void
    {
        $this->tournamentType = $type;
        $this->scoreBuilder = $this->createQueryBuilder();
    }

    public function getStatisticByBracketAndRound(string $bracket, int $round): Statistic
    {
        $scores = $this->getScoreBuilder()
            ->where('current_bracket', $bracket)
            ->where('round', $round)
            ->get();

        return $this->getStatisticBetweenBotAndTop($scores, '');
    }

    public function getWinnersWRStatisticNotFirstRound(): Statistic
    {
        $scores = $this->getScoreBuilder()
            ->where('current_bracket', Score::BRACKET_TYPE_WINNERS)
            ->where('round', '!=', 1)
            ->get();

        return $this->getStatisticBetweenBotAndTop($scores, 'Винрейт верхней команды в виннерах НЕ первого раунда');
    }

    public function getFallenFromWinnersStatistic(): Statistic
    {
        $scores = $this->getScoreBuilder()
            ->where('previous_bracket_top', Score::BRACKET_TYPE_WINNERS)
            ->get();

        return $this->getStatisticBetweenBotAndTop($scores, 'Винрейт верхней команды, упавшей из виннер брекета');
    }

    public function getGrandfinalScoreStatistic()
    {
        $scores = $this->getScoreBuilder()
            ->where('current_bracket', Score::BRACKET_TYPE_GRAND_FINAL)
            ->where(function(Builder $query) {
                $score = 2;
                $query->where('score_top', $score)
                    ->orWhere('score_bot', $score);
            })
            ->get();
    }

//    public function getTest(): Statistic
//    {
//        $scores = $this->getScoreBuilder()
//            ->where(function(Builder $q) {
//                $q->where('score_top', 3)
//                ->orWhere('score_bot', 3);
//            })
//            ->get();
//        print_r($scores);die;
//
//        return $this->getStatisticBetweenBotAndTop($scores, 'Винрейт верхней команды, упавшей из виннер брекета');
//    }

    public function getTournamentsCount(): int
    {
        return $this->getScoreBuilder()
            ->distinct('tournament_id')
            ->count('tournament_id');
    }

    public function getMatchesCountStatisticBo3(string $bracket, int $round): Statistic
    {
        $scores = $this->getScoreBuilder()
            ->where('current_bracket', $bracket)
            ->where('round', $round)
            ->get();

        return $this->getStatisticForMatchResultBo3($scores);
    }

    /**
     * @param string $bracket
     * @param int $round
     * @return Statistic[]
     */
    public function getMatchesCountStatisticBo5Array(string $bracket, int $round): array
    {
        $scores = $this->getScoreBuilder()
            ->where('current_bracket', $bracket)
            ->where('round', $round)
            ->get();

        return $this->getStatisticForMatchResultBo5Array($scores);
    }

    private function getStatisticBetweenBotAndTop(Collection $scores, ?string $description)
    {
        $statistic = new Statistic();
        if ($description) {
            $statistic->description = $description;
        }
        $statistic->total = sizeof($scores);
        $topWonScores = $scores->filter(function (Score $score) {
            return $score->score_top > $score->score_bot;
        });
        $statistic->count = sizeof($topWonScores);

        return $statistic;
    }

    private function getStatisticForMatchResultBo3(Collection $scores, ?string $description = ''): Statistic //todo
    {
        $statistic = new Statistic();
//        if ($description) {
//            $statistic->description = $description;
//        }
        $statistic->description = 'Кол-во матчей всухую (2:0) среди всех игр';

        $zeroWinScores = $scores->filter(function (Score $score) {
            return ($score->score_bot === 2 && $score->score_top === 0) || ($score->score_bot === 0 && $score->score_top === 2);
        });

        $oneWinScores = $scores->filter(function (Score $score) {
            return ($score->score_bot === 2 && $score->score_top === 1) || ($score->score_bot === 1 && $score->score_top === 2);
        });

        $statistic->total = sizeof($oneWinScores) + sizeof($zeroWinScores);
        $statistic->count = sizeof($zeroWinScores);

        return $statistic;
    }

    /**
     * @param Collection $scores
     * @param string|null $description
     * @return Statistic[]
     */
    private function getStatisticForMatchResultBo5Array(Collection $scores, ?string $description = ''): array //todo
    {
        $statistics = [];
        for ($i = 0; $i < 4; $i ++) {
            $statistics[] = $this->getStatisticForMatchResultBo5($scores, 3, $i);
        }
        return $statistics;
    }

    /**
     * @param Collection $scores
     * @param int $score1
     * @param int $score2
     * @param string|null $description
     * @return Statistic
     */
    private function getStatisticForMatchResultBo5(Collection $scores, int $score1, int $score2, ?string $description = ''): Statistic
    {
        $statistic = new Statistic();
//        if ($description) {
//            $statistic->description = $description;
//        }
        $statistic->description = "Кол-во встреч $score1:$score2";

        $allScoresCount = sizeof($scores->filter(function (Score $score) {
            return $score->score_bot === 3 || $score->score_top === 3;
        }));

        $zeroWinScores = $scores->filter(function (Score $score) use ($score1, $score2) {
            return ($score->score_bot === $score1 && $score->score_top === $score2) || ($score->score_bot === $score2 && $score->score_top === $score1);
        });

        $statistic->total = $allScoresCount;
        $statistic->count = sizeof($zeroWinScores);

        return $statistic;
    }

    private function createQueryBuilder(): Builder
    {
        $ids = $this->tournament
            ->where('type', $this->tournamentType)
            ->pluck('id')
            ->toArray();

        return $this->score->whereIn('tournament_id', $ids);
    }

    private function getScoreBuilder(): Builder
    {
        return clone $this->scoreBuilder;
    }

}
