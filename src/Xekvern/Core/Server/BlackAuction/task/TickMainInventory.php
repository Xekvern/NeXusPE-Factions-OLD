<?php

namespace core\blackauction\task;

use core\blackauction\inventory\BlackAuctionMainInventory;
use core\libs\muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class TickMainInventory extends Task {

    /** @var BlackAuctionMainInventory */
    private $inventory;

    /**
     * TickMainInventory constructor.
     *
     * @param BlackAuctionMainInventory $inventory
     */
    public function __construct(BlackAuctionMainInventory $inventory) {
        $this->inventory = $inventory;
        $this->inventory->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {

        });
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if(!$this->inventory->tick()) {
            $this->getHandler()->cancel();
        }
    }
}