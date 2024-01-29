<?php

namespace Xekvern\Core\Server\Item\Types;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Item\Utils\CustomItem;
use Xekvern\Core\Server\Item\Utils\ExtraVanillaItems;

class Soul extends CustomItem {

    const SOUL = "Soul";

    /**
     * Soul constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::DARK_AQUA . TextFormat::BOLD . "Soul";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "A special currency to buy items from " . TextFormat::BOLD . TextFormat::DARK_AQUA . "Voldemort" . TextFormat::RESET . TextFormat::GRAY . ".";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "He appears every Friday!";
        parent::__construct(VanillaItems::CLOCK(), $customName, $lore, 
        [
            new EnchantmentInstance(\Xekvern\Core\Server\Item\Enchantment\Enchantment::getEnchantment(50), 1)
        ], 
        [
            self::SOUL => new StringTag(self::SOUL)
        ]);
    }
}