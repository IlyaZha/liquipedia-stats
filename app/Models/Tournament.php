<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $team_winner
 * @property string $type
 * @property Score[] $scores
 * @method where(string $string, string $tournamentType)
 */
class Tournament extends Model
{

    public const DPC_TYPE_MINOR = 'Minor';
    public const DPC_TYPE_OLD_MINOR = 'Old Minor';
    public const DPC_TYPE_MAJOR = 'Major';
    /**
     * @var array
     */
    protected $fillable = ['name', 'url', 'created_at', 'updated_at', 'team_winner'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function scores()
    {
        return $this->hasMany('App\Score');
    }
}
