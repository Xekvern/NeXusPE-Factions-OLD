<?php

namespace Xekvern\Core\Server\Price\Inventory;

use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Server\Price\PriceEntry;
use Xekvern\Core\Server\Price\ShopPlace;
use Xekvern\Core\Translation\Translation;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Price\Forms\TransactionForm;

class ShopListInventory extends InvMenu {

    /** @var ShopPlace */
    private $place;

    /** @var PriceEntry */
    private $entries;

    /**
     * ShopMainInventory constructor.
     *
     * @param ShopPlace $place
     */
    public function __construct(ShopPlace $place) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_CHEST));
        $this->initItems($place);
        $this->place = $place;
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . $place->getName());
        $this->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $action = $transaction->getAction();
            $player = $transaction->getPlayer();
            $slot = $action->getSlot();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot === 26) {
                $player->removeCurrentWindow($action->getInventory());
                $player->sendDelayedWindow(new ShopMainInventory(Nexus::getInstance()->getServerManager()->getPriceHandler()->getPlaces()));
            }
            if(isset($this->entries[$slot])) {
                $entry = $this->entries[$slot];
                if($entry->getLevel() !== null and !$player->getDataSession()->getCurrentLevel() >= $entry->getLevel()) {
                    $player->sendMessage(Translation::getMessage("noPermission"));
                    return;
                }
                $player->removeCurrentWindow($action->getInventory());
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entry, $player) extends Task {

                    /** @var PriceEntry */
                    private $entry;

                    /** @var NexusPlayer */
                    private $player;

                    /**
                     *  constructor.
                     *
                     * @param PriceEntry $entry
                     * @param NexusPlayer $player
                     */
                    public function __construct(PriceEntry $entry, NexusPlayer $player) {
                        $this->entry = $entry;
                        $this->player = $player;
                    }

                    /**
                     * @param int $currentTick
                     */
                    public function onRun(): void {
                        if($this->player->isOnline() and (!$this->player->isClosed())) {
                            $this->player->sendForm(new TransactionForm($this->player, $this->entry));
                        }
                    }
                }, 20);
            }
        }));
    }

    /**
     * @param ShopPlace $place
     */
    public function initItems(ShopPlace $place): void {
        $glass = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->asItem();
        $glass->setCustomName(" ");
        $entries = $place->getEntries();
        for($i = 0; $i < 27; $i++) {
            $entry = array_shift($entries);
            if($entry instanceof PriceEntry) {
                $display = clone $entry->getItem();
                $this->entries[$i] = $entry;
                $lore = $display->getLore();
                $add = [];
                $add[] = "";
                if($entry->getBuyPrice() !== null) {
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Buy Price(ea): " . TextFormat::GREEN . "$" . number_format($entry->getBuyPrice());
                }
                else {
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Buy Price(ea): " . TextFormat::RED . "Not buyable";
                }
                if($entry->getSellPrice() !== null) {
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Sell Price(ea): " . TextFormat::GREEN . "$" . number_format($entry->getSellPrice());
                }
                else {
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Sell Price(ea): " . TextFormat::RED . "Not sellable";
                }
                if($entry->getLevel() !== null) {
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Level Required: " . TextFormat::GREEN . number_format($entry->getLevel());
                }
                else {
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Level Required: " . TextFormat::RED . "None";
                }
                $display->setLore(array_merge($lore, $add));
                $this->getInventory()->setItem($i, $display);
            }
            else {
                $this->getInventory()->setItem($i, $glass);
            }
        }
        $home = VanillaBlocks::OAK_DOOR()->asItem();
        $home->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Home");
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Return to the main";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "shopping menu";
        $home->setLore($lore);
        $this->getInventory()->setItem(26, $home);
    }
}