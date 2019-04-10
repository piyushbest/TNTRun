<?php

namespace TNTRun;

use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\level\Level;

class sendPopupAgain extends TNTRunTask {

    public $prefix;

    public function __construct($plugin, $text, Player $player) {
        $this->plugin = $plugin;
        $this->text = $text;
        $this->player = $player;
        parent::__construct($plugin, $text, $player);
        $this->prefix = $this->plugin->getConfig()->get("prefix");
    }

    public function onRun($tick) {
        $this->player->sendPopup($this->text);
    }

}
