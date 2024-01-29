<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Types;

use Xekvern\Core\Server\Item\Utils\ExtraVanillaItems;
use Xekvern\Core\Server\Item\Utils\CustomItem;
use Xekvern\Core\Server\Item\ItemHandler;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class EnchantmentCrystal extends CustomItem {

    const ENCHANT = "Enchant";

    /**
     * EnchantmentCrystal constructor.
     *
     * @param Enchantment $enchantment
     * @param int $level
     * @param int $success
     */
    public function __construct(Enchantment $enchantment) {
        $customName = TextFormat::RESET . ItemHandler::rarityToColor($enchantment->getRarity()) . TextFormat::BOLD . "{$enchantment->getName()} Crystal";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Enchantment: " . TextFormat::WHITE . $enchantment->getName();
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Rarity: " . TextFormat::WHITE . ItemHandler::rarityToString($enchantment->getRarity());
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Move this crystal on top of an item with the same enchant to upgrade it.";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Use " . TextFormat::YELLOW . TextFormat::WHITE . "/ceinfo" . TextFormat::RESET . TextFormat::YELLOW . " to check what this enchantment does.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "This does not add an enchantment.\nGet an enchantment book to add an enchantment.";
        parent::__construct(ExtraVanillaItems::END_CRYSTAL(), $customName, $lore, [], [
            self::ENCHANT => new IntTag(EnchantmentIdMap::getInstance()->toId($enchantment)),
            "UniqueId" => new StringTag(uniqid())
        ]);
    }
}