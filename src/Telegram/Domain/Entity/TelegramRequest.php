<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Entity;

use Symfony\Component\Serializer\Annotation\SerializedName;

final readonly class TelegramRequest
{
    public function __construct(
        private Message|null $message = null,

        #[SerializedName('edited_message')]
        private Message|null $editedMessage = null,

        #[SerializedName('callback_query')]
        private CallbackQuery|null $callbackQuery = null,
    ) {
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function getEditedMessage(): ?Message
    {
        return $this->editedMessage;
    }

    public function getCallbackQuery(): ?CallbackQuery
    {
        return $this->callbackQuery;
    }
}
