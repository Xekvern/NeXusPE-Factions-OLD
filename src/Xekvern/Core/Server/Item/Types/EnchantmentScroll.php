<?php

namespace Xekvern\Core\Server\Item\Types;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Item\Utils\CustomItem;
use Xekvern\Core\Server\Item\Utils\ExtraVanillaItems;

class EnchantmentScroll extends CustomItem {

    const ENCHANTMENT_SCROLL = "EnchantmentScroll";
    const SCROLL_AMOUNT = "ScrollAmount";

    /**
     * Soul constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Enchantment Scroll";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Forge this scroll to an item to hack";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "the system and gain a higher enchantment limit!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Apply this to your item by forging it.";
        parent::__construct(ExtraVanillaItems::ENDER_EYE(), $customName, $lore, 
        [
            new EnchantmentInstance(\Xekvern\Core\Server\Item\Enchantment\Enchantment::getEnchantment(50), 1)
        ], 
        [
            self::ENCHANTMENT_SCROLL => new StringTag(self::ENCHANTMENT_SCROLL),
            self::SCROLL_AMOUNT => new IntTag(1),
        ]);
    }

    /**
     * @return int
     */
    public function getMaxStackSize(): int {
        return 1;
    }   
}