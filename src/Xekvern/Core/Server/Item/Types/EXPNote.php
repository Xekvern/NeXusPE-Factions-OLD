<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Types;

use Xekvern\Core\Player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;
use Xekvern\Core\Server\Item\Utils\CustomItem;

class EXPNote extends CustomItem {

    const EXP = "EXP";

    /**
     * EXPNote constructor.
     *
     * @param int $amount
     * @param string $withdrawer
     */
    public function __construct(int $amount, string $withdrawer = "Admin") {
        $customName = TextFormat::RESET . TextFormat::DARK_AQUA . TextFormat::BOLD . number_format($amount) . " Level XP";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Tap anywhere to claim.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Withdrawn by " . TextFormat::RESET . TextFormat::WHITE . $withdrawer;
        parent::__construct(VanillaItems::PAPER(), $customName, $lore, [], [
            self::EXP => new IntTag($amount)
        ]);
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param CompoundTag $tag
     * @param int $face
     * @param Block $blockClicked
     */
    public static function execute(NexusPlayer $player, Inventory $inventory, Item $item, CompoundTag $tag, int $face, Block $blockClicked): void {
        $amount = $tag->getInt(self::EXP);
        $player->playXpLevelUpSound();
        $player->getDataSession()->addXPProgress($amount);
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
    }
}