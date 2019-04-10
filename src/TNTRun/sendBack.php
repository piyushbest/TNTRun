<?php

namespace TNTRun;

use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\level\Position;

class sendBack extends TNTRunTask {

    public $prefix;

    public function __construct($plugin, Player $player) {
        $this->plugin = $plugin;
        $this->player = $player;
        parent::__construct($plugin);
        $this->prefix = $this->plugin->getConfig()->get("prefix");
    }

    public function onRun($tick) {
        $level = $this->plugin->getServer()->getDefaultLevel();
        $spawn = $level->getSafeSpawn();
        $this->player->teleport(new Position($spawn->getX(), $spawn->getY(), $spawn->getZ(), $level), 0, 0);
    }

}