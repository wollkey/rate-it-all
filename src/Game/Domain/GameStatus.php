<?php

declare(strict_types=1);

namespace App\Game\Domain;

enum GameStatus: string
{
    case Waiting = 'waiting';
    case Collecting = 'collecting';
    case Rating = 'rating';
    case Finished = 'finished';

    public function canJoin(): bool
    {
        return $this === self::Waiting;
    }

    public function canAddThings(): bool
    {
        return $this === self::Collecting;
    }

    public function canRate(): bool
    {
        return $this === self::Rating;
    }
}
