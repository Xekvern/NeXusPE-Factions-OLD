<?php

declare(strict_types=1);

namespace Xekvern\Core\Server\World\Tile;

use pocketmine\block\tile\Spawnable;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class Generator extends Spawnable {

    /** @var string */
    public const TAG_STACK = "Stack";

    /** @var int */
    private $stack = 1;

    /**
     * Generator constructor.
     *
     * @param World $world
     * @param Vector3 $pos
     */
    public function __construct(World $level, Vector3 $position) {
        parent::__construct($level, $position);
    }

    /**
     * @return bool
     */
    public function onUpdate(): bool {
        return true;
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

    /**
     * @param CompoundTag $nbt
     */
    public function readSaveData(CompoundTag $nbt): void {
        if(!$nbt->getTag(self::TAG_STACK) === null) {
            $nbt->setInt(self::TAG_STACK, $this->stack);
        }
        $this->stack = $nbt->getInt(self::TAG_STACK, $this->stack);
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function writeSaveData(CompoundTag $nbt): void {
        $nbt->setInt(self::TAG_STACK, $this->getStack());
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function addAdditionalSpawnData(CompoundTag $nbt): void {
        $nbt->setInt(self::TAG_STACK, $this->getStack());
    }
}