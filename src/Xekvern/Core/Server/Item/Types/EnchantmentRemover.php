<?php

namespace Xekvern\Core\Server\Item\Types;

use pocketmine\item\VanillaItems;
use Xekvern\Core\Server\Item\Utils\CustomItem;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;

class EnchantmentRemover extends CustomItem {

    const SUCCESS_PERCENTAGE = "SuccessPercentage";

    /**
     * EnchantmentRemover constructor.
     *
     * @param int $success
     */
    public function __construct(int $success) {
        $customName = TextFormat::RESET . TextFormat::DARK_PURPLE . TextFormat::BOLD . "Enchantment Remover";
        $fail = 100 - $success;
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "You have a " . TextFormat::BOLD . TextFormat::GREEN . "$success%" . TextFormat::RESET . TextFormat::GRAY . " chance of removing a selected";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "enchantment as a crystal and a " . TextFormat::BOLD . TextFormat::RED . "$fail%" . TextFormat::RESET . TextFormat::GRAY . " chance of";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "removing a random enchantment.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Bring this to the alchemist to remove your enchantment on your item.";
        parent::__construct(VanillaItems::SUGAR(), $customName, $lore, [], [
            self::SUCCESS_PERCENTAGE => new IntTag($success)
        ]);
    }
}