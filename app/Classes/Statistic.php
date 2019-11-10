<?php

namespace App\Classes;

/**
 * Class Statistic
 * @package App\Classes
 * @property int total
 * @property int count
 * @property string description
 * @property float winrate
 */
class Statistic
{
    public function display()
    {
        $this->winrate = round($this->count / $this->total * 100, 2);
        print_r($this);
    }
}
