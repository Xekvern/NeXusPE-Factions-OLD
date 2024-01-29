<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Types;

use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Item\Utils\CustomItem;

class LuckyBlock extends CustomItem {

    const LUCK = "Luck";

    /**
     * LuckyBlock constructor.
     *
     * @param int $luck
     */
    public function __construct(int $luck) {
        $customName = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Lucky Block";
        $unluck = 100 - $luck;
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "You have a " . TextFormat::BOLD . TextFormat::GREEN . "$luck%" . TextFormat::RESET . TextFormat::GRAY . " of getting something";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "good and a " . TextFormat::BOLD . TextFormat::RED . "$unluck%" . TextFormat::RESET . TextFormat::GRAY . " of getting something bad.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Break this for a surprise.";
        parent::__construct(VanillaBlocks::GLAZED_TERRACOTTA()->setColor(DyeColor::BLACK())->asItem(), $customName, $lore, [], [
            self::LUCK => new IntTag($luck)
        ]);
    }
}