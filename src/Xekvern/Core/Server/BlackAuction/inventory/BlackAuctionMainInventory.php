<?php

namespace core\blackauction\inventory;

use core\blackauction\forms\SubmitBidForm;
use core\blackauction\task\TickMainInventory;
use core\Main;
use core\MainPlayer;
use core\utils\Utils;
use core\libs\muqsit\invmenu\InvMenu;
use core\libs\muqsit\invmenu\InvMenuHandler;
use core\libs\muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class BlackAuctionMainInventory extends InvMenu {

    /**
     * AuctionPageInventory constructor.
     *
     * @param int $page
     */
    public function __construct(int $page = 1) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_HOPPER));
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::GOLD . TextFormat::BOLD . "Black Market Auction");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $action = $transaction->getAction();
            $player = $transaction->getPlayer();
            $slot = $action->getSlot();
            $manager = Main::getInstance()->getBlackAuctionManager();
            if(!$player instanceof MainPlayer) {
                return;
            }
            if($slot === 0) {
                $player->removeCurrentWindow();
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player): void {
                    $player->getInbox()->send($player);
                }), 20);
            }
            if($slot === 4) {
                $player->removeCurrentWindow();
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($manager, $player): void {
                    (new BlackAuctionRecordsInventory($manager->getRecentlySold()))->send($player);
                }), 20);
            }
            if($slot === 2) {
                $active = $manager->getActiveAuction();
                if($active !== null) {
                    $player->removeCurrentWindow();
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($active, $player): void {
                        $player->sendForm(new SubmitBidForm($active, $active->getNextBidPrice()));
                    }), 20);
                    return;
                }
                $player->playErrorSound();
            }
        }));
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TickMainInventory($this), 20);
    }

    public function initItems(): void {
        $manager = Main::getInstance()->getBlackAuctionManager();
        $timeLeft = Utils::secondsToCD($manager->getTimeBeforeNext());
        $current = $manager->getActiveAuction();
        if($manager->getTimeBeforeNext() > 0) {
            $alert = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem();
            $alert->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "???");
            $lore = [];
            $lore[] = TextFormat::RESET . TextFormat::RED . "Next Auction in $timeLeft...";
            $alert->setLore($lore);
            for($i = 1; $i < 4; $i++) {
                $this->getInventory()->setItem($i, $alert);
            }
        }
        elseif($current !== null) {
            $alert = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
            $alert->setCustomName(" ");
            for($i = 1; $i < 4; $i++) {
                if($i === 2) {
                    $item = clone $current->getItem();
                    $lore = $item->getLore();
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "------------------------------";
                    $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "CLICK" . TextFormat::RESET . TextFormat::GRAY . " to bid " . TextFormat::GREEN . "$" . number_format($current->getNextBidPrice()) . "!";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GOLD . "Current Bid: " . TextFormat::YELLOW . "$" . number_format($current->getBid());
                    if($current->getBidder() !== null) {
                        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Bidder: " . TextFormat::YELLOW . $current->getBidder();
                    }
                    $lore[] = TextFormat::RESET . TextFormat::GOLD . "Bid Increment: " . TextFormat::YELLOW . "$" . number_format($current->getBidIncrement());
                    $lore[] = TextFormat::RESET . TextFormat::GOLD . "Bidding ends in: " . TextFormat::YELLOW . Utils::secondsToCD($current->getTimeLeft());
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "------------------------------";
                    $item->setLore($lore);
                    $this->getInventory()->setItem($i, $item);
                    continue;
                }
                $this->getInventory()->setItem($i, $alert);
            }
        }
        $collection = VanillaBlocks::CHEST()->asItem();
        $collection->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Collection Bin");
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Items that you win in the";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "/bah will appear in here";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "for you to collect!";
        $collection->setLore($lore);
        $this->getInventory()->setItem(0, $collection);
        $recent = VanillaItems::WRITABLE_BOOK();
        $recent->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Recently Sold Items");
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Click to view recently sold /bah items!";
        $recent->setLore($lore);
        $this->getInventory()->setItem(4, $recent);
    }

    public function tick(): bool {
        $manager = Main::getInstance()->getBlackAuctionManager();
        $timeLeft = Utils::secondsToCD($manager->getTimeBeforeNext());
        $current = $manager->getActiveAuction();
        if($manager->getTimeBeforeNext() > 0) {
            $alert = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem();
            $alert->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "???");
            $lore = [];
            $lore[] = TextFormat::RESET . TextFormat::RED . "Next Auction in $timeLeft...";
            $alert->setLore($lore);
            for($i = 1; $i < 4; $i++) {
                $this->getInventory()->setItem($i, $alert);
            }
        }
        elseif($current !== null) {
            $alert = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
            $alert->setCustomName(" ");
            for($i = 1; $i < 4; $i++) {
                if($i === 2) {
                    $item = clone $current->getItem();
                    $lore = $item->getLore();
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "--------------------------";
                    $lore[] = TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "CLICK" . TextFormat::RESET . TextFormat::GRAY . " to bid " . TextFormat::GREEN . "$" . number_format($current->getNextBidPrice()) . "!";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GOLD . "Current Bid: " . TextFormat::YELLOW . "$" . number_format($current->getBid());
                    if($current->getBidder() !== null) {
                        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Bidder: " . TextFormat::YELLOW . $current->getBidder();
                    }
                    $lore[] = TextFormat::RESET . TextFormat::GOLD . "Bid Increment: " . TextFormat::YELLOW . "$" . number_format($current->getBidIncrement());
                    $lore[] = TextFormat::RESET . TextFormat::GOLD . "Bidding ends in: " . TextFormat::YELLOW . Utils::secondsToCD($current->getTimeLeft());
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "--------------------------";
                    $item->setLore($lore);
                    $this->getInventory()->setItem($i, $item);
                    continue;
                }
                $this->getInventory()->setItem($i, $alert);
            }
        }
        foreach($this->getInventory()->getViewers() as $viewer) {
            if($viewer instanceof MainPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->getInventory());
            }
        }
        return true;
    }
}