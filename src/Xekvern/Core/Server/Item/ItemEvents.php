<?php

declare(strict_types=1);

namespace Xekvern\Core\Server\Item;

use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\TranslatonException;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Container;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\enchantment\Rarity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\Position;
use Xekvern\Core\Server\Entity\Types\Creeper;
use Xekvern\Core\Server\Item\Enchantment\Enchantment;
use Xekvern\Core\Server\Item\Types\CreeperEgg;
use Xekvern\Core\Server\Item\Types\EnchantmentBook;
use Xekvern\Core\Server\Item\Types\EnchantmentCrystal;
use Xekvern\Core\Server\Item\Types\LuckyBlock;
use Xekvern\Core\Server\Item\Types\Soul;
use Xekvern\Core\Server\Item\Types\Vanilla\CreeperSpawnEgg;
use Xekvern\Core\Server\Item\Utils\CustomItem;
use Xekvern\Core\Server\Item\Utils\ExtraVanillaItems;
use Xekvern\Core\Translation\Translation;
use pocketmine\color\Color;
use pocketmine\entity\object\FallingBlock;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\world\sound\AnvilBreakSound;
use Xekvern\Core\Player\Faction\Faction;
use Xekvern\Core\Server\Entity\Types\PrimedTNT;
use Xekvern\Core\Server\Item\Types\SellWand;
use Xekvern\Core\Server\Item\Types\SpongeLauncher;
use Xekvern\Core\Server\Item\Types\TNTLauncher;
use Xekvern\Core\Server\Item\Types\WaterCannon;
use Xekvern\Core\Server\Price\Event\ItemSellEvent;

class ItemEvents implements Listener
{

    /** @var Nexus */
    private $core;

    /** @var int */
    private $itemCooldowns = [];

    /**
     * ItemEvents constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core)
    {
        $this->core = $core;
    }

    /**
     * @priority LOWEST
     * @param PlayerChatEvent $event
     */
    public function onPlayerChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        $item = $player->getInventory()->getItemInHand();
        $name = TextFormat::RESET . TextFormat::WHITE . $item->getName();
        if ($item->hasCustomName()) {
            $name = $item->getCustomName();
        }
        $replace = TextFormat::DARK_GRAY . "[" . $name . TextFormat::RESET . TextFormat::GRAY . " * " . TextFormat::WHITE . $item->getCount() . TextFormat::DARK_GRAY . "]" . TextFormat::RESET;
        $message = $event->getMessage();
        $message = str_replace("[item]", $replace, $message);
        $event->setMessage($message);
    }


    /**
     * @priority HIGHEST
     * @param PlayerInteractEvent $event
     *
     * @throws TranslatonException
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $item = $event->getItem();
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        $inventory = $player->getInventory();
        $tag = $item->getNamedTag();
        if ($item->getTypeId() === ExtraVanillaItems::CREEPER_SPAWN_EGG()->getTypeId()) {
            $position = $player->getPosition();
            $area = $this->core->getServerManager()->getAreaHandler()->getAreaByPosition($position);
            if ($this->core->isInGracePeriod()) {
                $event->cancel();
                $player->sendTitle(TextFormat::BOLD . TextFormat::RED . "Grace Period", TextFormat::GRAY . "You can't do this action while on grace period!");
                $player->sendMessage(Translation::RED . "You can't use this while the server is on grace period!");
                $player->playErrorSound();
                return;
            }
            if ($area !== null) {
                $event->cancel();
                $player->sendMessage(Translation::RED . "You can only use a creeper egg in the wilderness!");
                return;
            }
        }
        if ($block->getTypeId() === BlockTypeIds::ENCHANTING_TABLE) {
            $event->cancel();
            if ($player->getInventory()->getSize() === count($player->getInventory()->getContents())) {
                $player->sendMessage(Translation::getMessage("fullInventory"));
                return;
            }
            if ($player->getXpManager()->getCurrentTotalXp() < 10000) {
                $player->sendMessage(TextFormat::BOLD . TextFormat::RED . " >> " . TextFormat::RESET . TextFormat::GRAY . "You don't have enough levels! You Need 10,000 xp");
                return;
            }
            if (mt_rand(1, 35) === mt_rand(1, 35)) {
                $enchantment = ItemHandler::getRandomEnchantment(Enchantment::RARITY_GODLY);
                if($enchantment === ItemHandler::getEnchantment(50)) {
                    $enchantment = ItemHandler::getRandomEnchantment(Enchantment::RARITY_GODLY);
                }
                if (mt_rand(1, 3) === mt_rand(1, 3)) {
                    $item = (new EnchantmentBook($enchantment, mt_rand(60, 100)))->getItemForm();
                } else {
                    $item = (new EnchantmentCrystal($enchantment))->getItemForm();
                }
            } else {
                switch (mt_rand(1, 7)) {
                    case 1:
                    case 2:
                        $enchantment = ItemHandler::getRandomEnchantment(Rarity::COMMON);
                        if($enchantment === ItemHandler::getEnchantment(50)) {
                            $enchantment = ItemHandler::getRandomEnchantment(Rarity::UNCOMMON);
                        }
                        if (mt_rand(1, 3) === mt_rand(1, 3)) {
                            $item = (new EnchantmentBook($enchantment, mt_rand(60, 100)))->getItemForm();
                        } else {
                            $item = (new EnchantmentCrystal($enchantment))->getItemForm();
                        }
                        break;
                    case 3:
                    case 4:
                        $enchantment = ItemHandler::getRandomEnchantment(Rarity::UNCOMMON);
                        if (mt_rand(1, 3) === mt_rand(1, 3)) {
                            $item = (new EnchantmentBook($enchantment, mt_rand(60, 100)))->getItemForm();
                        } else {
                            $item = (new EnchantmentCrystal($enchantment))->getItemForm();
                        }
                        break;
                    case 5:
                    case 6:
                        $enchantment = ItemHandler::getRandomEnchantment(Rarity::UNCOMMON);
                        if (mt_rand(1, 3) === mt_rand(1, 3)) {
                            $item = (new EnchantmentBook($enchantment, mt_rand(60, 100)))->getItemForm();
                        } else {
                            $item = (new EnchantmentCrystal($enchantment))->getItemForm();
                        }
                        break;
                    default:
                        $enchantment = ItemHandler::getRandomEnchantment(Rarity::MYTHIC);
                        $item = (new EnchantmentBook($enchantment, mt_rand(60, 100)))->getItemForm();
                        break;
                }
            }

            $level = $player->getWorld();
            $player->getXpManager()->subtractXp(10000);
            $player->sendMessage(Translation::getMessage("buy", [
                "amount" => TextFormat::GREEN . "x1",
                "item" => TextFormat::DARK_GREEN . $item->getCustomName(),
                "price" => TextFormat::LIGHT_PURPLE . "10,000 XP",
            ]));
            if ($level !== null) {
                for ($i = 0; $i <= 5; $i++) {
                    $x = sin($i);
                    $z = cos($i);
                    $particle = new DustParticle(new Color(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
                    $level->addParticle($block->getPosition(), $particle);
                }
            }
            $player->getInventory()->addItem($item);
            return;
        }
        if ($tag === null) {
            return;
        }
        if ($tag instanceof CompoundTag) {
            if (isset($this->itemCooldowns[$player->getUniqueId()->toString()]) and (time() - $this->itemCooldowns[$player->getUniqueId()->toString()]) < 1) {
                $event->cancel();
                return;
            }
            if (!$tag->getTag(Soul::SOUL) === null) {
                $event->cancel();
                return;
            }
            if (!$tag->getTag(EnchantmentBook::ENCHANT) === null) {
                $event->cancel();
                return;
            }
            if ($tag->getTag(CustomItem::ITEM_CLASS) === null) {
                $tag->setString(CustomItem::ITEM_CLASS, CustomItem::ITEM_CLASS);
            }
            $matchedItem = $this->core->getServerManager()->getItemHandler()->matchItem($tag);
            if ($matchedItem !== null) {
                $event->cancel();
                call_user_func($matchedItem . '::execute', $player, $inventory, $item, $tag, $event->getFace(), $event->getBlock());
                $this->itemCooldowns[$player->getUniqueId()->toString()] = time();
            } else {
                if (isset($tag->getValue()[LuckyBlock::LUCK])) {
                    $event->cancel();
                    $world = $player->getWorld();
                    if ($world === null) {
                        return;
                    }
                    if ($world->getDisplayName() !== Faction::CLAIM_WORLD) {
                        $player->sendMessage(Translation::getMessage("notInClaimWorld"));
                        return;
                    }
                    $position = $player->getPosition();
                    $area = $this->core->getServerManager()->getAreaHandler()->getAreaByPosition($position);
                    if ($area !== null) {
                        $player->sendMessage(Translation::RED . "You can only use this in the wilderness!");
                        return;
                    }
                    $luck = $tag->getInt(LuckyBlock::LUCK);
                    $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
                    $position = Position::fromObject($event->getBlock()->getPosition()->add(0, 1, 0), $player->getWorld());
                    if (!$tile instanceof \Xekvern\Core\Server\World\Tile\LuckyBlock) {
                        if ($block->getTypeId() !== BlockTypeIds::AIR && $block->getPosition()->getY() < 255) {
                            $position = Position::fromObject($event->getBlock()->getPosition()->add(0, 1, 0), $player->getWorld());
                            if ($player->getWorld()->getBlock($position)->getTypeId() === BlockTypeIds::AIR) {
                                $inventory->setItemInHand($item->setCount($item->getCount() - 1));
                                $position->getWorld()->setBlock($position, VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::BLACK()));
                            }
                        }
                        $tile = new \Xekvern\Core\Server\World\Tile\LuckyBlock($block->getPosition()->getWorld(), $position);
                        $tile->setLuck($luck);
                        if (!$block->getPosition()->getWorld()->getTile($tile->getPosition())) {
                            $block->getPosition()->getWorld()->addTile($tile);
                        }
                    }
                }
                if (isset($tag->getValue()[SellWand::SELL_WAND])) {
                    if ($player->getDataSession()->getSellWandUses() <= 0) {
                        $player->sendMessage(Translation::getMessage("noSellWandUses"));
                        return;
                    }
                    if ($event->isCancelled()) {
                        $player->sendMessage(Translation::getMessage("blockProtected"));
                        return;
                    }
                    $block = $event->getBlock();
                    $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
                    if (!$tile instanceof Container) {
                        $player->sendMessage(Translation::getMessage("invalidBlock"));
                        return;
                    }
                    if ($block instanceof Chest) {
                        $player->getWorld()->scheduleDelayedBlockUpdate($block->getPosition(), 1);
                    }
                    $content = $tile->getInventory()->getContents();
                    /** @var Item[] $items */
                    $items = [];
                    $sellable = false;
                    $sellables = $this->core->getServerManager()->getPriceHandler()->getSellables();
                    $entries = [];
                    foreach ($content as $i) {
                        if (!isset($sellables[$i->getTypeId()])) {
                            continue;
                        }
                        $entry = $sellables[$i->getTypeId()];
                        if (!$entry->equal($i)) {
                            continue;
                        }
                        if ($sellable === false) {
                            $sellable = true;
                        }
                        if (!isset($entries[$entry->getName()])) {
                            $entries[$entry->getName()] = $entry;
                            $items[$entry->getName()] = $i;
                        } else {
                            $items[$entry->getName()]->setCount($items[$entry->getName()]->getCount() + $i->getCount());
                        }
                    }
                    if ($sellable === false) {
                        $event->cancel();
                        return;
                    }
                    $price = 0;
                    foreach ($entries as $entry) {
                        $i = $items[$entry->getName()];
                        $price += $i->getCount() * $entry->getSellPrice();
                        $tile->getInventory()->removeItem($i);
                        $ev = new ItemSellEvent($player, $i, $price);
                        $ev->call();
                        $player->sendMessage(Translation::getMessage("sell", [
                            "amount" => TextFormat::GREEN . number_format($i->getCount()),
                            "item" => TextFormat::DARK_GREEN . $entry->getName(),
                            "price" => TextFormat::LIGHT_PURPLE . "$" . number_format((int)$i->getCount() * $entry->getSellPrice())
                        ]));
                    }
                    $player->getDataSession()->addToBalance($price);
                    $player->playXpLevelUpSound();
                    $player->getDataSession()->subtractFromSellWandUses(1);
                    $event->cancel();
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerItemUseEvent $event
     *
     * @throws UtilsException
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        $item = $event->getItem();
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        $inventory = $player->getInventory();
        $level = $player->getWorld();
        $tag = $item->getNamedTag();
        if ($tag === null) {
            return;
        }
        if ($tag instanceof CompoundTag) {
            if (isset($this->itemCooldowns[$player->getUniqueId()->toString()]) and (time() - $this->itemCooldowns[$player->getUniqueId()->toString()]) < 1) {
                $event->cancel();
                return;
            }
            $matchedItem = $this->core->getServerManager()->getItemHandler()->matchItem($tag);
            if ($matchedItem !== null) {
                $event->cancel();
                $this->itemCooldowns[$player->getUniqueId()->toString()] = time();
            } else {
                if (isset($tag->getValue()[TNTLauncher::USES]) and isset($tag->getValue()[TNTLauncher::TIER]) and isset($tag->getValue()[TNTLauncher::TYPE])) {
                    $level = $player->getWorld();
                    if ($this->core->isInGracePeriod()) {
                        $player->sendTitle(TextFormat::BOLD . TextFormat::RED . "Grace Period", TextFormat::GRAY . "You can't do this action while on grace period!");
                        $player->sendMessage(Translation::RED . "You can't use this while the server is on grace period!");
                        $player->playErrorSound();
                        return;
                    }
                    if ($level === null) {
                        return;
                    }
                    if ($level->getDisplayName() !== Faction::CLAIM_WORLD) {
                        $player->sendMessage(Translation::getMessage("notInClaimWorld"));
                        return;
                    }
                    $position = $player->getPosition();
                    $area = $this->core->getServerManager()->getAreaHandler()->getAreaByPosition($position);
                    if ($area !== null) {
                        $player->sendMessage(Translation::RED . "You can only use Launchers in the wilderness!");
                        return;
                    }
                    if ($this->core->getPlayerManager()->getFactionHandler()->getClaimInPosition($player->getPosition()) !== null) {
                        $player->sendMessage(Translation::getMessage("inClaim"));
                        return;
                    }
                    $amount = $tag->getInt(TNTLauncher::USES);
                    $tier = $tag->getInt(TNTLauncher::TIER);
                    $range = $tag->getString(TNTLauncher::RANGE);
                    $fuelAmount = ItemHandler::getFuelAmountByTier($tier);
                    if ($inventory->contains(VanillaBlocks::TNT()->asItem()->setCount($fuelAmount)) === false) {
                        $player->sendMessage(Translation::getMessage("notEnoughFuel"));
                        return;
                    }
                    $directionVector = $player->getDirectionVector();
                    $nbt = new CompoundTag();
                    $nbt->setShort("Force", $fuelAmount);
                    $entity = new PrimedTNT($player->getLocation(), $nbt);
                    $multiplicationFactor = match ($range) {
                        "Short" => 1.5,
                        "Mid" => 3,
                        "Long" => 6,
                        default => 3,
                    };
                    $entity->setMotion($entity->getDirectionVector()->normalize()->multiply($multiplicationFactor));
                    $entity->spawnToAll();
                    --$amount;
                    if ($amount <= 0) {
                        $player->getWorld()->addSound($player->getEyePos(), new AnvilBreakSound());
                        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
                    } else {
                        $tag->setInt(TNTLauncher::USES, $amount);
                        $lore = [];
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Uses: " . TextFormat::WHITE . number_format((int)$amount);
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Tier: " . TextFormat::WHITE . $tier;
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Range: " . TextFormat::WHITE . $range;
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Each fire will require " . TextFormat::YELLOW . ItemHandler::getFuelAmountByTier($tier) . TextFormat::WHITE . " TNT.";
                        $item->setLore($lore);
                        $inventory->setItemInHand($item);
                    }
                    $event->cancel();
                    $inventory->removeItem(VanillaBlocks::TNT()->asItem()->setCount($fuelAmount));
                } else if (isset($tag->getValue()[SpongeLauncher::USES]) and isset($tag->getValue()[SpongeLauncher::TIER]) and isset($tag->getValue()[SpongeLauncher::TYPE])) {
                    $level = $player->getWorld();
                    if ($this->core->isInGracePeriod()) {
                        $player->sendTitle(TextFormat::BOLD . TextFormat::RED . "Grace Period", TextFormat::GRAY . "You can't do this action while on grace period!");
                        $player->sendMessage(Translation::RED . "You can't use this while the server is on grace period!");
                        $player->playErrorSound();
                        return;
                    }
                    if ($level === null) {
                        return;
                    }
                    if ($level->getDisplayName() !== Faction::CLAIM_WORLD) {
                        $player->sendMessage(Translation::getMessage("notInClaimWorld"));
                        return;
                    }
                    $position = $player->getPosition();
                    $area = $this->core->getServerManager()->getAreaHandler()->getAreaByPosition($position);
                    if ($area !== null) {
                        $player->sendMessage(Translation::RED . "You can only use Launchers in the wilderness!");
                        return;
                    }
                    if ($this->core->getPlayerManager()->getFactionHandler()->getClaimInPosition($player->getPosition()) !== null) {
                        $player->sendMessage(Translation::getMessage("inClaim"));
                        return;
                    }
                    $amount = $tag->getInt(SpongeLauncher::USES);
                    $tier = $tag->getInt(SpongeLauncher::TIER);
                    $range = $tag->getString(SpongeLauncher::RANGE);
                    $fuelAmount = ItemHandler::getSpongeFuelAmountByTier($tier);
                    if ($inventory->contains(VanillaBlocks::SPONGE()->asItem()->setCount($fuelAmount)) === false) {
                        $player->sendMessage(Translation::getMessage("notEnoughFuel"));
                        return;
                    }
                    $multiplicationFactor = match ($range) {
                        "Short" => 1.5,
                        "Mid" => 3,
                        "Long" => 6,
                        default => 3,
                    };
                    for ($i = 0; $i <= $fuelAmount; $i++) {
                        $verticalDeviation = random_int(-15, 30) / 100;
                        $horizontalDeviation = random_int(-15, 15) / 100;
                        $entity = new FallingBlock($player->getLocation(), VanillaBlocks::SPONGE());
                        $vector = $entity->getDirectionVector()->normalize()->multiply($multiplicationFactor);
                        $vector = $vector->add($horizontalDeviation, $verticalDeviation, 0);
                        $entity->setMotion($vector);
                        $entity->spawnToAll();
                    }
                    --$amount;
                    if ($amount <= 0) {
                        $player->getWorld()->addSound($player->getEyePos(), new AnvilBreakSound());
                        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
                    } else {
                        $tag->setInt(SpongeLauncher::USES, $amount);
                        $lore = [];
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Uses: " . TextFormat::WHITE . number_format((int)$amount);
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Tier: " . TextFormat::WHITE . $tier;
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Range: " . TextFormat::WHITE . $range;
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Each fire will require " . TextFormat::YELLOW . ItemHandler::getSpongeFuelAmountByTier($tier) . TextFormat::WHITE . " Sponges.";
                        $item->setLore($lore);
                        $inventory->setItemInHand($item);
                    }
                    $event->cancel();
                    $inventory->removeItem(VanillaBlocks::SPONGE()->asItem()->setCount($fuelAmount));
                } else if (isset($tag->getValue()[WaterCannon::USES]) and isset($tag->getValue()[WaterCannon::TIER]) and isset($tag->getValue()[WaterCannon::TYPE])) {
                    $level = $player->getWorld();
                    if ($this->core->isInGracePeriod()) {
                        $player->sendTitle(TextFormat::BOLD . TextFormat::RED . "Grace Period", TextFormat::GRAY . "You can't do this action while on grace period!");
                        $player->sendMessage(Translation::RED . "You can't use this while the server is on grace period!");
                        $player->playErrorSound();
                        return;
                    }
                    if ($level === null) {
                        return;
                    }
                    if ($level->getDisplayName() !== Faction::CLAIM_WORLD) {
                        $player->sendMessage(Translation::getMessage("notInClaimWorld"));
                        return;
                    }
                    $position = $player->getPosition();
                    $area = $this->core->getServerManager()->getAreaHandler()->getAreaByPosition($position);
                    if ($area !== null) {
                        $player->sendMessage(Translation::RED . "You can only use Launchers in the wilderness!");
                        return;
                    }
                    if ($this->core->getPlayerManager()->getFactionHandler()->getClaimInPosition($player->getPosition()) !== null) {
                        $player->sendMessage(Translation::getMessage("inClaim"));
                        return;
                    }
                    $amount = $tag->getInt(WaterCannon::USES);
                    $tier = $tag->getInt(WaterCannon::TIER);
                    $range = $tag->getString(WaterCannon::RANGE);
                    $fuelAmount = ItemHandler::getWaterFuelAmountByTier($tier);
                    if ($inventory->contains(VanillaBlocks::WATER()->getFlowingForm()->asItem()->setCount($fuelAmount)) === false) {
                        $player->sendMessage(Translation::getMessage("notEnoughFuel"));
                        return;
                    }
                    $multiplicationFactor = match ($range) {
                        "Short" => 1.5,
                        "Mid" => 3,
                        "Long" => 6,
                        default => 3,
                    };
                    for ($i = 0; $i <= $fuelAmount; $i++) {
                        $verticalDeviation = random_int(-15, 30) / 100;
                        $horizontalDeviation = random_int(-15, 15) / 100;
                        $entity = new FallingBlock($player->getLocation(), VanillaBlocks::WATER()->getFlowingForm());
                        $vector = $entity->getDirectionVector()->normalize()->multiply($multiplicationFactor);
                        $vector = $vector->add($horizontalDeviation, $verticalDeviation, 0);
                        $entity->setMotion($vector);
                        $entity->spawnToAll();
                    }
                    --$amount;
                    if ($amount <= 0) {
                        $player->getWorld()->addSound($player->getEyePos(), new AnvilBreakSound());
                        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
                    } else {
                        $tag->setInt(WaterCannon::USES, $amount);
                        $lore = [];
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Uses: " . TextFormat::WHITE . number_format((int)$amount);
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Tier: " . TextFormat::WHITE . $tier;
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Range: " . TextFormat::WHITE . $range;
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Each fire will require " . TextFormat::YELLOW . ItemHandler::getWaterFuelAmountByTier($tier) . TextFormat::WHITE . " Water.";
                        $item->setLore($lore);
                        $inventory->setItemInHand($item);
                    }
                    $event->cancel();
                    $inventory->removeItem(VanillaBlocks::WATER()->getFlowingForm()->asItem()->setCount($fuelAmount));
                }
            }
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerItemHeldEvent $event
     */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event)
    {
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        if (!$player->isLoaded()) {
            return;
        }
        $this->core->getScheduler()->scheduleDelayedTask(new class($player) extends Task
        {

            /** @var NexusPlayer */
            private $player;

            /**
             *  constructor.
             *
             * @param NexusPlayer $player
             */
            public function __construct(NexusPlayer $player)
            {
                $this->player = $player;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(): void
            {
                if ($this->player->isOnline() === false or (!$this->player->isLoaded())) {
                    return;
                }
                $this->player->getCESession()->setActiveHeldItemEnchantments();
            }
        }, 1);
    }

    /**
     * @priority HIGHEST
     */
    public function onEntityArmorChange(SlotChangeAction $action)
    {
        foreach ($action->getInventory()->getViewers() as $viewer) {
            $entity = $viewer;
        }
        if ($entity instanceof NexusPlayer) {
            $oldItem = $action->getSourceItem();
            $newItem = $action->getTargetItem();
            if ($oldItem->equals($newItem, false, true)) {
                return;
            }
            if ($entity->isLoaded()) {
                $this->core->getScheduler()->scheduleDelayedTask(new class($entity) extends Task
                {

                    /** @var NexusPlayer */
                    private $player;

                    /**
                     *  constructor.
                     *
                     * @param NexusPlayer $player
                     */
                    public function __construct(NexusPlayer $player)
                    {
                        $this->player = $player;
                    }

                    /**
                     * @param int $currentTick
                     */
                    public function onRun(): void
                    {
                        if ($this->player->isOnline() === false or (!$this->player->isLoaded())) {
                            return;
                        }
                        $this->player->getCESession()->setActiveArmorEnchantments();
                    }
                }, 1);
            }
        }
    }

    /**
     * @priority LOW
     * @param BlockFormEvent $event
     */
    public function onBlockForm(BlockFormEvent $event): void
    {
        $block = $event->getNewState();
        if ($block->getTypeId() === BlockTypeIds::OBSIDIAN) {
            return;
        }
        if ($block->getTypeId() === BlockTypeIds::COBBLESTONE) {
            $event->cancel();
        }
    }
}
