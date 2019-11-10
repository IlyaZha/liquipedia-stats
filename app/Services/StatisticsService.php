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

    public function setTournamentType(string $type): void
    {
        $this->tournamentType = $type;
        $this->scoreBuilder = $this->createQueryBuilder();
    }

    public function getWinnersWRStatistic(): Statistic
    {
        $scores = $this->getScoreBuilder()
            ->where('current_bracket', Score::BRACKET_TYPE_WINNERS)
            ->get();

        return $this->getStatisticBetweenBotAndTop($scores, 'Винрейт верхней команды в виннерах');
    }

    public function getWinnersWRStatisticFirstRound(): Statistic
    {
        $scores = $this->getScoreBuilder()
            ->where('current_bracket', Score::BRACKET_TYPE_WINNERS)
            ->where('round', 1)
            ->get();

        return $this->getStatisticBetweenBotAndTop($scores, 'Винрейт верхней команды в виннерах первого раунда');
    }

    public function getWinnersWRStatisticNotFirstRound(): Statistic
    {
        $scores = $this->getScoreBuilder()
            ->where('current_bracket', Score::BRACKET_TYPE_WINNERS)
            ->where('round', '!=', 1)
            ->get();

        return $this->getStatisticBetweenBotAndTop($scores, 'Винрейт верхней команды в виннерах НЕ первого раунда');
    }

    public function getLosersWRStatisticFirstRound(): Statistic
    {
        $scores = $this->getScoreBuilder()
            ->where('current_bracket', Score::BRACKET_TYPE_LOSERS)
            ->where('round', 1)
            ->get();

        return $this->getStatisticBetweenBotAndTop($scores, 'Винрейт верхней команды в лузерах первого раунда');
    }

    public function getGrandfinalStatistic(): Statistic
    {
        $scores = $this->getScoreBuilder()
            ->where('current_bracket', Score::BRACKET_TYPE_GRAND_FINAL)
            ->get();

        return $this->getStatisticBetweenBotAndTop($scores, 'Винрейт верхней команды в грандфинале');
    }

    public function getFallenFromWinnersStatistic(): Statistic
    {
        $scores = $this->getScoreBuilder()
            ->where('previous_bracket_top', Score::BRACKET_TYPE_WINNERS)
            ->get();

        return $this->getStatisticBetweenBotAndTop($scores, 'Винрейт верхней команды, упавшей из виннер брекета');
    }

    private function getStatisticBetweenBotAndTop(Collection $scores, string $description)
    {
        $statistic = new Statistic();
        $statistic->description = $description;
        $statistic->total = sizeof($scores);
        $topWonScores = $scores->filter(function (Score $score) {
            return $score->score_top > $score->score_bot;
        });
        $statistic->count = sizeof($topWonScores);

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
