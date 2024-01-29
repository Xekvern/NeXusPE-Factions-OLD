<?php

namespace core\blackauction\command;

use core\command\utils\Command;
use core\blackauction\command\subCommands\BidSubCommand;
use core\blackauction\inventory\BlackAuctionMainInventory;
use core\MainPlayer;
use core\Translations;
use pocketmine\command\CommandSender;

class BlackAuctionHouseCommand extends Command {

    /**
     * AuctionHouseCommand constructor.
     */
    public function __construct() {
        parent::__construct("bah", "Open black market auction house menu", "/bah");
        $this->addSubCommand(new BidSubCommand());
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof MainPlayer) {
            if(isset($args[0])) {
                $subCommand = $this->getSubCommand($args[0]);
                if($subCommand !== null) {
                    $subCommand->execute($sender, $commandLabel, $args);
                    return;
                }
                $sender->sendMessage(str_replace("{USAGE}", $this->getUsage(), Translations::TYPES["usage"]));
                return;
            }
            $inventory = new BlackAuctionMainInventory();
            $inventory->send($sender);
            return;
        }
        $sender->sendMessage(Translations::TYPES["no-permission-cmd"]);
    }
}