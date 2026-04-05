# Rate it all game

A game where you and your friends add any crazy things that come to mind and then rate them together.

Telegram bot — [@rate_it_all_bot](https://t.me/rate_it_all_bot)

## Architecture

### Modules
Modular structure (package-by-feature) with two main modules: `Game` and `Telegram`.
Dependency is unidirectional — `Game` uses `Telegram`, but `Telegram` knows nothing about the game.
Each module is split into layers: Domain, Application, Infrastructure.

### Domain
DDD approach: aggregate `Game`, domain events, value objects.
The game logic is UI-agnostic — it doesn't depend on Telegram, web, or any other interface.
New UIs can be added without touching the domain.

### Webhook routing
Chain of Responsibility pattern with PHP attributes for routing Telegram webhooks.
Handlers are resolved by type: `CommandHandlerResolver` handles slash commands via `#[OnCommand]`,
`GameStateHandlerResolver` routes by current game state via `#[OnGameState]`.
Custom resolvers can be added without modifying existing code.

### Localization
Each user receives messages in their own language based on their Telegram locale settings.
Broadcast messages (sent to all players at once) are also localized per recipient —
each player gets the message in their own language regardless of who triggered the action.

## Technical stack

PHP 8.4, Symfony 7.4, Doctrine ORM, FrankenPHP, PHPUnit 13, PHPStan level 10, PHP CS Fixer, Rector, Deptrac.

See [composer.json](composer.json).

## Testing

Detroit school of testing. Pyramid of tests: unit tests on the aggregate and domain entities,
integration tests on use cases with a real database. AAA pattern (Arrange, Act, Assert).

## Setup

Requires Docker.
```shell
make setup && make up
```

See [Makefile](Makefile) for all available commands.
