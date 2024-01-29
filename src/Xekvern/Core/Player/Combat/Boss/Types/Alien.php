<?php

namespace Xekvern\Core\Player\Combat\Boss\Types;

use libs\muqsit\arithmexp\Util;
use Xekvern\Core\Player\Combat\Boss\Boss;
use Xekvern\Core\Player\Combat\Boss\Event\DamageBossEvent;
use Xekvern\Core\Server\Crate\Crate;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\Translation;
use Xekvern\Core\Translation\TranslatonException;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Entity\Utils\IEManager;
use Xekvern\Core\Utils\Utils;

class Alien extends Boss {

    const BOSS_ID = 2;

    /** @var int */
    private $spawnMinionsTick = 800;

    /** @var Minion[] */
    private $minions = [];

    /**
     * Alien constructor.
     *
     * @param Location $location
     * @param Skin $skin
     * @param CompoundTag $nbt
     *
     * @throws TranslatonException
     */
    public function __construct(Location $location, ?Skin $skin = null, ?CompoundTag $nbt = null) {
		parent::__construct($location, $skin, $nbt);
        $this->setSkin($skin);
        $this->setMaxHealth(10000);
        $this->setHealth(7500);
        $this->setNametag(TextFormat::BOLD . TextFormat::GREEN . "Alien " . TextFormat::RESET . TextFormat::WHITE . $this->getHealth() . "/" . $this->getMaxHealth());
        $this->setScale(3);
        $this->attackDamage = 60;
        $this->speed = 1.2;
        $this->attackWait = 9;
        $this->regenerationRate = 8;
        $message = implode(TextFormat::RESET . "\n", [
            Utils::centerAlignText(TextFormat::BOLD . TextFormat::RED . "BOSS EVENT", 58),
            Utils::centerAlignText(TextFormat::BOLD . TextFormat::GREEN . "Alien" . TextFormat::RESET . TextFormat::WHITE . " has arrived..", 58),
            Utils::centerAlignText(TextFormat::YELLOW . "Use the command /boss to get there!", 58),
        ]);
        Server::getInstance()->broadcastMessage($message);
    }

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool {
        $this->setNametag(TextFormat::BOLD . TextFormat::GREEN . "Alien " . TextFormat::RESET . TextFormat::WHITE . $this->getHealth() . "/" . $this->getMaxHealth());
        $bb = $this->getBoundingBox();
        $bb = $bb->expandedCopy(16, 16, 16);
        $level = $this->getWorld();
        if($level === null) {
            return false;
        }
        if($this->spawnMinionsTick === 100) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "Minion Wave", TextFormat::RESET . TextFormat::GRAY . "Coming in 5 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->spawnMinionsTick === 80) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "Minion Wave", TextFormat::RESET . TextFormat::GRAY . "Coming in 4 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->spawnMinionsTick === 60) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "Minion Wave", TextFormat::RESET . TextFormat::GRAY . "Coming in 3 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->spawnMinionsTick === 40) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "Minion Wave", TextFormat::RESET . TextFormat::GRAY . "Coming in 2 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->spawnMinionsTick === 20) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "Minion Wave", TextFormat::RESET . TextFormat::GRAY . "Coming in 1 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if(--$this->spawnMinionsTick <= 0) {
            $this->spawnMinionsTick = 600;
            for($i = 1; $i <= 4; $i++) {
                $manager = new IEManager(Nexus::getInstance(), "minion.png");
                $skin = $manager->skin;
                $entity = new Minion(new Location($this->getPosition()->getX(), $this->getPosition()->getY(), $this->getPosition()->getZ(), $this->getWorld(), 0, 0), $skin);
                $entity->spawnToAll();
            }
        }
        return parent::entityBaseTick($tickDiff);
    }

    public function createBaseNBT(Vector3 $pos, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0): CompoundTag {
        return CompoundTag::create()
            ->setTag("Pos", new ListTag([
                new DoubleTag($pos->x),
                new DoubleTag($pos->y),
                new DoubleTag($pos->z)
            ]))
            ->setTag("Motion", new ListTag([
                new DoubleTag($motion !== null ? $motion->x : 0.0),
                new DoubleTag($motion !== null ? $motion->y : 0.0),
                new DoubleTag($motion !== null ? $motion->z : 0.0)
            ]))
            ->setTag("Rotation", new ListTag([
                new FloatTag($yaw),
                new FloatTag($pitch)
            ]));
    }

    public function attack(EntityDamageEvent $source): void {
        if($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if($damager instanceof NexusPlayer) {
                if((!$damager->getEffects()->has(VanillaEffects::WITHER())) and mt_rand(1, 2) === mt_rand(1, 2)) {
                    $damager->getEffects()->add(new EffectInstance(VanillaEffects::WITHER(), 100, 1));
                }
            }
        }
        parent::attack($source);
    }

    public function onDeath(): void {
        foreach($this->minions as $minion) {
            if($minion === null) {
                continue;
            }
            if($minion->isClosed() === false and $minion->isFlaggedForDespawn() === false) {
                $minion->flagForDespawn();
            }
        }
        $newDamage = [];
        foreach($this->damages as $name => $damage) {
            $player = Server::getInstance()->getPlayerExact($name);
            if($player === null) {
                continue;
            }
            $newDamage[$name] = $damage;
        }
        arsort($newDamage);
        $top = array_slice($newDamage, 0, intval((count($newDamage) >= 5) ? 5 : count($newDamage)));
        $newDamage = array_slice($newDamage, intval((count($newDamage) >= 5) ? 5 : count($newDamage)));
        Server::getInstance()->broadcastMessage(TextFormat::GRAY . "The " . TextFormat::GREEN . TextFormat::BOLD . "Alien" . TextFormat::RESET . TextFormat::GRAY . " has been slain!");
        Server::getInstance()->broadcastMessage(TextFormat::GRAY . "Top damagers:");
        $i = 0;
        $crate = Nexus::getInstance()->getServerManager()->getCrateHandler()->getCrate(Crate::BOSS);
        foreach($top as $name => $damage) {
            $i++;
            /** @var NexusPlayer $player */
            $player = Server::getInstance()->getPlayerExact($name);
            if($player === null) {
                continue;
            }
            $ev = new DamageBossEvent($player, (int)$damage);
            $ev->call();
            $keys = 9 - $i;
            Server::getInstance()->broadcastMessage(TextFormat::DARK_RED . TextFormat::BOLD . "$i. " . TextFormat::RESET . TextFormat::GRAY . $player->getName() . TextFormat::DARK_GRAY . " (" . TextFormat::WHITE . number_format((int)$damage) . TextFormat::RED . TextFormat::BOLD . " DMG" . TextFormat::RESET . TextFormat::DARK_GRAY . ") " . TextFormat::DARK_RED . "| " . TextFormat::RED . $keys . "x Boss Crate Keys");
            $player->getDataSession()->addKeys($crate, $keys);
        }
        foreach($newDamage as $name => $damage) {
            $i++;
            /** @var NexusPlayer $player */
            $player = Server::getInstance()->getPlayerExact($name);
            if($player === null) {
                continue;
            }
            $ev = new DamageBossEvent($player, $damage);
            $ev->call();
            $keys = 1;
            $player->sendMessage(TextFormat::DARK_RED . TextFormat::BOLD . "$i. " . TextFormat::RESET . TextFormat::GRAY . $player->getName() . TextFormat::DARK_GRAY . " (" . TextFormat::WHITE . number_format((int)$damage) . TextFormat::RED . TextFormat::BOLD . " DMG" . TextFormat::RESET . TextFormat::DARK_GRAY . ") " . TextFormat::DARK_RED . "| " . TextFormat::RED . $keys . "x Boss Crate Keys");
            $player->getDataSession()->addKeys($crate, $keys);
        }
    }
}