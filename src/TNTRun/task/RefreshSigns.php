<?php

namespace TNTRun\task;

use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;

class RefreshSigns extends TNTRunTask {

    public $prefix;

    public function __construct($plugin) {
        $this->plugin = $plugin;
        parent::__construct($plugin);
        $this->prefix = $this->plugin->getConfig()->get("prefix");
    }

    public function onRun($tick) {
        $allplayers = $this->plugin->getServer()->getOnlinePlayers();
        $level = $this->plugin->getServer()->getDefaultLevel();
        $tiles = $level->getTiles();
        foreach ($tiles as $t) {
            if ($t instanceof Sign) {
                $text = $t->getText();
                if ($text[0] == $this->prefix) {
                    $aop = 0;
                    foreach ($allplayers as $player) {
                        if ($player->getLevel()->getFolderName() == TextFormat::clean($text[1])) {
                            $aop = $aop + 1;
                        }
                    }
                    $ingame = TextFormat::GREEN . "JOIN";
                    $config = $this->plugin->getConfig();
                    $time = (int) $config->getNested("arenas." . TextFormat::clean($text[1]) . ".start");
                    $maxPlayers = (int) $config->getNested("arenas." . TextFormat::clean($text[1]) . ".maxPlayers");
                    if ($time === 0) {
                        $ingame = TextFormat::RED . "INGAME";
                    }
                    if ($aop >= $maxPlayers && $time != 0) {
                        $ingame = TextFormat::GOLD . "FULL";
                    }
                    $t->setText($this->prefix, $text[1], $ingame, TextFormat::YELLOW . $aop . "/" . $config->getNested("arenas." . TextFormat::clean($text[1]) . ".maxPlayers"));
                }
            }
        }
    }

}
