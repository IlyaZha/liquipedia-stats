<?php

namespace App\Classes;

/**
 * Class Statistic
 * @package App\Classes
 * @property int total
 * @property int count
 * @property string description
 * @property float $percent
 */
class Statistic
{
    public function __get($name)
    {
        if ($name !== 'percent') {
            return $this->$name;
        };

        if (!$this->total) {
            return 0;
        }

        return round($this->count / $this->total * 100, 2);
    }
}
