<?php

declare(strict_types=1);

namespace Xekvern\Core\Player;

use muqsit\invmenu\InvMenu;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerExperienceChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use Xekvern\Core\Command\Forms\StaffTeleportForm;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\Faction\Faction;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Player\Rank\Rank;
use Xekvern\Core\Provider\Event\PlayerLoadEvent;
use Xekvern\Core\Server\Item\ItemHandler;
use Xekvern\Core\Translation\Translation;

class PlayerEvents implements Listener
{

    /** @var Nexus */
    private $core;

    /** @var int[] */
    protected $times = [];
    /** @var int[] */
    protected $lastMoved = [];

    /**
     * PlayerEvents constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core)
    {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     * @param PlayerCreationEvent $event
     */
    public function onPlayerCreation(PlayerCreationEvent $event): void
    {
        $event->setPlayerClass(NexusPlayer::class);
    }

    /**
     * @param PlayerLoadEvent $event
     *
     * @throws NexusException
     */
    public function onPlayerLoad(PlayerLoadEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        $world = $this->core->getServer()->getWorldManager()->getDefaultWorld();
        if (date("l") !== "Friday") {
            $player->addFloatingText(new Position(121.2542, 107.9198, -49.7925, $world), "Mystery", "§c§lVoldemort is currently away\n \n§r§fUnfortunately, he won't be back until Friday\nHe will be happy to hear from you if you bring §l§3Souls§r§f when he is back!");
        }
        $info = implode(TextFormat::RESET . "\n", [
            TextFormat::BOLD . TextFormat::AQUA . "NeXus" . TextFormat::DARK_AQUA . "PE " . TextFormat::RESET . TextFormat::GRAY . "OP Factions",
            " ",
            TextFormat::WHITE . "Welcome to " . TextFormat::BOLD . TextFormat::YELLOW . "Season ". ItemHandler::getRomanNumber(Nexus::SEASON),
            TextFormat::WHITE . "of op factions.",
            " ",
            TextFormat::WHITE . "Start your amazing adventures by joining a faction or",
            TextFormat::WHITE . "form your own using " . TextFormat::BOLD . TextFormat::AQUA . "/f create",
            " ",
            TextFormat::WHITE . "Use your Once Kit by using the command " . TextFormat::BOLD . TextFormat::AQUA . "/kit",
            TextFormat::WHITE . "and do " . TextFormat::BOLD . TextFormat::AQUA . "/wild" . TextFormat::RESET . TextFormat::WHITE . " to get to the wilderness.",
        ]);
        $mapInfo = implode(TextFormat::RESET . "\n", [
            TextFormat::BOLD . TextFormat::AQUA . "Map Information",
            " ",
            TextFormat::AQUA . "Faction Size: " . TextFormat::WHITE . "20",
            TextFormat::AQUA . "Maximum Allies: " . TextFormat::WHITE . "1",
            TextFormat::AQUA . "Maximum Border: " . TextFormat::WHITE . "15,000 x 15,000",
        ]);
        $linksInfo = implode(TextFormat::RESET . "\n", [
            TextFormat::BOLD . TextFormat::AQUA . "Server Links",
            " ",
            TextFormat::AQUA . "Store: " . TextFormat::WHITE . "store.nexuspe.net",
            TextFormat::AQUA . "Vote: " . TextFormat::WHITE . "vote.nexuspe.net",
            TextFormat::AQUA . "Discord: " . TextFormat::WHITE . "discord.nexuspe.net",
        ]);
        $topRewards = implode(TextFormat::RESET . "\n", [
            TextFormat::BOLD . TextFormat::AQUA . "TOP FACTION REWARDS",
            " ",
            TextFormat::BOLD . TextFormat::GREEN . "Value",
            TextFormat::YELLOW . "#1 " . TextFormat::WHITE . "$150 Via PayPal & $200 Buycraft",
            TextFormat::YELLOW . "#2 " . TextFormat::WHITE . "$80 Via PayPal & $150 Buycraft",
            TextFormat::YELLOW . "#3 " . TextFormat::WHITE . "$20 Via PayPal & $50 Buycraft",
            " ",
            TextFormat::BOLD . TextFormat::GREEN . "Strength",
            TextFormat::YELLOW . "#1 " . TextFormat::WHITE . "$75 Via PayPal & $100 Buycraft",
            TextFormat::YELLOW . "#2 " . TextFormat::WHITE . "$30 Via PayPal & $50 Buycraft",
            TextFormat::YELLOW . "#3 " . TextFormat::WHITE . "$15 Via PayPal &$25 Buycraft",
            " ",
            TextFormat::RESET . TextFormat::GRAY . "Rewards are handed out at discord.nexuspe.net",
        ]);
        $player->addFloatingText(new Position(177.8649, 117.2425, 7.4778, $world), "Info", $info);
        $player->addFloatingText(new Position(175.0124, 118.9383, 16.9973, $world), "Rewards", $topRewards);
        $player->addFloatingText(new Position(160.9708, 111.3988, 11.0021, $world), "MapInfo", $mapInfo);
        //$player->addFloatingText(new Position(160.9708, 111.3988, 11.0021, $world), "LinksInfo", $linksInfo);
        //$player->addFloatingText(new Position(-5.522, 164.9362, -3.5183, $world), "PVP", TextFormat::BOLD . TextFormat::RED . "PVP" . TextFormat::RESET . TextFormat::GRAY . "\nPvP is enabled below.");
    }

    /**
     * @priority HIGHEST
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $event->setJoinMessage("");
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        //$player->teleport(Nexus::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
        $player->setFlying(false);
        $player->setAllowFlight(false);
        if ($player->getXpManager()->getCurrentTotalXp() > 0x7fffffff) {
            $player->getXpManager()->setCurrentTotalXp(0x7fffffff);
        }
        if ($player->getXpManager()->getCurrentTotalXp() < -0x80000000) {
            $player->getXpManager()->setCurrentTotalXp(0);
        }
        $os = $player->getPlayerInfo()->getExtraData()["DeviceOS"];
        $name = match ($os) {
            1 => "Android",
            2 => "iOS",
            3 => "Mac",
            4 => "Amazon",
            5 => "GearVR",
            6 => "Hololens",
            7 => "Windows 10",
            8 => "Windows 32",
            9 => "Dedicated",
            10 => "TVOS",
            11 => "PS4",
            12 => "Nintendo",
            13 => "Xbox",
            14 => "Windows Phone",
            default => "Unknown",
        };
        $player->setOS($name);
        $hp = round($player->getHealth(), 1);
        $player->setScoreTag(TextFormat::WHITE . $hp . TextFormat::RED . TextFormat::BOLD . " HP" . TextFormat::RESET . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . $player->getOS());
        $player->sendTitle(TextFormat::BOLD . TextFormat::GREEN . "Loading...", TextFormat::GRAY . "Fetching your data right now!", 20, 600, 20);
        $this->core->getMySQLProvider()->getLoadQueue()->addToQueue($player);
    }

    /**
     * @priority NORMAL
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $event->setQuitMessage("");
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        $uuid = $player->getUniqueId()->toString();
        if (isset($this->lastMoved[$uuid])) {
            $diff = time() - $this->lastMoved[$uuid];
            if (time() - $this->lastMoved[$uuid] >= 300) {
                $this->times[$uuid] = ($this->times[$uuid] ?? time()) + $diff;
            }
            unset($this->lastMoved[$uuid]);
        }
        if (isset($this->times[$uuid])) {
            $old = $player->getDataSession()->getOnlineTime();
            $player->getDataSession()->setOnlineTime($old + (time() - $this->times[$uuid]));
            unset($this->times[$uuid]);
        }
        if ($player->isLoaded()) {
            $session = $player->getDataSession();
            $session->saveData();
            $this->core->getSessionManager()->setCESession($player->getCESession());
        }
        if ($player->isInStaffMode()) {
            foreach ($player->getStaffModeInventory() as $item) {
                $player->getDataSession()->addToInbox($item);
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerExperienceChangeEvent $event
     */
    public function onPlayerExperienceChange(PlayerExperienceChangeEvent $event): void
    {
        $player = $event->getEntity();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        if ($player->getXpManager()->getCurrentTotalXp() > 0x7fffffff or $player->getXpManager()->getCurrentTotalXp() < -0x80000000) {
            $event->cancel();
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        $world = $player->getWorld();
        $to = $event->getTo();
        $from = $event->getFrom();
        $uuid = $player->getUniqueId()->toString();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            $event->cancel();
            return;
        }
        if ($to->getYaw() !== $from->getYaw() or $to->getPitch() !== $from->getPitch()) {
            if (isset($this->lastMoved[$uuid]) and isset($this->times[$uuid])) {
                $diff = (time() - $this->lastMoved[$uuid]) - 300;
                if ($diff >= 300) {
                    $this->times[$uuid] = $this->times[$uuid] + $diff;
                } else {
                    $this->lastMoved[$uuid] = time();
                }
            }
            $this->lastMoved[$uuid] = time();
        }
        $x = abs($player->getPosition()->getFloorX());
        $y = abs($player->getPosition()->getFloorY());
        $z = abs($player->getPosition()->getFloorZ());
        $message = Translation::getMessage("maxBorder");
        if (($world->getDisplayName() === Faction::CLAIM_WORLD) && // Wilderness
            ($x >= Nexus::BORDER
            or $z >= Nexus::BORDER
            or $x >= Nexus::BORDER and abs($z) >= Nexus::BORDER)
        ) {
            $player->sendMessage($message);
            $event->cancel();
            return;
        } 
        $max = 800;
        if (($world->getFolderName() === "warzone") && // Warzone
            ($x >= $max
            or $z >= $max
            or $x >= $max and abs($z) >= $max)
        ) {
            $player->sendMessage(Translation::RED . "You have reached the max border of this warzone.");
            $event->cancel();
            return;
        }
    }

    /**
     * @priority LOW
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        if ($player->isInStaffMode()) {
            $event->cancel();
            return;
        }
        if ($player->hasPermission(DefaultPermissionNames::GROUP_OPERATOR)) {
            return;
        }
        $block = $event->getTransaction();
        $world = $player->getWorld();
        if ($world === null) {
            return;
        }
        if ($world->getDisplayName() !== Faction::CLAIM_WORLD) {
            $event->cancel();
            return;
        }
        $x = abs($player->getPosition()->getFloorX());
        $z = abs($player->getPosition()->getFloorZ());
        if ($x >= Nexus::BORDER or $z >= Nexus::BORDER) {
            $event->cancel();
       }
    }

    /**
     * @priority LOW
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        if ($player->isInStaffMode()) {
            $event->cancel();
            return;
        }
        if ($player->hasPermission(DefaultPermissionNames::GROUP_OPERATOR)) {
            return;
        }
        $block = $event->getBlock();
        $world = $player->getWorld();
        if ($world === null) {
            return;
        }
        if ($world->getDisplayName() !== Faction::CLAIM_WORLD) {
            $event->cancel();
            return;
        }
        $x = abs($player->getPosition()->getFloorX());
        $z = abs($player->getPosition()->getFloorZ());
        if ($x >= Nexus::BORDER or $z >= Nexus::BORDER) {
            $event->cancel();
        }
    }

    /**
     * @param PlayerExhaustEvent $event
     */
    public function onPlayerExhaust(PlayerExhaustEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        if (!$player->isTagged()) {
            $event->cancel();
            return;
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     */
    public function onInvTransaction(InventoryTransactionEvent $event): void
    {
        $player = $event->getTransaction()->getSource();
        if ($player instanceof NexusPlayer) {
            if ($player->isInStaffMode()) {
                $event->cancel();
                return;
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $event
     */
    public function onItemDrop(PlayerDropItemEvent $event): void
    {
        $player = $event->getPlayer();
        if ($player instanceof NexusPlayer) {
            if ($player->isInStaffMode()) {
                $event->cancel();
                return;
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     * @handleCancelled 
     */
    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($player instanceof NexusPlayer) {
            if ($player->isInStaffMode()) {
                if ($block->getTypeId() === BlockTypeIds::CHEST) {
                    $event->cancel();
                    return;
                }
            }
        }
    }

    /**
     * @param EntityItemPickupEvent $event
     */
    public function onItemPickUp(EntityItemPickupEvent $event)
    {
        $player = $event->getEntity();
        if ($player instanceof NexusPlayer) {
            if ($player->isInStaffMode()) {
                $event->cancel();
                return;
            }
        }
    }

    /** 
     * @param BlockPlaceEvent $event
     */
    public function onPlaceHead(BlockPlaceEvent $event): void 
    {
        if($event->getItem()->getTypeId() == VanillaBlocks::MOB_HEAD()->getTypeId()) {
            $event->cancel();
        }
    }

    /**
     * @param CommandEvent $event
     */
    public function onCommandPreProcess(CommandEvent $event): void
    {
        $player = $event->getSender();
        if ($player instanceof NexusPlayer) {
            if (substr($event->getCommand(), 0, 1) === "/") {
                $command = substr(explode(" ", $event->getCommand())[0], 1);
                if (
                    strtolower($command) === "tp" or
                    strtolower($command) === "teleport" or
                    strtolower($command) === "teleport" or
                    strtolower($command) === "f"
                ) {
                    if ($player->isInStaffMode()) {
                        $player->sendMessage(Translation::RED . "You can not use this while in staff mode!");
                        $event->cancel();
                        return;
                    }
                }
            }
        }
    }

    /**
     * @param PlayerItemUseEvent $event
     * @handleCancelled 
     */
    public function onItemUse(PlayerItemUseEvent $event)
    {
        $player = $event->getPlayer();
        if ($player instanceof NexusPlayer) {
            if ($player->isInStaffMode()) {
                $item = $event->getItem();
                switch ($item->getTypeId()) {
                    case VanillaBlocks::ICE()->getTypeId():
                        $player->sendMessage(Translation::ORANGE . "You must tap a player with this item to freeze/unfreeze them!");
                        break;
                    case ItemTypeIds::COMPASS:
                        $player->sendForm(new StaffTeleportForm($player));
                        break;
                    case ItemTypeIds::CLOCK:
                        $event->cancel();
                        $randomPlayer = $this->core->getServer()->getOnlinePlayers()[array_rand($this->core->getServer()->getOnlinePlayers())];
                        if ($randomPlayer instanceof NexusPlayer) {
                            $player->teleport($randomPlayer->getPosition()->asPosition());
                        }
                        break;
                    case VanillaBlocks::CHEST()->getTypeId():
                        $player->sendMessage(Translation::ORANGE . "Hit a player to see it's inventory.");
                        break;
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $event
     * @handleCancelled
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof NexusPlayer and $event->getCause() === EntityDamageEvent::CAUSE_FALL and $entity->justLoaded()) {
            $event->cancel();
        }
        if ($entity instanceof NexusPlayer) {
            if ($entity->isInStaffMode()) {
                $event->cancel();
                return;
            }
            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if ($damager instanceof NexusPlayer) {
                    if ($damager->isInStaffMode()) {
                        $event->cancel();
                        switch ($damager->getInventory()->getItemInHand()->getTypeId()) {
                            case VanillaBlocks::ICE()->asItem()->getTypeId():
                                $entity->setNoClientPredictions(!$entity->hasNoClientPredictions());
                                $damager->sendMessage($entity->hasNoClientPredictions() ? Translation::GREEN . "You have frozen " . TextFormat::YELLOW . $entity->getName() . "" : Translation::ORANGE . "You have no longer set " . TextFormat::YELLOW . $entity->getName() . TextFormat::GRAY . " frozen!");
                                break;
                            case VanillaBlocks::CHEST()->asItem()->getTypeId():
                                $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
                                $menu->setName($entity->getName() . " Inventory");
                                $menu->setListener($menu->readonly());
                                foreach ($entity->getInventory()->getContents() as $item) {
                                    $menu->getInventory()->addItem($item);
                                }
                                $menu->send($damager);
                                break;
                        }
                    }
                }
            }
        }
    }
}
