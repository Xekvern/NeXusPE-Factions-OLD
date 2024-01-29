<?php

namespace Xekvern\Core\Server\Auction\Task;

use Xekvern\Core\Server\Auction\Inventory\AuctionConfirmationInventory;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class TickConfirmationInventory extends Task {

    /** @var AuctionConfirmationInventory */
    private $inventory;

    private bool $status = false;

    /**
     * TickConfirmationInventory constructor.
     *
     * @param AuctionConfirmationInventory $inventory
     */
    public function __construct(AuctionConfirmationInventory $inventory) {
        $this->inventory = $inventory;
        $this->inventory->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {
            $this->status = true;
        });
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if($this->status === true){
            $this->getHandler()->cancel();
        }
        if(!$this->inventory->tick()) {
            $this->getHandler()->cancel();
        }
    }
}