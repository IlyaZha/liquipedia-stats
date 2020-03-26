<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\EmptyNameException;
use App\Exceptions\EmptyScoreException;
use App\Models\Score;
use App\Models\Tournament;

class ScoreService
{
    public const TEAM_TOP = 'top';
    public const TEAM_BOTTOM = 'bottom';
    /**
     * @var MatchesService
     */
    private $matchesService;

    /**
     * ScoreService constructor.
     * @param MatchesService $matchesService
     */
    public function __construct(MatchesService $matchesService)
    {
        $this->matchesService = $matchesService;
    }


    /**
     * @param string $content
     * @param string $currentBracket
     * @param int $round
     * @param Tournament $tournament
     * @param int|null $sizeOfPreviousGroup
     * @param int $sizeOfCurrentGroup
     * @throws EmptyNameException
     * @throws EmptyScoreException
     */
    public function setScores(string $content, string $currentBracket, int $round, Tournament $tournament, ?int $sizeOfPreviousGroup, int $sizeOfCurrentGroup): void
    {
        $explodes = explode('bracket-cell', $content);
        array_shift($explodes);

        $score = new Score();
        $score->current_bracket = $currentBracket;
        $score->round = $round;
        $score->team_top = $this->getTeamName($explodes[0]);
        $score->score_top = $this->getTeamScore($explodes[0]);
        $score->team_bot = $this->getTeamName($explodes[1]);
        $score->score_bot = $this->getTeamScore($explodes[1]);
        $score->previous_bracket_top = $this->getPreviousBracket($currentBracket, $round, self::TEAM_TOP, $sizeOfPreviousGroup, $sizeOfCurrentGroup);
        $score->previous_bracket_bot = $this->getPreviousBracket($currentBracket, $round, self::TEAM_BOTTOM, $sizeOfPreviousGroup, $sizeOfCurrentGroup);
        $score->tournament_id = $tournament->id;
        $score->save();

        $this->matchesService->setMatches($score->id, $content);
    }
    /**
     * @param string $content
     * @return string
     * @throws EmptyNameException
     */
    private function getTeamName(string $content): string
    {
        preg_match('@team-template-text.+>(.+)</@msU', $content, $match);
        if (!isset($match[1])) {
            throw new EmptyNameException('Can not find name');
        };

        return $match[1];
    }

    /**
     * @param string $content
     * @return string
     * @throws EmptyScoreException
     */
    private function getTeamScore(string $content): string
    {
        preg_match('@bracket-score.+>(.*)<@msU', $content, $match);
        if (!isset($match[1])) {
            throw new EmptyScoreException('Can not find score');
        };

        return $match[1];
    }

    private function getPreviousBracket(string $bracket, int $round, string $teamPosition, ?int $sizeOfPreviousGroup, int $sizeOfCurrentGroup): ?string
    {
        if ($round === 1) {
            return null;
        };
        if ($bracket === Score::BRACKET_TYPE_WINNERS) {
            return Score::BRACKET_TYPE_WINNERS;
        };
        if ($teamPosition === self::TEAM_BOTTOM) {
            return Score::BRACKET_TYPE_LOSERS;
        };

        if ($sizeOfPreviousGroup === $sizeOfCurrentGroup) {
            if ($teamPosition === self::TEAM_TOP) {
                return Score::BRACKET_TYPE_WINNERS;
            } else {
                return Score::BRACKET_TYPE_LOSERS;
            }
        }

        return Score::BRACKET_TYPE_LOSERS;
    }
}
