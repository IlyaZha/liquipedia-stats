<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\EmptyScoreException;
use App\Exceptions\EmptyNameException;
use App\Exceptions\WrongBracketException;
use App\Interfaces\Client;
use App\Models\Tournament;
use App\Models\Score;
use Illuminate\Support\Collection;

/**
 * Class MainService
 * @package App\Services\MainService
 */
class TournamentService
{
    private const SKIP_TESTING_BRACKETS_FOR_TOURNAMENTS = [
        'https://liquipedia.net/dota2/Mars_Dota_2_League/Changsha_Major/2018',
    ];

    private const TEAM_TOP = 'top';
    private const TEAM_BOTTOM = 'bottom';
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $url;
    /**
     * @var Tournament
     */
    private $tournament;

    /**
     * MainService constructor.
     * @param Client $client
     * @param Tournament $tournament
     */
    public function __construct(Client $client, Tournament $tournament)
    {
        $this->client = $client;
        $this->tournament = $tournament;
    }

    public function getTournamentsByType(string $type): Collection
    {
        return $this->tournament
            ->where('type', $type)
            ->get();
    }

    /**
     * @param string $url
     * @param string $type
     * @throws EmptyNameException
     * @throws EmptyScoreException
     * @throws WrongBracketException
     */
    public function setData(string $url, string $type): void
    {
        $this->url = $url;
        $content = $this->client->get($this->url);
        $tournament = $this->createTournament($content, $type);

        $gamesGrouped = $this->getGamesGrouped($content);
        $isValid = $this->isValidGamesGroups($gamesGrouped);
        if (!$isValid) {
            throw new WrongBracketException(sprintf('Got wrong brackets from %s', $this->url));
        };
        $this->processRounds($gamesGrouped, $tournament);

        echo "Procesed $url<br>";
        sleep(1);
    }

    private function createTournament(string $content, string $type): Tournament
    {
        $tournament = $this->tournament->newInstance();
        $tournament->url = $this->url;
        $tournament->name = $this->getTournamentName($content);
        $tournament->team_winner = $this->getTeamWinner($content);
        $tournament->type = $type;
        $tournament->save();

        return $tournament;
    }

    /**
     * @param string $content
     * @return array[]
     */
    private function getGamesGrouped(string $content): array
    {
        $gamesGrouped = [];
        foreach ($this->getBracketColumns($content) as $bracketColumn) {
            $games = $this->getGames($bracketColumn);
            if (sizeof($games) === 0) {
                continue;
            }

            $gamesGrouped[] = $games;
        }

        return $gamesGrouped;
    }

    private function isValidGamesGroups(array $gamesGrouped): bool
    {
        if (in_array($this->url, self::SKIP_TESTING_BRACKETS_FOR_TOURNAMENTS) !== false) {
            return true;
        };

        $sizeOfPreviousGroup = null;
        $bracket = Score::BRACKET_TYPE_WINNERS;
        $repeatedSize = null;

        foreach ($gamesGrouped as $games) {
            if ($sizeOfPreviousGroup === 1 && $bracket === Score::BRACKET_TYPE_WINNERS) {
                $bracket = Score::BRACKET_TYPE_LOSERS;
                $repeatedSize = false;
                $sizeOfPreviousGroup = sizeof($games);
                continue;
            };

            if ($bracket === Score::BRACKET_TYPE_WINNERS) {
                if ($sizeOfPreviousGroup === null) {
                    $sizeOfPreviousGroup = sizeof($games);
                    continue;
                };
                if ($sizeOfPreviousGroup / 2 !== sizeof($games)) {
                    return false;
                };
            } else {
                if ($sizeOfPreviousGroup === sizeof($games) && !$repeatedSize) {
                    $sizeOfPreviousGroup = sizeof($games);
                    continue;
                }
                if ($sizeOfPreviousGroup / 2 !== sizeof($games)) {
                    return false;
                };
            }
            $sizeOfPreviousGroup = sizeof($games);
        }

        return true;
    }

    /**
     * @param array $gamesGrouped
     * @param Tournament $tournament
     * @throws EmptyNameException
     * @throws EmptyScoreException
     */
    private function processRounds(array $gamesGrouped, Tournament $tournament): void
    {
        $sizeOfPreviousGroup = null;
        $bracket = Score::BRACKET_TYPE_WINNERS;
        $round = 0;
        foreach ($gamesGrouped as $group) {
            ++$round;
            if ($sizeOfPreviousGroup === 1) {
                if ($bracket === Score::BRACKET_TYPE_WINNERS) {
                    $round = 1;
                };
                $bracket = Score::BRACKET_TYPE_LOSERS;
            }
            foreach ($group as $serie) {
                $this->setScores($serie, $bracket, $round, $tournament, $sizeOfPreviousGroup, sizeof($group));
            }

            $sizeOfPreviousGroup = sizeof($group);
        }
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
    private function setScores(string $content, string $currentBracket, int $round, Tournament $tournament, ?int $sizeOfPreviousGroup, int $sizeOfCurrentGroup): void
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
    }

    private function getTournamentName(string $content): string
    {
        preg_match('@firstHeading".*span.*>(.+)</span@msU', $content, $match);
        return $match[1];
    }

    private function getTeamWinner(string $content): string
    {
        preg_match('@background-color-first-place.+team-template-text.+<a.+>(.*)<@msU', $content, $match);
        return $match[1];
    }

    private function getBracketColumns(string $content): array
    {
        preg_match('@bracket-wrapper(.*)<h2@msU', $content, $match);
        $contents = explode('bracket-column bracket-column-matches', $match[1]);
        array_shift($contents);
        return $contents;
    }

    private function getGames(string $content): array
    {
        $contents = explode('bracket-game', $content);
        array_shift($contents);
        return $contents;
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
        if (empty($match[1])) {
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
