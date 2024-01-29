<?php

namespace Xekvern\Core\Server\Auction\Inventory;

use Xekvern\Core\Server\Auction\AuctionEntry;
use Xekvern\Core\Server\Auction\Task\TickOffersInventory;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class AuctionOffersInventory extends InvMenu {

    /** @var NexusPlayer */
    private $owner;

    /** @var AuctionEntry[] */
    private $entries = [];

    /**
     * AuctionOffersInventory constructor.
     *
     * @param NexusPlayer $owner
     */
    public function __construct(NexusPlayer $owner) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->owner = $owner;
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Auction House");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $action = $transaction->getAction();
            $slot = $action->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot === 4) {
                $player->removeCurrentWindow();
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player): void {
                    (new AuctionPageInventory())->send($player);
                }), 20);
            }
            if($slot >= 9) {
                if(isset($this->entries[$slot])) {
                    $entry = $this->entries[$slot];
                    $entry->cancel($player);
                    unset($this->entries[$slot]);
                    $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
                    $glass->setCustomName(" ");
                    $this->getInventory()->setItem($slot, $glass);
                }
            }
            return;
        }));
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TickOffersInventory($this), 20);
    }

    public function initItems(): void {
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
        $glass->setCustomName(" ");
        $entries = Nexus::getInstance()->getServerManager()->getAuctionHandler()->getEntriesOf($this->owner);
        for($i = 0; $i < 54; $i++) {
            if($i === 4) {
                $home = VanillaBlocks::OAK_DOOR()->asItem();
                $home->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Home");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Return to the Nexus";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "auction page";
                $home->setLore($lore);
                $this->getInventory()->setItem($i, $home);
                continue;
            }
            if($i >= 9 and (!empty($entries))) {
                $entry = array_shift($entries);
                $this->entries[$i] = $entry;
                $item = clone $entry->getItem();
                $item->getNamedTag()->setString("AntiDupe", "AntiDupe");
                $lore = $item->getLore();
                $time = AuctionEntry::MAX_TIME - (time() - $entry->getStartTime());
                if($time > 0) {
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Click to cancel this offer";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Price(ea): " . TextFormat::GREEN . "$" . number_format($entry->getBuyPrice(), 2);
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "ReNexusing: " . TextFormat::GOLD . number_format($item->getCount());
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Expires: " . TextFormat::GOLD . Utils::secondsToTime($time);
                }
                else {
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "EXPIRED";
                }
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
    }

    public function tick(): bool {
        $this->entries = [];
        $entries = Nexus::getInstance()->getServerManager()->getAuctionHandler()->getEntriesOf($this->owner);
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
        $glass->setCustomName(" ");
        if(empty($entries)) {
            for($i = 9; $i < 54; $i++) {
                $this->getInventory()->setItem($i, $glass);
            }
            return false;
        }
        for($i = 9; $i < 54; $i++) {
            if($i >= 9 and (!empty($entries))) {
                $entry = array_shift($entries);
                $this->entries[$i] = $entry;
                $item = clone $entry->getItem();
                $item->getNamedTag()->setString("AntiDupe", "AntiDupe");
                $lore = $item->getLore();
                $time = AuctionEntry::MAX_TIME - (time() - $entry->getStartTime());
                if($time > 0) {
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Click to cancel this offer";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Price(ea): " . TextFormat::GREEN . "$" . number_format($entry->getBuyPrice(), 2);
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "ReNexusing: " . TextFormat::GOLD . number_format($item->getCount());
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Expires: " . TextFormat::GOLD . Utils::secondsToTime($time);
                }
                else {
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "EXPIRED";
                }
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
        foreach($this->getInventory()->getViewers() as $viewer) {
            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->getInventory());
            }
        }
        return true;
    }

    /**
     * @return AuctionEntry[][]
     */
    public function getEntries(): array {
        return $this->entries;
    }
}