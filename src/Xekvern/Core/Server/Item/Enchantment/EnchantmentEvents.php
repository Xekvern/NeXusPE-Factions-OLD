<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Enchantment;

use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Server\Item\Utils\ExtraVanillaItems;
use Xekvern\Core\Server\Item\Types\EnchantmentBook;
use Xekvern\Core\Server\Item\Types\EnchantmentCrystal;
use Xekvern\Core\Translation\Translation;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\AnvilBreakSound;
use pocketmine\world\sound\AnvilFallSound;
use pocketmine\world\sound\AnvilUseSound;
use pocketmine\world\sound\XpLevelUpSound;
use Xekvern\Core\Player\Combat\Boss\ArtificialIntelligence;
use Xekvern\Core\Server\Entity\EntityHandler;
use Xekvern\Core\Server\Item\Enchantment\Utils\EnchantmentIdentifiers;
use Xekvern\Core\Server\Item\ItemHandler;
use Xekvern\Core\Server\Item\Types\EnchantmentScroll;
use Xekvern\Core\Server\Item\Utils\CustomItem;

class EnchantmentEvents implements Listener {

    /** @var Nexus */
    private $core;

    /** @var int[] */
    private $lastAttack = [];

    /**
     * EnchantmentEvents constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event) {
        $entity = $event->getEntity();
        if(!$entity instanceof NexusPlayer) {
            return;
        }
        if($entity->isLoaded()) {
            if($event instanceof EntityDamageByEntityEvent) {
                if($event->isCancelled()) { // Should cancel events that handle @handleCancelled
                    return;
                }
                $damager = $event->getDamager();
                if(!$damager instanceof NexusPlayer) {
                    return;
                }
                if($damager instanceof NexusPlayer && $entity instanceof NexusPlayer) {
                    if($damager->getDataSession()->getFaction() !== null && 
                    $entity->getDataSession()->getFaction() !== null && 
                    $damager->getDataSession()->getFaction()->getName() == $entity->getDataSession()->getFaction()->getName()
                    ) {
                        return;
                    }
                    if($damager->getWorld()->getFolderName() === "bossarena" and (!$entity instanceof ArtificialIntelligence)) { // This is to fix boss CE activiation temporarily
                        return;
                    }
                }
                if($damager->isLoaded()) {
                    if(!$damager->isTagged()) {
                        return;
                    }
                    $damagerUuid = $damager->getUniqueId()->toString();
                    if(isset($this->lastAttack[$damagerUuid])) {
                        $ticks = $this->lastAttack[$damagerUuid];
                        if(($this->core->getServer()->getTick() - $ticks) < 10) {
                            $event->cancel();
                            return;
                        }
                        else {
                            $this->lastAttack[$damagerUuid] = $this->core->getServer()->getTick();
                        }
                    }
                    else {
                        $this->lastAttack[$damagerUuid] = $this->core->getServer()->getTick();
                    }
                    $enchantments = $damager->getCESession()->getActiveEnchantments();
                    if(!isset($enchantments[Enchantment::DAMAGE])) {
                        return;
                    }
                    /** @var EnchantmentInstance $enchantment */
                    foreach($enchantments[Enchantment::DAMAGE] as $enchantment) {
                        /** @var Enchantment $type */
                        $type = $enchantment->getType();
                        $callable = $type->getCallable();
                        $callable($event, $enchantment->getLevel());
                    }
                    $enchantments = $entity->getCESession()->getActiveEnchantments();
                    if(!isset($enchantments[Enchantment::DAMAGE_BY])) {
                        return;
                    }
                    /** @var EnchantmentInstance $enchantment */
                    foreach($enchantments[Enchantment::DAMAGE_BY] as $enchantment) {
                        /** @var Enchantment $type */
                        $type = $enchantment->getType();
                        $callable = $type->getCallable();
                        $callable($event, $enchantment->getLevel());
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage2(EntityDamageEvent $event) {
        if($event->isCancelled()) {
            return;
        }
        if($event instanceof EntityDamageByChildEntityEvent) {
            $event->setBaseDamage($event->getBaseDamage() * 0.65);
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityEffectAddEvent $event
     */
    public function onEntityEffectAdd(EntityEffectAddEvent $event) {
        $entity = $event->getEntity();
        if(!$entity instanceof NexusPlayer) {
            return;
        }
        if($event->isCancelled()) {
            return;
        }
        if($entity->isLoaded()) {
            $enchantments = $entity->getCESession()->getActiveEnchantments();
            if(!isset($enchantments[Enchantment::EFFECT_ADD])) {
                return;
            }
            /** @var EnchantmentInstance $enchantment */
            foreach($enchantments[Enchantment::EFFECT_ADD] as $enchantment) {
                /** @var Enchantment $type */
                $type = $enchantment->getType();
                $callable = $type->getCallable();
                $callable($event, $enchantment->getLevel());
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityShootBowEvent $event
     */
    public function onEntityShootBow(EntityShootBowEvent $event) {
        if($event->isCancelled()) {
            return;
        }
        $entity = $event->getEntity();
        if(!$entity instanceof NexusPlayer) {
            return;
        }
        if($entity->isLoaded()) {
            $enchantments = $entity->getCESession()->getActiveEnchantments();
            if(!isset($enchantments[Enchantment::SHOOT])) {
                return;
            }
            /** @var EnchantmentInstance $enchantment */
            foreach($enchantments[Enchantment::SHOOT] as $enchantment) {
                /** @var Enchantment $type */
                $type = $enchantment->getType();
                $callable = $type->getCallable();
                $callable($event, $enchantment->getLevel());
            }
        }
    }

    /**
     * @priority HIGH
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if($player instanceof NexusPlayer) {
                if($player->isLoaded()) {
                    $enchantments = $player->getCESession()->getActiveEnchantments();
                    if(isset($enchantments[Enchantment::DEATH])) {
                        /** @var EnchantmentInstance $enchantment */
                        foreach($enchantments[Enchantment::DEATH] as $enchantment) {
                            /** @var Enchantment $type */
                            $type = $enchantment->getType();
                            $callable = $type->getCallable();
                            $callable($event, $enchantment->getLevel());
                        }
                    }
                }
            }
            if($damager instanceof NexusPlayer) {
                if($damager->isLoaded()) {
                    $enchantments = $damager->getCESession()->getActiveEnchantments();
                    if(isset($enchantments[Enchantment::DEATH])) {
                        /** @var EnchantmentInstance $enchantment */
                        foreach($enchantments[Enchantment::DEATH] as $enchantment) {
                            /** @var Enchantment $type */
                            $type = $enchantment->getType();
                            $callable = $type->getCallable();
                            $callable($event, $enchantment->getLevel());
                        }
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event) {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isLoaded()) {
            $enchantments = $player->getCESession()->getActiveEnchantments();
            if(!isset($enchantments[Enchantment::MOVE])) {
                return;
            }
            $overload = false;
            /** @var EnchantmentInstance $enchantment */
            foreach($enchantments[Enchantment::MOVE] as $enchantment) {
                /** @var Enchantment $type */
                $type = $enchantment->getType();
                $callable = $type->getCallable();
                $callable($event, $enchantment->getLevel());
            }
            if($player->getMaxHealth() !== 20 and (!$overload)) {
                $player->setMaxHealth(20);
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract(PlayerInteractEvent $event) {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isLoaded()) {
            $enchantments = $player->getCESession()->getActiveEnchantments();
            if(!isset($enchantments[Enchantment::INTERACT])) {
                return;
            }
            /** @var EnchantmentInstance $enchantment */
            foreach($enchantments[Enchantment::INTERACT] as $enchantment) {
                /** @var Enchantment $type */
                $type = $enchantment->getType();
                $callable = $type->getCallable();
                $callable($event, $enchantment->getLevel());
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event):void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isLoaded()) {
            $enchantments = $player->getCESession()->getActiveEnchantments();
            if(!isset($enchantments[Enchantment::BREAK])) {
                return;
            }
            /** @var EnchantmentInstance $enchantment */
            foreach($enchantments[Enchantment::BREAK] as $enchantment) {
                /** @var Enchantment $type */
                $type = $enchantment->getType();
                $callable = $type->getCallable();
                $callable($event, $enchantment->getLevel());
            }
        }
    }

       /**
     * @param PlayerJoinEvent $event
     * @return void
     */
    public function onJoin(PlayerJoinEvent $event) : void {
        $onSlot = function (Inventory $inventory, int $slot, Item $oldItem): void {
            if(!$inventory instanceof ArmorInventory) return;
            $holder = $inventory->getHolder();
            if($holder instanceof NexusPlayer) {
                foreach ($oldItem->getEnchantments() as $enchantment) {
                    $type = $enchantment->getType();
                    if($type instanceof \Xekvern\Core\Server\Item\Enchantment\Enchantment) {
                        if($type->getEffect() !== null) {
                            if($holder->getEffects()->has($type->getEffect()) && $holder->getEffects()->get($type->getEffect())->getAmplifier() === $enchantment->getLevel()) {
                                $holder->getEffects()->remove($type->getEffect());
                            }
                        } 
                    }
                }
                $newItem = $inventory->getItem($slot);
                if($newItem->getTypeId() !== VanillaBlocks::AIR()->asItem()->getTypeId()) {
                    foreach ($newItem->getEnchantments() as $enchantment) {
                        $type = $enchantment->getType();
                        if($type instanceof \Xekvern\Core\Server\Item\Enchantment\Enchantment) {
                            if($type->getEffect() !== null) {
                                $holder->getEffects()->add(new EffectInstance($type->getEffect(), 2147483647, $enchantment->getLevel()));
                            } 
                        }
                    }
                }
            }
        };

        /**
         * @param Item[] $oldContents
         */
        $onContent = function (Inventory $inventory, array $oldContents) use ($onSlot): void {
            foreach ($oldContents as $slot => $oldItem) {
                if ($inventory instanceof ArmorInventory) $onSlot($inventory, $slot, $oldItem);
            }
        };
        $event->getPlayer()->getArmorInventory()->getListeners()->add(new CallbackInventoryListener($onSlot, $onContent));
        foreach($event->getPlayer()->getArmorInventory()->getContents() as $c) {
            foreach($c->getEnchantments() as $e) {
                if($e instanceof \Xekvern\Core\Server\Item\Enchantment\Enchantment) {
                    if($e->getEffect() !== null) {
                        $event->getPlayer()->getEffects()->add(new EffectInstance($e->getEffect(), 2147483647, $e->getLevel()));
                    } 
                }
            }
        }
    }

    /**
     * @priority LOWEST
     * @param InventoryTransactionEvent $event
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event) {
        $transaction = $event->getTransaction();
        if($event->isCancelled()) {
            return;
        }
        foreach($transaction->getActions() as $action) {
            if($action instanceof SlotChangeAction) {
                $sourceItem = $action->getSourceItem();
                $tag = $sourceItem->getNamedTag(CustomItem::CUSTOM);
                if($sourceItem->getTypeId() === VanillaItems::ENCHANTED_BOOK()->getTypeId() && isset($tag->getValue()[EnchantmentBook::ENCHANT])) {
                    if($tag instanceof CompoundTag) {
                        $enchantmentBookAction = $action;
                        $success = $tag->getInt(EnchantmentBook::SUCCESS);
                        $enchantment = ItemHandler::getEnchantment($tag->getInt(EnchantmentBook::ENCHANT));
                    }
                }
                elseif(!$sourceItem->isNull()) {
                    $equipmentAction = $action;
                }
            }
        }
        /** @var NexusPlayer $player */
        $player = $transaction->getSource();
        if(!$event->isCancelled()) {
            if(isset($enchantmentBookAction, $equipmentAction, $enchantment, $success)) {
                $book = $enchantmentBookAction->getSourceItem();
                $equipment = $equipmentAction->getSourceItem();
                if(ItemHandler::canEnchant($equipment, $enchantment)) {
                    $equipmentAction->getInventory()->removeItem($equipment);
                    $enchantmentBookAction->getInventory()->removeItem($book);
                    $chance = mt_rand(1, 100);
                    $tag = $equipment->getNamedTag();
                    if(isset($tag->getValue()[EnchantmentScroll::SCROLL_AMOUNT])) {
                        $amount = $tag->getInt(EnchantmentScroll::SCROLL_AMOUNT);
                        if(count($equipment->getEnchantments()) >= $amount && !$equipment->hasEnchantment($enchantment)) {
                            $player->sendMessage(Translation::RED . "You have exceeded the maximum number of enchantments allowed on an single item.");
                            $player->playErrorSound();
                            return;
                        }
                    } else {
                        return;
                    }
                    if($chance <= $success) {
                        if($equipment->hasEnchantment($enchantment)) {
                            $player->sendMessage(Translation::getMessage("alreadyEnchanted"));
                            $player->playErrorSound();
                            return;
                        }
                        else {
                            $enchantment = new EnchantmentInstance($enchantment, 1);
                            $equipment->addEnchantment($enchantment);
                        }
                        $equipmentAction->getInventory()->addItem(ItemHandler::setLoreForItem($equipment));
                        $event->cancel();
                        $player->sendMessage(Translation::GREEN . "Your enchantment has successfully been added to the item.");
                        $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound());
                    } else {
                        $equipmentAction->getInventory()->addItem($equipment);
                        $event->cancel();
                        $player->sendMessage(Translation::getMessage("enchantmentBookFail"));
                        $player->getWorld()->addSound($player->getPosition(), new AnvilFallSound());
                    } 
                }
            }
        } 
    }

    /**
     * @priority LOWEST
     * @param InventoryTransactionEvent $event
     */
    public function onInventoryTransaction2(InventoryTransactionEvent $event) {
        $transaction = $event->getTransaction();
        if($event->isCancelled()) {
            return;
        }
        foreach($transaction->getActions() as $action) {
            if($action instanceof SlotChangeAction) {
                $sourceItem = $action->getSourceItem();
                $tag = $sourceItem->getNamedTag(CustomItem::CUSTOM);
                if($sourceItem->getTypeId() === ExtraVanillaItems::END_CRYSTAL()->getTypeId() && isset($tag->getValue()[EnchantmentCrystal::ENCHANT])) {
                    if($tag instanceof CompoundTag) {
                        $enchantmentBookAction = $action;
                        $enchantment = ItemHandler::getEnchantment($tag->getInt(EnchantmentCrystal::ENCHANT));
                    }
                }
                elseif(!$sourceItem->isNull()) {
                    $equipmentAction = $action;
                }
            }
        }
        /** @var NexusPlayer $player */
        $player = $transaction->getSource();
        if(!$event->isCancelled()) {
            if(isset($enchantmentBookAction, $equipmentAction, $enchantment)) {
                $book = $enchantmentBookAction->getSourceItem();
                $equipment = $equipmentAction->getSourceItem();
                if(ItemHandler::canEnchant($equipment, $enchantment)) {
                    $equipmentAction->getInventory()->removeItem($equipment);
                    $enchantmentBookAction->getInventory()->removeItem($book);
                    $enchantment = new EnchantmentInstance($enchantment);
                    if(!$equipment->hasEnchantment($enchantment->getType())) {
                        $player->sendMessage(Translation::RED . "The item does not have the same enchantment as your crystal.");
                        $player->playErrorSound();
                        return;
                    }
                    else {
                        $enchantment = new EnchantmentInstance($enchantment->getType(), $equipment->getEnchantmentLevel($enchantment->getType()) + 1);
                        $levels = round(10 + (($enchantment->getLevel() * 5) * ItemHandler::rarityToMultiplier($enchantment->getType()->getRarity())));
                        $equipment->addEnchantment($enchantment);
                    }
                    if ($player->getXpManager()->getXpLevel() < $levels) {
                        $player->sendMessage(Translation::getMessage("needLevelsToEnchant", [
                            "amount" => TextFormat::RED . $levels
                        ]));
                        return;
                    }
                    $player->getXpManager()->subtractXpLevels((int)$levels);
                    $equipmentAction->getInventory()->addItem(ItemHandler::setLoreForItem($equipment));
                    $event->cancel();
                    $player->sendMessage(Translation::GREEN . "Your enchantment has successfully been added to the item.");
                    $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound());
                }
            }
        } 
    }

    /**
     * @priority LOWEST
     * @param InventoryTransactionEvent $event
     */
    public function onInventoryTransaction3(InventoryTransactionEvent $event) {
        $transaction = $event->getTransaction();
        if($event->isCancelled()) {
            return;
        }
        foreach($transaction->getActions() as $action) {
            if($action instanceof SlotChangeAction) {
                $sourceItem = $action->getSourceItem();
                $tag = $sourceItem->getNamedTag(CustomItem::CUSTOM);
                if($sourceItem->getTypeId() === ExtraVanillaItems::ENDER_EYE()->getTypeId() && isset($tag->getValue()[EnchantmentScroll::ENCHANTMENT_SCROLL])) {
                    if($tag instanceof CompoundTag) {
                        $scrollAction = $action;
                        $scrollamount = $tag->getInt(EnchantmentScroll::SCROLL_AMOUNT);
                    }
                }
                elseif(!$sourceItem->isNull()) {
                    $equipmentAction = $action;
                }
            }
        }
        /** @var NexusPlayer $player */
        $player = $transaction->getSource();
        if(!$event->isCancelled()) {
            if(isset($scrollAction, $equipmentAction, $scrollamount)) {
                $scroll = $scrollAction->getSourceItem();
                $equipment = $equipmentAction->getSourceItem();
                if($equipment->hasEnchantments() && $equipment instanceof Durable) {
                    $equipmentAction->getInventory()->removeItem($equipment);
                    $scrollAction->getInventory()->removeItem($scroll);
                    $equipmentTag = $equipment->getNamedTag();
                    $currentAmount = ItemHandler::DEFAULT_ENCHANT_LIMIT;
                    if(isset($equipmentTag->getValue()[EnchantmentScroll::SCROLL_AMOUNT])) {
                        $currentAmount = $equipmentTag->getInt(EnchantmentScroll::SCROLL_AMOUNT);
                        if($currentAmount >= ItemHandler::MAX_ENCHANT_LIMIT) {
                            $player->playErrorSound();
                            $player->sendMessage(Translation::RED . "You have already reached the limit of adding enchantment scrolls in this item!");
                            $equipmentTag->setInt(EnchantmentScroll::SCROLL_AMOUNT, ItemHandler::MAX_ENCHANT_LIMIT);
                            return;
                        }
                    }
                    $equipmentTag->setInt(EnchantmentScroll::SCROLL_AMOUNT, $currentAmount + 1);
                    $event->cancel();
                    $player->playSound("random.levelup", 0.5, 0.5);
                    $equipmentAction->getInventory()->addItem(ItemHandler::setLoreForItem($equipment));
                    $player->sendMessage(Translation::GREEN . "You have successfully forge an enchantment scroll giving an extra of limits to the item.");
                }
            }
        } 
    }
}