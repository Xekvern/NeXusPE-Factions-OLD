<?php

namespace Xekvern\Core\Server\Auction\Command;

use Xekvern\Core\Server\Auction\AuctionEntry;
use Xekvern\Core\Server\Auction\Inventory\AuctionPageInventory;
use Xekvern\Core\Command\Utils\Command;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\Translation;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class AuctionHouseCommand extends Command
{

    /**
     * AuctionHouseCommand constructor.
     */
    public function __construct()
    {
        parent::__construct("auctionhouse", "Open auction house menu", "/ah sell <price: int> [amount: int]", ["ah"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof NexusPlayer) {
            if (isset($args[0])) {
                if ($args[0] === "sell") {
                    $manager = $this->getCore()->getServerManager()->getAuctionHandler();
                    $entries = $manager->getEntriesOf($sender);
                    if (count($entries) >= $sender->getDataSession()->getRank()->getAuctionLimit()) {
                        $sender->sendMessage(Translation::getMessage("maxEntries"));
                        return;
                    }
                    if (!isset($args[2])) {
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage()
                        ]));
                        return;
                    }
                    $buyPrice = (int)$args[1];
                    $amount = (int)$args[2];
                    if ((!is_numeric($args[1])) or $args[1] <= 0) {
                        $sender->sendMessage(Translation::getMessage("invalidAmount"));
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage()
                        ]));
                        return;
                    }
                    if ((!is_numeric($args[2])) or $args[2] <= 0) {
                        $sender->sendMessage(Translation::getMessage("invalidAmount"));
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage()
                        ]));
                        return;
                    }
                    $item = $sender->getInventory()->getItemInHand();
                    if ($item->isNull()) {
                        $sender->sendMessage(Translation::getMessage("invalidItem"));
                        return;
                    }
                    if ($item->getCount() < $amount) {
                        $sender->sendMessage(Translation::getMessage("invalidAmount"));
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage()
                        ]));
                        return;
                    }
                    $sender->getInventory()->setItemInHand($item->setCount($item->getCount() - $amount));
                    $manager->addEntry(new AuctionEntry($item->setCount($amount), $sender->getName(), $this->getCore()->getServerManager()->getAuctionHandler()->getNewIdentifier(), time(), $buyPrice));
                    $name = $item->hasCustomName() ? $item->getCustomName() : $item->getName();
                    $name .= TextFormat::RESET . TextFormat::GRAY . " * " . TextFormat::WHITE . $amount;
                    $sender->sendMessage(Translation::getMessage("addAuctionEntry", [
                        "item" => $name,
                        "price" => TextFormat::LIGHT_PURPLE . "$" . number_format($buyPrice),
                        "profit" => TextFormat::YELLOW . "$" . number_format($amount * $buyPrice)
                    ]));
                    return;
                }
            }
            $inventory = new AuctionPageInventory();
            $inventory->send($sender);
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
        return;
    }
}