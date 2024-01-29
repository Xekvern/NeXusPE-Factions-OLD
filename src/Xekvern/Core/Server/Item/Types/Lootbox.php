<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Types;

use Xekvern\Core\Server\Item\Utils\ClickableItem;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\Translation;
use Xekvern\Core\Translation\TranslatonException;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Crate\Crate;
use Xekvern\Core\Server\Crate\Reward;
use Xekvern\Core\Server\Item\Task\LootboxTask;

class Lootbox extends ClickableItem {

    const LOOTBOX = "Lootbox";
    const LOOTBOX_TYPE = "LootboxType";
    const LOOTBOX_CUSTOM_NAME = "LootboxCustomName";

    /**
     * Lootbox constructor.
     */
    public function __construct(string $type, string $customName) {
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "Lootbox: " . $customName;
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Receive rewards! Only best for success";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "Possible Rewards (" . TextFormat::RESET . TextFormat::GRAY . "5 Items" . TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . ")";
        switch($type) {
            case "Test":
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Diamond";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Emerald";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Gold Ingot";
                break;
            case "SOTW":
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " $2,500,00";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "2" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Sacred Stone";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Spartan Kit";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Prince Kit";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "3" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Souls";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " KOTH Starter";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "3" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Ultra Crate Key Note";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Legendary Crate Key Note";
                break;
            case "Husk":
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " $7,500,00";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Tier Skip";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Alien Boss Egg";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "15" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Sacred Stone";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Deity Kit";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "5" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Souls";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Custom Tag";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " KOTH Starter";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "5" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Ultra Crate Key Note";
                break;
            default:
                break;
        }
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "Rare Rewards (" . TextFormat::RESET . TextFormat::GRAY . "1 Item" . TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . ")";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . " * " . TextFormat::RESET . TextFormat::WHITE . "1" . TextFormat::GRAY . "x" . TextFormat::YELLOW . " Random Holybox";
        parent::__construct(VanillaBlocks::BEACON()->asItem(), $customName, $lore, [], [
            self::LOOTBOX => new StringTag(self::LOOTBOX),   
            self::LOOTBOX_TYPE => new StringTag($type),
            self::LOOTBOX_CUSTOM_NAME => new StringTag($customName)
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
        if(($player->getInventory()->getSize() - count($player->getInventory()->getContents())) < 6) {
            $player->sendMessage(Translation::getMessage("fullInventory"));
            return;
        }
        $type = $tag->getString(Lootbox::LOOTBOX_TYPE);
        switch($type) {
            case "Test":
                $rewards = [
                    new Reward("Diamond", VanillaItems::DIAMOND(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem(VanillaItems::DIAMOND());
                    }, 100),
                    new Reward("Emerald", VanillaItems::EMERALD(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem(VanillaItems::EMERALD());
                    }, 100),
                    new Reward("Gold", VanillaItems::GOLD_INGOT(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem(VanillaItems::GOLD_INGOT());
                    }, 100),
                    new Reward("Lapis", VanillaItems::LAPIS_LAZULI(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem(VanillaItems::LAPIS_LAZULI());
                    }, 100),
                    new Reward("Iron", VanillaItems::IRON_INGOT(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem(VanillaItems::IRON_INGOT());
                    }, 100),
                    new Reward("HolyBox", VanillaBlocks::CHEST()->asItem(), function(NexusPlayer $player): void {
                        $kits = Nexus::getInstance()->getServerManager()->getKitHandler()->getSacredKits();
                        $kit = $kits[array_rand($kits)];
                        $player->getInventory()->addItem((new HolyBox($kit))->getItemForm());
                    }, 100),
                ];
                break;
            case "SOTW":
                $rewards = [
                    new Reward("$500,000", (new MoneyNote(500000))->getItemForm(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new MoneyNote(500000))->getItemForm());
                    }, 100),
                    new Reward("$1,500,000", (new MoneyNote(1500000))->getItemForm(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new MoneyNote(1500000))->getItemForm());
                    }, 100),
                    new Reward("5x Sacred Stone", (new SacredStone())->getItemForm()->setCount(5), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new SacredStone())->getItemForm()->setCount(5));
                    }, 85),
                    new Reward("3x Souls", (new Soul())->getItemForm()->setCount(3), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new Soul())->getItemForm()->setCount(3));
                    }, 80),
                    new Reward("Spartan Kit", (new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Spartan")))->getItemForm(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Spartan")))->getItemForm());
                    }, 100),
                    new Reward("Prince Kit", (new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Prince")))->getItemForm(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Prince")))->getItemForm());
                    }, 100),
                    new Reward("HolyBox", VanillaBlocks::CHEST()->asItem()->setCustomName(TextFormat::BOLD . TextFormat::YELLOW . "Random Holy Box"), function(NexusPlayer $player): void {
                        $kits = Nexus::getInstance()->getServerManager()->getKitHandler()->getSacredKits();
                        $kit = $kits[array_rand($kits)];
                        $player->getInventory()->addItem((new HolyBox($kit))->getItemForm());
                    }, 15),
                    new Reward("KOTH Starter", (new KOTHStarter())->getItemForm(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new KOTHStarter())->getItemForm());
                    }, 25),
                    new Reward("3x Ultra Crate Key Note", (new CrateKeyNote(Crate::ULTRA, 3))->getItemForm(), function(NexusPlayer $player): void {
                        $item = (new CrateKeyNote(Crate::ULTRA, 3))->getItemForm();
                        $player->getInventory()->addItem($item);
                    }, 70),
                    new Reward("1x Legendary Crate Key Note", (new CrateKeyNote(Crate::LEGENDARY, 1))->getItemForm(), function(NexusPlayer $player): void {
                        $item = (new CrateKeyNote(Crate::LEGENDARY, 1))->getItemForm();
                        $player->getInventory()->addItem($item);
                    }, 70),
                ];
                break;
            case "Husk":
                $rewards = [
                    new Reward("$7,500,000", (new MoneyNote(7500000))->getItemForm(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new MoneyNote(7500000))->getItemForm());
                    }, 100),
                    new Reward("Alien Boss Egg", (new BossEgg("Alien"))->getItemForm(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new BossEgg("Alien"))->getItemForm());
                    }, 100),
                    new Reward("15x Sacred Stone", (new SacredStone())->getItemForm()->setCount(15), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new SacredStone())->getItemForm()->setCount(15));
                    }, 85),
                    new Reward("5x Souls", (new Soul())->getItemForm()->setCount(5), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new Soul())->getItemForm()->setCount(5));
                    }, 55),
                    new Reward("Deity Kit", (new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Deity")))->getItemForm(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new ChestKit(Nexus::getInstance()->getServerManager()->getKitHandler()->getKitByName("Deity")))->getItemForm());
                    }, 100),
                    new Reward("HolyBox", VanillaBlocks::CHEST()->asItem()->setCustomName(TextFormat::BOLD . TextFormat::YELLOW . "Random Holy Box"), function(NexusPlayer $player): void {
                        $kits = Nexus::getInstance()->getServerManager()->getKitHandler()->getSacredKits();
                        $kit = $kits[array_rand($kits)];
                        $player->getInventory()->addItem((new HolyBox($kit))->getItemForm());
                    }, 100),
                    new Reward("KOTH Starter", (new KOTHStarter())->getItemForm(), function(NexusPlayer $player): void {
                        $player->getInventory()->addItem((new KOTHStarter())->getItemForm());
                    }, 55),
                    new Reward("5x Ultra Crate Key Note", (new CrateKeyNote(Crate::ULTRA, 5))->getItemForm(), function(NexusPlayer $player): void {
                        $item = (new CrateKeyNote(Crate::ULTRA, 3))->getItemForm();
                        $player->getInventory()->addItem($item);
                    }, 100),
                    new Reward("2x Legendary Crate Key Note", (new CrateKeyNote(Crate::LEGENDARY, 2))->getItemForm(), function(NexusPlayer $player): void {
                        $item = (new CrateKeyNote(Crate::LEGENDARY, 2))->getItemForm();
                        $player->getInventory()->addItem($item);
                    }, 70),
                ];
                break;
            default:
                break;
        }
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $player->getCore()->getScheduler()->scheduleRepeatingTask(new LootboxTask($player, $tag->getString(Lootbox::LOOTBOX_CUSTOM_NAME), $rewards), 2);
    }
}