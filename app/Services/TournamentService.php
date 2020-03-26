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
     * @var ScoreService
     */
    private $scoreService;

    /**
     * MainService constructor.
     * @param Client $client
     * @param Tournament $tournament
     */
    public function __construct(Client $client, Tournament $tournament, ScoreService $scoreService)
    {
        $this->client = $client;
        $this->tournament = $tournament;
        $this->scoreService = $scoreService;
    }

    public function getTournaments(): Collection
    {
        return $this->tournament->get();
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
        $tournament->type = $this->getType($content, $type);
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
        for ($i = 0, $gamesGroupedTotal = sizeof($gamesGrouped); $i < $gamesGroupedTotal; $i++) {
            $group = $gamesGrouped[$i];
            ++$round;

            if ($i === ($gamesGroupedTotal - 1)) {
                $bracket = Score::BRACKET_TYPE_GRAND_FINAL;
                $round = 1;
            } elseif ($sizeOfPreviousGroup === 1) {
                if ($bracket === Score::BRACKET_TYPE_WINNERS) {
                    $round = 1;
                };
                $bracket = Score::BRACKET_TYPE_LOSERS;
            }

            foreach ($group as $serie) {
                $this->scoreService->setScores($serie, $bracket, $round, $tournament, $sizeOfPreviousGroup, sizeof($group));
            }

            $sizeOfPreviousGroup = sizeof($group);
        }
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

    private function getType(string $content, string $type): string
    {
        $date = $this->getTournamentDate($content);
        if ($type === Tournament::DPC_TYPE_MINOR && $date < '2018 Oct') {
            return Tournament::DPC_TYPE_OLD_MINOR;
        };
        return $type;
    }

    private function getTournamentDate(string $content): string
    {
        preg_match('@Dates:.+<div .+>(.+)</div>@msU', $content, $match);
        [$monthDate, $year] = explode(',', $match[1]);
        return "$year $monthDate";
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

}
