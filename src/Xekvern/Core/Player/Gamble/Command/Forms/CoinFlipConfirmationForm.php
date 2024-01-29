<?php

namespace Xekvern\Core\Player\Gamble\Command\Forms;

use Xekvern\Core\Player\Gamble\Event\CoinFlipLoseEvent;
use Xekvern\Core\Player\Gamble\Event\CoinFlipWinEvent;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\Translation;
use Xekvern\Core\Translation\TranslatonException;
use libs\form\ModalForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CoinFlipConfirmationForm extends ModalForm {

    /** @var NexusPlayer */
    private $target;

    /**
     * CoinFlipConfirmationForm constructor.
     *
     * @param NexusPlayer $target
     */
    public function __construct(NexusPlayer $target) {
        $this->target = $target;
        $amount = $target->getCore()->getPlayerManager()->getGambleHandler()->getCoinFlip($target);
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Coin Flip";
        $text = "Are you sure you would like to do a $$amount coin flip with {$target->getName()}?";
        parent::__construct($title, $text);
    }

    /**
     * @param Player $player
     * @param bool $choice
     *
     * @throws TranslatonException
     */
    public function onSubmit(Player $player, bool $choice): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($choice == true) {
            if((!$this->target instanceof NexusPlayer) or (!$this->target->isOnline())) {
                $player->sendMessage(Translation::getMessage("invalidPlayer"));
                return;
            }
            $gambleManager = $player->getCore()->getPlayerManager()->getGambleHandler();
            $amount = $gambleManager->getCoinFlip($this->target);
            if($amount === null) {
                $player->sendMessage(Translation::getMessage("invalidPlayer"));
                return;
            }
            if($this->target->getDataSession()->getBalance() < $amount) {
                $player->sendMessage(Translation::getMessage("targetNotEnoughMoney", [
                    "name" => TextFormat::RED . $this->target->getName()
                ]));
                return;
            }
            $chance = mt_rand(1, 100);
            $winner = $player;
            $loser = $this->target;
            if($chance > 50) {
                $winner = $this->target;
                $loser = $player;
            }
            $gambleManager->addWin($winner);
            $gambleManager->addLoss($loser);
            $gambleManager->getRecord($winner, $wins, $losses);
            $gambleManager->getRecord($loser, $wins2, $losses2);
            $ev = new CoinFlipWinEvent($winner, $amount);
            $ev->call();
            $ev = new CoinFlipLoseEvent($loser, $amount);
            $ev->call();
            $winTotal = $amount * 2;
            $player->getServer()->broadcastMessage(TextFormat::GREEN . $winner->getName() . TextFormat::DARK_GRAY . " ($wins-$losses)" . TextFormat::GRAY . " has defeated " . TextFormat::RED . $loser->getName() . TextFormat::DARK_GRAY . " ($wins2-$losses2) " . TextFormat::GRAY . "in a " . TextFormat::LIGHT_PURPLE . "$" . number_format((int)$winTotal) . TextFormat::GRAY . " coin flip");
            $winner->getDataSession()->addToBalance($amount);
            $loser->getDataSession()->subtractFromBalance($amount);
            $gambleManager->removeCoinFlip($this->target);
        }
        return;
    }
}