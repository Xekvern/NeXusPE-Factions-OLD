<?php

declare(strict_types = 1);

namespace Xekvern\Core\Command\Types;

use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\Translation;
use Xekvern\Core\Translation\TranslatonException;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use Xekvern\Core\Command\Forms\LevelInfoForm;

class LevelCommand extends Command {

    /**
     * LevelCommand constructor.
     */
    public function __construct() {
        parent::__construct("level", "View progress and possible rewards.");
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
        $sender->sendForm(new LevelInfoForm($sender));
    }
}