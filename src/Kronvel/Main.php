<?php

declare(strict_types=1);

namespace Kronvel;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use Kronvel\Commands\LevelCommand;
use Kronvel\Commands\AdminExpCommand;

final class Main extends PluginBase implements Listener
{
    private LevelManager $levelManager;

    private Language $language;

    private KronvelAPI $api;

    protected function onEnable() : void
    {
        $this->saveDefaultConfig();
        @mkdir($this->getDataFolder());

        if (!is_file($this->getDataFolder() . 'lang.yml')) {
            $this->saveResource('lang.yml');
        }

        $this->language = new Language($this->getDataFolder() . 'lang.yml');

        $this->levelManager = new LevelManager($this, $this->language);
        $this->api = new KronvelAPI($this->levelManager);

        $this->registerEvents();
        $this->registerCommands();
    }

    private function registerEvents() : void
    {
        $this->getServer()->getPluginManager()->registerEvents(
            new EventListener($this->levelManager),
            $this
        );
    }

    private function registerCommands() : void
    {
        $commandMap = $this->getServer()->getCommandMap();

        foreach (["level", "kexp"] as $name) {
            $existing = $commandMap->getCommand($name);
            if ($existing !== null) {
                $existing->unregister($commandMap);
            }
        }

        $levelCommand = new LevelCommand($this->levelManager, $this->language);
        $adminCommand = new AdminExpCommand($this->levelManager, $this->language);

        $commandMap->register($this->getName(), $levelCommand);
        $commandMap->register($this->getName(), $adminCommand);
    }

    public function getLevelManager() : LevelManager
    {
        return $this->levelManager;
    }

    public function getLanguage() : Language
    {
        return $this->language;
    }

    public function getAPI() : KronvelAPI
    {
        return $this->api;
    }
}
