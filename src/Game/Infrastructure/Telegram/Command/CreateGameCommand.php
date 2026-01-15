<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\CreateGameUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Game;
use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\Repository\PlayerRepository;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use App\Game\Infrastructure\Telegram\Storage\GameTelegramContext;
use App\Telegram\AsTelegramCommand;
use App\Telegram\ConversationalCommand;
use App\Telegram\ConversationStep;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\Infrastructure\Http\TelegramResponder;
use App\Telegram\TelegramDto;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramCommand(self::COMMAND_NAME, inputTypes: [InputType::Text, InputType::Callback])]
final readonly class CreateGameCommand implements ConversationalCommand
{
    public const string COMMAND_NAME = '/create_game';
    private const string STEP_AWAITING_THINGS_COUNT = 'awaiting_things_count';

    public function __construct(
        private PlayerRepository $playerRepository,
        private TranslatorInterface $translator,
        private CreateGameUseCase $createGameUseCase,
        private string $telegramBotName,
        private GameTelegramContext $gameTelegramContext,
        private GameRepository $gameRepository,
        private TelegramResponder $telegramResponder,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramDto $telegramDto, null|ConversationStep $step = null): ConversationStep|null
    {
        $player = $this->playerRepository->find($telegramDto->user->id);
        $game = $this->gameRepository->findActiveByPlayer($player);

        if ($game !== null) {
            $this->handlePlayerAlreadyInGame($telegramDto, $game, $player);

            return null;
        }

        return match ($step?->name) {
            null => $this->askThingsCount($telegramDto),
            self::STEP_AWAITING_THINGS_COUNT => $this->createGame($telegramDto, $player, $step),
        };
    }

    private function handlePlayerAlreadyInGame(TelegramDto $telegramDto, Game $game, Player $player): void
    {
        if ($game->isMaster($player)) {
            $message = $this->translator->trans('Already playing. Would you like to finish the current one?');
            $keyboard = new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '💀' . $this->translator->trans('Finish the game'),
                        callbackData: FinishGameCommand::COMMAND_NAME,
                    ),
                ],
            ]);
        } else {
            $message = $this->translator->trans('You are in another game, do you want to leave it?');
            $keyboard = new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '💀' . $this->translator->trans('Leave the game'),
                        callbackData: LeaveGameCommand::COMMAND_NAME,
                    ),
                ],
            ]);
        }

        $this->telegramResponder->reply(
            $telegramDto,
            $message,
            $keyboard,
        );
    }

    private function askThingsCount(TelegramDto $telegramDto): ConversationStep|null
    {
        if (!$telegramDto->isCallback()) {
            return null;
        }

        $inlineKeyboard = new InlineKeyboardMarkup([
            array_map(static fn (int $numberOfThings): InlineKeyboardButton => new InlineKeyboardButton(
                text: (string) $numberOfThings,
                callbackData: (string) $numberOfThings,
            ), range(ThingsPerPlayer::MIN_THINGS_PER_PLAYER, ThingsPerPlayer::MAX_THINGS_PER_PLAYER)),
            [
                new InlineKeyboardButton(
                    text: '⬅️ '.$this->translator->trans('Back to menu'),
                    callbackData: RulesCommand::COMMAND_NAME,
                ),
            ],
        ]);

        $this->telegramResponder->replyCallback(
            $telegramDto,
            $this->translator->trans('Enter the number of rated things per player:'),
            $inlineKeyboard,
        );

        return new ConversationStep(self::STEP_AWAITING_THINGS_COUNT);
    }

    private function createGame(TelegramDto $telegramDto, Player $player, ConversationStep $step): ConversationStep|null
    {
        if (!$telegramDto->isCallback()) {
            return $step;
        }

        $this->gameTelegramContext->saveEditedMessage($telegramDto->message);

        $numberOfThings = new ThingsPerPlayer((int) $telegramDto->callbackQuery->data);

        $newGame = ($this->createGameUseCase)($player, $numberOfThings);

        $joinLink = "https://t.me/{$this->telegramBotName}?start={$newGame->getCode()->toRfc4122()}";

        $this->telegramResponder->replyCallback(
            $telegramDto,
            'Отправьте ссылку друзьям, чтобы они присоединились',
            new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '📤 Поделиться с друзьями',
                        url: 'https://t.me/share/url?url='.urlencode($joinLink).'&text='.'Присоединяйся к игре!',
                    ),
                ],
            ]),
        );

        return null;
    }
}
