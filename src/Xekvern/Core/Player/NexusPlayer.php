<?php

declare(strict_types=1);

namespace Xekvern\Core\Player;

use Xekvern\Core\Provider\Task\LoadScreenTask;
use Xekvern\Core\Player\Rank\Rank;
use Xekvern\Core\Session\Types\CESession;
use Xekvern\Core\Session\Types\DataSession;
use Xekvern\Core\Utils\Utils;
use muqsit\invmenu\InvMenu;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\form\Form;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\permission\Permission;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\world\sound\XpLevelUpSound;
use Xekvern\Core\Nexus;
use Xekvern\Core\NexusException;
use Xekvern\Core\Player\Faction\Faction;
use Xekvern\Core\Server\Update\Utils\Scoreboard;
use Xekvern\Core\Utils\FloatingTextParticle;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use Xekvern\Core\Player\Faction\FactionHandler;
use Xekvern\Core\Server\Item\ItemHandler;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use Xekvern\Core\Server\Update\Utils\BossBar;

class NexusPlayer extends Player
{

    const PUBLIC = 0;
    const FACTION = 1;
    const ALLY = 2;
    const STAFF = 3;

    /** @var int*/
    public $cps = 0;

    /** @var int */
    private $loadTime = 0;

    /** @var bool */
    private $transferred = false;

    /** @var null|CommandSender */
    private $lastTalked = null;

    /** @var null|string */
    private $lastHit = null;

    /** @var bool */
    private $vanish = false;

    /** @var bool */
    private $disguise = false;

    /** @var null|Rank */
    private $disguiseRank = null;

    /** @var Nexus */
    private $core;

    /** @var bool */
    private $runningCrateAnimation = false;

    /** @var bool */
    private $autoSell = false;

    /** @var bool */
    private $voteChecking = false;

    /** @var bool */
    private $voted = false;

    /** @var bool */
    private $teleporting = false;

    /** @var bool */
    private $frozen = false;

    /** @var bool */
    private $togglePrivateMessage = true;
    
    /** @var int */
    private $chatMode = self::PUBLIC;

    /** @var int */
    private $combatTag = 0;

    /** @var bool */
    private $combatTagged = false;

    /** @var Scoreboard */
    private $scoreboard;

    /** @var BossBar */
    private $bossBar;

    /** @var bool */
    private $fMapHud = false;

    /** @var FloatingTextParticle[] */
    private $floatingTexts = [];

    /** @var int[] */
    private $teleportRequests = [];

    /** @var string */
    private $os = "Unknown";

    /** @var null|DataSession */
    private $dataSession = null;

    /** @var null|CESession */
    private $ceSession = null;

    /** @var int */
    private $lastRepair = 0;

    /** @var int[] */
    private $itemCooldowns = [];
    
    /** @var bool $staffMode */
    protected $staffMode = false;

    /** @var Item[] $staffModeInventory */
    protected $staffModeInventory = [];

    /**
     * @param Nexus $core
     */
    public function load(Nexus $core): void
    {
        $this->core = $core;
        $this->loadTime = time();
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new LoadScreenTask($this), 1);
        $this->scoreboard = new Scoreboard($this);
        $this->bossBar = new BossBar($this);
        $this->dataSession = new DataSession($this);
        $this->ceSession = $core->getSessionManager()->getCESession($this);
    }

    /**
     * @return bool
     */
    public function justLoaded(): bool
    {
        return (time() - $this->loadTime) <= 30;
    }

    /**
     * @param CommandSender $sender
     */
    public function setLastTalked(CommandSender $sender): void
    {
        $this->lastTalked = $sender;
    }

    /**
     * @return CommandSender|null
     */
    public function getLastTalked(): ?CommandSender
    {
        if ($this->lastTalked === null) {
            return null;
        }
        if (!$this->lastTalked instanceof NexusPlayer) {
            return null;
        }
        return $this->lastTalked->isOnline() ? $this->lastTalked : null;
    }

    /**
     * @param string $name
     */
    public function setLastHit(?string $name): void
    {
        $this->lastHit = $name;
    }

    /**
     * @return string|null
     */
    public function getLastHit(): ?string
    {
        if ($this->lastHit === null) {
            return "None";
        }
        return $this->lastHit;
    }

    /**
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->ceSession !== null and $this->dataSession !== null and $this->dataSession->isLoaded();
    }

    /**
     * @return DataSession|null
     */
    public function getDataSession(): ?DataSession
    {
        return $this->dataSession;
    }

    /**
     * @return CESession|null
     */
    public function getCESession(): ?CESession
    {
        return $this->ceSession;
    }

    /**
     * @param bool $value
     */
    public function vanish(bool $value = true): void
    {
        if ($value) {
            /** @var NexusPlayer $player */
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                if ($player->isLoaded() === false) {
                    continue;
                }
                if ($player->getDataSession()->getRank()->getIdentifier() >= Rank::TRIAL_MODERATOR and $player->getDataSession()->getRank()->getIdentifier() <= Rank::OWNER) {
                    continue;
                }
                $player->hidePlayer($this);
            }
        } else {
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                if (!$player->canSee($this)) {
                    $player->showPlayer($this);
                }
            }
        }
        $this->vanish = $value;
    }

    /**
     * @return bool
     */
    public function hasVanished(): bool
    {
        return $this->vanish;
    }

    /**
     * @return Nexus
     */
    public function getCore(): Nexus
    {
        return $this->core;
    }

    /**
     * @return Scoreboard
     */
    public function getScoreboard(): Scoreboard
    {
        return $this->scoreboard;
    }

    /**
     * @return BossBar
     */
    public function getBossBar(): BossBar {
        return $this->bossBar;
    }

    public function initializeScoreboard(): void
    {
        $this->scoreboard->spawn(Nexus::SERVER_NAME);
        $this->scoreboard->setScoreLine(1, " ");
        $this->scoreboard->setScoreLine(2, " " . $this->dataSession->getRank()->getColoredName() . TextFormat::RESET . TextFormat::WHITE . " " . $this->getName());
        $this->scoreboard->setScoreLine(3, " ");
        $this->scoreboard->setScoreLine(4, TextFormat::BOLD . TextFormat::AQUA . " Stats");
        $this->scoreboard->setScoreLine(5, TextFormat::BOLD . TextFormat::AQUA . " |" . TextFormat::RESET . TextFormat::WHITE . " Balance: " . TextFormat::RESET . TextFormat::YELLOW . "$" . $this->dataSession->getBalance());
        $this->scoreboard->setScoreLine(6, TextFormat::BOLD . TextFormat::AQUA . " |" . TextFormat::RESET . TextFormat::WHITE . " Power: " . TextFormat::RESET . TextFormat::YELLOW . $this->dataSession->getPower());
        $this->scoreboard->setScoreLine(7, TextFormat::BOLD . TextFormat::AQUA . " |" . TextFormat::RESET . TextFormat::WHITE . " Level: " . TextFormat::RESET . TextFormat::YELLOW . $this->dataSession->getCurrentLevel());
        $this->scoreboard->setScoreLine(8, " ");
        $this->scoreboard->setScoreLine(9, TextFormat::RESET . TextFormat::ITALIC . TextFormat::LIGHT_PURPLE . " store.nexuspe.net");
        $this->scoreboard->setScoreLine(10, TextFormat::RESET . TextFormat::ITALIC . TextFormat::LIGHT_PURPLE . " vote.nexuspe.net");
    }

    /**
     * @return bool
     */
    public function isUsingFMapHUD(): bool {
        return $this->fMapHud;
    }

    /**
     * @throws UtilsException
     */
    
    public function toggleFMapHUD(): void {
        $this->fMapHud = !$this->fMapHud;
        if($this->fMapHud === false) {
            $this->scoreboard->setScoreLine(4, TextFormat::BOLD . TextFormat::AQUA . " Stats");
            $this->scoreboard->setScoreLine(5, TextFormat::BOLD . TextFormat::AQUA . " |" . TextFormat::RESET . TextFormat::WHITE . " Balance: " . TextFormat::RESET . TextFormat::YELLOW . "$" . $this->dataSession->getBalance());
            $this->scoreboard->setScoreLine(6, TextFormat::BOLD . TextFormat::AQUA . " |" . TextFormat::RESET . TextFormat::WHITE . " Power: " . TextFormat::RESET . TextFormat::YELLOW . $this->dataSession->getPower());
            $this->scoreboard->setScoreLine(7, TextFormat::BOLD . TextFormat::AQUA . " |" . TextFormat::RESET . TextFormat::WHITE . " Level: " . TextFormat::RESET . TextFormat::YELLOW . $this->dataSession->getCurrentLevel());
            $this->scoreboard->setScoreLine(8, " ");
            $this->scoreboard->setScoreLine(9, TextFormat::RESET . TextFormat::ITALIC . TextFormat::LIGHT_PURPLE . " store.nexuspe.net");
            $this->scoreboard->setScoreLine(10, TextFormat::RESET . TextFormat::ITALIC . TextFormat::LIGHT_PURPLE . " vote.nexuspe.net");
            if($this->scoreboard->getLine(11) !== null) {
                $this->scoreboard->removeLine(11);
            }
            if($this->scoreboard->getLine(12) !== null) {
                $this->scoreboard->removeLine(12);
            }
            if($this->scoreboard->getLine(13) !== null) {
                $this->scoreboard->removeLine(13);
            }
            if($this->scoreboard->getLine(14) !== null) {
                $this->scoreboard->removeLine(14);
            }
            if($this->scoreboard->getLine(15) !== null) {
                $this->scoreboard->removeLine(15);
            }
        }
        else {
            $lines = FactionHandler::sendFactionMap($this);
            $scoreboard = $this->getScoreboard();
            $i = 4;
            foreach($lines as $line) {
                $scoreboard->setScoreLine($i++, $line);
            }
        }
    }
    
    public function setPlayerTag(): void
    {
        
        $rankId = $this->getDataSession()->getRank()->getIdentifier();
        /** @var NexusPlayer $onlinePlayer */
        foreach ($this->core->getServer()->getOnlinePlayers() as $onlinePlayer) {
            if ($rankId >= Rank::TRIAL_MODERATOR and $rankId <= Rank::OWNER) {
                break;
            }
            if ($onlinePlayer->hasVanished()) {
                $this->hidePlayer($onlinePlayer);
            }
        }
        $this->setNameTag($this->dataSession->getRank()->getTagFormatFor($this, [
            "faction_rank" => $this->getDataSession()->getFactionRoleToString(),
            "faction" => $this->dataSession->getFaction() instanceof Faction ? $this->dataSession->getFaction()->getName() : "",
            "kills" => $this->dataSession->getKills(),
            "level" => $this->dataSession->getCurrentLevel()
        ]));
    }

    /**
     * @param bool $disguise
     */
    public function setDisguise(bool $disguise): void
    {
        $this->disguise = $disguise;
    }

    /**
     * @return bool
     */
    public function isDisguise(): bool
    {
        return $this->disguise;
    }

    /**
     * @return Rank|null
     */
    public function getDisguiseRank(): ?Rank
    {
        return $this->disguiseRank;
    }

    /**
     * @param Rank|null $disguiseRank
     */
    public function setDisguiseRank(?Rank $disguiseRank): void
    {
        $this->disguiseRank = $disguiseRank;
        if ($this->disguiseRank !== null) {
            $this->setNameTag($this->disguiseRank->getTagFormatFor($this, [
                "faction_rank" => "",
                "faction" => "",
                "kills" => $this->getDataSession()->getKills(),
                "level" => $this->dataSession->getCurrentLevel()
            ]));
            return;
        }
        $this->setPlayerTag();
    }

    /**
     * @return bool
     */
    public function isAutoSelling(): bool
    {
        return $this->autoSell;
    }

    /**
     * @param bool $value
     */
    public function setAutoSelling(bool $value = true): void
    {
        $this->autoSell = $value;
    }

    /**
     * @param bool $value
     */
    public function setCheckingForVote(bool $value = true): void
    {
        $this->voteChecking = $value;
    }

    /**
     * @return bool
     */
    public function isCheckingForVote(): bool
    {
        return $this->voteChecking;
    }

    /**
     * @return bool
     */
    public function hasVoted(): bool
    {
        return $this->voted;
    }

    /**
     * @param bool $value
     */
    public function setVoted(bool $value = true): void
    {
        $this->voted = $value;
    }

    /**
     * @return bool
     */
    public function isRunningCrateAnimation(): bool
    {
        return $this->runningCrateAnimation;
    }

    /**
     * @param bool $value
     */
    public function setRunningCrateAnimation(bool $value = true): void
    {
        $this->runningCrateAnimation = $value;
    }

    /**
     * @return FloatingTextParticle[]
     */
    public function getFloatingTexts(): array
    {
        return $this->floatingTexts;
    }

    /**
     * @param string $identifier
     *
     * @return FloatingTextParticle|null
     */
    public function getFloatingText(string $identifier): ?FloatingTextParticle
    {
        return $this->floatingTexts[$identifier] ?? null;
    }

    /**
     * @param Position $position
     * @param string $identifier
     * @param string $message
     *
     * @throws NexusException
     */
    public function addFloatingText(Position $position, string $identifier, string $message): void
    {
        if ($position->getWorld() === null) {
            throw new NexusException("Attempt to add a floating text particle with an invalid world.");
        }
        $floatingText = new FloatingTextParticle($position, $identifier, $message);
        $this->floatingTexts[$identifier] = $floatingText;
        $floatingText->sendChangesTo($this);
    }

    /**
     * @param string $identifier
     *
     * @throws NexusException
     */
    public function removeFloatingText(string $identifier): void
    {
        $floatingText = $this->getFloatingText($identifier);
        if ($floatingText === null) {
            throw new NexusException("Failed to despawn floating text: $identifier");
        }
        $floatingText->despawn($this);
        unset($this->floatingTexts[$identifier]);
    }

    /**
     * @param Permission|string $name
     *
     * @return bool
     */
    public function hasPermission($name): bool
    {
        if ($this->isLoaded()) {
            if (in_array($name, $this->getDataSession()->getPermissions())) {
                return true;
            }
            if ($this->getDataSession()->getRank() !== null) {
                if (in_array($name, $this->getDataSession()->getRank()->getPermissions())) {
                    return true;
                }
            }
            if (in_array($name, $this->getDataSession()->getPermanentPermissions())) {
                return true;
            }
        }
        return parent::hasPermission($name);
    }

    /**
     * @param int $amount
     * @param bool $playSound
     *
     * @return bool
     */
    public function addXp(int $amount, bool $playSound = true): bool
    {
        if ($amount + $this->getXpManager()->getCurrentTotalXp() > 0x7fffffff) {
            return false;
        }
        $bool = $this->getXpManager()->addXp($amount, $playSound);
        return $bool;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public function isRequestingTeleport(NexusPlayer $player): bool
    {
        return isset($this->teleportRequests[$player->getUniqueId()->toString()]) and (time() - $this->teleportRequests[$player->getUniqueId()->toString()]) < 30;
    }

    /**
     * @param NexusPlayer $player
     */
    public function addTeleportRequest(NexusPlayer $player): void
    {
        $this->teleportRequests[$player->getUniqueId()->toString()] = time();
    }

    /**
     * @param NexusPlayer $player
     */
    public function removeTeleportRequest(NexusPlayer $player): void
    {
        if (isset($this->teleportRequests[$player->getUniqueId()->toString()])) {
            unset($this->teleportRequests[$player->getUniqueId()->toString()]);
        }
    }

    /**
     * @return bool
     */
    public function isTeleporting(): bool
    {
        return $this->teleporting;
    }

    /**
     * @param bool $value
     */
    public function setTeleporting(bool $value = true): void
    {
        $this->teleporting = $value;
    }

    /**
     * @return bool
     */
    public function isFrozen(): bool 
    {
        return $this->frozen;
    }

    /**
     * @param bool $frozen
     */
    public function setFrozen(bool $frozen = true): void 
    {
        $this->frozen = $frozen;
    }

    /**
     * @return int
     */
    public function getLastRepair(): int {
        return $this->lastRepair;
    }

    public function setLastRepair(): void {
        $this->lastRepair = time();
    }

    /**
     * @param string $type
     */
    public function setCustomItemCooldown(string $type): void {
        $this->itemCooldowns[$type] = time();
    }

    /**
     * @param string $type
     *
     * @return int
     */
    public function getCustomItemCooldown(string $type): int {
        return $this->itemCooldowns[$type] ?? 0;
    }

    /**
     * @return int
     */
    public function getChatMode(): int
    {
        return $this->chatMode;
    }

    /**
     * @return string
     */
    public function getChatModeToString(): string
    {
        return match ($this->chatMode) {
            self::PUBLIC => "public",
            self::FACTION => "faction",
            self::ALLY => "ally",
            self::STAFF => "staff",
            default => "unknown",
        };
    }

    /**
     * @param int $mode
     */
    public function setChatMode(int $mode): void
    {
        $this->chatMode = $mode;
    }

    /**
     * @return string
     */
    public function getOS(): string
    {
        return $this->os;
    }

    /**
     * @param string $os
     */
    public function setOS(string $os): void
    {
        $this->os = $os;
    }


    /**
     * @param bool $value
     */
    public function combatTag(bool $value): void
    {
        if ($value) {
            $this->combatTag = 15;
        }
    }

    /**
     * @param int $value
     */
    public function setCombatTagTime(int $value): void
    {
        $this->combatTag = $value;
    }

    /**
     * @param int $value
     */
    public function setCombatTagged(bool $value): void
    {
        $this->combatTagged = $value;
    }

    /**
     * @return int
     */
    public function combatTagTime(): int
    {
        return $this->combatTag;
    }

    /**
     * @return bool
     */
    public function isTagged(): bool
    {
        return $this->combatTagged;
    }

    public function isInStaffMode(): bool
    {
        return $this->staffMode;
    }

    public function setStaffMode(bool $status =  true): void
    {
        $this->staffMode = $status;
        if ($status) {
            $this->setStaffModeInventory($this->getInventory()->getContents());
            $this->getInventory()->clearAll();
            $this->vanish(true);
            $this->setFlying(true);
            $this->setAllowFlight(true);
            $this->setHealth(20);
            $this->getHungerManager()->setFood(20);
            $this->getInventory()->setItem(0, VanillaBlocks::ICE()->asItem()->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Freeze / UnFreeze"));
            $this->getInventory()->setItem(3, VanillaItems::COMPASS()->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Choose Player to Teleport"));
            $this->getInventory()->setItem(5, VanillaItems::CLOCK()->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "Teleport To Random Player"));
            $this->getInventory()->setItem(8, VanillaBlocks::CHEST()->asItem()->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Check Inventory"));
            $pk = new GameRulesChangedPacket();
            $pk->gameRules = ["showcoordinates" => new BoolGameRule(false, false)];
            $this->getNetworkSession()->sendDataPacket($pk);
        } else {
            $this->getInventory()->clearAll();
            $this->getInventory()->setContents($this->getStaffModeInventory());
            $this->vanish(false);
            $this->setHealth(20);
            $this->getHungerManager()->setFood(20);
            $this->setFlying(false);
            $this->setAllowFlight(false);
            $this->teleport($this->getCore()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
        }
    }

    public function getStaffModeInventory(): array
    {
        return $this->staffModeInventory;
    }

    public function setStaffModeInventory(array $inventory): void
    {
        $this->staffModeInventory = $inventory;
    }

    /**
     * @return bool
     */
    public function isTakingPMs(): bool {
        return $this->togglePrivateMessage;
    }

    public function togglePMs(): void {
        $this->togglePrivateMessage = !$this->togglePrivateMessage;
    }

    public function sendDelayedWindow(InvMenu $menu): void
    {
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($menu, $this) extends Task
        {

            /** @var InvMenu */
            private $menu;

            /** @var NexusPlayer */
            private $player;

            /**
             *  constructor.
             *
             * @param InvMenu $menu
             * @param NexusPlayer $player
             */
            public function __construct(InvMenu $menu, NexusPlayer $player)
            {
                $this->menu = $menu;
                $this->player = $player;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(): void
            {
                if ($this->player->isOnline() and (!$this->player->isClosed())) {
                    $this->menu->send($this->player);
                }
            }
        }, 20);
    }

    public function sendDelayedForm(Form $form): void
    {
        $this->getCore()->getScheduler()->scheduleDelayedTask(new class($form, $this) extends Task
        {

            /** @var Form */
            private $form;

            /** @var NexusPlayer */
            private $player;

            /**
             *  constructor.
             *
             * @param Form $form
             * @param NexusPlayer $player
             */
            public function __construct(Form $form, NexusPlayer $player)
            {
                $this->form = $form;
                $this->player = $player;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(): void
            {
                if ($this->player->isOnline() and (!$this->player->isClosed())) {
                    $this->player->sendForm($this->form);
                }
            }
        }, 20);
    }

    /**
     * @param int $extraData
     */
    public function playNoteSound(int $extraData = 1): void
    {
        $sound = new NoteSound(NoteInstrument::PIANO(), 1);
        $this->broadcastSound($sound, [$this]);
    }

    public function playErrorSound(): void
    {
        //$this->playSound("note.pling", 2, 1);
        $sound = new NoteSound(NoteInstrument::PLING(), 3);
        $this->broadcastSound($sound, [$this]);
    }

    /**
     * @param float $pitch
     */
    public function playXpLevelUpSound(float $pitch = 1.0): void
    {
        $this->getWorld()->addSound($this->getPosition(), new XpLevelUpSound(30));
    }

    /**
     * @param int $pitch
     */
    public function playDingSound(int $pitch = -1): void
    {
        if ($pitch !== -1) {
            $pitch *= 1000;
        } else {
            $pitch = 100000000;
        }
        $sound = new XpCollectSound();
        $this->broadcastSound($sound, [$this]);
    }

    /**
     * @param string $sound
     */
    public function playSound(string $sound, $volume = 1, $pitch = 1): void {
        $spk = new PlaySoundPacket();
        $spk->soundName = $sound;
        $spk->x = $this->getLocation()->getX();
        $spk->y = $this->getLocation()->getY();
        $spk->z = $this->getLocation()->getZ();
        $spk->volume = $volume;
        $spk->pitch = $pitch;
        $this->getNetworkSession()->sendDataPacket($spk);
	}

    /**
     * @param Vector3 $pos
     * @param float|null $yaw
     * @param float|null $pitch
     *
     * @return bool
     */
    public function teleport(Vector3 $pos, float $yaw = null, float $pitch = null): bool
    {
        if ($pos instanceof Position) {
            $level = $pos->getWorld();
            if ($level !== null) {
                foreach ($this->getFloatingTexts() as $floatingText) {
                    if ($level->getDisplayName() === $floatingText->getWorld()->getDisplayName()) {
                        $floatingText->spawn($this);
                        continue;
                    }
                    if ($level->getDisplayName() !== $floatingText->getWorld()->getDisplayName()) {
                        $floatingText->despawn($this);
                        continue;
                    }
                }
            }
        }
        return parent::teleport($pos, $yaw, $pitch);
    }
}
