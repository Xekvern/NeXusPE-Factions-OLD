<?php

namespace core\blackauction\forms;

use core\blackauction\BlackAuctionEntry;
use core\MainPlayer;
use core\libs\form\ModalForm;
use core\Translations;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SubmitBidForm extends ModalForm {

    /** @var BlackAuctionEntry */
    private $entry;

    /** @var int */
    private $bid;

    /**
     * SubmitBidForm constructor.
     *
     * @param BlackAuctionEntry $entry
     * @param int $bid
     */
    public function __construct(BlackAuctionEntry $entry, int $bid) {
        $this->entry = $entry;
        $this->bid = $bid;
        $item = $entry->getItem();
        $title = TextFormat::BOLD . TextFormat::GOLD . "Black Market Auction";
        $text = "Are you sure you would like to bid $" . number_format($bid) . " for: \n \n" . implode("\n", array_merge([$entry->getItemName()], $item->getLore()));
        parent::__construct($title, $text);
    }

    /**
     * @param Player $player
     * @param bool $choice
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, bool $choice): void {
        if(!$player instanceof MainPlayer) {
            return;
        }
        if($this->entry->getTimeLeft() <= 0) {
            $player->sendMessage("§l§c(!)§r §7This bidding has already ended!");
            return;
        }
        if($choice == true) {
            if($player->getBalance() < $this->bid) {
                $player->sendMessage(Translations::TYPES["notEnoughMoney"]);
                return;
            }
            $this->entry->placeBid($player, $this->bid);
        }
    }
}