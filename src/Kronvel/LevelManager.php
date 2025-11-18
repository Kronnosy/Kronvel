<?php

declare(strict_types=1);

namespace Kronvel;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class LevelManager
{
    private Main $plugin;

    private \SQLite3 $database;

    private int $baseExp;

    private int $expStep;

    private int $maxLevel = 9999;

    private bool $popupEnabled;

    /** @var array<string,int|float> */
    private array $blockExp;

    /** @var array<string,int|float> */
    private array $entityExp;

    /** @var array<string,float|int> */
    private array $multipliers;

    private Language $language;

    private int $maxAddAmount = 100000;

    private int $maxSetLevel = 9999;

    private float $maxMultiplier = 10.0;

    public function __construct(Main $plugin, Language $language)
    {
        $this->plugin = $plugin;
        $this->language = $language;

        $this->loadConfigValues();
        $this->initializeDatabase();
    }

    private function loadConfigValues() : void
    {
        $config = $this->plugin->getConfig();

        $leveling = (array) $config->get("leveling", []);
        $this->baseExp = (int) ($leveling["base-exp"] ?? 100);
        $this->expStep = (int) ($leveling["exp-step"] ?? 25);

        $this->blockExp = (array) $config->get("blocks", []);
        $this->entityExp = (array) $config->get("entities", []);
        $this->multipliers = (array) $config->get("multipliers", []);

        $messages = (array) $config->get("messages", []);
        $this->popupEnabled = (bool) ($messages["popup-enabled"] ?? true);

        $security = (array) $config->get("security", []);
        $this->maxAddAmount = max(1, (int) ($security["max-add-amount"] ?? 100000));
        $this->maxSetLevel = max(1, (int) ($security["max-setlevel"] ?? $this->maxLevel));
        $this->maxMultiplier = max(0.0, (float) ($security["max-multiplier"] ?? 10.0));
    }

    private function initializeDatabase() : void
    {
        $path = $this->plugin->getDataFolder() . "playerslevel";
        $this->database = new \SQLite3($path);
        $this->database->exec(
            "CREATE TABLE IF NOT EXISTS players (" .
            "uuid TEXT PRIMARY KEY, " .
            "name TEXT NOT NULL, " .
            "level INTEGER NOT NULL, " .
            "exp INTEGER NOT NULL)"
        );
    }

    public function getBlockExp(Block $block) : int
    {
        $name = strtolower($block->getName());
        $key = str_replace(" ", "_", $name);

        $value = $this->blockExp[$key] ?? 0;
        return (int) $value;
    }

    public function getEntityExp(Entity $entity) : int
    {
        // Expecting keys like "minecraft:zombie" from the entity network ID
        if (method_exists($entity, "getNetworkTypeId")) {
            /** @var mixed $id */
            $id = $entity->getNetworkTypeId();
            if (is_string($id)) {
                $key = $id;
            } else {
                $key = (string) $id;
            }
        } else {
            $key = strtolower((new \ReflectionClass($entity))->getShortName());
        }

        $value = $this->entityExp[$key] ?? 0;
        return (int) $value;
    }

    public function getLevel(Player $player) : int
    {
        $row = $this->getOrCreatePlayerRow($player);
        return (int) $row["level"];
    }

    public function getExp(Player $player) : int
    {
        $row = $this->getOrCreatePlayerRow($player);
        return (int) $row["exp"];
    }

    public function getExpToNextLevel(Player $player) : int
    {
        $row = $this->getOrCreatePlayerRow($player);
        $level = (int) $row["level"];
        $exp = (int) $row["exp"];

        if ($level >= $this->maxLevel) {
            return 0;
        }

        $required = $this->getRequiredExpForLevel($level);
        return max(0, $required - $exp);
    }

    public function getRequiredExpForLevel(int $level) : int
    {
        if ($level < 1) {
            $level = 1;
        }

        return $this->baseExp + ($level - 1) * $this->expStep;
    }

    public function addExp(Player $player, int $baseAmount) : void
    {
        if ($baseAmount <= 0) {
            return;
        }

        $row = $this->getOrCreatePlayerRow($player);
        $level = (int) $row["level"];
        $exp = (int) $row["exp"];

        $multiplier = $this->getMultiplier($player);
        $effective = (int) round($baseAmount * $multiplier);
        if ($effective <= 0) {
            $effective = 1;
        }

        $oldLevel = $level;
        $exp += $effective;

        while ($level < $this->maxLevel) {
            $required = $this->getRequiredExpForLevel($level);
            if ($exp < $required) {
                break;
            }

            $exp -= $required;
            $level++;
        }

        if ($level >= $this->maxLevel) {
            $level = $this->maxLevel;
            $exp = 0;
        }

        $this->savePlayer($player, $level, $exp);

        if ($this->popupEnabled && $effective > 0) {
            $text = $this->language->get('exp-gain', [
                'amount' => $effective,
                'multiplier' => $multiplier,
            ]);
            $player->sendPopup($text);
        }

        if ($level > $oldLevel) {
            $this->sendLevelUpMessages($player, $oldLevel, $level);
        }
    }

    public function setLevel(Player $player, int $level) : void
    {
        if ($level < 1) {
            $level = 1;
        }
        if ($level > $this->maxLevel) {
            $level = $this->maxLevel;
        }

        $row = $this->getOrCreatePlayerRow($player);
        $oldLevel = (int) $row["level"];

        $this->savePlayer($player, $level, 0);

        if ($level > $oldLevel) {
            $this->sendLevelUpMessages($player, $oldLevel, $level);
        }
    }

    private function getMultiplier(Player $player) : float
    {
        $max = 1.0;

        foreach ($this->multipliers as $permission => $value) {
            if (!$player->hasPermission($permission)) {
                continue;
            }

            $multiplier = (float) $value;
            if ($multiplier < 0.0) {
                $multiplier = 0.0;
            }
            if ($multiplier > $this->maxMultiplier) {
                $multiplier = $this->maxMultiplier;
            }
            if ($multiplier > $max) {
                $max = $multiplier;
            }
        }

        return $max;
    }

    public function getMaxAddAmount() : int
    {
        return $this->maxAddAmount;
    }

    public function getMaxSetLevel() : int
    {
        return $this->maxSetLevel;
    }

    private function sendLevelUpMessages(Player $player, int $oldLevel, int $newLevel) : void
    {
        $player->sendMessage($this->language->get('level-up.title'));
        $player->sendMessage($this->language->get('level-up.line', [
            'old' => $oldLevel,
            'new' => $newLevel,
        ]));
        $player->sendMessage($this->language->get('level-up.reached', [
            'new' => $newLevel,
        ]));
    }

    /**
     * @return array{level:int,exp:int}
     */
    private function getOrCreatePlayerRow(Player $player) : array
    {
        $uuid = $player->getUniqueId()->toString();

        $stmt = $this->database->prepare("SELECT name, level, exp FROM players WHERE uuid = :uuid");
        $stmt->bindValue(":uuid", $uuid, \SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(\SQLITE3_ASSOC) ?: null;
        $result->finalize();

        if ($row === null) {
            $level = 1;
            $exp = 0;

            $insert = $this->database->prepare(
                "INSERT INTO players (uuid, name, level, exp) VALUES (:uuid, :name, :level, :exp)"
            );
            $insert->bindValue(":uuid", $uuid, \SQLITE3_TEXT);
            $insert->bindValue(":name", $player->getName(), \SQLITE3_TEXT);
            $insert->bindValue(":level", $level, \SQLITE3_INTEGER);
            $insert->bindValue(":exp", $exp, \SQLITE3_INTEGER);
            $insert->execute();
            $insert->close();

            return ["level" => $level, "exp" => $exp];
        }

        if ($row["name"] !== $player->getName()) {
            $updateName = $this->database->prepare("UPDATE players SET name = :name WHERE uuid = :uuid");
            $updateName->bindValue(":uuid", $uuid, \SQLITE3_TEXT);
            $updateName->bindValue(":name", $player->getName(), \SQLITE3_TEXT);
            $updateName->execute();
            $updateName->close();
        }

        return [
            "level" => (int) $row["level"],
            "exp" => (int) $row["exp"],
        ];
    }

    private function savePlayer(Player $player, int $level, int $exp) : void
    {
        $uuid = $player->getUniqueId()->toString();

        $stmt = $this->database->prepare(
            "UPDATE players SET name = :name, level = :level, exp = :exp WHERE uuid = :uuid"
        );
        $stmt->bindValue(":uuid", $uuid, \SQLITE3_TEXT);
        $stmt->bindValue(":name", $player->getName(), \SQLITE3_TEXT);
        $stmt->bindValue(":level", $level, \SQLITE3_INTEGER);
        $stmt->bindValue(":exp", $exp, \SQLITE3_INTEGER);
        $stmt->execute();
        $stmt->close();
    }
}
