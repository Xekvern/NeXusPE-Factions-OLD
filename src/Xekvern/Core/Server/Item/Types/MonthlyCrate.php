<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Types;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Server\Crate\Crate;
use Xekvern\Core\Server\Crate\Reward;
use Xekvern\Core\Server\Item\Task\MonthlyCrateAnimationTask;
use Xekvern\Core\Server\Item\Types\MoneyNote;
use Xekvern\Core\Server\Item\Utils\ClickableItem;
use Xekvern\Core\Translation\Translation;

class MonthlyCrate extends ClickableItem {

    const MONTH = "Month";

    /**
     * MonthlyCrate constructor.
     */
    public function __construct() {
        $month = date("F", time());
        $customName = TextFormat::RESET . TextFormat::OBFUSCATED . TextFormat::BOLD . TextFormat::RED . "|" . TextFormat::GOLD . "|" . TextFormat::YELLOW . "|" . TextFormat::GREEN . "|" . TextFormat::AQUA . "|" . TextFormat::LIGHT_PURPLE . "|" . TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . " $month Crate";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Place in spawn to open this magical box!";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Receive 4 random possible items 1 bonus item!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "POSSIBLE ITEMS:";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::YELLOW . "$5,000,000";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::DARK_AQUA . TextFormat::BOLD . "BONUS ITEM:";
        $lore[] = TextFormat::RESET . TextFormat::DARK_AQUA . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::AQUA . "Holy Box";
        parent::__construct(VanillaBlocks::ENDER_CHEST()->asItem(), $customName, $lore, [], [
            self::MONTH => new StringTag($month),
            "UniqueId" => new StringTag(uniqid())
        ]);    
    }
    
    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param CompoundTag $tag
     * @param int $face
     * @param Block $blockClicked
     *
     * @throws TranslatonException
     */
    public static function execute(NexusPlayer $player, Inventory $inventory, Item $item, CompoundTag $tag, int $face, Block $blockClicked): void {
        if($player->getWorld()->getFolderName() !== $player->getCore()->getServer()->getWorldManager()->getDefaultWorld()->getFolderName()) {
            $player->sendMessage(Translation::getMessage("onlyInSpawn"));
            return;
        }
        if(($player->getInventory()->getSize() - count($player->getInventory()->getContents())) < 5) {
            $player->sendMessage(Translation::getMessage("fullInventory"));
            return;
        }
        $rewards = [
            new Reward("$10,000,000", (new MoneyNote(10000000))->getItemForm(), function(NexusPlayer $player): void {
                $player->getInventory()->addItem((new MoneyNote(10000000))->getItemForm());
            }, 100),
            new Reward("10x Sacred Stone", (new SacredStone())->getItemForm()->setCount(10), function(NexusPlayer $player): void {
                $player->getInventory()->addItem((new SacredStone())->getItemForm()->setCount(10));
            }, 85),
            new Reward("Sell Wand", (new SellWandNote(500))->getItemForm(), function(NexusPlayer $player): void {
                $player->getInventory()->addItem((new SellWandNote(500))->getItemForm());
            }, 85),
            new Reward("Custom Tag", (new CustomTag())->getItemForm(), function(NexusPlayer $player): void {
                $player->getInventory()->addItem((new CustomTag())->getItemForm());
            }, 75),
            new Reward("Alien Boss Egg", (new BossEgg("Alien"))->getItemForm(), function(NexusPlayer $player): void {
                $player->getInventory()->addItem((new BossEgg("Alien"))->getItemForm());
            }, 100),
            new Reward("5x Legendary Crate Key Note", (new CrateKeyNote(Crate::LEGENDARY, 5))->getItemForm(), function(NexusPlayer $player): void {
                $item = (new CrateKeyNote(Crate::LEGENDARY, 5))->getItemForm();
                $player->getInventory()->addItem($item);
            }, 100),
            new Reward("10x Epic Crate Key Note", (new CrateKeyNote(Crate::EPIC, 10))->getItemForm(), function(NexusPlayer $player): void {
                $item = (new CrateKeyNote(Crate::EPIC, 10))->getItemForm();
                $player->getInventory()->addItem($item);
            }, 100),
        ];
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $player->getCore()->getScheduler()->scheduleRepeatingTask(new MonthlyCrateAnimationTask($player, $tag->getString(MonthlyCrate::MONTH), $rewards), 1);
    }
}