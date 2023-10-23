<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Model\Thing;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;

final readonly class RateTheThingCommand implements BotCommandInterface
{
    public function __construct(
        private GameSession $gameSession,
        private PlayerRepositoryInterface $playerRepository,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $from = $telegramDto->getUser();
        $player = $this->playerRepository->find($from->getId());
        $game = $this->gameSession->continueGame($player);
        $ratedThing = $game->getRatedThing();

        if ($game->alreadyRated($ratedThing, $from->getId())) {
            throw new TelegramException('You must not change you rating. Wait other players');
        }

        $rating = $telegramDto->getMessage()->getText();

        if (!is_numeric($rating)) {
            throw new TelegramException('Rating must be a number');
        }

        $rating = (int) $rating;

        try {
            $game->rateThing($ratedThing, $from->getId(), $rating);
        } catch (GameException $exception) {
            throw new TelegramException($exception->getMessage());
        }

        $this->gameSession->save($game);

        if ($game->isThingFullyRated($ratedThing)) {
            $randomThing = $game->getRandomThing();

            if ($randomThing === null) {
                foreach ($game->getPlayers() as $player) {
                    if ($game->isPlayerMaster($player)) {

                    }
                    $this->telegramApi->sendMessage(
                        $player->getTelegramId(),
                        "Congrats! You really rated all this nonsense",
                        [
                            'reply_markup' => [
                                'inline_keyboard' => [[
                                    [
                                        'text' => 'Show result',
                                        'callback_data' => ShowResultCommand::COMMAND_NAME,
                                    ],
                                ]],
                            ],
                        ],
                    );

                    $this->telegramBot->stopProcessingCommand($player->getTelegramId());
                }

                return;
            }

            $game->setRatedThing(new Thing($randomThing));
            $this->gameSession->save($game);

            foreach ($game->getPlayers() as $player) {
                $this->telegramApi->sendMessage($player->getTelegramId(), "Rate the next thing: {$game->getRatedThing()->getValue()}");
            }

            return;
        }

        $this->telegramApi->sendMessage($from->getId(), "Okay. Wait other players");
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return false;
    }
}
