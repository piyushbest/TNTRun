<?php

namespace TNTRun\task;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;

abstract class TNTRunTask extends Task {

    protected $owner;

    public function __construct(Plugin $owner) {
     $this->owner = $owner;
    }

    final public function getOwner(): Plugin {
     return $this->owner;
    }
 }
