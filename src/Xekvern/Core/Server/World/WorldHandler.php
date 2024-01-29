<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\World;

use Xekvern\Core\Server\World\Block\Generator;
use Xekvern\Core\Nexus;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\tile\Tile;
use pocketmine\block\tile\TileFactory;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use ReflectionException;
use Xekvern\Core\Server\Entity\Types\Spawner\Blaze;
use Xekvern\Core\Server\Entity\Types\Spawner\Cow;
use Xekvern\Core\Server\Entity\Types\Spawner\IronGolem;
use Xekvern\Core\Server\Entity\Types\Spawner\Pig;
use Xekvern\Core\Server\Entity\Types\Spawner\Squid;
use Xekvern\Core\Server\Entity\Types\Spawner\Zombie;
use Xekvern\Core\Server\World\Utils\GeneratorId;

class WorldHandler {

    /** @var Nexus */
    private $core;

    /** @var Config */
    private static $setup;

    /**
     * WorldHandler constructor.
     *
     * @param Nexus $core
     *
     * @throws ReflectionException
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        self::$setup = new Config($this->core->getDataFolder() . "setup.yml", Config::YAML);
        $core->getServer()->getPluginManager()->registerEvents(new WorldEvents($core), $core);
        $this->init();
    }

    /**
     * @throws ReflectionException
     */
    public function init(): void {
        $blockFactory  = RuntimeBlockStateRegistry::getInstance();
        $tileFactory = TileFactory::getInstance();
        (function () {
            /** @noinspection all */
            unset(
                $this->typeIndex[BlockTypeIds::HOPPER], 
                $this->typeIndex[BlockTypeIds::BEDROCK], 
                $this->typeIndex[BlockTypeIds::OBSIDIAN],
                $this->typeIndex[BlockTypeIds::GLAZED_TERRACOTTA], 
            );
        })->call($blockFactory);
        $blockFactory->register(new Generator(BlockTypeIds::GLAZED_TERRACOTTA), true);
        $tileFactory->register(\Xekvern\Core\Server\World\Tile\Generator::class, ["Generators"]);
        $tileFactory->register(\Xekvern\Core\Server\World\Tile\LuckyBlock::class, ["Luckyblocks"]);
        $tileFactory->register(\Xekvern\Core\Server\World\Tile\MobSpawner::class, ['MobSpawner', 'minecraft:mob_spawner']);
    }

    /**
     * @param World $level
     * @param AxisAlignedBB $bb
     *
     * @return Tile[]
     */
    public static function getNearbyTiles(World $level, AxisAlignedBB $bb) : array{
        $nearby = [];
        $minX = ((int) floor($bb->minX - 2)) >> 4;
        $maxX = ((int) floor($bb->maxX + 2)) >> 4;
        $minZ = ((int) floor($bb->minZ - 2)) >> 4;
        $maxZ = ((int) floor($bb->maxZ + 2)) >> 4;
        for($x = $minX; $x <= $maxX; ++$x) {
            for($z = $minZ; $z <= $maxZ; ++$z) {
                foreach($level->getChunk($x, $z)->getTiles() as $ent) {
                    $entbb = $ent->getBlock()->getCollisionBoxes();
                    foreach ($entbb as $entb){
                        if($entb !== null) {
                            if($entb->intersectsWith($bb)) {
                                $nearby[] = $ent;
                            }
                        }
                    }
                }
            }
        }
        return $nearby;
    }

    /**
     * @param GeneratorId $id
     * @param bool $auto
     * 
     * @return Item 
     */
    public function getGeneratorItem(int $id, bool $auto = false) : Item { // TODO: Get generators here and return as an item.
        $lore = [];
        $lore[] = "";
        if($auto === true) { // Auto
            switch($id) {
                case GeneratorId::COAL:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::BLUE())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Coal Generator";
                    break;
                case GeneratorId::REDSTONE_DUST:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::LIGHT_BLUE())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Redstone Dust Generator";
                    break;
                case GeneratorId::IRON:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::GRAY())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Iron Generator";
                    break;
                case GeneratorId::DIAMOND:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::MAGENTA())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Diamond Generator";
                    break;
                case GeneratorId::EMERALD:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::GREEN())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Emerald Generator";
                    break;
            }
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "Place a " . TextFormat::RED . TextFormat::BOLD . "chest" . TextFormat::RESET . TextFormat::WHITE . " below generator to collect items.";
        } else { // Mining
            switch($id) {
                case GeneratorId::COAL:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::BROWN())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Coal Ore Generator";
                    break;
                case GeneratorId::LAPIS_LAZULI:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::CYAN())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Lapis Lazuli Ore Generator";
                    break;
                case GeneratorId::IRON:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::LIGHT_GRAY())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Iron Ore Generator";
                    break;
                case GeneratorId::DIAMOND:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::PINK())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Diamond Ore Generator";
                    break;
                case GeneratorId::AMETHYST:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::PURPLE())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Amethyst Ore Generator";
                    break;
                case GeneratorId::EMERALD:
                    $item = VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::LIME())->asItem();
                    $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "Emerald Ore Generator";
                    break;
            }
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "Place to create an ore above the generator.";
        }
        $item->setCustomName($customName);
        $item->setLore($lore);
        return $item;
    }

    /**
     * @param DyeColor $color
     * 
     * @return int
     */
    public function getGeneratorValue(DyeColor $color) : int {
        return match ($color->id()) {
            DyeColor::BROWN()->id() => 100000, // MINING
            DyeColor::CYAN()->id() => 200000,
            DyeColor::LIGHT_GRAY()->id()  => 500000,
            DyeColor::PINK()->id() => 3000000,
            DyeColor::LIME()->id() => 5000000, 
            DyeColor::BLUE()->id()  => 160000, // AUTO
            DyeColor::LIGHT_BLUE()->id() => 300000,
            DyeColor::GRAY()->id() => 700000,
            DyeColor::MAGENTA()->id() => 4000000,
            DyeColor::PURPLE()->id() => 10000000,
            DyeColor::GREEN()->id()  => 7000000,
        };
    }

    /**
     * @param string $entityTypeId
     * 
     * @return string
     */
    public function getSpawnerNameById(string $entityTypeId) : string {
        return match ($entityTypeId) {
            Pig::getNetworkTypeId() => "Pig",
            Cow::getNetworkTypeId() => "Cow",
            Zombie::getNetworkTypeId() => "Zombie",
            Squid::getNetworkTypeId() => "Squid",
            Blaze::getNetworkTypeId() => "Blaze",
            IronGolem::getNetworkTypeId() => "Iron Golem",
        };
    }

    /**
     * @param string $entityTypeId
     * 
     * @return int
     */
    public function getSpawnerValue(string $entityTypeId) : int {
        return match ($entityTypeId) {
            Pig::getNetworkTypeId() => 500000,
            Cow::getNetworkTypeId() => 1000000,
            Zombie::getNetworkTypeId() => 1750000,
            Squid::getNetworkTypeId() => 2500000,
            Blaze::getNetworkTypeId() => 5000000,
            IronGolem::getNetworkTypeId() => 9000000,
        };
    }

    /**
     * @return Config
     */
    public static function getSetup() : Config {
        return self::$setup;
    }
}