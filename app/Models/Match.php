<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $score_id
 * @property string $winner
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Match extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['score_id', 'winner', 'created_at', 'updated_at'];
}
