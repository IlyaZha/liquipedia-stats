<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tournament_id
 * @property string $team_top
 * @property string $team_bot
 * @property int $score_top
 * @property int $score_bot
 * @property string $current_bracket
 * @property string|null $previous_bracket_top
 * @property string|null $previous_bracket_bot
 * @property string $round
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Tournament $tournament
 * @method whereIn(string $string, $ids)
 */
class Score extends Model
{

    /**
     * @var array
     */
    protected $guarded = [];

    public const BRACKET_TYPE_WINNERS = 'winners';
    public const BRACKET_TYPE_LOSERS = 'losers';
    public const BRACKET_TYPE_GRAND_FINAL = 'grandfinal';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }
}
