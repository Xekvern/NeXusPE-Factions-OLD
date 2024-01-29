<?php
declare(strict_types=1);

namespace core\blackauction\command\subCommands;

use core\command\utils\args\IntegerArgument;
use core\command\utils\SubCommand;
use core\game\auction\AuctionEntry;
use core\blackauction\forms\SubmitBidForm;
use core\Main;
use core\MainPlayer;
use core\Translations;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class BidSubCommand extends SubCommand {

    /**
     * SellSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("bid", "/bah bid");
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
            $manager = Main::getInstance()->getBlackAuctionManager();
            $active = $manager->getActiveAuction();
            if($active !== null) {
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($active, $sender): void {
                    $sender->sendForm(new SubmitBidForm($active, $active->getNextBidPrice()));
                }), 20);
                return;
            }
            $sender->sendMessage("§l§c(!)§r §7There are no active biddings!");
            return;
        }
        $sender->sendMessage(Translations::TYPES["no-permission-cmd"]);
    }
}