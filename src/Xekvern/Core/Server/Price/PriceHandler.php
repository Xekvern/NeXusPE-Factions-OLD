<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Price;

use Xekvern\Core\Nexus;
use Xekvern\Core\Server\Item\Types\CreeperEgg;
use Xekvern\Core\Server\Item\Types\GeneratorBucket;
use Xekvern\Core\Server\Item\Types\SellWand;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use Xekvern\Core\Server\Item\Types\SellWandNote;
use Xekvern\Core\Server\Item\Utils\ExtraVanillaItems;
use Xekvern\Core\Server\World\Utils\GeneratorId;
use Xekvern\Core\Server\World\WorldHandler;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\TextFormat;

class PriceHandler {

    /** @var Nexus */
    private $core;

    /** @var ShopPlace[] */
    private $places = [];

    /** @var PriceEntry[] */
    private $sellables = [];

    /**
     * PriceHandler constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
    }

    public function init() {
        $worldHandler = new WorldHandler($this->core);
        $this->places = [
            new ShopPlace("Blocks", VanillaBlocks::GRASS()->asItem(), [
                new PriceEntry(VanillaBlocks::OBSIDIAN()->asItem(), null, 25, 175),
                new PriceEntry(VanillaBlocks::BEDROCK()->asItem(), null, 200, 10000),
                new PriceEntry(VanillaBlocks::GLOWSTONE()->asItem(), null, null, 12),
                new PriceEntry(VanillaBlocks::SEA_LANTERN()->asItem(), null, null, 15),
                new PriceEntry(VanillaBlocks::DIRT()->asItem(), null, 2, 5),
                new PriceEntry(VanillaBlocks::GRASS()->asItem(), null, 5, 10),
                new PriceEntry(VanillaBlocks::COBBLESTONE()->asItem(), null, 3),
                new PriceEntry(VanillaBlocks::ANDESITE()->asItem(), null, 3),
                new PriceEntry(VanillaBlocks::DIORITE()->asItem(), null, 5),
                new PriceEntry(VanillaBlocks::GRANITE()->asItem(), null, 5),
                new PriceEntry(VanillaBlocks::ENCHANTING_TABLE()->asItem(), null, null, 100000),
                new PriceEntry(VanillaBlocks::OAK_WOOD()->asItem(), null, 6, 12),
                new PriceEntry(VanillaBlocks::SPRUCE_WOOD()->asItem(), null, 6, 12),
                new PriceEntry(VanillaBlocks::BIRCH_WOOD()->asItem(), null, 6, 12),
                new PriceEntry(VanillaBlocks::JUNGLE_WOOD()->asItem(), null, 6, 12),
                new PriceEntry(VanillaBlocks::STONE_BRICKS()->asItem(), null, null, 12),
                new PriceEntry(VanillaBlocks::MOSSY_STONE_BRICKS()->asItem(), null, null, 12),
                new PriceEntry(VanillaBlocks::CRACKED_STONE_BRICKS()->asItem(), null, null, 12),
                new PriceEntry(VanillaBlocks::END_STONE_BRICKS()->asItem(), null, null, 12),
                new PriceEntry(VanillaBlocks::PRISMARINE()->asItem(), null, null, 24),
                new PriceEntry(VanillaBlocks::DARK_PRISMARINE()->asItem(), null, null, 24),
                new PriceEntry(VanillaBlocks::GLASS()->asItem(), null, null, 6),
                new PriceEntry(VanillaBlocks::WOOL()->setColor(DyeColor::WHITE())->asItem(), null, null, 15),
                new PriceEntry(VanillaBlocks::NETHER_BRICKS()->asItem(), null, null, 30),
                new PriceEntry(VanillaBlocks::NETHERRACK()->asItem(), null, null, 12),
                new PriceEntry(VanillaBlocks::QUARTZ()->asItem(), null, null, 60),
                new PriceEntry(VanillaBlocks::CHISELED_QUARTZ()->asItem(), null, null, 60),
                new PriceEntry(VanillaBlocks::SAND()->asItem(), null, null, 6),
                new PriceEntry(VanillaBlocks::RED_SAND()->asItem(), null, null, 8),
                new PriceEntry(VanillaBlocks::GRAVEL()->asItem(), null, null, 8)
            ]), 
            new ShopPlace("Dyes", VanillaItems::DYE()->setColor(DyeColor::BLUE()), [
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::BLACK()), "Black Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::RED()), "Red Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::GREEN()), "Green Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::BROWN()), "Brown Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::PURPLE()), "Purple Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::CYAN()), "Cyan Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::LIGHT_GRAY()), "Light Gray Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::GRAY()), "Gray Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::PINK()), "Pink Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::LIME()), "Lime Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::YELLOW()), "Dandelion Yellow Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::LIGHT_BLUE()), "Light Blue Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::MAGENTA()), "Magenta Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::ORANGE()), "Orange Dye", 30, 60),
                new PriceEntry(VanillaItems::DYE()->setColor(DyeColor::WHITE()), "White Dye", 30, 60)
            ]),
            new ShopPlace("Combat", VanillaItems::DIAMOND_SWORD(), [
                new PriceEntry(VanillaItems::GOLDEN_APPLE(), null, null, 500),
                new PriceEntry(VanillaItems::ENCHANTED_GOLDEN_APPLE(), null, null, 5000),
            ]),
            new ShopPlace("Valuables", VanillaItems::DIAMOND(), [
                new PriceEntry(VanillaItems::COAL(), null, 40),
                new PriceEntry(VanillaItems::LAPIS_LAZULI(), null, 7),
                new PriceEntry(VanillaItems::REDSTONE_DUST(), null, 60),
                new PriceEntry(VanillaItems::IRON_INGOT(), null, 100),
                new PriceEntry(VanillaItems::GOLD_INGOT(), null, 150),
                new PriceEntry(VanillaItems::RAW_IRON(), null, 100),
                new PriceEntry(VanillaItems::RAW_GOLD(), null, 150),
                new PriceEntry(VanillaItems::DIAMOND(), null, 200),
                new PriceEntry(VanillaItems::EMERALD(), null, 250),
                new PriceEntry(VanillaBlocks::COAL()->asItem(), null, 350),
                new PriceEntry(VanillaBlocks::LAPIS_LAZULI()->asItem(), null, 45),
                new PriceEntry(VanillaBlocks::REDSTONE()->asItem(), null, 500),
                new PriceEntry(VanillaBlocks::IRON()->asItem(), null, 850),
                new PriceEntry(VanillaBlocks::GOLD()->asItem(), null, 1250),
                new PriceEntry(VanillaBlocks::DIAMOND()->asItem(), null, 1700),
                new PriceEntry(VanillaBlocks::EMERALD()->asItem(), null, 2100),
            ]),
            new ShopPlace("Utilities", VanillaItems::WOODEN_HOE(), [
                new PriceEntry((new CreeperEgg())->getItemForm(), null, null, 10000),
                new PriceEntry((new GeneratorBucket(BlockTypeIds::COBBLESTONE))->getItemForm(), "Cobblestone Generator Bucket", null, 500000),
                new PriceEntry((new GeneratorBucket(BlockTypeIds::OBSIDIAN))->getItemForm(), "Obsidian Generator Bucket", null, 5000000),
                new PriceEntry((new GeneratorBucket(BlockTypeIds::BEDROCK))->getItemForm(), "Bedrock Generator Bucket", null, 25000000),
                new PriceEntry((new GeneratorBucket(BlockTypeIds::WATER))->getItemForm(), "Water Generator Bucket", null, 10000000),
                new PriceEntry((new GeneratorBucket(BlockTypeIds::LAVA))->getItemForm(), "Lava Generator Bucket", null, 50000000),
                new PriceEntry(VanillaItems::BOW(), null, null, 5000),
                new PriceEntry(VanillaItems::ARROW(), null, null, 5),
                new PriceEntry(VanillaBlocks::TNT()->asItem(), null, null, 2000),
                new PriceEntry(VanillaBlocks::SPONGE()->asItem(), null, null, 10000),
                new PriceEntry(VanillaBlocks::WATER()->getFlowingForm()->asItem(), null, null, 15000),
                new PriceEntry(VanillaBlocks::LAVA()->getFlowingForm()->asItem(), null, null, 30000),
                new PriceEntry(VanillaBlocks::CHEST()->asItem(), null, null, 1000),
                new PriceEntry(VanillaBlocks::HOPPER()->asItem(), null, null, 10000),
                new PriceEntry(VanillaItems::ENDER_PEARL(), null, null, 100),
                new PriceEntry(VanillaItems::STEAK(), null, null, 1),
                new PriceEntry(VanillaBlocks::TORCH()->asItem(), null, null, 5),
            ]),
            new ShopPlace("Mining Generators", VanillaItems::DIAMOND_PICKAXE(), [
                new PriceEntry($worldHandler->getGeneratorItem(GeneratorId::COAL, false), "Coal Ore Block Generator", null, 100000),
                new PriceEntry($worldHandler->getGeneratorItem(GeneratorId::LAPIS_LAZULI, false), "Lapis Lazuli Ore Block Generator", null, 200000, 3),
                new PriceEntry($worldHandler->getGeneratorItem(GeneratorId::IRON, false), "Iron Ore Block Generator", null, 500000, 10),
                new PriceEntry($worldHandler->getGeneratorItem(GeneratorId::DIAMOND, false), "Diamond Ore Block Generator", null, 3000000, 20),
                new PriceEntry($worldHandler->getGeneratorItem(GeneratorId::EMERALD, false), "Emerald Ore Block Generator", null, 5000000, 50),
            ]),
            new ShopPlace("Auto Generators", VanillaBlocks::CHEST()->asItem(), [
                new PriceEntry($worldHandler->getGeneratorItem(GeneratorId::COAL, true), "Coal Auto Generator", null, 160000),
                new PriceEntry($worldHandler->getGeneratorItem(GeneratorId::REDSTONE_DUST, true), "Redstone Dust Auto Generator", null, 300000, 3),
                new PriceEntry($worldHandler->getGeneratorItem(GeneratorId::IRON, true), "Iron Auto Generator", null, 700000, 10),
                new PriceEntry($worldHandler->getGeneratorItem(GeneratorId::DIAMOND, true), "Diamond Auto Generator", null, 4000000, 20),
                new PriceEntry($worldHandler->getGeneratorItem(GeneratorId::EMERALD, true), "Emerald Auto Generator", null, 7000000, 50),
                new PriceEntry((new SellWand())->getItemForm(), null, null, 500000, 10),
                new PriceEntry((new SellWandNote(100))->getItemForm(), "Sell Uses 100", null, 250000, 10),
                new PriceEntry((new SellWandNote(1000))->getItemForm(), "Sell Uses 100", null, 2750000, 15),
            ]),
            /**new ShopPlace("Spawners", VanillaBlocks::MONSTER_SPAWNER()->asItem(), [
                new PriceEntry(StringToItemParser::getInstance()->parse("Pig_spawner"), "Pig Spawner", null, 500000),
                new PriceEntry(StringToItemParser::getInstance()->parse("Cow_spawner"), "Cow Spawner", null, 1000000, 3),
                new PriceEntry(StringToItemParser::getInstance()->parse("Zombie_spawner"), "Zombie Spawner", null, 1750000, 5),
                new PriceEntry(StringToItemParser::getInstance()->parse("Squid_spawner"), "Squid Spawner", null, 2500000, 10),
                new PriceEntry(StringToItemParser::getInstance()->parse("Blaze_spawner"), "Blaze Spawner", null, 5000000, 20),
                new PriceEntry(StringToItemParser::getInstance()->parse("Iron Golem_spawner"), "Iron Golem Spawner", null, 9000000, 30),
            ]),*/
            /**new ShopPlace("Mob Drops", VanillaItems::ROTTEN_FLESH(), [
                new PriceEntry(VanillaItems::RAW_PORKCHOP(), null, 75),
                new PriceEntry(VanillaItems::LEATHER(), null, 65),
                new PriceEntry(VanillaItems::RAW_BEEF(), null, 55),
                new PriceEntry(VanillaItems::ROTTEN_FLESH(), null, 125),
                new PriceEntry(VanillaItems::INK_SAC(), null, 175),
                new PriceEntry(VanillaItems::BLAZE_ROD(), null, 250),
                new PriceEntry(VanillaItems::NETHER_STAR(), null, 350),
                new PriceEntry(VanillaBlocks::POPPY()->asItem(), null, 10),
            ])*/
        ];
        foreach($this->getAll() as $entry) {
            if($entry->getSellPrice() !== null) {
                $this->sellables[$entry->getItem()->getTypeId()] = $entry;
            }
        }
    }

    /**
     * @return PriceEntry[]
     */
    public function getAll(): array {
        $all = [];
        foreach($this->places as $place) {
            $all = array_merge($all, $place->getEntries());
        }
        return $all;
    }

    /**
     * @return PriceEntry[]
     */
    public function getSellables(): array {
        return $this->sellables;
    }

    /**
     * @return ShopPlace[]
     */
    public function getPlaces(): array {
        return $this->places;
    }

    /**
     * @param string $name
     *
     * @return ShopPlace|null
     */
    public function getPlace(string $name): ?ShopPlace {
        foreach($this->places as $place) {
            if($place->getName() === $name) {
                return $place;
            }
        }
        return null;
    }
}