<?php
declare(strict_types=1);

namespace Xekvern\Core\Server\BlackAuction;

use pocketmine\item\Item;
use pocketmine\Server;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;

class BlackAuctionEntry {

    /** @var int */
    private $bid = 0;

    /** @var null|string */
    private $bidder = null;

    /** @var null|string */
    private $xuid = null;

    /** @var Item */
    private $item;

    /** @var int */
    private $time;

    /** @var int */
    private $length = 300;

    /**
     * BlackAuctionEntry constructor.
     *
     * @param Item $item
     */
    public function __construct(Item $item) {
        $this->item = $item;
        $this->time = time();
    }

    /**
     * @return string
     */
    public function getItemName(): string {
        return $this->item->hasCustomName() ? $this->item->getCustomName() : $this->item->getName();
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    public function announce(): void {
        $message = implode("§r\n", [
            "§l§8[§6Black Market Auction§8]§r",
            "§l§6Item:§r §f" . $this->getItemName(),
            "§l§6Time:§r §75 Minutes",
            "§7(Use the command /bah to view and place bids.)"
        ]);
        Server::getInstance()->broadcastMessage($message);
    }

    public function announceAlert(): void {
        Server::getInstance()->broadcastMessage("§l§8[§6BLACK AUCTION§8]§r §f" . $this->getItemName() . " §r§ehas 60 seconds remaining!");
    }

    public function placeBid(NexusPlayer $player, int $bid): void {
        if($bid <= $this->bid) {
            $player->sendMessage("§l§c(!)§r §7This bid offer has expired. The new offer is §l§e$" . number_format($this->getNextBidPrice()));
            return;
        }
        if($this->bidder !== null) {
            if($this->bidder === $player->getName()) {
                $player->sendMessage("§l§c(!)§r §7You current possess the highest bidding!");
                return;
            }
            /** @var NexusPlayer $onlineBidder */
            $onlineBidder = Server::getInstance()->getPlayerByPrefix($this->bidder);
            if($onlineBidder !== null) {
                if($onlineBidder->isOnline()) {
                    $onlineBidder->getDataSession()->addToBalance($this->bid, false);
                }
                $onlineBidder->sendMessage("§l§c(!)§r §7You have been outbid by §e" . $player->getName() . " §7on §f" . $this->item->getCustomName());
            }
            else {
                $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE stats SET balance = balance + ? WHERE username = ?");
                $stmt->bind_param("is", $this->bid, $this->bidder);
                $stmt->execute();
                $stmt->close();
            }
        }
        $this->bid = $bid;
        $this->bidder = $player->getName();
        $this->xuid = $player->getXuid();
        if($this->getTimeLeft() <= 15) {
            $this->length += 15;
            Server::getInstance()->broadcastMessage("§l§8[§6BLACK AUCTION§8]§r §eDue to a bid last moment, the auction has been extended!");
        }
        Server::getInstance()->broadcastMessage("§l§8[§6BLACK AUCTION§8]§r §b" . $player->getName() . " §ehas bid §d" . "$" . number_format($bid) . " §eon §f" . $this->getItemName());
        $player->sendMessage("§l§6(!)§r §7You have placed a bid §e$" . number_format($bid) . " §7on §f" . $this->item->getCustomName());
        $player->getDataSession()->subtractFromBalance($bid, false);
    }

    public function sell(): void {
        if($this->bidder !== null) {
            $onlineBidder = Server::getInstance()->getPlayerByPrefix($this->bidder);
            $message = implode("§r\n", [
                "§l§8[§6Black Market Auction§8]§r",
                "§e" . $this->bidder . " §7has won the §b/bah §7on:",
                "§l§6Item:§r §f" . $this->getItemName(),
                "§l§6Winning Bid:§r §e$"  . number_format($this->bid),
            ]);
            Server::getInstance()->broadcastMessage($message);
            if($onlineBidder instanceof NexusPlayer) {
                $onlineBidder->addToInbox($this->item);
            } else {
                $database = Main::getInstance()->getMySQLProvider()->getDatabase();
                $stmt = $database->prepare("SELECT items FROM inboxes WHERE xuid = ?");
                $stmt->bind_param("s", $this->xuid);
                $stmt->execute();
                $stmt->bind_result($inbox);
                $stmt->fetch();
                $stmt->close();
                $items = [];
                if($inbox !== null) {
                    $items = Main::decodeInventory($inbox);
                }
                $items[] = $this->item;
                $inbox = Main::encodeItems($items);
                $stmt = $database->prepare("UPDATE inboxes SET items = ? WHERE xuid = ?");
                $stmt->bind_param("ss", $inbox, $this->xuid);
                $stmt->execute();
                $stmt->close();
            }
        }
        else {
            Server::getInstance()->broadcastMessage("§l§8[§6BLACK AUCTION§8]§r §eNo one has created a bid! Therefore no one has won the /bah");
            $this->bidder = "None";
        }
        Main::getInstance()->getBlackAuctionManager()->resetActiveAuction();
        Main::getInstance()->getBlackAuctionManager()->addSellRecord($this->item, $this->bid, $this->bidder);
    }

    public function getTimeLeft(): int {
        return $this->length - (time() - $this->time);
    }

    /**
     * @return int
     */
    public function getNextBidPrice(): int {
        return $this->bid + $this->getBidIncrement();
    }

    /**
     * @return int
     */
    public function getBidIncrement(): int {
        if($this->bid < 100000) {
            return 10000;
        }
        if($this->bid < 1000000) {
            return 50000;
        }
        if($this->bid < 10000000) {
            return 500000;
        }
        if($this->bid < 20000000) {
            return 1000000;
        }
        if($this->bid < 50000000) {
            return 2000000;
        }
        if($this->bid < 100000000) {
            return 5000000;
        }
        if($this->bid < 250000000) {
            return 10000000;
        }
        if($this->bid < 500000000) {
            return 25000000;
        }
        return 100000000;
    }

    /**
     * @return int
     */
    public function getBid(): int {
        return $this->bid;
    }

    /**
     * @return string|null
     */
    public function getBidder(): ?string {
        return $this->bidder;
    }
}