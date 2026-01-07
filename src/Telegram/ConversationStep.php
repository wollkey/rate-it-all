<?php

declare(strict_types=1);

namespace App\Telegram;

final readonly class ConversationStep
{
    public function __construct(
        public string $name,
        public array $data = [],
    ) {
    }

    public function with(string $key, mixed $value): self
    {
        return new self($this->name, [...$this->data, $key => $value]);
    }

    public function next(string $name): self
    {
        return new self($name, $this->data);
    }
}
