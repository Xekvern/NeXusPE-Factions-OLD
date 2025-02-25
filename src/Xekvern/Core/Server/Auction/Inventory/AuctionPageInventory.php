<?php

namespace Xekvern\Core\Server\Auction\Inventory;

use Xekvern\Core\Server\Auction\AuctionEntry;
use Xekvern\Core\Server\Auction\Forms\AuctionSearchForm;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Durable;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class AuctionPageInventory extends InvMenu {

    /** @var int */
    private $page;

    /** @var AuctionEntry[][] */
    private $entries;

    /**
     * AuctionPageInventory constructor.
     *
     * @param int $page
     */
    public function __construct(int $page = 1) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->page = $page;
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Auction House");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $action = $transaction->getAction();
            $player = $transaction->getPlayer();
            $itemClicked = $transaction->getItemClicked();
            $slot = $action->getSlot();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot === 0) {
                $player->removeCurrentWindow();
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player): void {
                    $player->sendForm(new AuctionSearchForm());
                }), 20);
            }
            if($slot === 8) {
                $player->removeCurrentWindow();
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player): void {
                    (new AuctionOffersInventory($player))->send($player);
                }), 20);
            }
            if($slot === 47 and $this->page > 1) {
                --$this->page;
                $this->initItems();
            }
            if($slot >= 9 and $slot <= 44) {
                if($itemClicked instanceof Durable) {
                    $identifier = $itemClicked->getTypeId() . ":0";
                }
                else {
                    $identifier = $itemClicked->getTypeId() . ":" . $itemClicked->getStateId();
                }
                if(isset($this->entries[$identifier])) {
                    $entries = $this->entries[$identifier];
                    foreach($entries as $id => $entry) {
                        if(!$entry->isRunning()) {
                            unset($entries[$id]);
                        }
                    }
                    if(!empty($entries)) {
                        $player->removeCurrentWindow();
                        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($entries, $player): void {
                            (new AuctionListInventory($entries, 1))->send($player);
                        }), 20);
                    }
                    else {
                        $player->playErrorSound();
                        $this->initItems();
                        foreach($this->getInventory()->getViewers() as $viewer) {
                            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                                $viewer->getNetworkSession()->getInvManager()->syncContents($this->getInventory());
                            }
                        }
                    }
                }
            }
            if($slot === 51 and $this->page < Nexus::getInstance()->getServerManager()->getAuctionHandler()->getMaxPages()) {
                ++$this->page;
                $this->initItems();
            }
            return;
        }));
        $this->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {
        });
    }

    public function initItems(): void {
        $this->entries = Nexus::getInstance()->getServerManager()->getAuctionHandler()->getPage($this->page);
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
        $glass->setCustomName(" ");
        $whiteGlass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::WHITE())->asItem();
        $whiteGlass->setCustomName(" ");
        for($i = 0; $i < 9; $i++) {
            if($i === 0) {
                $offers = VanillaItems::COMPASS();
                $offers->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Search");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Search for a certain key";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "word within entries.";
                $offers->setLore($lore);
                $this->getInventory()->setItem($i, $offers);
                continue;
            }
            if($i === 8) {
                $offers = VanillaItems::BOOK();
                $offers->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Offers");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Check your current items";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "that are being sold";
                $offers->setLore($lore);
                $this->getInventory()->setItem($i, $offers);
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
        $i = 9;
        foreach($this->entries as $itemInfo => $entryCategory) {
            $parts = explode(":", $itemInfo);
            $count = 0;
            $availableCount = 0;
            $lowest = null;
            foreach($entryCategory as $entry) {
                $count += $entry->getItem()->getCount();
                if($lowest === null) {
                    $lowest = $entry->getBuyPrice();
                    $availableCount = $entry->getItem()->getCount();
                }
                elseif($lowest > $entry->getBuyPrice()) {
                    $lowest = $entry->getBuyPrice();
                    $availableCount = $entry->getItem()->getCount();
                }
                $entryItem = $entry->getItem();
            }
            $item = $entryItem;
            $lore = [];
            $lore[] = "";
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "For sale: " . TextFormat::WHITE . TextFormat::BOLD . number_format($count);
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "Lowest price(ea): " . TextFormat::GREEN . "$" . number_format($lowest, 2);
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "Available: " . TextFormat::GOLD . number_format($availableCount);
            $item->setLore($lore);
            $item->getNamedTag()->setString("AntiDupe", "AntiDupe");
            $this->getInventory()->setItem($i++, $item);
        }
        for($i = (9 + count($this->entries)); $i < 54; $i++) {
            if($i === 47 and $this->page > 1) {
                $prevPage = $this->page - 1;
                $pageSelector = VanillaBlocks::REDSTONE()->asItem();
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "<  Previous page ($prevPage)");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            if($i === 49) {
                $page = VanillaItems::PAPER();
                $page->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Page $this->page");
                $this->getInventory()->setItem($i, $page);
                continue;
            }
            if($i === 51 and $this->page < Nexus::getInstance()->getServerManager()->getAuctionHandler()->getMaxPages()) {
                $nextPage = $this->page + 1;
                $pageSelector = VanillaBlocks::REDSTONE()->asItem();
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Next page ($nextPage)  >");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            if($i < 45) {
                $this->getInventory()->setItem($i, $whiteGlass);
            }
            else {
                $this->getInventory()->setItem($i, $glass);
            }
        }
    }

    /**
     * @return int
     */
    public function getPage(): int {
        return $this->page;
    }

    /**
     * @return AuctionEntry[][]
     */
    public function getEntries(): array {
        return $this->entries;
    }
}