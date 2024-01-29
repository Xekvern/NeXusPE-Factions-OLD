<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Types;

use Xekvern\Core\Player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\block\tile\Chest;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Item\Utils\ClickableItem;
use Xekvern\Core\Server\Item\Utils\CustomItem;

class TreasureChest extends CustomItem {

    const TREASURE_CHEST = "TreasureChest";

    /**
     * TreasureChest constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Treasure Chest";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "!";
        parent::__construct(VanillaBlocks::CHEST()->asItem(), $customName, $lore, [], [
            self::TREASURE_CHEST => self::TREASURE_CHEST,
        ]);
    }
}