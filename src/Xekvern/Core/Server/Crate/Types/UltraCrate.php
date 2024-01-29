<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Crate\Types;

use pocketmine\block\utils\DyeColor;
use Xekvern\Core\Player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use Xekvern\Core\Nexus;
use Xekvern\Core\Server\Crate\Crate;
use Xekvern\Core\Server\Crate\Reward;
use Xekvern\Core\Server\Item\ItemHandler;
use Xekvern\Core\Server\Item\Types\ChestKit;
use Xekvern\Core\Server\Item\Types\EnchantmentBook;
use Xekvern\Core\Server\Item\Types\EnchantmentScroll;
use Xekvern\Core\Server\Item\Types\MoneyNote;
use Xekvern\Core\Server\Item\Types\SacredStone;
use Xekvern\Core\Server\Item\Types\SellWandNote;
use Xekvern\Core\Server\Item\Types\SpongeLauncher;
use Xekvern\Core\Server\Item\Types\TNTLauncher;
use Xekvern\Core\Server\Item\Types\WaterCannon;
use Xekvern\Core\Server\Item\Utils\CustomItem;
use Xekvern\Core\Server\Item\Utils\ExtraVanillaItems;
use Xekvern\Core\Server\World\Utils\GeneratorId;
use Xekvern\Core\Server\World\WorldHandler;

class UltraCrate extends Crate {

    /**
     * UltraCrate constructor.
     *
     * @param Position $position
     */
    public function __construct(Position $position) {
        parent::__construct(self::ULTRA, $position, [
            new Reward("1,000 XP", VanillaItems::PAPER()->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "1,000 XP"), function(NexusPlayer $player): void {
                $player->addXp(1000);
            }, 100),
            new Reward("150 Level XP", VanillaItems::PAPER()->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "150 Level XP"), function (NexusPlayer $player): void {
                $player->getXpManager()->addXp(800);
            }, 100),
            new Reward("$350,000", (new MoneyNote(350000))->getItemForm(), function(NexusPlayer $player): void {
                $items = [
                    (new MoneyNote(350000))->getItemForm(),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 65),
            new Reward("$1,250,000", (new MoneyNote(1250000))->getItemForm(), function(NexusPlayer $player): void {
                $items = [
                    (new MoneyNote(1250000))->getItemForm(),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 15),
            new Reward("x10 Sell Wand Note", VanillaItems::PAPER()->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "x10 Sell Wand Note"), function(NexusPlayer $player): void {
                $items = [
                    (new SellWandNote(10))->getItemForm(),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 50),
            new Reward("Enchanted Golden Apples", VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(64), function(NexusPlayer $player): void {
                $items = [
                    VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(64),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 75),
            new Reward("Bedrock", VanillaBlocks::BEDROCK()->asItem()->setCount(64), function(NexusPlayer $player): void {
                $items = [
                    VanillaBlocks::BEDROCK()->asItem()->setCount(64),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 100),
            new Reward("Obsidian", VanillaBlocks::BEDROCK()->asItem()->setCount(64), function(NexusPlayer $player): void {
                $items = [
                    VanillaBlocks::BEDROCK()->asItem()->setCount(64),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 100),
            new Reward("TNT", VanillaBlocks::TNT()->asItem()->setCount(64), function(NexusPlayer $player): void {
                $items = [
                    VanillaBlocks::TNT()->asItem()->setCount(64),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 100),
            new Reward("TNT Launcher", VanillaItems::STICK()->setCustomName(TextFormat::RESET . TextFormat::BLUE . "TNT Launcher"), function(NexusPlayer $player): void {
                $items = [
                    (new TNTLauncher(1, 50, "TNT", ["Short", "Mid", "Long"][array_rand(["Short", "Mid", "Long"])]))->getItemForm(),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 100),
            new Reward("Sponge Launcher", VanillaItems::BAMBOO()->setCustomName(TextFormat::RESET . TextFormat::BLUE . "Sponge Launcher"), function(NexusPlayer $player): void {
                $items = [
                    (new SpongeLauncher(1, 25, "Sponge", ["Short", "Mid", "Long"][array_rand(["Short", "Mid", "Long"])]))->getItemForm(),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 100),
            new Reward("Water Cannon", VanillaItems::ECHO_SHARD()->setCustomName(TextFormat::RESET . TextFormat::BLUE . "Water Cannon"), function(NexusPlayer $player): void {
                $items = [
                    (new WaterCannon(1, 20, "Water", ["Short", "Mid", "Long"][array_rand(["Short", "Mid", "Long"])]))->getItemForm(),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 100),
            new Reward("x2 Lapis Lazuli Generator", VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::CYAN())->asItem()->setCount(3), function(NexusPlayer $player): void {
                $worldHandler = new WorldHandler(Nexus::getInstance());
                $items = [
                    $worldHandler->getGeneratorItem(GeneratorId::LAPIS_LAZULI, false)->setCount(3),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 80),
            new Reward("Enchantment Scroll", (new EnchantmentScroll())->getItemForm(), function(NexusPlayer $player): void {
                $items = [
                    (new EnchantmentScroll())->getItemForm(),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 80),
            new Reward("Subordinate Kit", VanillaBlocks::CHEST()->asItem()->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Subordinate Kit"), function(NexusPlayer $player): void {
                $items = [
                    (new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Subordinate")))->getItemForm(),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 80),
            new Reward("Knight Kit", VanillaBlocks::CHEST()->asItem()->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Knight Kit"), function(NexusPlayer $player): void {
                $items = [
                    (new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Knight")))->getItemForm(),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 80),
            new Reward("Random Enchantment", VanillaItems::ENCHANTED_BOOK()->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "Enchantment"), function(NexusPlayer $player): void {
                $enchantment = ItemHandler::getRandomEnchantment();
                $items = [
                    (new EnchantmentBook($enchantment, mt_rand(1, 50)))->getItemForm(),
                ];
                foreach($items as $item) {
                    if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    }
                    else {
                        $player->getDataSession()->addToInbox($item);
                    }
                }
            }, 80),
            new Reward("Ultra Tag", ExtraVanillaItems::NAME_TAG()->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Ultra" . TextFormat::RESET . TextFormat::GRAY . " Tag"), function(NexusPlayer $player): void {
                $tag = TextFormat::BOLD . TextFormat::RED . "Ultra";
                if($player->getDataSession()->hasTag($tag)) {
                    $player->sendMessage(TextFormat::RED . "Unlucky! You got a duplicate tag!");
                    return;
                }
                $player->getDataSession()->addTag($tag);
            }, 100),
            new Reward("Random Tag", ExtraVanillaItems::NAME_TAG()->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Random" . TextFormat::RESET . TextFormat::GRAY . " Tag"), function(NexusPlayer $player): void {
                $tags = [
                    TextFormat::BOLD . TextFormat::RED . "M" . TextFormat::GOLD . "E" . TextFormat::YELLOW . "M" . TextFormat::GREEN . "E" . TextFormat::DARK_BLUE . "D",
                    TextFormat::BOLD . TextFormat::YELLOW . "Is" . TextFormat::GOLD . "It" . TextFormat::RED . "Reset" . TextFormat::DARK_RED . "Yet",
                    TextFormat::BOLD . TextFormat::BLUE . "Season" . TextFormat::AQUA . Nexus::SEASON,
                    TextFormat::BOLD . TextFormat::YELLOW . "Lucky",
                    TextFormat::BOLD . TextFormat::DARK_GRAY . "BOT",
                    TextFormat::BOLD . TextFormat::YELLOW . "GG" . TextFormat::AQUA . "No" . TextFormat::GREEN . "Ree",
                    TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "Xek" . TextFormat::AQUA . "Is" . TextFormat::DARK_PURPLE . "Hot",
                    TextFormat::BOLD . TextFormat::DARK_RED . "Daddy" . TextFormat::AQUA . "David" . TextFormat::RED . "<3",
                    TextFormat::BOLD . TextFormat::GOLD . "David" . TextFormat::DARK_GRAY . "Got" . TextFormat::DARK_AQUA . "Scammed",
                    TextFormat::BOLD . TextFormat::DARK_RED . "Hype" . TextFormat::RED . "Beast",
                    TextFormat::BOLD . TextFormat::DARK_PURPLE . "PvP" . TextFormat::LIGHT_PURPLE . "God",
                    TextFormat::BOLD . TextFormat::RED . "100" . TextFormat::GREEN . "CPS",
                    TextFormat::BOLD . TextFormat::YELLOW . "M" . TextFormat::RED . "V" . TextFormat::BLUE . "P",
                    TextFormat::BOLD . TextFormat::GOLD . "Popeye" . TextFormat::YELLOW . "Chicken" . TextFormat::RED . "Sandwich",
                    TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "W" . TextFormat::DARK_AQUA . "E" . TextFormat::DARK_PURPLE . "E" . TextFormat::AQUA . "B",
                    TextFormat::BOLD . TextFormat::GREEN . "YO" . TextFormat::DARK_GRAY . "DA",
                    TextFormat::BOLD . TextFormat::DARK_PURPLE . "BEE" . TextFormat::LIGHT_PURPLE . "RUS",
                    TextFormat::BOLD . TextFormat::GREEN . "9000" . TextFormat::BLUE . "IQ",
                    TextFormat::BOLD . TextFormat::AQUA . "Ok" . TextFormat::DARK_AQUA . "Boomer",
                    TextFormat::BOLD . TextFormat::GOLD . "Simp" . TextFormat::YELLOW . "Nation",
                    TextFormat::BOLD . TextFormat::GREEN . "Baby" . TextFormat::DARK_GREEN . "Yoda",
                    TextFormat::BOLD . TextFormat::AQUA . "VSCO",
                    TextFormat::BOLD . TextFormat::DARK_RED . "OG",
                    TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "BAE",
                    TextFormat::BOLD . TextFormat::RED . "No" . TextFormat::GRAY . "Skillz",
                    TextFormat::BOLD . TextFormat::ITALIC . TextFormat::YELLOW . "SMOOVE",
                    TextFormat::BOLD . TextFormat::AQUA . "Duck" . TextFormat::LIGHT_PURPLE . "Gang",
                    TextFormat::BOLD . TextFormat::DARK_AQUA . "Plat" . TextFormat::GOLD . "Army",
                    TextFormat::BOLD . TextFormat::BLACK . "BLM",
                    TextFormat::BOLD . TextFormat::RED . "DONT" . TextFormat::DARK_RED . "LEAVE" . TextFormat::RED . "ME",
                ];
                $tag = $tags[array_rand($tags)];
                if($player->getDataSession()->hasTag($tag)) {
                    $player->sendMessage(TextFormat::RED . "Unlucky! You got a duplicate tag!");
                    return;
                }
                $player->getDataSession()->addTag($tag);
            }, 100),
        ]);
    }

    /**
     * @param NexusPlayer $player
     *
     * @throws UtilsException
     */
    public function spawnTo(NexusPlayer $player): void {
        $particle = $player->getFloatingText($this->getName());
        if($particle !== null) {
            return;
        }
        $player->addFloatingText(Position::fromObject($this->getPosition()->add(0.5, 1.25, 0.5), $this->getPosition()->getWorld()), $this->getName(), TextFormat::RED . TextFormat::BOLD .  "Ultra Crate\n" . TextFormat::RESET . TextFormat::WHITE . "You have " . TextFormat::RED . $player->getDataSession()->getKeys($this) . TextFormat::WHITE . " keys");
    }

    /**
     * @param NexusPlayer $player
     *
     * @throws UtilsException
     */
    public function updateTo(NexusPlayer $player): void {
        $particle = $player->getFloatingText($this->getName());
        if($particle === null) {
            $this->spawnTo($player);
        }
        $text = $player->getFloatingText($this->getName());
        $text->update(TextFormat::RED . TextFormat::BOLD .  "Ultra Crate\n" . TextFormat::RESET . TextFormat::WHITE . "You have " . TextFormat::RED . $player->getDataSession()->getKeys($this) . TextFormat::WHITE . " keys");
        $text->sendChangesTo($player);
    }

    /**
     * @param NexusPlayer $player
     */
    public function despawnTo(NexusPlayer $player): void {
        $particle = $player->getFloatingText($this->getName());
        if($particle !== null) {
            $particle->despawn($player);
        }
    }

    /**
     * @param Reward $reward
     * @param NexusPlayer $player
     *
     * @throws UtilsException
     */
    public function showReward(Reward $reward, NexusPlayer $player): void {
        $particle = $player->getFloatingText($this->getName());
        if($particle === null) {
            $this->spawnTo($player);
        }
        $text = $player->getFloatingText($this->getName());
        $text->update(TextFormat::BOLD . TextFormat::RED . $reward->getName());
        $text->sendChangesTo($player);
    }

    /**
     * @param Reward $reward
     *
     * @return string
     */
    public function getRewardDisplayName(Reward $reward): string {
        return TextFormat::BOLD . TextFormat::RED . $reward->getName();
    }
}
