<?php

namespace Xekvern\Core\Server\Auction\Task;

use Xekvern\Core\Server\Auction\Inventory\AuctionOffersInventory;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class TickOffersInventory extends Task {

    /** @var AuctionOffersInventory */
    private AuctionOffersInventory $inventory;

    private bool $status = false;

    /**
     * TickOffersInventory constructor.
     *
     * @param AuctionOffersInventory $inventory
     */
    public function __construct(AuctionOffersInventory $inventory) {
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