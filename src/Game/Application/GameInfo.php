<?php

declare(strict_types=1);

namespace App\Game\Application;

use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class GameInfo
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function description(): string
    {
        return $this->translator->trans(
            'In this game you need to rate any things that come to your mind: *red color*, *hand washing*, *a small salary*, *anything*...'
        );
    }

    /**
     * @return list<string>
     */
    public function rules(): array
    {
        return array_map($this->translator->trans(...), [
            'Create a game or join an existing one',
            'Invite more friends',
            'Add any things that come to your mind - the weirder, the better',
            'Debate and rate with your fellow these madness things',
        ]);
    }

    public function prettyInfo(): string
    {
        return implode(PHP_EOL, [
            $this->description().PHP_EOL,
            $this->translator->trans('Gameplay rules:'),
            ...array_map(static fn (string $rule) => "- $rule", $this->rules()),
            '',
            $this->translator->trans('Have fun!').' 🎊',
        ]);
    }
}
