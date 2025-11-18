<?php

declare(strict_types=1);

namespace Kronvel\Commands;

use Kronvel\LevelManager;
use Kronvel\Language;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class AdminExpCommand extends Command
{
    private LevelManager $levelManager;

    private Language $language;

    public function __construct(LevelManager $levelManager, Language $language)
    {
        parent::__construct("kexp", "Manage player EXP and levels", "");
        $this->levelManager = $levelManager;
        $this->language = $language;

        $this->setPermission("kronvel.cmd.kexp");
        $this->setUsage("");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool
    {
        if (!$sender->hasPermission("kronvel.cmd.kexp")) {
            $sender->sendMessage($this->language->get('error.no-permission'));
            return true;
        }

        if (count($args) < 1) {
            $sender->sendMessage($this->language->get('usage.kexp-root'));
            return true;
        }

        $sub = strtolower(array_shift($args));

        switch ($sub) {
            case "add":
                $this->handleAdd($sender, $args);
                break;
            case "setlevel":
                $this->handleSetLevel($sender, $args);
                break;
            case "info":
                $this->handleInfo($sender, $args);
                break;
            default:
                $sender->sendMessage($this->language->get('usage.kexp-root'));
                break;
        }

        return true;
    }

    /**
     * @param string[] $args
     */
    private function handleAdd(CommandSender $sender, array $args) : void
    {
        if (count($args) < 2) {
            $sender->sendMessage($this->language->get('usage.kexp-add'));
            return;
        }

        [$name, $amountStr] = $args;
        $amount = (int) $amountStr;

        if ($amount <= 0) {
            $sender->sendMessage($this->language->get('error.amount-positive'));
            return;
        }

        $max = $this->levelManager->getMaxAddAmount();
        if ($amount > $max) {
            $sender->sendMessage($this->language->get('error.amount-max', [
                'max' => $max,
            ]));
            return;
        }

        $target = Server::getInstance()->getPlayerExact($name);
        if (!$target instanceof Player) {
            $sender->sendMessage($this->language->get('error.player-not-found'));
            return;
        }

        $this->levelManager->addExp($target, $amount);
        $sender->sendMessage($this->language->get('success.kexp-add', [
            'amount' => $amount,
            'name' => $target->getName(),
        ]));
    }

    /**
     * @param string[] $args
     */
    private function handleSetLevel(CommandSender $sender, array $args) : void
    {
        if (count($args) < 2) {
            $sender->sendMessage($this->language->get('usage.kexp-setlevel'));
            return;
        }

        [$name, $levelStr] = $args;
        $level = (int) $levelStr;

        if ($level <= 0) {
            $sender->sendMessage($this->language->get('error.level-positive'));
            return;
        }

        $max = $this->levelManager->getMaxSetLevel();
        if ($level > $max) {
            $sender->sendMessage($this->language->get('error.level-max', [
                'max' => $max,
            ]));
            return;
        }

        $target = Server::getInstance()->getPlayerExact($name);
        if (!$target instanceof Player) {
            $sender->sendMessage($this->language->get('error.player-not-found'));
            return;
        }

        $this->levelManager->setLevel($target, $level);
        $sender->sendMessage($this->language->get('success.kexp-setlevel', [
            'name' => $target->getName(),
            'level' => $level,
        ]));
    }

    /**
     * @param string[] $args
     */
    private function handleInfo(CommandSender $sender, array $args) : void
    {
        if (count($args) < 1) {
            $sender->sendMessage($this->language->get('usage.kexp-info'));
            return;
        }

        $name = $args[0];
        $target = Server::getInstance()->getPlayerExact($name);
        if (!$target instanceof Player) {
            $sender->sendMessage($this->language->get('error.player-not-found'));
            return;
        }

        $level = $this->levelManager->getLevel($target);
        $exp = $this->levelManager->getExp($target);
        $toNext = $this->levelManager->getExpToNextLevel($target);
        $nextTotal = $exp + $toNext;

        $sender->sendMessage($this->language->get('card.other.header', [
            'name' => $target->getName(),
        ]));
        $sender->sendMessage($this->language->get('card.other.level', [
            'level' => $level,
        ]));
        $sender->sendMessage($this->language->get('card.other.exp', [
            'exp' => $exp,
            'total' => $nextTotal,
            'toNext' => $toNext,
        ]));
        $sender->sendMessage($this->language->get('card.other.footer'));
    }
}
