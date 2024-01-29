<?php
declare(strict_types=1);

namespace Xekvern\Core\Server\BlackAuction;

use core\blackauction\task\BlackAuctionHeartbeatTask;
use core\item\types\EnchantmentBox;
use pocketmine\item\Item;
use Xekvern\Core\Nexus;
use Xekvern\Core\Server\Item\Types\SacredStone;

class BlackAuctionHandler {

    const INTERVAL = 1800;

    /** @var Nexus */
    private $core;

    /** @var int */
    private $lastSold = 0;

    /** @var BlackAuctionRecord[] */
    private $recentlySold = [];

    /** @var Item[] */
    private $itemPool = [];

    /** @var null|BlackAuctionEntry */
    private $activeAuction = null;

    /**
     * BlackAuctionHandler constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
        $core->getScheduler()->scheduleRepeatingTask(new BlackAuctionHeartbeatTask($this), 20);
    }

    public function init(): void {
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT soldTime, buyer, item, buyPrice FROM blackAuctionHistory ORDER BY soldTime DESC LIMIT 108");
        $stmt->execute();
        $stmt->bind_result($soldTime, $buyer, $item, $buyPrice);
        while($stmt->fetch()) {
            $this->recentlySold[$soldTime] = new BlackAuctionRecord(Nexus::decodeItem($item), $soldTime, $buyPrice, $buyer);
        }
        $stmt->close();
        $this->recalculateSales();
        $this->initItemPool();
    }

    public function initItemPool(): void {
        $this->itemPool = [
            (new SacredStone())->getItemForm()->setCount(5),
        ];
    }

    /**
     * @return BlackAuctionEntry|null
     */
    public function getActiveAuction(): ?BlackAuctionEntry {
        return $this->activeAuction;
    }

    /**
     * @return int
     */
    public function getTimeBeforeNext(): int {
        return self::INTERVAL - (time() - $this->lastSold);
    }

    public function startAuction(): void {
        $item = $this->selectItem();
        $entry = new BlackAuctionEntry($item);
        $entry->announce();
        $this->activeAuction = $entry;
    }

    public function resetActiveAuction(): void {
        $this->activeAuction = null;
    }

    /**
     * @param Item $item
     * @param int $buyPrice
     * @param string $buyer
     */
    public function addSellRecord(Item $item, int $buyPrice, string $buyer): void {
        $sellTime = time();
        $this->recentlySold[$sellTime] = new BlackAuctionRecord($item, $sellTime, $buyPrice, $buyer);
        $item = Nexus::encodeItem($item);
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("INSERT INTO blackAuctionHistory(soldTime, buyer, item, buyPrice) VALUES(?, ?, ?, ?)");
        $stmt->bind_param("issi", $sellTime, $buyer, $item, $buyPrice);
        $stmt->execute();
        $stmt->close();
        $this->recalculateSales();
    }

    public function recalculateSales(): void {
        $maxTime = 0;
        foreach($this->recentlySold as $sold) {
            if($sold->getSoldTime() > $maxTime) {
                $maxTime = $sold->getSoldTime();
            }
        }
        $this->lastSold = $maxTime;
        krsort($this->recentlySold);
        if(count($this->recentlySold) > 108) {
            $this->recentlySold = array_slice($this->recentlySold, 0, 108, true);
        }
    }

    /**
     * @param int $loop
     *
     * @return Item
     */
    public function selectItem(int $loop = 0): Item {
        $item = $this->itemPool[array_rand($this->itemPool)];
        $lastTen = array_slice($this->recentlySold, 0, 10, true);
        foreach($lastTen as $record) {
            if($record->getItem()->equals($item)) {
                return $this->selectItem(++$loop);
            }
        }
        if($loop >= 10) {
            return $item;
        }
        return $item;
    }

    /**
     * @return BlackAuctionRecord[]
     */
    public function getRecentlySold(): array {
        return $this->recentlySold;
    }
}