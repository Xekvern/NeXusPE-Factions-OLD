<?php

declare(strict_types = 1);

namespace Xekvern\Core\Player\Gamble\Command\Forms;

use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\Translation;
use Xekvern\Core\Translation\TranslatonException;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Player\Gamble\Command\Forms\CoinFlipConfirmationForm;

class CoinFlipListForm extends MenuForm {

    /**
     * CoinFlipListForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Coin Flip";
        $text = "Select a player to coin flip with.";
        $coinFlips = $player->getCore()->getPlayerManager()->getGambleHandler()->getCoinFlips();
        $options = [];
        $server = $player->getServer();
        foreach($coinFlips as $uuid => $coinFlip) {
            $p = $server->getPlayerExact($uuid);
            if($p !== null && $p->isOnline()) {
                $options[] = new MenuOption($p->getName() . "\n" . TextFormat::RESET . TextFormat::BLACK . "$" . number_format($coinFlip));
            }
        }
        $options[] = new MenuOption("Refresh");
        parent::__construct($title, $text, $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     *
     * @throws TranslatonException
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $text = $this->getOption($selectedOption)->getText();
        if($text === "Refresh") {
            $player->sendForm(new CoinFlipListForm($player));
            return;
        }
        $name = explode("\n", $text)[0];
        $target = $player->getServer()->getPlayerExact($name);
        if(!$target instanceof NexusPlayer) {
            $player->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        if($target->getUniqueId()->toString() === $player->getUniqueId()->toString()) {
            $player->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $amount = $player->getCore()->getPlayerManager()->getGambleHandler()->getCoinFlip($target);
        if($target->getDataSession()->getBalance() < $amount) {
            $player->sendMessage(Translation::getMessage("targetNotEnoughMoney", [
                "name" => TextFormat::RED . $target->getName()
            ]));
            return;
        }
        if($player->getDataSession()->getBalance() < $amount) {
            $player->sendMessage(Translation::getMessage("notEnoughMoney"));
            return;
        }
        $player->sendForm(new CoinFlipConfirmationForm($target));
    }
}