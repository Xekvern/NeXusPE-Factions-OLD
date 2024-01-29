<?php

declare(strict_types=1);

namespace Xekvern\Core\Player\Combat;

use pocketmine\block\Redstone;
use pocketmine\entity\projectile\EnderPearl;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Player\Rank\Rank;
use Xekvern\Core\Translation\Translation;
use Xekvern\Core\Translation\TranslatonException;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use Xekvern\Core\Player\Combat\Boss\ArtificialIntelligence;
use Xekvern\Core\Player\Combat\Task\KillLogTask;
use Xekvern\Core\Player\Combat\Task\CombatTagTask;
use Xekvern\Core\Server\Item\Types\Drops;

class CombatEvents implements Listener
{

    /** @var int[] */
    public $godAppleCooldown = [];

    /** @var int[] */
    public $goldenAppleCooldown = [];

    /** @var int[] */
    public $enderPearlCooldown = [];

    /** @var Nexus */
    private $core;

    private const WHITELISTED = [
        "/mute",
        "/kick",
        "/unban",
        "/freeze",
        "/tempban",
        "/tempblock",
        "/f tl",
    ];

    /**
     * CombatEvents constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core)
    {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     * @param CommandEvent $event
     *
     * @throws TranslatonException
     */
    public function onPlayerCommandPreprocess(CommandEvent $event): void
    {
        $player = $event->getSender();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        if (!$player->isLoaded()) {
            return;
        }
        if ($player->getDataSession()->getRank()->getIdentifier() >= Rank::TRIAL_MODERATOR and $player->getDataSession()->getRank()->getIdentifier() <= Rank::OWNER) {
            return;
        }
        if (strpos($event->getCommand(), "/") !== 0) {
            return;
        }
        if (in_array(explode(" ", $event->getCommand())[0], self::WHITELISTED)) {
            return;
        }
        if ($player->isTagged()) {
            $player->sendMessage(Translation::getMessage("noPermissionCombatTag"));
            $event->cancel();
        }
    }

    /**
     * @priority LOW
     * @param PlayerItemConsumeEvent $event
     *
     * @throws TranslatonException
     */
    public function onPlayerItemConsume(PlayerItemConsumeEvent $event)
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($item->getTypeId() === VanillaItems::ENCHANTED_GOLDEN_APPLE()->getTypeId()) {
            if (isset($this->godAppleCooldown[$player->getUniqueId()->getBytes()])) {
                if ((time() - $this->godAppleCooldown[$player->getUniqueId()->getBytes()]) < 29) {
                    if (!$event->isCancelled()) {
                        $time = 29 - (time() - $this->godAppleCooldown[$player->getUniqueId()->getBytes()]);
                        $player->sendTip(TextFormat::BOLD . TextFormat::RED . "In Cooldown for $time §7seconds...");
                        $event->cancel();
                        return;
                    }
                }
                $this->godAppleCooldown[$player->getUniqueId()->getBytes()] = time();
                return;
            }
            $this->godAppleCooldown[$player->getUniqueId()->getBytes()] = time();
            return;
        }
        if ($item->getTypeId() === VanillaItems::GOLDEN_APPLE()->getTypeId()) {
            if (isset($this->goldenAppleCooldown[$player->getUniqueId()->getBytes()])) {
                if ((time() - $this->goldenAppleCooldown[$player->getUniqueId()->getBytes()]) < 2) {
                    if (!$event->isCancelled()) {
                        $time = 2 - (time() - $this->goldenAppleCooldown[$player->getUniqueId()->getBytes()]);
                        $player->sendTip(TextFormat::BOLD . TextFormat::RED . "In Cooldown for $time §7seconds...");
                        $event->cancel();
                        return;
                    }
                }
                $this->goldenAppleCooldown[$player->getUniqueId()->getBytes()] = time();
                return;
            }
            $this->goldenAppleCooldown[$player->getUniqueId()->getBytes()] = time();
            return;
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerRespawnEvent $event
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event)
    {
        $player = $event->getPlayer();
        $player->setSpawn($player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
        $world = $player->getServer()->getWorldManager()->getDefaultWorld();
        $spawn = $world->getSpawnLocation();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        $this->core->getScheduler()->scheduleDelayedTask(new class($player, $spawn) extends Task
        {

            /** @var NexusPlayer */
            private $player;

            /** @var Position */
            private $position;

            /**
             *  constructor.
             *
             * @param NexusPlayer $player
             * @param Position      $position
             */
            public function __construct(NexusPlayer $player, Position $position)
            {
                $this->player = $player;
                $this->position = $position;
            }

            /**
             */
            public function onRun(): void
            {
                if (!$this->player->isClosed()) {
                    $this->player->teleport($this->player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                }
            }
        }, 20 * 2);
    }

    /**
     * @priority LOW
     * @param PlayerDeathEvent $event
     *
     * @throws TranslatonException
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        $event->setXpDropAmount(0);
        $event->setDrops([(new Drops($player->getName(), $event->getDrops()))->getItemForm()]);
        $cause = $player->getLastDamageCause();
        $message = Translation::getMessage("death", [
            "name" => TextFormat::GREEN . $player->getName() . TextFormat::DARK_GRAY . " [" . TextFormat::DARK_RED . TextFormat::BOLD . $player->getDataSession()->getKills() . TextFormat::RESET . TextFormat::DARK_GRAY . "]",
        ]);
        if ($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            $entity = $cause->getEntity();
            $areaManager = $this->core->getServerManager()->getAreaHandler();
            $area = $areaManager->getAreaByPosition($killer->getPosition()->asPosition());
            $amount = mt_rand(1, 8);
            if($area !== null) {
                if($area->getPvpFlag() === true) { // x2 Power in WARZONES
                    $amount * 2;
                }
            }
            if ($killer instanceof NexusPlayer) {
                if ($killer->isLoaded()) {
                    $killer->getDataSession()->addToPower($amount);
                    $killer->getDataSession()->addXPProgress(100, 500);
                    $killer->getDataSession()->addKills();
                    $killer->sendTitle(TextFormat::BOLD . TextFormat::RED . "KILLED", TextFormat::RESET . TextFormat::GRAY . $player->getName(), 3, 20, 3);
                }
                $message = Translation::getMessage("deathByPlayer", [
                    "name" => TextFormat::GREEN . $player->getName() . TextFormat::DARK_GRAY . " [" . TextFormat::DARK_RED . TextFormat::BOLD . $player->getDataSession()->getKills() . TextFormat::RESET . TextFormat::DARK_GRAY . "]",
                    "killer" => TextFormat::RED . $killer->getName() . TextFormat::DARK_GRAY . " [" . TextFormat::DARK_RED . TextFormat::BOLD . $killer->getDataSession()->getKills() . TextFormat::RESET . TextFormat::DARK_GRAY . "]",
                    "item" => $killer->getInventory()->getItemInHand()->getName()
                ]);
            }
            if ($entity instanceof NexusPlayer) {
                if ($entity->isLoaded()) {
                    if($entity->getDataSession()->getPower() > 1) {
                        $entity->getDataSession()->subtractFromPower($amount);
                    }
                    $faction = $entity->getDataSession()->getFaction();
                    if($faction !== null) {
                        foreach($faction->getOnlineMembers() as $member) {
                            $member->sendMessage(Translation::RED . "Your faction has lost" . TextFormat::YELLOW . "{$amount} " . TextFormat::GRAY . "power due to the death of " . TextFormat::YELLOW . $player->getName());
                        }
                    }
                    
                }
            }
        }
        $player->setCombatTagged(false);
        $player->combatTag(false);
        $event->setDeathMessage($message);
        $this->core->getServer()->getAsyncPool()->increaseSize(2);
        $this->core->getServer()->getAsyncPool()->submitTaskToWorker(new KillLogTask("[Faction] " . date("[n/j/Y][G:i:s]", time()) . TextFormat::clean($message)), 1);
    }

    /**
     * @priority NORMAL
     * @param PlayerMoveEvent $event
     *
     * @throws TranslatonException
     */
    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $to = $event->getTo();
        $area = $this->core->getServerManager()->getAreaHandler()->getAreaByPosition($to);
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isInStaffMode() or $player->getGamemode() === GameMode::CREATIVE()) {
            return;
        }
        if($player->getWorld()->getFolderName() === "warzone") {
            $player->setFlying(false);
            $player->setAllowFlight(false);
        }
        if (!$player->isTagged()) {
            return;
        }
        if ($area === null) {
            return;
        }
        if ($area->getPvpFlag() === false) {
            $event->cancel();
            $player->sendMessage(Translation::getMessage("enterSafeZoneInCombat"));
            return;
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        if ($player->isTagged()) {
            $player->setHealth(0);
        }
    }

    /**
     * @priority HIGH
     * @param PlayerItemUseEvent $event
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event)
    {
        if ($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        $item = $event->getItem();
        if ($item->getTypeId() === ItemTypeIds::ENDER_PEARL) {
            if (!isset($this->enderPearlCooldown[$player->getUniqueId()->getBytes()])) {
                $this->enderPearlCooldown[$player->getUniqueId()->getBytes()] = time();
                return;
            }
            if (time() - $this->enderPearlCooldown[$player->getUniqueId()->getBytes()] < 10) {
                if (!$event->isCancelled()) {
                    $time = 10 - (time() - $this->enderPearlCooldown[$player->getUniqueId()->getBytes()]);
                    $time = TextFormat::RED . $time . TextFormat::GRAY;
                    $player->sendTip(TextFormat::BOLD . TextFormat::RED . "In Cooldown for $time §7seconds...");
                    $event->cancel();
                }
                return;
            }
            $this->enderPearlCooldown[$player->getUniqueId()->getBytes()] = time();
            return;
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $event
     *
     * @throws TranslationException
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }
        $entity = $event->getEntity();
        if ($entity instanceof NexusPlayer) {
            if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                $event->cancel();
                return;
            }
            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if (!$damager instanceof NexusPlayer) {
                    return;
                }
                if ($damager == null) {
                    return;
                }
                if ($damager->isInStaffMode()) {
                    $event->cancel();
                    return;
                }
                if ($damager->getWorld() === $damager->getServer()->getWorldManager()->getDefaultWorld()) {
                    return;
                }
                if($damager->getWorld()->getFolderName() === "bossarena" and (!$entity instanceof ArtificialIntelligence)) {
                    $event->cancel();
                    return;
                }
                if ($entity->isTagged()) {
                    $entity->combatTag(true);
                } else {
                    Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new CombatTagTask($entity), 20);
                    $entity->setCombatTagged(true);
                    $entity->setLastHit($damager->getName());
                }
                if ($damager->isTagged()) {
                    $damager->combatTag(true);
                    if($entity->getName() === $entity->getName()) { // Checks if you're not targetting the same opponent.
                        $damager->setLastHit($entity->getName());
                    }
                } else {
                    Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new CombatTagTask($damager), 20);
                    $damager->setCombatTagged(true);
                    $damager->setLastHit($entity->getName());
                }
                if ($entity->isFlying() === true or $entity->getAllowFlight() === true) {
                    $entity->setFlying(false);
                    $entity->setAllowFlight(false);
                    $entity->sendMessage(Translation::getMessage("flightToggle"));
                }
                if ($damager->isFlying() === true or $damager->getAllowFlight() === true) {
                    $damager->setFlying(false);
                    $damager->setAllowFlight(false);
                    $damager->sendMessage(Translation::getMessage("flightToggle"));
                }
            }
        }
    }

    /**
     * @priority HIGH
     * @param EntityTeleportEvent $event
     *
     * @throws TranslatonException
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof NexusPlayer) {
            return;
        }
        if (!$entity->isTagged()) {
            return;
        }
        $to = $event->getTo();
        if ($to->getWorld() === null) {
            return;
        }
        $area = $this->core->getServerManager()->getAreaHandler()->getAreaByPosition($to);
        if ($area === null) {
            return;
        }
        if ($area->getPvpFlag() === false) {
            $event->cancel();
            $entity->sendMessage(Translation::getMessage("enterSafeZoneInCombat"));
        }
    }

    /**
     * @priority HIGH
     * @param EntityRegainHealthEvent $event
     * 
     * @throws TranslationException
     * This is to fix players regaining health due to saturation.
     */
    public function onRegainHealth(EntityRegainHealthEvent $ev)
    {
        $reason = $ev->getRegainReason();
        if ($reason === EntityRegainHealthEvent::CAUSE_SATURATION) {
            $ev->cancel(true);
        }
    }

    /**
     * @priority HIGH
     * @param ProjectileLaunchEvent $event
     * 
     * @throws Exception
     * This is to change the throw force of the ender pearl.
     */
    public function onProjectileLaunch(ProjectileLaunchEvent $event): void
    {
        $entity = $event->getEntity();
        $owningEntity = $event->getEntity()->getOwningEntity();
        if (!$owningEntity instanceof NexusPlayer) return;
        if ($entity instanceof EnderPearl) {
            $entity->setMotion($owningEntity->getDirectionVector()->multiply(3.2));
        }
    }
    
}
