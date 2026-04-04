<?php

declare(strict_types=1);

namespace App\Game\Application;

final readonly class GameInfo
{
    public function description(): string
    {
        return 'In this game you need to rate any things that come to your mind: *red color*, *hand washing*, *a small salary*, *anything*...';
    }

    /**
     * @return list<string>
     */
    public function rules(): array
    {
        return [
            'Create a game or join an existing one',
            'Invite more friends',
            'Add any things that come to your mind - the weirder, the better',
            'Debate and rate with your fellow these madness things',
        ];
    }
}
