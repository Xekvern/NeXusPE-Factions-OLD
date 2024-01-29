<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Kit\Types;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Item\Enchantment\Enchantment;
use Xekvern\Core\Server\Item\Utils\CustomItem;
use Xekvern\Core\Server\Kit\Kit;

class Subordinate extends Kit {

    /**
     * Subordinate constructor.
     */
    public function __construct() {
        $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Subordinate " . TextFormat::RESET . TextFormat::DARK_RED;
        $items =  [
            (new CustomItem(VanillaItems::DIAMOND_HELMET(), $name . "Helmet", [], [
                new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3),
                new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EVADE), 1),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::NOURISH), 1)
            ]))->getItemForm(),
            (new CustomItem(VanillaItems::DIAMOND_CHESTPLATE(), $name . "Chestplate", [], [
                new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3),
                new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1)
            ]))->getItemForm(),
            (new CustomItem(VanillaItems::DIAMOND_LEGGINGS(), $name . "Leggings", [], [
                new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3),
                new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1)
            ]))->getItemForm(),
            (new CustomItem(VanillaItems::DIAMOND_BOOTS(), $name . "Boots", [], [
                new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3),
                new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1)
            ]))->getItemForm(),
            (new CustomItem(VanillaItems::DIAMOND_SWORD(), $name . "Sword", [], [
                new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 5),
                new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1),
            ]))->getItemForm(),
            (new CustomItem(VanillaItems::DIAMOND_SHOVEL(), $name . "Shovel", [], [
                new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 4),
                new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1)
            ]))->getItemForm(),
            (new CustomItem(VanillaItems::DIAMOND_PICKAXE(), $name . "Pickaxe", [], [
                new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 4),
                new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1)
            ]))->getItemForm(),
            (new CustomItem(VanillaItems::DIAMOND_AXE(), $name . "Axe", [], [
                new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 5),
                new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 4),
                new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1)
            ]))->getItemForm(),
            VanillaItems::STEAK()->setCount(64),
            VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(12),
            VanillaItems::GOLDEN_APPLE()->setCount(32),
            VanillaBlocks::OBSIDIAN()->asItem()->setCount(64),
            VanillaBlocks::BEDROCK()->asItem()->setCount(64)
        ];
        parent::__construct("Subordinate", self::COMMON, $items, 21600);
    }

    /**
     * @return string
     */
    public function getColoredName(): string {
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Subordinate" . TextFormat::RESET;
    }
}