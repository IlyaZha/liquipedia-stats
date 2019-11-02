<?php

namespace App\Interfaces;

interface Client
{
    public function get(string $url): string;
}
