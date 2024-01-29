<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Types;

use Xekvern\Core\Server\Item\Utils\ClickableItem;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\TranslatonException;
use pocketmine\block\Block;
use pocketmine\block\utils\MobHeadType;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Item\Types\Soul;

class Head extends ClickableItem {

    const PLAYER = "Player";

    /**
     * Head constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $customName = TextFormat::RESET . TextFormat::DARK_RED . TextFormat::BOLD . "{$player->getName()}'s Head";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "You will receive " . TextFormat::BOLD . TextFormat::AQUA . "1-2 Souls";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Tap anywhere to claim.";
        parent::__construct(VanillaBlocks::MOB_HEAD()->setMobHeadType(MobHeadType::PLAYER())->asItem(), $customName, $lore, [], [
            self::PLAYER => new StringTag($player->getUniqueId()->toString()),
            "UniqueId" => new StringTag(uniqid())
        ], 3);
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param CompoundTag $tag
     * @param int $face
     * @param Block $blockClicked
     *
     * @throws TranslatonException
     */
    public static function execute(NexusPlayer $player, Inventory $inventory, Item $item, CompoundTag $tag, int $face, Block $blockClicked): void {
        $item = (new Soul())->getItemForm()->setCount(mt_rand(1, 2));
        if($inventory->canAddItem($item)) {
            $inventory->addItem($item);
        } else {
            $player->getWorld()->dropItem($player->getPosition(), $item);
        }
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
    }
}