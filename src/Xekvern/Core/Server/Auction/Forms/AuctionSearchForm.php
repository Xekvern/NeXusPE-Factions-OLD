<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Auction\Forms;

use Xekvern\Core\Server\Auction\Inventory\AuctionListInventory;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\TranslationException;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Input;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class AuctionSearchForm extends CustomForm {

    /**
     * AuctionSearchForm constructor.
     */
    public function __construct() {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Search";
        $elements[] = new Input("Search", "Enter in a key word");
        parent::__construct($title, $elements);
    }

    /**
     * @param Player $player
     * @param CustomFormResponse $data
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, CustomFormResponse $data): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $entries = Nexus::getInstance()->getServerManager()->getAuctionHandler()->getEntries();
        $search = $data->getString("Search");
        $found = [];
        foreach($entries as $entry) {
            $item = $entry->getItem();
            if(strpos(strtolower(TextFormat::clean($item->getName())), strtolower($search)) !== false) {
                $found[] = $entry;
                continue;
            }
            if(strpos(strtolower(TextFormat::clean($item->getCustomName())), strtolower($search)) !== false) {
                $found[] = $entry;
                continue;
            }
            foreach($item->getLore() as $lore) {
                if(strpos(strtolower(TextFormat::clean($lore)), strtolower($search)) !== false) {
                    $found[] = $entry;
                    break;
                }
            }
        }
        $player->sendDelayedWindow(new AuctionListInventory($found));
    }
}