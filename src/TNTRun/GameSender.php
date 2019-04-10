<?php

namespace TNTRun;

use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\tile\Chest;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\tile\Sign;

class GameSender extends TNTRunTask {

    public $prefix;

    public function __construct($plugin) {
        $this->plugin = $plugin;
        $this->prefix = $this->plugin->getConfig()->get("prefix");
    }

    public function onRun($tick) {
        $config = $this->plugin->getConfig();
        $arenas = $config->get("arenas");
        if (count($arenas) > 0) {
            foreach ($arenas as $arena) {
                $name = $arena["name"];
                $time = $arena["time"];
                $timeToStart = $config->getNested("arenas." . $name . ".start");
                $minPlayers = $arena["minPlayers"];
                $ingameplayers = $arena["players"];
                $levelArena = $this->plugin->getServer()->getLevelByName($name);
                if ($levelArena instanceof Level) {
                    $playersArena = $this->plugin->getServer()->getLevelByName($name)->getPlayers();
                    $onlineplayers = $this->plugin->getServer()->getOnlinePlayers();
                    if (count($playersArena) === 0) {
                        $config->setNested("arenas." . $name . ".time", $config->get("time"));
                        $config->setNested("arenas." . $name . ".players", array());
                        $config->setNested("arenas." . $name . ".start", 60);
                        $config->save();
                    } elseif (count($playersArena) < $minPlayers && $timeToStart > 0) {
                        foreach ($playersArena as $pl) {
                            $pl->sendPopup(TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::GOLD . "More Players needed" . TextFormat::GRAY . TextFormat::BOLD . " <");
                            $this->plugin->getScheduler()->scheduleDelayedTask(new sendPopupAgain($this->plugin, TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::GOLD . "More Players needed" . TextFormat::GRAY . TextFormat::BOLD . " <", $pl), 10);
                        }
                        $config->setNested("arenas." . $name . ".time", $config->get("time"));
                        $config->setNested("arenas." . $name . ".start", 60);
                        $config->save();
                    } else {
                        if ((count($playersArena) === 1) && ($timeToStart === 0)) {
                            foreach($playersArena as $pl) {
                                $level = $this->plugin->getServer()->getDefaultLevel();
                                $tiles = $level->getTiles();
                                foreach ($tiles as $t) {
                                    if ($t instanceof Sign) {
                                        $text = $t->getText();
                                        if (TextFormat::clean($text[1]) == $pl->getLevel()->getFolderName()) {
                                            $ingame = TextFormat::GREEN . "JOIN";
                                            $t->setText($this->prefix, $text[1], $ingame, TextFormat::YELLOW . "0/" . $config->getNested("arenas." . $name . ".maxPlayers"));
                                        }
                                    }
                                }
                                $pl->sendMessage($this->prefix . TextFormat::GREEN . " You won!");
                                $spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                                $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
                                if ($pl->isOnline()) {
                                    $pl->teleport($spawn, 0, 0);
                                    $pl->getInventory()->clearAll();
                                }

                                $this->plugin->getServer()->unloadLevel($levelArena);
                                $this->deleteDirectory($this->plugin->getServer()->getDataPath() . "/worlds/" . $name);
                                $this->copymap($this->plugin->getDataFolder() . "/maps/" . $name, $this->plugin->getServer()->getDataPath() . "/worlds/" . $name);
                                $this->plugin->getServer()->loadLevel($name);
                            }
                            foreach ($levelArena->getEntities() as $entity) {
                                if (!$entity instanceof Player) {
                                    $entity->despawnFromAll();
                                }
                            }
                            $config->setNested("arenas." . $name . ".time", $time);
                            $config->save();
                        }
                        if ((count($playersArena) > 1) && ($timeToStart === 0)) {
                            foreach ($playersArena as $pl) {
                                $pl->sendPopup(TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::AQUA . count($playersArena) . TextFormat::GOLD . " Players left" . TextFormat::GRAY . TextFormat::BOLD . " <");
                                $this->plugin->getScheduler()->scheduleDelayedTask(new sendPopupAgain($this->plugin, TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::AQUA . count($playersArena) . TextFormat::GOLD . " Players left" . TextFormat::GRAY . TextFormat::BOLD . " <", $pl), 10);
                            }
                        }
                        if (count($playersArena) >= $minPlayers) {
                            if ($timeToStart > 0) {
                                $timeToStart--;
                                $config->setNested("arenas." . $name . ".start", $timeToStart);
                                if ($timeToStart <= 0) {
                                    if ((count($playersArena) != 0) || (count($playersArena) != 1)) {
                                        sort($ingameplayers, SORT_NATURAL | SORT_FLAG_CASE);
                                        foreach ($ingameplayers as $key => $igp) {
                                            $p = $this->plugin->getServer()->getPlayer($igp);
                                            $spawns = $this->plugin->getConfig()->getNested("arenas." . $levelArena->getFolderName() . ".spawn");
                                            $x = $spawns["x"];
                                            $y = $spawns["y"];
                                            $z = $spawns["z"];
                                            $p->teleport(new Vector3($x, $y, $z));
                                            $p->getInventory()->clearAll();
                                            $p->sendMessage($this->prefix . TextFormat::YELLOW . " The Game started!");
                                        }
                                        $config->setNested("arenas." . $name . ".time", $config->get("time"));
                                        $config->save();
                                        $levelArena->setTime(0);
                                    } else {
                                        $timeToStart = 60;
                                        foreach ($playersArena as $pl) {
                                            $pl->sendMessage($this->prefix . TextFormat::YELLOW . " Reset the Timer. You cannot play alone!");
                                        }
                                    }
                                }
                                foreach($playersArena as $pl) {
                                    $pl->sendPopup(TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::AQUA . $timeToStart . TextFormat::GOLD . "s left" . TextFormat::GRAY . TextFormat::BOLD . " <");
                                    $this->plugin->getScheduler()->scheduleDelayedTask(new sendPopupAgain($this->plugin, TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::AQUA . $timeToStart . TextFormat::GOLD . "s left" . TextFormat::GRAY . TextFormat::BOLD . " <", $pl), 10);
                                }
                                $config->setNested("arenas." . $name . ".time", $config->get("time"));
                                $config->save();
                            } elseif ((count($playersArena) > 1) && ($timeToStart === 0)) {
                                foreach ($playersArena as $pl) {
                                    $pl->sendPopup(TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::AQUA . count($playersArena) . TextFormat::GOLD . " Players left" . TextFormat::GRAY . TextFormat::BOLD . " <");
                                    $this->plugin->getScheduler()->scheduleDelayedTask(new sendPopupAgain($this->plugin, TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::AQUA . count($playersArena) . TextFormat::GOLD . " Players left" . TextFormat::GRAY . TextFormat::BOLD . " <", $pl), 10);
                                }
                            }
                            $time--;
                            $minutes = $time / 60;
                            if (is_int($minutes) && $minutes != 0 && $minutes < 0) {
                                foreach ($playersArena as $pl) {
                                    $pl->sendMessage($this->prefix . TextFormat::YELLOW . " " . $minutes . " " . ($minutes > 1 ? "Minutes" : "Minute") . " left.");
                                }
                                $levelArena->setTime(0);
                            } else if ($time == 30 || $time == 15 || $time == 10 || $time == 5 || $time == 4 || $time == 3 || $time == 2) {
                                foreach ($playersArena as $pl) {
                                    $pl->sendMessage($this->prefix . TextFormat::YELLOW . " " . $time . " left.");
                                }
                            } else if ($time == 1) {
                                foreach ($playersArena as $pl) {
                                    $pl->sendMessage($this->prefix . TextFormat::YELLOW . " " . $time . " left.");
                                }
                            } else if ($time <= 0) {
                                foreach ($playersArena as $pl) {
                                    $level = $this->plugin->getServer()->getDefaultLevel();
                                    $tiles = $level->getTiles();
                                    foreach ($tiles as $t) {
                                        if ($t instanceof Sign) {
                                            $text = $t->getText();
                                            if (TextFormat::clean($text[1]) == $pl->getLevel()->getName()) {
                                                $ingame = TextFormat::GREEN . "JOIN";
                                                $t->setText($this->prefix, $text[1], $ingame, TextFormat::YELLOW . "0/" . $config->getNested("arenas." . $name . ".maxPlayers"));
                                            }
                                        }
                                    }
                                    $pl->sendMessage($this->prefix . TextFormat::GREEN . " There's no winner.");
                                    $spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                                    $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());

                                    if ($pl->isOnline()) {
                                        $pl->teleport($spawn, 0, 0);
                                        $pl->getInventory()->clearAll();
                                    }
                                }
                                $this->plugin->getServer()->unloadLevel($levelArena);
                                $this->deleteDirectory($this->plugin->getServer()->getDataPath() . "/worlds/" . $name);
                                $this->copymap($this->plugin->getDataFolder() . "/maps/" . $name, $this->plugin->getServer()->getDataPath() . "/worlds/" . $name);
                                $this->plugin->getServer()->loadLevel($levelArena);
                                foreach ($levelArena->getEntities() as $entity) {
                                    if (!$entity instanceof Player) {
                                        $entity->despawnFromAll();
                                    }
                                }
                            }
                            $config->setNested("arenas." . $name . ".time", $time);
                            $config->save();
                        } else {
                            if ($config->getNested("arenas." . $name . ".start") != 60) {
                                if ($timeToStart > 0) {
                                    $timeToStart--;
                                    $config->setNested("arenas." . $name . ".start", $timeToStart);
                                    if ($timeToStart <= 0) {
                                        if ((count($playersArena) != 0) || (count($playersArena) != 1)) {
                                            sort($ingameplayers, SORT_NATURAL | SORT_FLAG_CASE);
                                            foreach ($ingameplayers as $key => $igp) {
                                                $p = $this->plugin->getServer()->getPlayer($igp);
                                                $spawns = $this->plugin->getConfig()->getNested("arenas." . $levelArena->getFolderName() . ".spawn");
                                                $x = $spawns["x"];
                                                $y = $spawns["y"];
                                                $z = $spawns["z"];
                                                $p->teleport(new Vector3($x, $y, $z));
                                                $p->getInventory()->clearAll();
                                                $p->sendMessage($this->prefix . TextFormat::YELLOW . " The Game started!");
                                            }
                                            $config->setNested("arenas." . $name . ".time", $config->get("time"));
                                            $config->save();
                                            $levelArena->setTime(0);
                                        } else {
                                            $timeToStart = 60;
                                            foreach ($playersArena as $pl) {
                                                $pl->sendMessage($this->prefix . TextFormat::YELLOW . " Reset the Timer. You cannot play alone!");
                                            }
                                        }
                                    }
                                    foreach ($playersArena as $pl) {
                                        $pl->sendPopup(TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::AQUA . $timeToStart . TextFormat::GOLD . "s left" . TextFormat::GRAY . TextFormat::BOLD . " <");
                                        $this->plugin->getScheduler()->scheduleDelayedTask(new sendPopupAgain($this->plugin, TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::AQUA . $timeToStart . TextFormat::GOLD . "s left" . TextFormat::GRAY . TextFormat::BOLD . " <", $pl), 10);
                                    }
                                    $config->setNested("arenas." . $name . ".time", $config->get("time"));
                                    $config->save();
                                } elseif ((count($playersArena) > 1) && ($timeToStart === 0)) {
                                    foreach ($playersArena as $pl) {
                                        $pl->sendPopup(TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::AQUA . count($playersArena) . TextFormat::GOLD . " Players left" . TextFormat::GRAY . TextFormat::BOLD . " <");
                                        $this->plugin->getScheduler()->scheduleDelayedTask(new sendPopupAgain($this->plugin, TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::AQUA . count($playersArena) . TextFormat::GOLD . " Players left" . TextFormat::GRAY . TextFormat::BOLD . " <", $pl), 10);
                                    }
                                }
                                $time--;
                                $minutes = $time / 60;
                                if (is_int($minutes) && $minutes != 0 && $minutes < 0) {
                                    foreach ($playersArena as $pl) {
                                        $pl->sendMessage($this->prefix . TextFormat::YELLOW . " " . $minutes . " " . ($minutes > 1 ? "Minutes" : "Minute") . " left.");
                                    }
                                    $levelArena->setTime(0);
                                } else if ($time == 30 || $time == 15 || $time == 10 || $time == 5 || $time == 4 || $time == 3 || $time == 2) {
                                    foreach ($playersArena as $pl) {
                                        $pl->sendMessage($this->prefix . TextFormat::YELLOW . " " . $time . " left.");
                                    }
                                } else if ($time == 1) {
                                    foreach ($playersArena as $pl) {
                                        $pl->sendMessage($this->prefix . TextFormat::YELLOW . " " . $time . " left.");
                                    }
                                } else if ($time <= 0) {
                                    foreach ($playersArena as $pl) {
                                        $level = $this->plugin->getServer()->getDefaultLevel();
                                        $tiles = $level->getTiles();
                                        foreach ($tiles as $t) {
                                            if ($t instanceof Sign) {
                                                $text = $t->getText();
                                                if (TextFormat::clean($text[1]) == $pl->getLevel()->getName()) {
                                                    $ingame = TextFormat::GREEN . "JOIN";
                                                    $t->setText($this->prefix, $text[1], $ingame, TextFormat::YELLOW . "0/" . $config->getNested("arenas." . $name . ".maxPlayers"));
                                                }
                                            }
                                        }
                                        $pl->sendMessage($this->prefix . TextFormat::GREEN . " There's no winner.");
                                        $spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                                        $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());

                                        if ($pl->isOnline()) {
                                            $pl->teleport($spawn, 0, 0);
                                            $pl->getInventory()->clearAll();
                                        }
                                    }
                                    $this->plugin->getServer()->unloadLevel($levelArena);
                                    $this->deleteDirectory($this->plugin->getServer()->getDataPath() . "/worlds/" . $name);
                                    $this->copymap($this->plugin->getDataFolder() . "/maps/" . $name, $this->plugin->getServer()->getDataPath() . "/worlds/" . $name);
                                    $this->plugin->getServer()->loadLevel($name);
                                    foreach ($levelArena->getEntities() as $entity) {
                                        if (!$entity instanceof Player) {
                                            $entity->despawnFromAll();
                                        }
                                    }
                                }
                                $config->setNested("arenas." . $name . ".time", $time);
                                $config->save();
                            } else {
                                foreach ($playersArena as $pl) {
                                    $pl->sendPopup(TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::GOLD . "More Players needed" . TextFormat::GRAY . TextFormat::BOLD . " <");
                                    $this->plugin->getScheduler()->scheduleDelayedTask(new sendPopupAgain($this->plugin, TextFormat::GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::GOLD . "More Players needed" . TextFormat::GRAY . TextFormat::BOLD . " <", $pl), 10);
                                }
                                $config->setNested("arenas." . $name . ".start", 60);
                                $config->save();
                            }
                        }
                    }
                }
            }
        }
    }

    public function copymap($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($src . '/' . $file)) {
                    $this->copymap($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function deleteDirectory($dirPath) {
        if (is_dir($dirPath)) {
            $objects = scandir($dirPath);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir") {
                        $this->deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dirPath . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dirPath);
        }
    }

}
