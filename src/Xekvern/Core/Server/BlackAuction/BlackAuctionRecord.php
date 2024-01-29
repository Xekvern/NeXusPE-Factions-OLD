<?php
declare(strict_types=1);

namespace Xekvern\Core\Server\BlackAuction;

use pocketmine\item\Item;

class BlackAuctionRecord {

    /** @var Item */
    private $item;

    /** @var int */
    private $soldTime;

    /** @var int */
    private $buyPrice;

    /** @var string */
    private $buyer;

    /**
     * BlackAuctionEntry constructor.
     *
     * @param Item $item
     * @param int $soldTime
     * @param int $buyPrice
     * @param string $buyer
     */
    public function __construct(Item $item, int $soldTime, int $buyPrice, string $buyer) {
        $this->item = $item;
        $this->soldTime = $soldTime;
        $this->buyPrice = $buyPrice;
        $this->buyer = $buyer;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    /**
     * @return int
     */
    public function getSoldTime(): int {
        return $this->soldTime;
    }

    /**
     * @return int
     */
    public function getBuyPrice(): int {
        return $this->buyPrice;
    }

    /**
     * @return string
     */
    public function getBuyer(): string {
        return $this->buyer;
    }
}