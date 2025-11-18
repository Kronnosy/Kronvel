<?php

declare(strict_types=1);

namespace Kronvel;

use pocketmine\player\Player;

final class KronvelAPI
{
    private LevelManager $levelManager;

    public function __construct(LevelManager $levelManager)
    {
        $this->levelManager = $levelManager;
    }

    public function getLevel(Player $player) : int
    {
        return $this->levelManager->getLevel($player);
    }

    public function getExp(Player $player) : int
    {
        return $this->levelManager->getExp($player);
    }

    public function getExpToNextLevel(Player $player) : int
    {
        return $this->levelManager->getExpToNextLevel($player);
    }

    public function addExp(Player $player, int $amount) : void
    {
        $this->levelManager->addExp($player, $amount);
    }

    public function setLevel(Player $player, int $level) : void
    {
        $this->levelManager->setLevel($player, $level);
    }

    public function getRequiredExpForLevel(int $level) : int
    {
        return $this->levelManager->getRequiredExpForLevel($level);
    }
}
