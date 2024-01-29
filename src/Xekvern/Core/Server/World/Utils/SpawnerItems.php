<?php

namespace Xekvern\Core\Server\World\Utils;

use pocketmine\utils\CloningRegistryTrait;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\item\{Item, ToolTier, ItemIdentifier, ItemTypeIds};
use pocketmine\block\{Block, BlockIdentifier, BlockTypeIds, BlockTypeInfo, BlockBreakInfo, BlockToolType, MonsterSpawner};
use DayKoala\block\SpawnerBlock;
use DayKoala\block\tile\SpawnerTile;
use Xekvern\Core\Server\World\Tile\MobSpawnerTile;

final class SpawnersItems
{

    use CloningRegistryTrait;

    /**
     * 
     *  @method static SpawnerBlock MONSTER_SPAWNER()
     *  @method static SpawnEgg SPAWN_EGG() 
     *
     */

    public const TAG_MONSTER_SPAWNER_ENTITY_ID = 'SpawnerEntityId';
    public const MONSTER_SPAWNER_ID = BlockTypeNames::MOB_SPAWNER;

    private static int $spawnerRuntimeId = 0;

    public static function getSpawnerRuntimeId(): Int
    {
        return self::$spawnerRuntimeId;
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

    protected static function setup(): Void
    {
        self::register('monster_spawner', new MonsterSpawner(new BlockIdentifier(self::$spawnerRuntimeId = BlockTypeIds::MONSTER_SPAWNER, MonsterSpawner::class), 'Monster Spawner', new BlockTypeInfo(new BlockBreakInfo(5.0, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel()))));
    }

    protected static function register(String $name, Block|Item $result): Void
    {
        self::_registryRegister($name, $result);
    }

    private function __construct() {}
}
