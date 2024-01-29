<?php

namespace Xekvern\Core\Server\Item\Types;

use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Item\ItemHandler;
use Xekvern\Core\Server\Item\Utils\CustomItem;

class WaterCannon extends CustomItem {

    const USES = "Uses";
    const TIER = "Tier";
    const TYPE = "Water";
    const RANGE = "Mid";

    /**
     * WaterCannon constructor.
     *
     * @param int $tier
     * @param int $uses
     */
    public function __construct(int $tier, int $uses, string $type, string $range) {
        $customName = TextFormat::RESET . TextFormat::BLUE . TextFormat::BOLD . "Water Cannon";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "The greater the tier, the larger the water-bomb!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::RED . "Uses: " . TextFormat::WHITE . number_format((int)$uses);
        $lore[] = TextFormat::RESET . TextFormat::RED . "Tier: " . TextFormat::WHITE . $tier;
        $lore[] = TextFormat::RESET . TextFormat::RED . "Range: " . TextFormat::WHITE . $range;
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Each fire will require " . TextFormat::YELLOW . ItemHandler::getWaterFuelAmountByTier($tier) . TextFormat::GRAY . " Water";
        parent::__construct(VanillaItems::ECHO_SHARD(), $customName, $lore, [],
        [
            self::USES => new IntTag($uses),
            self::TIER => new IntTag($tier),
            self::TYPE => new StringTag($type),
            self::RANGE => new StringTag($range)
        ]);
    }
}