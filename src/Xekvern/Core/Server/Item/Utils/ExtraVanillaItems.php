<?php

namespace Xekvern\Core\Server\Item\Utils;

use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockToolType;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\ItemUseResult;
use pocketmine\item\SpawnEgg;
use pocketmine\item\StringToItemParser;
use pocketmine\item\ToolTier;
use pocketmine\utils\CloningRegistryTrait;
use pocketmine\world\World;
use Xekvern\Core\Server\Entity\Types\Creeper;
use Xekvern\Core\Server\Item\Types\Vanilla\CreeperSpawnEgg;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\World\Block\MonsterSpawner;
use Xekvern\Core\Server\World\Tile\MobSpawner;

/**
 * @method static Item ENCHANTED_BOOK()
 * @method static Item END_CRYSTAL()
 * @method static MonsterSpawner MONSTER_SPAWNER()
 * @method static SpawnEgg CREEPER_SPAWN_EGG()
 * @method static Item NAME_TAG()
 * @method static Item ENDER_EYE()
 * @method static Item FIREWORKS()
 * @method static Item MAP()
 */
final class ExtraVanillaItems
{
    use CloningRegistryTrait;

    private static int $spawnerRuntimeId = 0;
    public const MONSTER_SPAWNER_ID = BlockTypeNames::MOB_SPAWNER;
    public const TAG_MONSTER_SPAWNER_ENTITY_ID = 'SpawnerEntityId';

    private function __construct()
    {
    }

    protected static function register(string $name, Block|Item $item): void
    {
        self::_registryRegister($name, $item);
    }

    /**
     * @return Block|Item[]
     * @phpstan-return array<string, Item>
     */
    public static function getAll(): array
    {
        /** @var Block|Item[] $result */
        $result = self::_registryGetAll();
        return $result;
    }

    protected static function setup(): void
    {
        $enchantedBookTypeId = ItemTypeIds::newId();
        self::register("enchanted_book", new Item(new ItemIdentifier($enchantedBookTypeId), "Enchanted Book"));
        $endCrystalTypeId = ItemTypeIds::newId();
        self::register("end_crystal", new Item(new ItemIdentifier($endCrystalTypeId), "End Crystal"));
        $nametagTypeId = ItemTypeIds::newId();
        self::register("name_tag", new Item(new ItemIdentifier($nametagTypeId), "Name Tag"));
        $endereyeTypeId = ItemTypeIds::newId();
        self::register("ender_eye", new Item(new ItemIdentifier($endereyeTypeId), "Ender Eye"));
        $fireworksTypeId = ItemTypeIds::newId();
        self::register("fireworks", new Item(new ItemIdentifier($fireworksTypeId), "Fireworks"));
        $mapTypeId = ItemTypeIds::newId();
        self::register("map", new Item(new ItemIdentifier($mapTypeId), "Map"));
        $creeperEggTypeId = ItemTypeIds::newId();
        self::register("creeper_spawn_egg", new class(new ItemIdentifier($creeperEggTypeId), "Creeper Spawn Egg") extends SpawnEgg
        {

            /**
             * @param World $world
             * @param Vector3 $pos
             * @param float $yaw
             * @param float $pitch
             * @return Entity
             */
            public function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch): Entity
            {
                return new Creeper(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        });
        self::register('monster_spawner', new MonsterSpawner(new BlockIdentifier(self::$spawnerRuntimeId = BlockTypeIds::MONSTER_SPAWNER, MobSpawner::class), 'Monster Spawner', new BlockTypeInfo(new BlockBreakInfo(5.0, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel()))));
    }

    public static function initHack(): void
    {
        static $nameToMeta = [
            10 => "Chicken",
            11 => "Cow",
            12 => "Pig",
            17 => "Squid",
            20 => "Iron Golem",
            32 => "Zombie",
            43 => "Blaze",
        ];
        foreach ($nameToMeta as $meta => $name) {
            StringToItemParser::getInstance()->override($name . "_spawner", fn () => self::setSpawnerEntityId(
                self::MONSTER_SPAWNER()->asItem(),
                $meta
            )->setCustomName(
                TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "{$name} Spawner"
            ));
        }
    }

    public static function getSpawnerEntityId(Item $item): Int
    {
        return $item->getNamedTag()->getInt(self::TAG_MONSTER_SPAWNER_ENTITY_ID, 0);
    }

    public static function setSpawnerEntityId(Item $item, Int $id): Item
    {
        $namedtag = $item->getNamedTag();
        $namedtag->setInt(self::TAG_MONSTER_SPAWNER_ENTITY_ID, $id);
        $item->setNamedTag($namedtag);
        return $item;
    }
}
