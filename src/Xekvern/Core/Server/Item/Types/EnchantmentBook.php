<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Types;

use Xekvern\Core\Server\Item\Utils\ExtraVanillaItems;
use Xekvern\Core\Server\Item\Utils\CustomItem;
use Xekvern\Core\Server\Item\ItemHandler;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class EnchantmentBook extends CustomItem {

    const ENCHANT = "Enchant";
    const SUCCESS = "Success";

    /**
     * EnchantmentBook constructor.
     *
     * @param Enchantment $enchantment
     * @param int $level
     * @param int $success
     */
    public function __construct(Enchantment $enchantment, int $success) {
        $fail = 100 - $success;
        $customName = TextFormat::RESET . ItemHandler::rarityToColor($enchantment->getRarity()) . TextFormat::BOLD . "{$enchantment->getName()} Book";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Enchantment: " . TextFormat::WHITE . $enchantment->getName();
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Rarity: " . TextFormat::WHITE . ItemHandler::rarityToString($enchantment->getRarity());
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "You have a " . TextFormat::BOLD . TextFormat::GREEN . "$success%" . TextFormat::RESET . TextFormat::GRAY . " chance of successfully adding enchantment with this";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "book and a " . TextFormat::BOLD . TextFormat::RED . "$fail%" . TextFormat::RESET . TextFormat::GRAY . " of failing to enchant.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Move this book on top of an item to enchant it.";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Use " . TextFormat::YELLOW . TextFormat::WHITE . "/ceinfo" . TextFormat::RESET . TextFormat::YELLOW . " to check what this enchantment does.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "This does not add more levels.\nGet crystals to upgrade your enchantment.";
        parent::__construct(VanillaItems::ENCHANTED_BOOK(), $customName, $lore, [], [
            self::ENCHANT => new IntTag(EnchantmentIdMap::getInstance()->toId($enchantment)),
            self::SUCCESS => new IntTag($success),
            "UniqueId" => new StringTag(uniqid())
        ]);
    }
}