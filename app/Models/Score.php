<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tournament_id
 * @property string $team_top
 * @property string $team_bot
 * @property boolean $score_top
 * @property boolean $score_bot
 * @property string $current_bracket
 * @property string $previous_bracket
 * @property string $round
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Tournament $tournament
 */
class Score extends Model
{

    /**
     * @var array
     */
    protected $fillable = ['tournament_id', 'team_top', 'team_bot', 'score_top', 'score_bot', 'current_bracket', 'previous_bracket', 'round', 'created_at', 'updated_at'];

    public const BRACKET_TYPE_WINNERS = 'winners';
    public const BRACKET_TYPE_LOSERS = 'losers';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tournament()
    {
        return $this->belongsTo('App\Tournament');
    }
}
