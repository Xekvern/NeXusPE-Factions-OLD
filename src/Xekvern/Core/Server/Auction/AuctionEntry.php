<?php

namespace Xekvern\Core\Server\Auction;

use pocketmine\item\Durable;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\Translation;
use Xekvern\Core\Translation\TranslationException;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Item\ItemHandler;

class AuctionEntry {

    const MAX_TIME = 43200;

    /** @var Item */
    private $item;

    /** @var int */
    private $id;

    /** @var int */
    private $startTime;

    /** @var int */
    private $buyPrice;

    /** @var string */
    private $seller;

    /** @var bool */
    private $cancelled = false;

    /**
     * AuctionEntry constructor.
     *
     * @param Item $item
     * @param string $seller
     * @param int $identifier
     * @param int $startTime
     * @param int $buyPrice
     */
    public function __construct(Item $item, string $seller, int $identifier, int $startTime, int $buyPrice) {
        $this->item = $item;
        $this->seller = $seller;
        $this->id = $identifier;
        $this->startTime = $startTime;
        $this->buyPrice = $buyPrice;
    }

    /**
     * @return int
     */
    public function getIdentifier(): int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSeller(): string {
        return $this->seller;
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
    public function getStartTime(): int {
        return $this->startTime;
    }

    /**
     * @param NexusPlayer $player
     * @param int $amount
     */
    public function buy(NexusPlayer $player, int $amount): void {
        $count = $this->item->getCount();
        $price = $this->getBuyPrice() * $amount;
        if($player->getName() === $this->getSeller()) {
            $player->sendMessage(Translation::getMessage("invalidAuction"));
            return;
        }
        if($amount <= 0 or (!is_numeric($amount))) {
            $player->sendMessage(Translation::getMessage("invalidAmount"));
            return;
        }
        if($player->getName() === $this->getSeller()) {
            $player->sendMessage(Translation::getMessage("invalidAuction"));
            return;
        }
        if($player->getDataSession()->getBalance() < $price) {
            $player->sendMessage(Translation::getMessage("notEnoughMoney"));
            return;
        }
        if(!$this->isRunning()) {
            $player->sendMessage(Translation::getMessage("invalidAuction"));
            return;
        }
        if($count < $amount) {
            $player->sendMessage(Translation::getMessage("invalidAuction"));
            return;
        }
        $seller = Server::getInstance()->getPlayerExact($this->seller);
        $name = $this->getItem()->hasCustomName() ? $this->getItem()->getCustomName() : $this->getItem()->getName();
        $name .= TextFormat::RESET . TextFormat::GRAY . " * " . TextFormat::WHITE . $amount;
        if($seller instanceof NexusPlayer) {
            if(!$seller->isLoaded()) {
                $seller->sendMessage(Translation::getMessage("errorOccurred"));
                return;
            }
            $seller->getDataSession()->addToBalance($price);
            $seller->sendMessage(Translation::getMessage("buyAuction", [
                "item" => $name,
                "name" => TextFormat::DARK_PURPLE . $player->getName(),
                "amount" => TextFormat::YELLOW . "$" . number_format($price),
            ]));
            $seller->playXpLevelUpSound();
        }
        else {
            $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE stats SET balance = balance + ? WHERE username = ?");
            $stmt->bind_param("is", $price, $this->seller);
            $stmt->execute();
            $stmt->close();
        }
        $player->getDataSession()->subtractFromBalance($price);
        $player->getInventory()->addItem($this->item->setCount($amount));
        $player->playNoteSound();
        if($count === $amount) {
            Nexus::getInstance()->getServerManager()->getAuctionHandler()->removeEntry($this->getIdentifier());
        }
        else {
            $this->item = $this->item->setCount($count - $amount);
        }
        Nexus::getInstance()->getServerManager()->getAuctionHandler()->updateEntry($this);
    }

    /**
     * @return int
     */
    public function getBuyPrice(): int {
        return $this->buyPrice;
    }

    /**
     * @return bool
     */
    public function isRunning(): bool {
        return ((time() - $this->startTime) < self::MAX_TIME) and (!$this->cancelled) and Nexus::getInstance()->getServerManager()->getAuctionHandler()->entryExists($this->id);
    }

    /**
     * @param NexusPlayer $seller
     *
     * @throws TranslationException
     */
    public function cancel(NexusPlayer $seller): void {
        $this->cancelled = true;
        if($seller->getName() === $this->seller) {
            $seller->sendMessage(Translation::getMessage("noSell"));
            if($this->item instanceof Durable) {
                $seller->getInventory()->addItem(ItemHandler::setLoreForItem($this->item));
            } else {
                $seller->getInventory()->addItem($this->item);
            }
            Nexus::getInstance()->getServerManager()->getAuctionHandler()->removeEntry($this->getIdentifier());
        }
        else {
            $seller->sendMessage(Translation::getMessage("invalidPlayer"));
        }
    }
}