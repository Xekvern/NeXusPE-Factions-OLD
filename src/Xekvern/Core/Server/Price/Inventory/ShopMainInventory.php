<?php

namespace Xekvern\Core\Server\Price\Inventory;

use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Server\Price\ShopPlace;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\TextFormat;

class ShopMainInventory extends InvMenu {

    /** @var ShopPlace[] */
    private $places;

    /**
     * ShopMainInventory constructor.
     *
     * @param ShopPlace[] $places
     */
    public function __construct(array $places) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_CHEST));
        $this->initItems($places);
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Shop");
        $this->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $action = $transaction->getAction();
            $player = $transaction->getPlayer();
            $slot = $action->getSlot();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if(isset($this->places[$slot])) {
                $place = $this->places[$slot];
                $player->removeCurrentWindow($action->getInventory());
                $player->sendDelayedWindow(new ShopListInventory($place));
            }
        }));
    }

    /**
     * @param ShopPlace[] $places
     */
    public function initItems(array $places): void {
        $glass = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem();
        $glass->setCustomName(" ");
        for($i = 0; $i < 27; $i++) {
            if(($i >= 11 and $i <= 15) or ($i >= 20 and $i <= 24)) {
                $place = array_shift($places);
                if($place instanceof ShopPlace) {
                    $display = $place->getItem();
                    $this->places[$i] = $place;
                    $display->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . $place->getName());
                    $display->setLore([TextFormat::RESET . TextFormat::GRAY . "Tap to view this category."]);
                    $this->getInventory()->setItem($i, $display);
                }
            }
            else {
                $this->getInventory()->setItem($i, $glass);
            }
        }
    }
}