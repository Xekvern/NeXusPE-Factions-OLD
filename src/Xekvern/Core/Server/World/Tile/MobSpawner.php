<?php

namespace Xekvern\Core\Server\World\Tile;

use pocketmine\block\tile\Spawnable;
use pocketmine\data\bedrock\LegacyEntityIdToStringIdMap;
use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\particle\MobSpawnParticle;
use pocketmine\world\World;
use Xekvern\Core\Nexus;
use Xekvern\Core\Server\Entity\Types\SpawnerEntity;
use Xekvern\Core\Server\World\Block\MonsterSpawner;

class MobSpawner extends Spawnable
{
    /** @var string */
    public const TAG_STACK = "Stack";

    /** @var int */
    private $stack = 1;

    protected const TAG_LEGACY_ENTITY_TYPE_ID = "EntityId";
    protected const TAG_ENTITY_TYPE_ID = "EntityIdentifier";

    protected const TAG_SPAWN_DELAY = "Delay";
    protected const TAG_MIN_SPAWN_DELAY = "MinSpawnDelay";
    protected const TAG_MAX_SPAWN_DELAY = "MaxSpawnDelay";

    protected const TAG_SPAWN_RANGE = "SpawnRange";
    protected const TAG_REQUIRED_PLAYER_RANGE = "RequiredPlayerRange";

    private int $spawnDelay = 0;
    private int $minSpawnDelay = 10;
    private int $maxSpawnDelay = 30;
    private int $spawnRange = 3;
    private int $requiredPlayerRange = 32;

    protected int $legacyEntityTypeId = 0;
    protected string $entityTypeId = ":";

    private ?TaskHandler $handler = null;

    public function __construct(World $world, Vector3 $pos)
    {
        parent::__construct($world, $pos);

        $this->handler = Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(
                function () {
                    if ($this->canUpdate()) $this->onUpdate();
                }
            ),
            20
        );
    }

    public function canUpdate(): Bool
    {
        return (
            $this->entityTypeId !== ":" and
            $this->getPosition()->getWorld()->getNearestEntity($this->getPosition(), $this->requiredPlayerRange, Player::class) !== null
        );
    }

    public function getLegacyEntityId(): Int
    {
        return $this->legacyEntityTypeId;
    }

    public function setLegacyEntityId(Int $id): Void
    {
        $this->entityTypeId = LegacyEntityIdToStringIdMap::getInstance()->legacyToString($this->legacyEntityTypeId = $id) ?? ':';
        if (($block = $this->getBlock()) instanceof MonsterSpawner) $block->setLegacyEntityId($id);
    }

    public function getEntityTypeId(): string
    {
        return $this->entityTypeId;
    }

    public function setEntityId(String $id): void
    {
        $this->legacyEntityTypeId = array_search(
            $this->entityTypeId = $id,
            LegacyEntityIdToStringIdMap::getInstance()->getLegacyToStringMap()
        );
        if (($block = $this->getBlock()) instanceof MonsterSpawner) $block->setLegacyEntityId($this->legacyEntityTypeId);
    }

    /**
     * @return int
     */
    public function getStack(): int {
        return $this->stack;
    }

    /**
     * @param int $stack
     */
    public function setStack(int $stack): void {
        $this->stack = $stack;
    }

    public function readSaveData(CompoundTag $nbt): void
    {
        $legacyIdTag = $nbt->getTag(self::TAG_LEGACY_ENTITY_TYPE_ID);
        if ($legacyIdTag instanceof IntTag) {
            $this->setLegacyEntityId($legacyIdTag->getValue());
        } else {
            $this->setEntityId($nbt->getString(self::TAG_ENTITY_TYPE_ID, ":"));
        }
        $this->spawnDelay = $nbt->getShort(self::TAG_SPAWN_DELAY, 200);
        $this->minSpawnDelay = $nbt->getShort(self::TAG_MIN_SPAWN_DELAY, 200);
        $this->maxSpawnDelay = $nbt->getShort(self::TAG_MAX_SPAWN_DELAY, 800);

        $this->requiredPlayerRange = $nbt->getShort(self::TAG_REQUIRED_PLAYER_RANGE, 16);
        $this->spawnRange = $nbt->getShort(self::TAG_SPAWN_RANGE, 5);

        if(!$nbt->getTag(self::TAG_STACK) === null) {
            $nbt->setInt(self::TAG_STACK, $this->stack);
        }
        $this->stack = $nbt->getInt(self::TAG_STACK, $this->stack);
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setString(self::TAG_ENTITY_TYPE_ID, $this->entityTypeId);
        $nbt->setShort(self::TAG_SPAWN_DELAY, $this->spawnDelay);
        $nbt->setShort(self::TAG_MIN_SPAWN_DELAY, $this->minSpawnDelay);
        $nbt->setShort(self::TAG_MAX_SPAWN_DELAY, $this->maxSpawnDelay);
        $nbt->setShort(self::TAG_SPAWN_RANGE, $this->spawnRange);
        $nbt->setShort(self::TAG_REQUIRED_PLAYER_RANGE, $this->requiredPlayerRange);
        $nbt->setInt(self::TAG_STACK, $this->getStack());
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt): void
    {
        $nbt->setString(self::TAG_ENTITY_TYPE_ID, $this->entityTypeId);
        $nbt->setInt(self::TAG_STACK, $this->getStack());
    }

    public function onUpdate(): void
    {
        if ($this->closed) {
            $this->handler->cancel();
            return;
        }
        $blockPos = $this->getPosition();
        $block =  $this->getBlock();
        if ($this->canUpdate()) {
            $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
            if($tile !== null) {
                $this->getPosition()->getWorld()->removeTile($tile);
            }
            if (--$this->spawnDelay <= 0 and $block instanceof MonsterSpawner) {
                $this->spawnDelay = mt_rand($this->minSpawnDelay, $this->maxSpawnDelay);
                $count = 4 + $this->getStack();
                $range = $this->spawnRange;
                $spawnPos = $blockPos->getWorld()->getSafeSpawn($blockPos->add(mt_rand(-$range, $range), 0, mt_rand(-$range, $range)));
                $nearest = $this->findNearestEntity($this->getEntityTypeId());
                if ($nearest !== null) {
                    $nearest->setStack($nearest->getStack() + $count);
                    return;
                }
                $nbt = (new CompoundTag())->setInt("stack", $count);
                (Nexus::getInstance()->getServerManager()->getEntityHandler()->getEntityFor($this->getEntityTypeId(), Location::fromObject($spawnPos, $blockPos->world), $nbt))->spawnToAll();
                $blockPos->getWorld()->addParticle($blockPos, new MobSpawnParticle(2, 2));
            }
        }
    }

    private function findNearestEntity(string $type): ?SpawnerEntity
    {
        $pos = $this->getBlock()->getPosition();
        foreach ($this->getBlock()->getPosition()->getWorld()->getNearbyEntities(new AxisAlignedBB(
            $pos->x - 25,
            $pos->y - 25,
            $pos->z - 25,
            $pos->x + 25,
            $pos->y + 25,
            $pos->z + 25
        )) as $entity) {
            if ($entity->isAlive() and ($entity instanceof SpawnerEntity)) {
                if ($entity::getNetworkTypeId() === $type) {
                    return $entity;
                }
            }
        }
        return null;
    }
}
