<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Match;

class MatchesService
{
    public function setMatches(int $scoreId, string $rawContent)
    {
        foreach ($this->getContentArray($rawContent) as $content) {
            if ($this->ifTopWon($content)) {
                $winner = ScoreService::TEAM_TOP;
            } elseif ($this->ifBotWon($content)) {
                $winner = ScoreService::TEAM_BOTTOM;
            } else {
                throw new \Exception('Can not define winner');
            }

            $match = new Match();
            $match->winner = $winner;
            $match->score_id = $scoreId;
            $match->save();
        }
    }

    /**
     * @param string $content
     * @return string[]
     */
    private function getContentArray(string $content): array
    {
        $result = explode('match-row', $content);
        array_shift($result);
        return $result;
    }

    private function ifTopWon(string $content): bool
    {
        return (bool) preg_match('@green-check.*<div class="right"@msU', $content);
    }

    private function ifBotWon(string $content): bool
    {
        return (bool) preg_match('@<div class="right".*green-check.*game-length@msU', $content);
    }

}
