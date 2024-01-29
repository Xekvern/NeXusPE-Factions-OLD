<?php

declare(strict_types=1);

namespace Xekvern\Core;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Player\PlayerManager;
use Xekvern\Core\Server\ServerManager;
use Xekvern\Core\Features\FeaturesManager;
use Xekvern\Core\Provider\MySQLProvider;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\plugin\PluginException;
use pocketmine\utils\Internet;
use pocketmine\world\generator\GeneratorManager;
use Xekvern\Core\Command\CommandManager;
use Xekvern\Core\Command\Task\CheckVoteTask;
use Xekvern\Core\Session\SessionManager;
use Xekvern\Core\Server\World\Utils\EmptyWorldGenerator;
use cosmicpe\blockdata\BlockDataFactory;
use cosmicpe\blockdata\world\BlockDataWorldManager;
use pocketmine\Server;
use pocketmine\utils\Config;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Server\Entity\Data\DurabilityBlockData;

class Nexus extends PluginBase
{

    const GRACE_PERIOD_TIME = 604800;
    const BORDER = 15000;
    const EXTRA_SLOTS = 50;
    const START = 1619038800;
    const CHAPTER = 2;
    const SEASON = 2;
    const GAMEMODE = "OP Factions";
    const SERVER_NAME = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "NeXus" . TextFormat::DARK_AQUA . "PE " . TextFormat::RESET . TextFormat::GRAY . "OP Factions";

    /** @var self */
    private static $instance;

    /** @var BigEndianNbtSerializer */
    private static $nbtWriter;
    /** @var NetworkNbtSerializer */
    private static $networkStream;

    /** @var bool */
    private static bool $debug = false;

    /** @var bool */
    private $loaded = false;

    /** @var bool */
    private $globalMute = false;

    /** @var int */
    private $startTime;
    
    /** @var int */
    private $votes;

    /** @var PlayerManager */
    private $playerManager;

    /** @var ServerManager */
    private $serverManager;

    /** @var CommandManager */
    private $commandManager;

    /** @var SessionManager */
    private $sessionManager;

    /** @var MySQLProvider */
    private $provider;

    const DURABILITY_BLOCK_DATA = "nexuspe:durability";
    private ?BlockDataWorldManager $block_data_manager = null;

    /**
     * @param string $message
     */
    public static function debug(string $message): void
    {
        if (self::$debug) {
            self::$instance->getLogger()->info("DEBUG: " . $message);
        }
    }

    public function onLoad(): void
    {
        self::$nbtWriter = new BigEndianNbtSerializer();
        self::$networkStream = new NetworkNbtSerializer();
        self::$instance = $this;
        $this->getServer()->getNetwork()->setName(self::SERVER_NAME);
        GeneratorManager::getInstance()->addGenerator(
            EmptyWorldGenerator::class,
            "emptyworld",
            fn () => null
        );
        $this->saveDefaultConfig();
    }

    /**
     * @throws NexusException
     */
    public function onEnable(): void
    {
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        $this->block_data_manager = BlockDataWorldManager::create($this);
        BlockDataFactory::register(self::DURABILITY_BLOCK_DATA, DurabilityBlockData::class);
        $this->startTime = time();
        $server = $this->getServer();
        $server->getWorldManager()->loadWorld("wild");
        $server->getWorldManager()->loadWorld("warzone");
        $server->getWorldManager()->loadWorld("bossarena");
        $this->saveDefaultConfig();
        $this->provider = new MySQLProvider($this);
        $this->serverManager = new ServerManager($this);
        $this->sessionManager = new SessionManager($this);
        $this->commandManager = new CommandManager($this);
        $this->playerManager = new PlayerManager($this);
        $this->getServer()->getPluginManager()->registerEvents(new NexusEvents($this), $this);
        $get = Internet::getURL(CheckVoteTask::STATS_URL);
        if($get === false) {
            return;
        }
        $get = json_decode($get->getBody(), true);
        $this->votes = 0;
        $this->loaded = true;
    }

    public function onDisable(): void
    {
        if (!$this->loaded) {
            $this->getServer()->forceShutdown();
            return;
        }
        foreach($this->getServer()->getWorldManager()->getWorlds() as $level) {
            $level->save(true);
            $this->getLogger()->notice("[Saving] Saved " . $level->getDisplayName());
        }
        foreach($this->getPlayerManager()->getFactionHandler()->getFactions() as $faction) {
            $faction->update();
        }
        $this->getLogger()->notice("[Saving] Save completed for Faction Data");
        foreach($this->getPlayerManager()->getFactionHandler()->getClaims() as $claim) {
            $claim->update();
        }
        $this->getLogger()->notice("[Saving] Save completed for Faction Claims");
        foreach(Server::getInstance()->getOnlinePlayers() as $player) {
            if(!$player instanceof NexusPlayer) continue;
            if($player->isInStaffMode()) {
                foreach($player->getStaffModeInventory() as $item) {
                    $player->getDataSession()->addToInbox($item);
                }
            }
            if($player->isTagged()) {
                $player->combatTag(false);
                $player->setCombatTagged(false);
            }
            $player->getDataSession()->saveData();
            $player->transfer("hub.nexuspe.net", 19132, TextFormat::RESET . TextFormat::RED . "Server is restarting...");
        }
        $this->getLogger()->notice("[Saving] Save completed for Player Data");
    }

    /**
     * @return Nexus
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @return int
     */
    public function getStartTime(): int 
    {
        return $this->startTime;
    }

    /**
     * @return int
     */
    public function getVotes(): int
    {
        return $this->votes;
    }

    /**
     * @param int $votes
     */
    public function setVotes(int $votes): void
    {
        $this->votes = $votes;
    }

    /**
     * @return bool
     */
    public function isGlobalMuted(): bool 
    {
        return $this->globalMute;
    }

    /**
     * @param bool $globalMute
     */
    public function setGlobalMute(bool $globalMute): void 
    {
        $this->globalMute = $globalMute;
    }

    /**
     * @return bool
     */
    public function isInGracePeriod(): bool 
    {
        $file = $this->getGracePeriodData();
        $fileTime = $file->get("time", null);
        $time = self::GRACE_PERIOD_TIME - (time() - $fileTime);
        if($fileTime !== null) {
            if($time > 0) {
                return true;
            } 
        } 
        return false;
    }

    /**
     * @return int
     */
    public function getGracePeriod(): int 
    {
        $file = $this->getGracePeriodData();
        $fileTime = $file->get("time", null);
        $time = self::GRACE_PERIOD_TIME - (time() - $fileTime);
        if($fileTime !== null) {
            if($time > 0) {
                return $time;
            } 
        } else {
            return 0;
        }
    } 

    /**
     * @param int $time
     * @param bool $gracePeriod
     */
    public function setGracePeriod(bool $gracePeriod): void 
    {
        $file = $this->getGracePeriodData();
        if($gracePeriod === true) {
            $file->set("announced", false); 
            $file->set("time", time()); 
            $file->save();
        } else {
            $file->set("announced", true); 
            $file->set("time", null);
            $file->save();
        }
    }

    /**
     * @return Config
     */
    public function getGracePeriodData(): Config
    {
        return new Config($this->getDataFolder() . "graceperiod.json", Config::JSON);
        // TODO: GP CMD, Updating on Scoreboard, Limitation of Features
    }

    /**
     * @return MySQLProvider
     */
    public function getMySQLProvider(): MySQLProvider
    {
        return $this->provider;
    }

    /**
     * @return PlayerManager
     */
    public function getPlayerManager(): PlayerManager
    {
        return $this->playerManager;
    }

    /**
     * @return ServerManager
     */
    public function getServerManager(): ServerManager
    {
        return $this->serverManager;
    }

    /**
     * @return CommandManager
     */
    public function getCommandManager(): CommandManager
    {
        return $this->commandManager;
    }

    /** 
     * @return SessionManager 
     * */
    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }

    public function getBlockDataManager(): BlockDataWorldManager 
    {
        return $this->block_data_manager;
    }

    /**
     * @return NetworkNbtSerializer
     */
    public static function getNetworkStream(): NetworkNbtSerializer
    {
        return self::$networkStream;
    }

    /**
     * @param Item $item
     *
     * @return string
     */
    public static function encodeItem(Item $item): string
    {
        return self::$nbtWriter->write(new TreeRoot($item->nbtSerialize()));
    }

    /**
     * @param string $compression
     *
     * @return Item
     *
     * @throws PluginException
     */
    public static function decodeItem(string $compression): Item
    {
        $tag = self::$nbtWriter->read($compression);
        if (!$tag instanceof CompoundTag) {
            if (!$tag instanceof TreeRoot) {
                throw new PluginException("Expected a CompoundTag, got " . get_class($tag));
            }
            return Item::nbtDeserialize($tag->mustGetCompoundTag());
        }
        return Item::nbtDeserialize($tag);
    }

    /**
     * @param Inventory $inventory
     * @param bool $includeEmpty
     *
     * @return string
     */
    public static function encodeInventory(Inventory $inventory, bool $includeEmpty = false): string
    {
        $contents = $inventory->getContents($includeEmpty);
        $items = [];
        foreach ($contents as $item) {
            $items[] = $item->nbtSerialize();
        }
        $tag = new CompoundTag();
        $list = new ListTag($items);
        $tag->setTag("Items", $list);
        return self::$nbtWriter->write(new TreeRoot($tag));
    }

    /**
     * @param Item[] $contents
     *
     * @return string
     */
    public static function encodeItems(array $contents): string
    {
        $items = [];
        foreach ($contents as $item) {
            $items[] = $item->nbtSerialize();
        }
        $tag = new CompoundTag();
        $list = new ListTag($items);
        $tag->setTag("Items", $list);
        return self::$nbtWriter->write(new TreeRoot($tag));
    }

    /**
     * @param string $compression
     *
     * @return Item[]
     */
    public static function decodeInventory(string $compression): array
    {
        if (empty($compression)) {
            return [];
        }
        try {
            $tag = self::$nbtWriter->read($compression);
        } catch (NbtDataException) {
            return [];
        }
        $tag = $tag->getTag();
        if (!$tag instanceof CompoundTag) {
            throw new PluginException("Expected a CompoundTag, got " . get_class($tag));
        }
        $items = $tag->getListTag("Items");
        $content = [];
        /** @var CompoundTag $item */
        foreach ($items->getValue() as $item) {
            $content[] = Item::nbtDeserialize($item);
        }
        return $content;
    }
}
