<?php

declare(strict_types = 1);

namespace Xekvern\Core\Player\Gamble\Command\SubCommands;

use Xekvern\Core\Command\Utils\SubCommand;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\Translation;
use Xekvern\Core\Translation\TranslatonException;
use pocketmine\command\CommandSender;

class CancelSubCommand extends SubCommand {

    /**
     * CancelSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("cancel", "/coinflip cancel");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslatonException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if($this->getCore()->getPlayerManager()->getGambleHandler()->getCoinFlip($sender) === null) {
            $sender->sendMessage(Translation::getMessage("invalidCoinFlip"));
            return;
        }
        $this->getCore()->getPlayerManager()->getGambleHandler()->removeCoinFlip($sender);
    }
}