<?php

namespace Xekvern\Core\Player\Combat\Boss\Types;

use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\data\bedrock\EffectIds;
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
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\NoteSound;
use Xekvern\Core\Utils\Utils;

class CorruptedKing extends Boss {

    const BOSS_ID = 1;

    /** @var int */
    private $flingTick = 600;

    /** @var int */
    private $knockoutTick = 1400;

    /**
     * CorruptedKing constructor.
     *
     * @param Location $location
     * @param Skin $skin
     * @param CompoundTag $nbt
     *
     * @throws TranslatonException
     */
    public function __construct(Location $location, ?Skin $skin = null, ?CompoundTag $nbt = null) {
		parent::__construct($location, $skin, $nbt);
        $this->skin = $skin;
        $this->setMaxHealth(5000);
        $this->setHealth(5000);
        $this->setNametag(TextFormat::BOLD . TextFormat::YELLOW . "Corrupted King " . TextFormat::RESET . TextFormat::WHITE . $this->getHealth() . "/" . $this->getMaxHealth());
        $this->setScale(2);
        $this->attackDamage = 50;
        $this->speed = 0.8;
        $this->attackWait = 15;
        $this->regenerationRate = 0;
        $message = implode(TextFormat::RESET . "\n", [
            Utils::centerAlignText(TextFormat::BOLD . TextFormat::RED . "BOSS EVENT", 58),
            Utils::centerAlignText(TextFormat::BOLD . TextFormat::YELLOW . "Corrupted King" . TextFormat::RESET . TextFormat::WHITE . " has arrived..", 58),
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
        $this->setNametag(TextFormat::BOLD . TextFormat::YELLOW . "Corrupted King " . TextFormat::RESET . TextFormat::WHITE . $this->getHealth() . "/" . $this->getMaxHealth());
        $bb = $this->getBoundingBox();
        $bb = $bb->expandedCopy(16, 16, 16);
        $level = $this->getWorld();
        if($level === null) {
            return false;
        }
        if($this->flingTick === 100) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "Fling Attack", TextFormat::RESET . TextFormat::GRAY . "Coming in 5 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->flingTick === 80) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "Fling Attack", TextFormat::RESET . TextFormat::GRAY . "Coming in 4 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->flingTick === 60) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "Fling Attack", TextFormat::RESET . TextFormat::GRAY . "Coming in 3 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->flingTick === 40) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "Fling Attack", TextFormat::RESET . TextFormat::GRAY . "Coming in 2 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->flingTick === 20) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "Fling Attack", TextFormat::RESET . TextFormat::GRAY . "Coming in 1 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if(--$this->flingTick <= 0) {
            $this->flingTick = 500;
            foreach($level->getNearbyEntities($bb) as $entity) {
                /** @var NexusPlayer $entity */
                $entity->setMotion($entity->getMotion()->add(0, 2, 0));
            }
        }

        if($this->knockoutTick === 100) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::DARK_RED . "Knockout Attack", TextFormat::RESET . TextFormat::GRAY . "Coming in 5 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->knockoutTick === 80) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::DARK_RED . "Knockout Attack", TextFormat::RESET . TextFormat::GRAY . "Coming in 4 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->knockoutTick === 60) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::DARK_RED . "Knockout Attack", TextFormat::RESET . TextFormat::GRAY . "Coming in 3 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->knockoutTick === 40) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::DARK_RED . "Knockout Attack", TextFormat::RESET . TextFormat::GRAY . "Coming in 2 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if($this->knockoutTick === 20) {
            foreach($level->getNearbyEntities($bb) as $entity) {
                if($entity instanceof NexusPlayer) {
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::DARK_RED . "Knockout Attack", TextFormat::RESET . TextFormat::GRAY . "Coming in 1 seconds");
                    $entity->playErrorSound();
                }
            }
        }
        if(--$this->knockoutTick <= 0) {
            $this->knockoutTick = 1300;
            foreach($level->getNearbyEntities($bb) as $entity) {
                /** @var NexusPlayer $entity */
                if($entity instanceof NexusPlayer) {
                    $entity->setHealth($entity->getHealth() - mt_rand(3, 6));
                    $entity->knockBack(0, $entity->getPosition()->getX() - $this->getPosition()->getX(), $entity->getPosition()->getZ() - $this->getPosition()->getZ(), 1);
                    $entity->getEffects()->add(new EffectInstance(EffectIdMap::getInstance()->fromId(EffectIds::SLOWNESS), 12 * 20, 1));
                    $entity->getEffects()->add(new EffectInstance(EffectIdMap::getInstance()->fromId(EffectIds::BLINDNESS), 10 * 20, 1));
                    $entity->sendTitle(TextFormat::BOLD . TextFormat::RED . "KNOCKED OUT!", TextFormat::RESET . TextFormat::GRAY . "You have been knocked out.");
                }
            }
        }
        return parent::entityBaseTick($tickDiff);
    }

    public function attack(EntityDamageEvent $source): void {
        if($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if($damager instanceof NexusPlayer) {
                if((!$damager->getEffects()->has(VanillaEffects::POISON())) and mt_rand(1, 2) === mt_rand(1, 2)) {
                    $damager->getEffects()->add(new EffectInstance(VanillaEffects::POISON(), 100, 1));
                }
            }
        }
        parent::attack($source);
    }

    public function onDeath(): void {
        $newDamage = [];
        foreach($this->damages as $name => $damage) {
            $player = Server::getInstance()->getPlayerExact($name);
            if($player === null) {
                continue;
            }
            $newDamage[$name] = $damage;
        }
        arsort($newDamage);
        $top = array_slice($newDamage, 0, (count($newDamage) >= 5) ? 5 : count($newDamage));
        $newDamage = array_slice($newDamage, (count($newDamage) >= 5) ? 5 : count($newDamage));
        Server::getInstance()->broadcastMessage(TextFormat::GRAY . "The " . TextFormat::YELLOW . TextFormat::BOLD . "Corrupted King" . TextFormat::RESET . TextFormat::GRAY . " has been slain!");
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
            $keys = 7 - $i;
            Server::getInstance()->broadcastMessage(TextFormat::DARK_RED . TextFormat::BOLD . "$i. " . TextFormat::RESET . TextFormat::GRAY . $player->getName() . TextFormat::DARK_GRAY . " (" . TextFormat::WHITE . number_format((int)$damage) . TextFormat::RED . TextFormat::BOLD . " DMG" . TextFormat::RESET . TextFormat::DARK_GRAY . ") " . TextFormat::DARK_RED . "| " . TextFormat::RED . $keys . "x Boss Crate Keys");
            $player->getDataSession()->addKeys($crate, $keys);
            $player->getDataSession()->addXPProgress(mt_rand(5000, 10000));
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
            $player->getDataSession()->addXPProgress(mt_rand(500, 1000));
        }
    }
}