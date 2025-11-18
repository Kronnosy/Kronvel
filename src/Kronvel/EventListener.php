<?php

declare(strict_types=1);

namespace Kronvel;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

final class EventListener implements Listener
{
    private LevelManager $levelManager;

    public function __construct(LevelManager $levelManager)
    {
        $this->levelManager = $levelManager;
    }

    public function onBlockBreak(BlockBreakEvent $event) : void
    {
        if ($event->isCancelled()) {
            return;
        }

        $player = $event->getPlayer();
        $gamemode = $player->getGamemode();

        if (!$gamemode->equals(GameMode::SURVIVAL()) && !$gamemode->equals(GameMode::ADVENTURE())) {
            return;
        }

        $block = $event->getBlock();
        $exp = $this->levelManager->getBlockExp($block);

        if ($exp > 0) {
            $this->levelManager->addExp($player, $exp);
        }
    }

    public function onEntityDeath(EntityDeathEvent $event) : void
    {
        $entity = $event->getEntity();
        $cause = $entity->getLastDamageCause();

        if (!$cause instanceof EntityDamageByEntityEvent) {
            return;
        }

        $damager = $cause->getDamager();

        if (!$damager instanceof Player) {
            return;
        }

        $exp = $this->levelManager->getEntityExp($entity);
        if ($exp > 0) {
            $this->levelManager->addExp($damager, $exp);
        }
    }
}
