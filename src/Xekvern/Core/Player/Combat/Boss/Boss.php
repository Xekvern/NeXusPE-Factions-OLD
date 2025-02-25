<?php

namespace Xekvern\Core\Player\Combat\Boss;

use Xekvern\Core\Player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\block\Flowable;
use pocketmine\block\Liquid;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\world\World;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\Combat\Task\CombatTagTask;

abstract class Boss extends Human implements ArtificialIntelligence {

    const FIND_DISTANCE = 15;
    const LOSE_DISTANCE = 25;
    const ATTACK_DISTANCE = 5;

    /** @var int */
    public $attackDamage;

    /** @var float */
    public $speed;

    /** @var int */
    public $attackWait;

    /** @var int */
    public $regenerationWait = 0;

    /** @var int */
    public $regenerationRate;

    /** @var int[] */
    protected $damages = [];

    /** @var Player|null */
    private $target = null;

    /** @var int */
    private $findNewTargetTicks = 0;

    /** @var int */
    private $jumpTicks = 5;

    /**
     * BaseEntity constructor.
     *
	 * @param World $level
	 * @param CompoundTag $nbt
	 * @param Player $player
     */
    public function __construct(Location $location, ?Skin $skin = null, ?CompoundTag $nbt = null) {
		parent::__construct($location, $skin, $nbt);
    }

    /**
     * @return string
     */
    public function getSaveId(): string {
        return self::class;
    }

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool {
        parent::entityBaseTick($tickDiff);
        if(!$this->isAlive()) {
            if(!$this->closed) {
                $this->flagForDespawn();
            }
            return false;
        }
        if($this->hasTarget()) {
			$this->lookAt($this->getTarget()->getLocation()->asVector3());
			return $this->attackTarget();
		}
        if($this->regenerationWait-- <= 0) {
            $this->setHealth($this->getHealth() + $this->regenerationRate);
            $this->regenerationWait = 20;
        }
        if($this->hasTarget()) {
            return $this->attackTarget();
        }
        if($this->findNewTargetTicks > 0) {
            $this->findNewTargetTicks--;
        }
        if(!$this->hasTarget() and $this->findNewTargetTicks === 0) {
            $this->findNewTarget();
        }
        if($this->jumpTicks > 0) {
            $this->jumpTicks--;
        }
        if(!$this->isOnGround()) {
            if($this->motion->y > -$this->gravity * 4) {
                $this->motion->y = -$this->gravity * 4;
            }
            else {
                $this->motion->y += $this->isUnderwater() ? $this->gravity : -$this->gravity;
            }
        }
        else {
            $this->motion->y -= $this->gravity;
        }
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldJump()) {
            $this->jump();
        }
        $this->updateMovement();
        return $this->isAlive();
    }

    /**
     * @return bool
     */
    public function attackTarget(): bool {
        $target = $this->getTarget();
        if($target == null or $target->getPosition()->distance($this->getPosition()) >= self::LOSE_DISTANCE) {
            $this->target = null;
            return true;
        }
        if($this->jumpTicks > 0) {
            $this->jumpTicks--;
        }
        if(!$this->isOnGround()) {
            if($this->motion->y > -$this->gravity * 4) {
                $this->motion->y = -$this->gravity * 4;
            }
            else {
                $this->motion->y += $this->isUnderwater() ? $this->gravity : -$this->gravity;
            }
        }
        else {
            $this->motion->y -= $this->gravity;
        }
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldJump()) {
            $this->jump();
        }
        $x = $target->getPosition()->x - $this->getPosition()->x;
        $y = $target->getPosition()->y - $this->getPosition()->y;
        $z = $target->getPosition()->z - $this->getPosition()->z;
        if($x * $x + $z * $z < 1.2) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        }
        else {
            $this->motion->x = $this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
        }
        $this->yaw = rad2deg(atan2(-$x, $z));
        $this->pitch = rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldJump()) {
            $this->jump();
        }
        if($this->getPosition()->distance($target->getPosition()) <= self::ATTACK_DISTANCE and $this->attackWait <= 0) {
            if($target->isCreative() or $target->isSpectator()){
                $this->target = null;
                return $this->isAlive();
            }
            $points = $target->getArmorPoints() !== 0 ? $target->getArmorPoints() : 1;
            $damage = $this->getBaseAttackDamage() / ($points * 1.5);
            if($target->getHealth() <= $damage) {
                $this->target = null;
                $this->findNewTarget();
            }
            $target->setHealth($target->getHealth() - $damage);
            $target->doHitAnimation();
			$this->broadcastAnimation(new ArmSwingAnimation($this));
            $this->attackWait = 20;
        }
        $this->updateMovement();
        $this->attackWait--;
        return $this->isAlive();
    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source): void {
        if($source instanceof EntityDamageByEntityEvent) {
            $killer = $source->getDamager();
            if($killer instanceof Player) {
                /** @var NexusPlayer $killer */
                if ($killer->isTagged()) {
                    $killer->combatTag(true);
                    $killer->setLastHit("Boss");
                } else {
                    Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new CombatTagTask($killer), 20);
                    $killer->setCombatTagged(true);
                    $killer->setLastHit("Boss");
                }
                if($killer->isFlying() or $killer->getAllowFlight() == true) {
                    $killer->setFlying(false);
                    $killer->setAllowFlight(false);
                }
                if($killer->isSpectator()) {
					$source->cancel();
					return;
				}
                if($this->target === null or $this->target->getName() != $killer->getName() and !$killer->isCreative()) {
                    $this->target = $killer;
                }
                if(isset($this->damages[$killer->getName()])) {
                    $this->damages[$killer->getName()] += $source->getFinalDamage();
                }
                else {
                    $this->damages[$killer->getName()] = $source->getFinalDamage();
                }
            }
        }
        parent::attack($source);
    }

	public function knockBack(float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4): void {
      parent::knockBack($x,$z,$force,$verticalLimit);
	}

    public function findNewTarget() {
        $distance = self::FIND_DISTANCE;
        $target = null;
        foreach($this->getWorld()->getPlayers() as $player) {
            if($player instanceof self) {
                continue;
            }
            if($player instanceof NexusPlayer and $player->getPosition()->distance($this->getPosition()) <= $distance and (!$player->isCreative())) {
                $distance = $player->getPosition()->distance($this->getPosition());
                $target = $player;
            }
        }
        $this->findNewTargetTicks = 60;
        $this->target = ($target != null ? $target : null);
    }

    /**
     * @return bool
     */
    public function hasTarget(): bool {
        $target = $this->getTarget();
        if($target == null) {
            return false;
        }
        return true;
    }

    /**
     * @return Player|null
     */
    public function getTarget(): ?Player {
        return $this->target;
    }

    /**
     * @return float
     */
    public function getSpeed(): float {
        return ($this->isUnderwater() ? $this->speed / 2 : $this->speed);
    }

    /**
     * @return int
     */
    public function getBaseAttackDamage(): int {
        return $this->attackDamage;
    }

    /**
     * @param int $y
     *
     * @return Block
     */
    public function getFrontBlock($y = 0): Block {
        $dv = $this->getDirectionVector();
        $pos = $this->getPosition()->asVector3()->add($dv->x * $this->getScale(), $y + 1, $dv->z * $this->getScale())->round();
        return $this->getWorld()->getBlock($pos);
    }

    /**
     * @return bool
     */
    public function shouldJump(): bool {
        if($this->jumpTicks > 0) {
            return false;
        }
        return $this->isCollidedHorizontally or
            ($this->getFrontBlock()->getTypeId() != 0 or $this->getFrontBlock(-1) instanceof Stair) or
            ($this->getWorld()->getBlock($this->getPosition()->asVector3()->add(0, -0, 5)) instanceof Slab and
                (!$this->getFrontBlock(-0.5) instanceof Slab and $this->getFrontBlock(-0.5)->getTypeId() != 0)) and
            $this->getFrontBlock(1)->getTypeId() == 0 and
            $this->getFrontBlock(2)->getTypeId() == 0 and
            !$this->getFrontBlock() instanceof Flowable and
            $this->jumpTicks == 0;
    }

    /**
     * @return int
     */
    public function getJumpMultiplier(): int {
        if($this->getFrontBlock() instanceof Slab or $this->getFrontBlock() instanceof Stair or
            $this->getWorld()->getBlock($this->getPosition()->asVector3()->subtract(0, 0.5, 0)->round()) instanceof Slab and
            $this->getFrontBlock()->getTypeId() != 0) {
            $fb = $this->getFrontBlock();
            if($fb instanceof Slab and $fb->getStateId() & 0x08 > 0) {
                return 8;
            }
            if($fb instanceof Stair and $fb->getStateId() & 0x04 > 0) {
                return 8;
            }
            return 4;
        }
        return 16;
    }

    public function jump(): void {
        $this->motion->y = $this->gravity * $this->getJumpMultiplier();
        $this->move($this->motion->x * 1.25, $this->motion->y, $this->motion->z * 1.25);
        $this->jumpTicks = 5;
    }
}