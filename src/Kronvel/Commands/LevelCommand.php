<?php

declare(strict_types=1);

namespace Kronvel\Commands;

use Kronvel\LevelManager;
use Kronvel\Language;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class LevelCommand extends Command
{
    private LevelManager $levelManager;

    private Language $language;

    public function __construct(LevelManager $levelManager, Language $language)
    {
        parent::__construct("level", "Show your current level and EXP", "", ["lvl", "mylevel"]);
        $this->levelManager = $levelManager;
        $this->language = $language;

        $this->setPermission("kronvel.cmd.level");
        $this->setUsage("");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool
    {
        if (!$sender->hasPermission("kronvel.cmd.level")) {
            $sender->sendMessage($this->language->get('error.no-permission'));
            return true;
        }

        if (!$sender instanceof Player) {
            $sender->sendMessage($this->language->get('error.ingame-only'));
            return true;
        }

        $level = $this->levelManager->getLevel($sender);
        $exp = $this->levelManager->getExp($sender);
        $toNext = $this->levelManager->getExpToNextLevel($sender);
        $nextTotal = $exp + $toNext;

        $sender->sendMessage($this->language->get('card.self.header'));
        $sender->sendMessage($this->language->get('card.self.level', [
            'level' => $level,
        ]));
        $sender->sendMessage($this->language->get('card.self.exp', [
            'exp' => $exp,
            'total' => $nextTotal,
            'toNext' => $toNext,
        ]));
        $sender->sendMessage($this->language->get('card.self.footer'));

        return true;
    }
}
