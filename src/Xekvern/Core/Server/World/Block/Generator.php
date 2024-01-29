<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\World\Block;

use Xekvern\Core\Nexus;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockToolType;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\GlazedTerracotta;
use pocketmine\block\tile\Container;    
use pocketmine\block\tile\Spawnable;
use pocketmine\block\tile\Tile;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\BlockTransaction;
use Xekvern\Core\Server\Item\Types\LuckyBlock;
use Xekvern\Core\Server\World\WorldHandler;

class Generator extends GlazedTerracotta {

    const AUTO = 0;

    const MINING = 1;

    /** @var Item */
    private $generatedItem;

    /** @var int */
    private $type;

    /**
     * Generator constructor.
     *
     * @param int $id
     * @param Item $generatedItem
     */
    public function __construct(int $id) {
        $idInfo = new BlockIdentifier($id, \Xekvern\Core\Server\World\Tile\Generator::class);
        $name = "Generator";
        $blockInfo = new BlockBreakInfo(1, BlockToolType::PICKAXE, 0, 1);
        $typeInfo = new BlockTypeInfo($blockInfo);
        parent::__construct($idInfo, $name, $typeInfo);
    }
    
    /**
     * @param Item $item
     * @param Player|null $player
     *
     * @return bool
     */
    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []): bool
    {
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if($tile !== null or $tile instanceof LuckyBlock) {
            return false;
        }
        if($this->getColor() === DyeColor::YELLOW()) {
            return false;
        }
        if(!$tile instanceof \Xekvern\Core\Server\World\Tile\Generator) {
            $tile = new \Xekvern\Core\Server\World\Tile\Generator($this->getPosition()->getWorld(), $this->getPosition());
            $this->getPosition()->getWorld()->addTile($tile);
            $bounds = $this->getCollisionBoxes();
            foreach ($bounds as $bound) {
                $bound->expandedCopy(5, 5, 5);
            }
            foreach(WorldHandler::getNearbyTiles($this->getPosition()->getWorld(), $bound) as $tile) {
                $block = $tile->getBlock();
                if($block instanceof Generator and $tile instanceof \Xekvern\Core\Server\World\Tile\Generator) {
                    $claim = Nexus::getInstance()->getPlayerManager()->getFactionHandler()->getClaimInPosition($tile->getPosition());
                    if($claim !== null) {
                        $chunk = $this->getPosition()->getWorld()->getChunk($claim->getChunkX(), $claim->getChunkZ());
                        if($chunk !== null) {
                            $claim->recalculateValue($chunk);
                        }
                    }
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param Item $item
     * @param Block $blockReplace
     * @param Block $blockClicked
     * @param int $face
     * @param Vector3 $clickVector
     * @param Player|null $player
     *
     * @return bool
     */
    public function place(BlockTransaction $transaction, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool {
        parent::place($transaction, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if($this->getColor() === DyeColor::YELLOW()) {
            return false;
        }
        if(!$tile instanceof \Xekvern\Core\Server\World\Tile\Generator) {
            $tileType = $this->idInfo->getTileClass();
            $oldTile = $this->position->getWorld()->getTile($this->position);
            if($oldTile !== null) {
                if($tileType === null or !($oldTile instanceof $tileType)) {
                    $oldTile->close();
                    $oldTile = null;
                } elseif ($oldTile instanceof Spawnable) {
                    $oldTile->setDirty(); //destroy old network cache
                }
            }
            if($oldTile === null and $tileType !== null) {
                /**
                 * @var Tile $tile
                 * @see Tile::__construct()
                 */
                $tile = new $tileType($this->position->getWorld(), $this->getPosition()->asVector3());
                if($tile === null and !$tile instanceof LuckyBlock) {
                    $this->getPosition()->getWorld()->addTile($tile);
                }
            }
            $tile = $this->position->getWorld()->getTile($this->position);
            $bounds = $this->getCollisionBoxes();
            foreach ($bounds as $bound) {
                $bound->expandedCopy(5, 5, 5);
            }
            foreach(WorldHandler::getNearbyTiles($this->getPosition()->getWorld(), $bound) as $tile) {
                $block = $tile->getBlock();
                if($block instanceof Generator and $tile instanceof \Xekvern\Core\Server\World\Tile\Generator) {
                    $claim = Nexus::getInstance()->getPlayerManager()->getFactionHandler()->getClaimInPosition($tile->getPosition());
                    if($claim !== null) {
                        $chunk = $this->getPosition()->getWorld()->getChunk($claim->getChunkX(), $claim->getChunkZ());
                        if($chunk !== null) {
                            $claim->recalculateValue($chunk);
                        }
                    }
                    return false;
                }
            }
        }
        return true;
    }

    public function onScheduledUpdate(): void {
        if($this->getColor()->equals(DyeColor::BLACK())) { return; }
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        $block = $this->getPosition()->getWorld()->getBlock($this->getPosition());
        $count = 1;
        if($tile instanceof \Xekvern\Core\Server\World\Tile\Generator) {
            $count = $tile->getStack();
        }
        if($block instanceof GlazedTerracotta and $this->getGeneratorType($block->getColor()) === \Xekvern\Core\Server\World\Block\Generator::AUTO) {
            $vector = $this->getSide(Facing::DOWN);
            $tile = $this->getPosition()->getWorld()->getTile($vector->getPosition());
            if($tile instanceof Container) {
                $inventory = $tile->getInventory();
                $item = $this->getGeneratorOreByTypeToItem($this->getColor())->setCount(1 + (int)(ceil(round($count / 4)))); // 16 count per ticks
                if($inventory->canAddItem($item)) {
                    $inventory->addItem($item);
                }
            } 
            $this->getPosition()->getWorld()->scheduleDelayedBlockUpdate($this->getPosition(), (int)(130 - round($count/2)));
        } elseif($block instanceof GlazedTerracotta and $this->getGeneratorType($block->getColor()) === \Xekvern\Core\Server\World\Block\Generator::MINING) {
            if($this->getPosition()->getWorld()->getBlock($this->getPosition()->add(0, 1, 0))->getTypeId() === VanillaBlocks::AIR()->getTypeId() && $this->getPosition()->getY() < 255) {
                $this->getPosition()->getWorld()->setBlock($this->getPosition()->add(0, 1, 0), $this->getGeneratorOreByType($block->getColor()));
            }
            $this->getPosition()->getWorld()->scheduleDelayedBlockUpdate($this->getPosition(), (int)round(130 - (int)round($count * 2)));
        }
    }

    /**
     * @param Item $item
     * @param Player|null $player
     *
     * @return bool
     */
    public function onBreak(Item $item, ?Player $player = null, array &$returnedItems = []): bool {
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        if($tile !== null) {
            $this->getPosition()->getWorld()->removeTile($tile);
        }
        return parent::onBreak($item, $player, $returnedItems);
    }

    /**
     * @return int
     */
    public function getXpDropAmount(): int {
        return 0;
    }

    /**
     * @param Item $item
     *
     * @return Item[]
     */
    public function getDrops(Item $item): array {
        if($this->getColor()->equals(DyeColor::BLACK())) { return []; }
        $tile = $this->getPosition()->getWorld()->getTile($this->getPosition());
        $count = 1;
        if($tile instanceof \Xekvern\Core\Server\World\Tile\Generator) {
            $count = $tile->getStack();
        }
        $drop = $this->asItem()->setCount($count); 
        $drop->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . $this->getGeneratorOreByType($this->getColor())->getName() . " Generator");
        $lore = [];
        $lore[] = "";
        if($this->getGeneratorType($this->getColor()) === self::AUTO) {
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "Place a " . TextFormat::RED . TextFormat::BOLD . "chest" . TextFormat::RESET . TextFormat::WHITE . " below generator to collect items.";
        }
        else {
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "Place to create an ore above the generator.";
        }
        $drop->setLore($lore);
        return [$drop];
    }

    /**
     * @return Item
     */
    public function getGeneratedItem(): Item {
        return $this->generatedItem;
    }

    /**
     * @param DyeColor $color
     * 
     * @return int
     */
    public function getGeneratorType(DyeColor $color) : int {
        switch($color) {
            //-- Mining
            case DyeColor::BROWN(): // Coal
                return self::MINING;
                break;
            case DyeColor::CYAN(): // Lapis
                return self::MINING;
                break;
            case DyeColor::LIGHT_GRAY(): // Iron
                return self::MINING;
                break;
            case DyeColor::PINK(): // Diamond
                return self::MINING;
                break;
            case DyeColor::PURPLE(): // Amethyst
                return self::MINING;
                break;
            case DyeColor::LIME(): // Emerald
                return self::MINING;
                break;
            //-- Auto
            case DyeColor::BLUE(): // Coal
                return self::AUTO;
                break;
            case DyeColor::LIGHT_BLUE(): // Redstone
                return self::AUTO;
                break;
            case DyeColor::GRAY(): // Iron
                return self::AUTO;
                break;
            case DyeColor::MAGENTA(): // Diamond
                return self::AUTO;
                break;
            case DyeColor::GREEN(): // Emerald
                return self::AUTO;
                break;
            case DyeColor::BLACK(): // To fix terracotta luckyblock
                return self::MINING;
                break;
            default:
                return self::MINING;
                break;
        }
    }
    /**
     * @param BlockTypeIds $id
     * 
     * @return Block
     */
    public function getGeneratorOreByType(DyeColor $color) : Block {
        switch($color) {
            case DyeColor::BROWN():
                return VanillaBlocks::COAL_ORE();
                break;
            case DyeColor::CYAN():
                return VanillaBlocks::LAPIS_LAZULI_ORE();
                break;
            case DyeColor::LIGHT_GRAY():
                return VanillaBlocks::IRON_ORE();
                break;
            case DyeColor::PINK():
                return VanillaBlocks::DIAMOND_ORE();
                break;
            case DyeColor::PURPLE():
                return VanillaBlocks::AMETHYST();
                break;
            case DyeColor::LIME():
                return VanillaBlocks::EMERALD_ORE();
                break;

            case DyeColor::BLUE(): // Coal
                return VanillaBlocks::COAL();
                break;
            case DyeColor::LIGHT_BLUE(): // Redstone
                return VanillaBlocks::REDSTONE();
                break;
             case DyeColor::GRAY(): // Iron
                return VanillaBlocks::IRON();
                break;
            case DyeColor::MAGENTA(): // Diamond
                return VanillaBlocks::DIAMOND();
                break;
            case DyeColor::GREEN(): // Emerald
                return VanillaBlocks::EMERALD();
                break;
            default:
                return VanillaBlocks::AIR();
                break;
        }
    }
    /**
     * @param DyeColor $color
     * 
     * @return string
     */
    public function getGeneratorOreByTypeColorName(DyeColor $color) : string {
        switch($color) {
            case DyeColor::BROWN():
                return TextFormat::BOLD . TextFormat::BLACK . "Coal";
                break;
            case DyeColor::CYAN():
                return TextFormat::BOLD . TextFormat::DARK_BLUE . "Lapis Lazuli";
                break;
            case DyeColor::LIGHT_GRAY():
                return TextFormat::BOLD . TextFormat::WHITE . "Iron";
                break;
            case DyeColor::PINK():
                return TextFormat::BOLD . TextFormat::BLUE . "Diamond";
                break;
            case DyeColor::PURPLE():
                return TextFormat::BOLD . TextFormat::DARK_PURPLE . "Amethyst";
                break;
            case DyeColor::LIME():
                return TextFormat::BOLD . TextFormat::GREEN . "Emerald";
                break;
            case DyeColor::BLUE():
                return TextFormat::BOLD . TextFormat::BLACK . "Coal";
                break;
            case DyeColor::LIGHT_BLUE():
                return TextFormat::BOLD . TextFormat::RED . "Redstone Dust";
                break;
            case DyeColor::GRAY():
                return TextFormat::BOLD . TextFormat::WHITE . "Iron";
                break;
            case DyeColor::MAGENTA():
                return TextFormat::BOLD . TextFormat::BLUE . "Diamond";
                break;
            case DyeColor::GREEN():
                return TextFormat::BOLD . TextFormat::GREEN . "Emerald";
                break;
            case DyeColor::BLACK():
                return "Air";
                break;
            default:
                return "Stone";
                break;
        }
    }

    /**
     * @param BlockTypeIds $id
     * 
     * @return Item
     */
    public function getGeneratorOreByTypeToItem(DyeColor $color) : Item {
        switch($color) {
            case DyeColor::BLUE(): //auto
                return VanillaItems::COAL();
                break;
            case DyeColor::LIGHT_BLUE():
                return VanillaItems::REDSTONE_DUST();
                break;
            case DyeColor::GRAY():
                return VanillaItems::IRON_INGOT();
                break;
            case DyeColor::MAGENTA():
                return VanillaItems::DIAMOND();
                break;
            case DyeColor::GREEN():
                return VanillaItems::EMERALD();
                break;
            case DyeColor::BROWN(): //manual
                return VanillaItems::COAL();
                break;
            case DyeColor::CYAN():
                return VanillaItems::LAPIS_LAZULI();
                break;
            case DyeColor::LIGHT_GRAY():
                return VanillaItems::IRON_INGOT();
                break;
            case DyeColor::PINK():
                return VanillaItems::DIAMOND();
                break;
            case DyeColor::PURPLE():
                return VanillaItems::AMETHYST_SHARD();
                break;
            case DyeColor::LIME():
                return VanillaItems::EMERALD();
                break;
            default:
                return VanillaBlocks::STONE()->asItem();
                break;
        }
    }

    public function getGeneratorXpPerBreakStat(DyeColor $color, int $stack) : int {
        switch($color) {
            case DyeColor::BROWN():
                return VanillaItems::COAL();
                break;
            case DyeColor::CYAN():
                return VanillaItems::LAPIS_LAZULI();
                break;
            case DyeColor::LIGHT_GRAY():
                return VanillaItems::IRON_INGOT();
                break;
            case DyeColor::PINK():
                return VanillaItems::DIAMOND();
                break;
            case DyeColor::PURPLE():
                return VanillaItems::AMETHYST_SHARD();
                break;
            case DyeColor::LIME():
                return VanillaItems::EMERALD();
                break;
            default:
                return 0;
                break;
        }
        return 0;
    }
    
}



















