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
    public $count = 0;
    public $total = 0;

    public function __get($name)
    {
        if ($name === 'percent') {
            if (!$this->total) {
                return 0;
            }

            return round($this->count / $this->total * 100, 2);
        }

        return $this->$name;
    }
}
