<?php

declare(strict_types=1);

namespace App\Game\Application;

use Symfony\Component\Translation\TranslatableMessage;

final class GameInfo
{
    public function description(): TranslatableMessage
    {
        return new TranslatableMessage(
            'In this game you need to rate any things that come to your mind: *red color*, *hand washing*, *a small salary*, *anything*...'
        );
    }

    /**
     * @return list<TranslatableMessage>
     */
    public function rules(): array
    {
        return [
            new TranslatableMessage('Create a game or join an existing one'),
            new TranslatableMessage('Invite more friends'),
            new TranslatableMessage('Add any things that come to your mind - the weirder, the better'),
            new TranslatableMessage('Debate and rate with your fellow these madness things'),
        ];
    }
}
