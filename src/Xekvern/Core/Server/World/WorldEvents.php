<?php

declare(strict_types=1);

namespace Xekvern\Core\Server\World;

use Error;
use Exception;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\NexusException;
use Xekvern\Core\Translation\Translation;
use Xekvern\Core\Translation\TranslatonException;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Chest;
use pocketmine\block\GlazedTerracotta;
use pocketmine\block\tile\Chest as TileChest;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\FurnaceSmeltEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\Pickaxe;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\math\Facing;
use pocketmine\world\Position;
use Xekvern\Core\Server\World\Tile\Generator;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use Xekvern\Core\Server\Crate\Crate;
use Xekvern\Core\Server\Item\Enchantment\Enchantment;
use Xekvern\Core\Server\Item\Enchantment\Utils\EnchantmentIdentifiers;
use Xekvern\Core\Server\Item\ItemHandler;
use Xekvern\Core\Server\Item\Types\ChestKit;
use Xekvern\Core\Server\Item\Types\CrateKeyNote;
use Xekvern\Core\Server\Item\Types\EnchantmentBook;
use Xekvern\Core\Server\Item\Types\LuckyBlock;
use Xekvern\Core\Server\Item\Types\MoneyNote;
use Xekvern\Core\Server\Item\Types\SacredStone;
use Xekvern\Core\Server\Item\Types\SellWand;
use Xekvern\Core\Server\Item\Types\SellWandNote;
use Xekvern\Core\Server\Item\Types\TNTLauncher;
use Xekvern\Core\Server\Item\Types\XPNote;
use Xekvern\Core\Server\Item\Utils\CustomItem;
use Xekvern\Core\Server\Item\Utils\ExtraVanillaItems;
use Xekvern\Core\Server\World\Block\MonsterSpawner;
use Xekvern\Core\Server\World\Tile\MobSpawner;
use pocketmine\math\Vector3;
use Xekvern\Core\Server\Item\Types\EXPNote;
use Xekvern\Core\Server\Item\Types\Soul;

class WorldEvents implements Listener
{

    /** @var Nexus */
    private $core;

    private $lastInteractionTime = [];

    /**
     * WorldEvents constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core)
    {
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     *
     * @throws TranslatonException
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        if (!$player->isLoaded()) {
            return;
        }
        $block = $event->getBlock();
        if ($block->getTypeId() === VanillaBlocks::STONE()->getTypeId()) {
            if (mt_rand(1, 750) === mt_rand(1, 750)) {
                $item = new LuckyBlock(mt_rand(0, 100));
                $player->playDingSound();
                $player->getDataSession()->addXPProgress(mt_rand(50, 150));
                $player->getDataSession()->addLuckyBlocksMined();
                $player->sendTip(TextFormat::BOLD . TextFormat::YELLOW . "+ Lucky Block");
                if(!$player->getInventory()->canAddItem($item->getItemForm())) {
                    $player->getDataSession()->addToInbox($item->getItemForm());
                    $player->sendMessage(Translation::AQUA . "Your inventory is full your item has been added to your /inbox");
                    return;
                }
                $player->getInventory()->addItem($item->getItemForm());
            }
            if(mt_rand(1, 15000) === mt_rand(1, 17000)) {
                $event->cancel();
                $nbt = new TileChest($player->getWorld(), $block->getPosition());
                $items = [
                    VanillaBlocks::IRON()->asItem()->setCount(64),
                    VanillaBlocks::DIAMOND()->asItem()->setCount(32),
                    VanillaBlocks::GOLD()->asItem()->setCount(32),
                    VanillaBlocks::EMERALD()->asItem()->setCount(16),
                    VanillaItems::IRON_INGOT()->setCount(64),
                    VanillaItems::DIAMOND()->setCount(32),
                    VanillaItems::EMERALD()->setCount(32),
                    VanillaItems::GOLDEN_APPLE()->setCount(64),
                    (new XPNote(mt_rand(5000, 50000)))->getItemForm(),
                    (new EXPNote(mt_rand(1000, 3500)))->getItemForm(),
                    (new Soul())->getItemForm(),
                    (new EnchantmentBook(ItemHandler::getRandomEnchantment(), 100))->getItemForm(),
                    (new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Prince")))->getItemForm(),
                    (new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Spartan")))->getItemForm(),
                ];
                $inventory = $nbt->getRealInventory();
                for($x = 0; $x <= 26; $x++) {
                    if(rand(1, 3) == 2) {
                        $inventory->setItem($x, $items[array_rand($items)]);
                    }
                };
                $player->getWorld()->addTile($nbt);
                $block->getPosition()->getWorld()->setBlock($block->getPosition(), VanillaBlocks::CHEST());
                $player->playSound("mob.wither.spawn", 1, 1);
                $player->sendTip(TextFormat::BOLD . TextFormat::GOLD . "+ Treasure Chest");
                $player->sendTitle(TextFormat::BOLD . TextFormat::GOLD . "Treasure Chest", TextFormat::GRAY . "You have found a treasure chest!");
                Server::getInstance()->broadcastMessage(Translation::PURPLE . $player->getName() . TextFormat::AQUA . " discovered a " . TextFormat::BOLD . TextFormat::GOLD . "Treasure Chest" . TextFormat::RESET . TextFormat::AQUA. " while mining!");
            }
            if (mt_rand(1, 11000) === mt_rand(1, 12000)) {
                $item = new SacredStone();
                $player->playDingSound();
                $player->getDataSession()->addXPProgress(mt_rand(300, 750));
                $player->sendTip(TextFormat::BOLD . TextFormat::RED . "+ Sacred Stone");
                $player->sendTitle(TextFormat::BOLD . TextFormat::RED . "Sacred Stone", TextFormat::GRAY . "You have found a sacred stone!");
                Server::getInstance()->broadcastMessage(Translation::PURPLE . $player->getName() . TextFormat::AQUA . " discovered a " . TextFormat::BOLD . TextFormat::RED . "Sacred Stone" . TextFormat::RESET . TextFormat::AQUA. " while mining!");
                if(!$player->getInventory()->canAddItem($item->getItemForm())) {
                    $player->getDataSession()->addToInbox($item->getItemForm());
                    $player->sendMessage(Translation::AQUA . "Your inventory is full your item has been added to your /inbox");
                    return;
                }
                $player->getInventory()->addItem($item->getItemForm());
            }
        }
        $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
        if ($tile === null) {
            return;
        }
        if ($block instanceof GlazedTerracotta && $block->getColor()->equals(DyeColor::BLACK())) {
            $goodRewards = [
                function (NexusPlayer $player, Position $position): void {
                    $item = VanillaBlocks::TNT()->asItem()->setCount(16);
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = VanillaItems::GOLDEN_APPLE()->setCount(32);
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Subordinate")))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new MoneyNote(mt_rand(1000, 5000)))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new XPNote(mt_rand(1000, 10000)))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new SellWandNote(mt_rand(5, 10)))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new SellWand())->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new TNTLauncher(1, 15, "TNT", ["Short", "Mid", "Long"][array_rand(["Short", "Mid", "Long"])]))->getItemForm();
                    $position->world->dropItem($position, $item);
                },  
                function (NexusPlayer $player, Position $position): void {
                    $crate = Nexus::getInstance()->getServerManager()->getCrateHandler()->getCrate(Crate::ULTRA);
                    $item = (new CrateKeyNote($crate->getName(), 1, $player->getName()))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new EnchantmentBook(ItemHandler::getRandomEnchantment(), mt_rand(40, 80)))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new CustomItem(VanillaItems::DIAMOND_HELMET(), TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Lucky Helmet", [], [
                        new EnchantmentInstance((VanillaEnchantments::PROTECTION()), 8),
                        new EnchantmentInstance((VanillaEnchantments::UNBREAKING()), 3),
                    ]))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new CustomItem(VanillaItems::DIAMOND_CHESTPLATE(), TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Lucky Chestplate", [], [
                        new EnchantmentInstance((VanillaEnchantments::PROTECTION()), 8),
                        new EnchantmentInstance((VanillaEnchantments::UNBREAKING()), 3),
                    ]))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new CustomItem(VanillaItems::DIAMOND_LEGGINGS(), TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Lucky Leggings", [], [
                        new EnchantmentInstance((VanillaEnchantments::PROTECTION()), 8),
                        new EnchantmentInstance((VanillaEnchantments::UNBREAKING()), 3),
                    ]))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new CustomItem(VanillaItems::DIAMOND_BOOTS(), TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Lucky Boots", [], [
                        new EnchantmentInstance((VanillaEnchantments::PROTECTION()), 8),
                        new EnchantmentInstance((VanillaEnchantments::UNBREAKING()), 3),
                        new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(Enchantment::QUICKENING), 1),
                    ]))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
                function (NexusPlayer $player, Position $position): void {
                    $item = (new CustomItem(VanillaItems::DIAMOND_PICKAXE(), TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Lucky Pickaxe", [], [
                        new EnchantmentInstance((VanillaEnchantments::EFFICIENCY()), 3),
                        new EnchantmentInstance((VanillaEnchantments::UNBREAKING()), 4),
                        new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(Enchantment::CHARM), 1),
                    ]))->getItemForm();
                    $position->world->dropItem($position, $item);
                },
            ];
            $badRewards = [
                function (NexusPlayer $player): void {
                    $player->teleport($player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                },
                function (NexusPlayer $player): void {
                    $player->setHealth(6);
                },
                function (NexusPlayer $player): void {
                    $player->getHungerManager()->setFood(0);
                },
                function (NexusPlayer $player): void {
                    $effects = [
                        new EffectInstance(VanillaEffects::POISON(), 600, 1),
                        new EffectInstance(VanillaEffects::BLINDNESS(), 200, 1)
                    ];
                    $player->getEffects()->add($effects[array_rand($effects)]);
                },
                function (NexusPlayer $player): void {
                    $effects = [
                        new EffectInstance(VanillaEffects::NIGHT_VISION(), 600, 1),
                        new EffectInstance(VanillaEffects::BLINDNESS(), 600, 1)
                    ];
                    foreach ($effects as $effect) {
                        $player->getEffects()->add($effect);
                    }
                }
            ];
            $item = $player->getInventory()->getItemInHand();
            $enchantment = EnchantmentIdMap::getInstance()->fromId(EnchantmentIdentifiers::CHARM);
            $add = $item->getEnchantmentLevel($enchantment) * 5;
            if ($tile instanceof \Xekvern\Core\Server\World\Tile\LuckyBlock) {
                $luck = $tile->getLuck() + $add;
                $block->getPosition()->getWorld()->removeTile($tile);
            } else {
                $luck = mt_rand(0, 100) + $add;
            }
            if (mt_rand(0, 100) <= $luck) {
                $reward = $goodRewards[array_rand($goodRewards)];
                $pk = new LevelSoundEventPacket();
                $pk->position = $player->getPosition();
                $pk->sound = LevelSoundEvent::BLAST;
            } else {
                $reward = $badRewards[array_rand($badRewards)];
                $pk = new LevelSoundEventPacket();
                $pk->position = $player->getEyePos();
                $pk->sound = LevelSoundEvent::RAID_HORN;
            }
            $player->getNetworkSession()->sendDataPacket($pk);
            $reward($player, $block->getPosition());
        }
    }

    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     *
     * @throws TranslatonException
     */
    public function onSpawnerBreak(BlockBreakEvent $event) {
        $item = $event->getItem();
        $block = $event->getBlock();
        $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
		if($event->isCancelled()) {
			return;
		}
        if(!$tile instanceof MobSpawner or !$item instanceof Pickaxe) {
			return;
		}
        if($tile !== null) {
            $block->getPosition()->getWorld()->removeTile($tile);
        }
		$event->setDrops([StringToItemParser::getInstance()->parse(Nexus::getInstance()->getServerManager()->getWorldHandler()->getSpawnerNameById($tile->getEntityTypeId()) . "_spawner")->setCount($tile->getStack()) ?? ExtraVanillaItems::MONSTER_SPAWNER()->setLegacyEntityId($tile->getLegacyEntityId())->asItem()->setCount($tile->getStack())]);
	}

    /** 
     * @priority HIGHEST
     * @param BlockPlaceEvent $event
     */
    public function onPlace(BlockPlaceEvent $event)
    {
        $item = $event->getItem();
        $block = $item->getBlock();
        if ($event->isCancelled())  {
            return;
        }
        if (!$item instanceof ItemBlock) {
            return;
        } 
        //if (!$block instanceof MonsterSpawner or $block instanceof \pocketmine\block\MonsterSpawner) {
            //return;
        //}
        //$transaction = $event->getTransaction();
        //foreach ($transaction->getBlocks() as [$x, $y, $z, $blocks]) {
            //$transaction->addBlock($blocks->getPosition(), ExtraVanillaItems::MONSTER_SPAWNER()->setLegacyEntityId(ExtraVanillaItems::getSpawnerEntityId($item)));
        //}
    }

    /** 
     * @priority HIGHEST
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($block->getTypeId() === BlockTypeIds::ENDER_CHEST) {
            $event->cancel();
            $player->sendMessage(Translation::RED . "Ender chests are disabled! An alternative is /pv!");
            return;
        }
        $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
        $item = $event->getItem();
        if ($tile === null) {
            return;
        }
        if ($tile instanceof MobSpawner) {
            if ($block->asItem()->getTypeId() === $item->getTypeId() and $tile->getStack() < 64) {
                $stack = $tile->getStack();
                $add = 1;
                if ($player->isSneaking()) {
                    $add = $item->getCount();
                }
                if ($add + $stack > 64) {
                    $add = 64 - $stack;
                }
                if ($tile->getStack() < 64) {
                    $stack += $add;
                    $tile->setStack($stack);
                    $player->getInventory()->setItemInHand($item->setCount($item->getCount() - $add));
                    $event->cancel();
                }
                $player->sendTip(TextFormat::RED . TextFormat::BOLD . "STACKED: " . $stack . "/64");
            } else {
                $stack = $tile->getStack();
                $player->sendTip(TextFormat::RED . TextFormat::BOLD . "STACKED: " . $stack . "/64");
            }
        }
        if ($tile instanceof Generator) {
            if ($block instanceof GlazedTerracotta && $block->asItem()->getStateId() === $item->getStateId() and $tile->getStack() < 64) {
                $stack = $tile->getStack();
                $add = 1;
                if ($player->isSneaking()) {
                    $add = $item->getCount();
                }
                if ($add + $stack > 64) {
                    $add = 64 - $stack;
                }
                if ($tile->getStack() < 64) {
                    $stack += $add;
                    $tile->setStack($stack);
                    $player->getInventory()->setItemInHand($item->setCount($item->getCount() - $add));
                    $event->cancel();
                }
                $player->sendTip(TextFormat::RED . TextFormat::BOLD . "STACKED: " . $stack . "/64");
            } else {
                $stack = $tile->getStack();
                $player->sendTip(TextFormat::RED . TextFormat::BOLD . "STACKED: " . $stack . "/64");
            }
        }
        if ($tile instanceof Generator or $tile instanceof MobSpawner) {
            if ($event->isCancelled()) {
                return;
            }
            $claim = Nexus::getInstance()->getPlayerManager()->getFactionHandler()->getClaimInPosition($tile->getPosition());
            $level = $tile->getPosition()->getWorld();
            if ($level === null or $claim === null) {
                return;
            }
            $chunk = $level->getChunk($claim->getChunkX(), $claim->getChunkZ());
            if ($chunk !== null) {
                $claim->recalculateValue($chunk);
            }
        }
        try {
            if ($tile instanceof TileChest) {
                if ($block->getSide(Facing::UP)->isTransparent() || !$tile->canOpenWith($item->getCustomName()) || $player->isSneaking()) {
                    return;
                }
                $aboveBlock = $block->getSide(Facing::UP)->getPosition()->getWorld()->getTile($block->getSide(Facing::UP)->getPosition());
                if ($aboveBlock instanceof Generator) {
                    $player->setCurrentWindow($tile->getInventory());
                    return;
                }
            }
        } catch (Exception $exception) {
        }
    }

    /**
     * @param EntityTeleport $event
     * 
     * @throws NexusException
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof NexusPlayer) {
            return;
        }
        if ($entity->getWorld()->getDisplayName() == $entity->getServer()->getWorldManager()->getDefaultWorld()->getDisplayName()) {
            foreach ($entity->getFloatingTexts() as $floatingText) {
                $floatingText->spawn($entity);
            }
        }
    }

    /**
     * @priority LOWEST
     * @param FurnaceSmeltEvent $event
     */
    public function onFurnaceSmelt(FurnaceSmeltEvent $event): void
    {
        $block = $event->getResult();
        if ($block >= VanillaBlocks::GLAZED_TERRACOTTA()->getColor()->equals(DyeColor::PURPLE()) and $block >= VanillaBlocks::GLAZED_TERRACOTTA()->getColor()->equals(DyeColor::BLACK())) {
            $event->cancel();
        }
    }
}
