# KronvelLevels

[![PocketMine API](https://img.shields.io/badge/PocketMine-API%205.0.0-blue.svg)](https://github.com/pmmp/PocketMine-MP)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](https://www.php.net/)
[![Status](https://img.shields.io/badge/Status-Beta-gold.svg)](#)

KronvelLevels is a modern EXP + leveling plugin for PocketMine servers. It listens to everyday gameplay (block breaking, mob kills), applies rank-based multipliers, stores player progress in SQLite, and keeps every user-facing string inside a language file for fast iteration.

---

## ✨ Highlights
- 🧱 **Configurable Sources**: Define EXP per block and entity in YAML.
- 🎖️ **Permission Multipliers**: Highest owned multiplier wins, clamped by security limits.
- 🪄 **UX-First Feedback**: Action bar popups and title-based level-up notifications.
- 🧾 **Admin Toolkit**: `/kexp` offers add/set/info utilities with guard rails.
- 🌐 **Localization Ready**: `lang.yml` controls every message with placeholders.
- 🧩 **Public API**: `KronvelAPI` exposes safe methods for other plugins to hook into leveling.

## 📋 Requirements
- PocketMine-MP 5.0.0 (API 5)
- PHP 8.1 or higher
- SQLite extension (bundled with PocketMine)

## 📦 Installation
1. Clone or download this repository into `plugins/Kronvel`, or package it as a `.phar`.
2. Start the server. On first boot the plugin copies `config.yml`, `lang.yml`, and creates `playerslevel` inside `plugin_data/Kronvel/`.
3. Stop the server to adjust configuration/language files and restart when ready.

## ⚙️ Configuration (`resources/config.yml`)

```yml
leveling:
  base-exp: 100      # EXP needed for level 1
  exp-step: 25       # Extra EXP per additional level
blocks:
  "diamond_ore": 50
entities:
  "minecraft:zombie": 30
multipliers:
  "kronvel.multiplier.vip": 1.5
messages:
  popup-enabled: true
security:
  max-add-amount: 100000
  max-setlevel: 9999
  max-multiplier: 10.0
```

### Tips
- Use snake_case keys for blocks (`stone_bricks`) and network IDs for entities (`minecraft:zombie`).
- Any missing entry defaults to 0 EXP, keeping gameplay safe.

## 🌐 Language System (`resources/lang.yml`)
- Copied to the data folder automatically; edit to match your branding.
- Placeholders such as `{amount}`, `{multiplier}`, `{level}` are filled at runtime.
- Supports `§` formatting codes and PocketMine color helpers.

## 🎮 Commands

| Command | Description | Usage | Permission | Default |
|---------|-------------|-------|------------|---------|
| `/level` | Shows the player’s personal level/EXP card. | `/level` | `kronvel.cmd.level` | `true` |
| `/kexp add` | Adds EXP to an online player. | `/kexp add <player> <amount>` | `kronvel.cmd.kexp` | `op` |
| `/kexp setlevel` | Sets a player’s level. | `/kexp setlevel <player> <level>` | `kronvel.cmd.kexp` | `op` |
| `/kexp info` | Displays another player’s card remotely. | `/kexp info <player>` | `kronvel.cmd.kexp` | `op` |

### Permission Summary

| Permission | Purpose | Default |
|------------|---------|---------|
| `kronvel.cmd.level` | Allow players to view their own level card. | `true` |
| `kronvel.cmd.kexp` | Allow staff to manage EXP/levels via `/kexp`. | `op` |
| `kronvel.multiplier.*` | Optional rank multipliers configured in YAML. | Custom |

## 🛡️ Data & Safety
- Player progress is stored in `plugin_data/Kronvel/playerslevel` (SQLite with `uuid`, `name`, `level`, `exp`).
- Multipliers are clamped by `max-multiplier` to prevent inflation.
- `/kexp` subcommands enforce `max-add-amount` and `max-setlevel` to avoid abuse.

## 🧩 Developer API
Expose KronvelLevels to other plugins via the built-in API:

```php
/** @var Kronvel\Main $plugin */
$api = $plugin->getAPI();
$api->addExp($player, 100);
$level = $api->getLevel($player);
$required = $api->getRequiredExpForLevel(10);
```

All API calls reuse the same validation and security logic as the in-game workflow.

## 🗂️ Project Structure

```
Kronvel/
├── plugin.yml
├── resources/
│   ├── config.yml
│   └── lang.yml
└── src/Kronvel/
    ├── Commands/        # /level and /kexp handlers
    ├── EventListener.php
    ├── KronvelAPI.php   # Public API surface
    ├── Language.php
    ├── LevelManager.php # Logic + storage
    └── Main.php         # Bootstrap + wiring
```

## 🛣️ Roadmap
- Additional EXP triggers (fishing, quests, custom integrations)
- Config/lang hot-reload command
- PlaceholderAPI / ScoreHud hooks for HUD overlays

## 🤝 Contributing
1. Fork the repository and create a feature branch.
2. Follow PSR-12 style; keep changes focused and documented.
3. Update defaults (`config.yml`, `lang.yml`, README) when adding new settings.
4. Open a PR describing behavior changes and manual testing steps.

## 📞 Support
- Open an issue or discussion on the repository with logs/steps.
- Ping `Kronnosy` on the PocketMine Discord for urgent questions.

## 👤 Author

**Kronnosy**
- GitHub: [@Kronnosy](https://github.com/Kronnosy)
- YouTube: [@Kronnosy](https://www.youtube.com/@Kronnosy)

---

⭐ If this plugin powers your server, consider leaving a star or sharing screenshots of your level cards!

