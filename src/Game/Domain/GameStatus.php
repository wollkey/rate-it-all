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
        return self::Waiting === $this;
    }

    public function canAddThings(): bool
    {
        return self::Collecting === $this;
    }

    public function canRate(): bool
    {
        return self::Rating === $this;
    }
}
