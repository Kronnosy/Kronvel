<?php

declare(strict_types=1);

namespace Kronvel;

use pocketmine\utils\Config;

final class Language
{
    private Config $config;

    public function __construct(string $filePath)
    {
        $this->config = new Config($filePath, Config::YAML);
    }

    /**
     * @param array<string, scalar> $params
     */
    public function get(string $key, array $params = []) : string
    {
        $value = $this->config->getNested($key, $key);
        if (!is_string($value)) {
            $value = (string) $key;
        }

        foreach ($params as $name => $param) {
            $value = str_replace('{' . $name . '}', (string) $param, $value);
        }

        return $value;
    }
}
