<?php

namespace TNTRun;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;
use pocketmine\tile\Sign;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;

class Main extends PluginBase implements Listener {

    public $mode = 0;
    public $signregister = false;
    public $levelname = "";

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "/maps");
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getLogger()->info("§aTNTRun by §PiyushBest §aloaded.");
        $this->prefix = $this->getConfig()->get("prefix");
        $this->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 20);
        foreach($this->getConfig()->getNested("arenas") as $a) {
            if (!$this->getServer()->getLevelByName($a["name"]) instanceof Level) {
                $this->deleteDirectory($this->getServer()->getDataPath() . "/worlds/" . $a["name"]);
                $this->copymap($this->getDataFolder() . "/maps/" . $a["name"], $this->getServer()->getDataPath() . "/worlds/" . $a["name"]);
                $this->getServer()->loadLevel($a["name"]);
            } else {
                $this->getServer()->unloadLevel($this->getServer()->getLevelByName($a["name"]));
                $this->deleteDirectory($this->getServer()->getDataPath() . "/worlds/" . $a["name"]);
                $this->copymap($this->getDataFolder() . "/maps/" . $a["name"], $this->getServer()->getDataPath() . "/worlds/" . $a["name"]);
                $this->getServer()->loadLevel($a["name"]);
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        if (!$event->getPlayer()->isOp()) {
            $event->setCancelled(true);
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        if (!$event->getPlayer()->isOp()) {
            $event->setCancelled(true);
        }
    }

    public function onDamage(EntityDamageEvent $event) {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            if ($this->getConfig()->getNested("arenas." . $player->getLevel()->getFolderName() . ".name") === $player->getLevel()->getFolderName()) {
                $event->setCancelled(true);
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        if ($this->getConfig()->getNested("arenas." . $player->getLevel()->getFolderName() . ".name") === $player->getLevel()->getFolderName()) {
            foreach ($player->getLevel()->getPlayers() as $pl) {
                $pl->sendMessage(TextFormat::DARK_GRAY . TextFormat::BOLD . "[" . TextFormat::DARK_RED . "-" . TextFormat::DARK_GRAY . "] " . TextFormat::RESET . TextFormat::GOLD . $player->getName() . " left the game.");
            }
            $players = $this->getConfig()->getNested("arenas." . $player->getLevel()->getFolderName() . ".players");
            $current = 0;
            foreach ($players as $name) {
                if ($name === strtolower($player->getName())) {
                    unset($players[$current]);
                }
                $current++;
            }
            $this->getConfig()->setNested("arenas." . $player->getLevel()->getFolderName() . ".players", $players);
            $this->getConfig()->save();
            $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
            $player->teleport($spawn, 0, 0);
        }
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $player->getInventory()->clearAll();
        $player->removeAllEffects();
        $this->getScheduler()->scheduleDelayedTask(new sendBack($this, $player), 2);
        $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
        $player->setSpawn(new Position($spawn->getX(), $spawn->getY(), $spawn->getZ(), $this->getServer()->getDefaultLevel()));
    }

    public function onRespawn(PlayerRespawnEvent $event) {
        $player = $event->getPlayer();
        $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
        $player->setSpawn(new Position($spawn->getX(), $spawn->getY(), $spawn->getZ(), $this->getServer()->getDefaultLevel()));
    }

    public function onMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        if ($this->getConfig()->getNested("arenas." . $player->getLevel()->getFolderName() . ".name") === $player->getLevel()->getFolderName()) {
            if ($player->getY() <= 1 && count($player->getLevel()->getPlayers()) > 0) {
                foreach ($player->getLevel()->getPlayers() as $pl) {
                    $pl->sendMessage(TextFormat::DARK_GRAY . TextFormat::BOLD . "> " . TextFormat::RESET . TextFormat::GOLD . $player->getName() . " died.");
                }
                $players = $this->getConfig()->getNested("arenas." . $player->getLevel()->getFolderName() . ".players");
                $current = 0;
                foreach ($players as $name) {
                    if ($name === strtolower($player->getName())) {
                        unset($players[$current]);
                    }
                    $current++;
                }
                $this->getConfig()->setNested("arenas." . $player->getLevel()->getFolderName() . ".players", $players);
                $this->getConfig()->save();
                $bug_fix = 0;
                $bug_fix2 = 0;
                foreach ($player->getLevel()->getPlayers() as $pl) {
                    if ($pl->getY() <= 1)
                        $bug_fix++;
                }
                if ($bug_fix === count($player->getLevel()->getPlayers())) {
                    foreach ($player->getLevel()->getPlayers() as $pl) {
                        if ($bug_fix2 != $bug_fix) {
                            $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
                            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
                            $pl->teleport($spawn, 0, 0);
                            $bug_fix2++;
                        } else {
                            $spawn = $pl->getLevel()->getSafeSpawn();
                            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
                            $pl->teleport($spawn, 0, 0);
                        }
                    }
                } else {
                    $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
                    $this->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
                    $player->teleport($spawn, 0, 0);
                }
            }
            if (($this->getConfig()->getNested("arenas." . $player->getLevel()->getFolderName() . ".time")) <= ($this->getConfig()->get("time") - 10)) {
                if ($this->getConfig()->getNested("arenas." . $player->getLevel()->getFolderName() . ".start") === 0) {
                    $block = $player->getLevel()->getBlock($player->floor()->subtract(0, 1));
                    $player->getLevel()->setBlock($block, Block::get(Block::AIR));
                }
            }
        }
    }

    public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $args) : bool{
        switch ($cmd->getName()) {
            case "tr":
                if (!$sender instanceof Player) {
                    $sender->sendMessage(TextFormat::RED . "You have to be a Player to perform this command!");
                    return true;
                }
                if (!isset($args[0])) {
                    $sender->sendMessage(TextFormat::RED . "Usage: " . $cmd->getUsage());
                    return true;
                }
                if (strtolower($args[0]) === "addarena") { // /tr addarena <name> <maxPlayers> <minPlayers>
                    if (!(isset($args[1])) || !(isset($args[2])) || !(isset($args[3]))) {
                        $sender->sendMessage("Usage: /tr addarena <name> <maxPlayers> <minPlayers>");
                        return true;
                    }
                    if (file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1])) {
                        $this->mode = 1;
                        $min = $args[3];
                        $this->getServer()->loadLevel($args[1]);
                        $this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getZ());
                        $sender->sendMessage(TextFormat::GRAY . "Tap the spawn now!");
                        $level = $this->getServer()->getLevelByName($args[1]);
                        $sender->teleport($level->getSafeSpawn());
                        $this->getConfig()->setNested("arenas." . $args[1] . ".name", $args[1]);
                        $this->getConfig()->setNested("arenas." . $args[1] . ".time", $this->getConfig()->get("time"));
                        $this->getConfig()->setNested("arenas." . $args[1] . ".start", 60);
                        $this->getConfig()->setNested("arenas." . $args[1] . ".players", array());
                        $this->getConfig()->setNested("arenas." . $args[1] . ".maxPlayers", (int) $args[2]);
                        $this->getConfig()->setNested("arenas." . $args[1] . ".minPlayers", (int) $min);
                        $this->getConfig()->save();
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::RED . "$args[1] is not a world!");
                        return true;
                    }
                } elseif (strtolower($args[0]) === "regsign") { // /tr regsign <name>
                    if (!isset($args[1])) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /tr regsign <name>");
                        return true;
                    }
                    if (file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1])) {
                        $this->signregister = true;
                        $this->levelname = $args[1];
                        $sender->sendMessage(TextFormat::GRAY . "Tap a sign now!");
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::GRAY . "$args[1] is not a world!");
                        return true;
                    }
                }
                return true;
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $sign = $player->getLevel()->getTile($block);
        if ($sign instanceof Sign) {
            if ($this->signregister === false) {
                $text = $sign->getText();
                if ($text[0] === $this->prefix) {
                    $level = TextFormat::clean($text[1]);
                    $this->getServer()->loadLevel($level);
                    $lvl = $this->getServer()->getLevelByName($level);
                    if ($text[2] != "§cINGAME") {
                        if (count($lvl->getPlayers()) != (int) $this->getConfig()->getNested("arenas." . $level . ".maxPlayers")) {
                            $array = $this->getConfig()->getNested("arenas." . $level . ".players");
                            array_push($array, strtolower($player->getName()));
                            $this->getConfig()->setNested("arenas." . $level . ".players", $array);
                            $this->getConfig()->save();
                            $spawn = $lvl->getSafeSpawn();
                            $player->teleport(new Position($spawn->getX(), $spawn->getY(), $spawn->getZ(), $lvl));
                            $s = $this->getServer()->getDefaultLevel()->getSafeSpawn();
                            $player->setSpawn(new Position($s->getX(), $s->getY(), $s->getZ(), $this->getServer()->getDefaultLevel()));
                            foreach ($lvl->getPlayers() as $pl) {
                                $pl->sendMessage(TextFormat::DARK_GRAY . TextFormat::BOLD . "[" . TextFormat::GREEN . "+" . TextFormat::DARK_GRAY . "] " . TextFormat::RESET . TextFormat::GOLD . $player->getName() . " joined the game.");
                            }
                        } else {
                            $player->sendMessage(TextFormat::GRAY . "This game is full.");
                        }
                    } else {
                        $player->sendMessage(TextFormat::GRAY . "This game already started.");
                    }
                }
            } else {
                $sign->setText($this->prefix, TextFormat::AQUA . $this->levelname, "0/" . $this->getConfig()->getNested("arenas." . $this->levelname . ".maxPlayers"));
                $this->signregister = false;
                $this->levelname = "";
                $player->sendMessage(TextFormat::GRAY . "Sign for $this->levelname set.");
            }
        }
        if ($this->mode != 0) {
            $this->getConfig()->setNested("arenas." . $player->getLevel()->getFolderName() . ".spawn", array("x" => $block->getX(), "y" => $block->getY() + 2, "z" => $block->getZ()));
            $this->getConfig()->save();
            $player->sendMessage(TextFormat::GRAY . "Successfully set the Spawn. Now set the sign using /tr regsign <name>.");
            $this->copymap($this->getServer()->getDataPath() . "/worlds/" . $player->getLevel()->getFolderName(), $this->getDataFolder() . "/maps/" . $player->getLevel()->getFolderName());
            $player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn(), 0, 0);
            $this->mode = 0;
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
