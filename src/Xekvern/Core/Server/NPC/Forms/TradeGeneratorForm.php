<?php

declare(strict_types = 1);

namespace Xekvern\Core\Entity\Forms;

use Xekvern\Core\NexusPlayer;
use Xekvern\Core\Translation\Translation;
use Xekvern\Core\Translation\TranslatonException;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class TradeGeneratorForm extends MenuForm {

    /**
     * TradeGeneratorForm constructor.
     */
    public function __construct() {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Generator Trader";
        $text = "What would you like to trade?";
        $options = [];
        $options[] = new MenuOption("4 Coal -> 1 Redstone\n(Mining)");
        $options[] = new MenuOption("4 Coal -> 1 Redstone\n(Auto)");
        $options[] = new MenuOption("4 Redstone -> 1 Iron\n(Mining)");
        $options[] = new MenuOption("4 Redstone -> 1 Iron\n(Auto)");
        $options[] = new MenuOption("4 Iron -> 1 Gold\n(Mining)");
        $options[] = new MenuOption("4 Iron -> 1 Gold\n(Auto)");
        $options[] = new MenuOption("4 Gold -> 1 Diamond\n(Mining)");
        $options[] = new MenuOption("4 Gold -> 1 Diamond\n(Auto)");
        $options[] = new MenuOption("4 Diamond -> 1 Emerald\n(Mining)");
        $options[] = new MenuOption("4 Diamond -> 1 Emerald\n(Auto)");
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
        $inventory = $player->getInventory();
        if($inventory->getSize() === count($inventory->getContents())) {
            $player->sendMessage(Translation::getMessage("fullInventory"));
            return;
        }
        switch($selectedOption) {
            case 0:
                $look = ItemFactory::getInstance()->get(ItemIds::BROWN_GLAZED_TERRACOTTA, 0, 4);
                $result = ItemFactory::getInstance()->get(ItemIds::CYAN_GLAZED_TERRACOTTA, 0, 1);
                break;
            case 1:
                $look = ItemFactory::getInstance()->get(ItemIds::BLUE_GLAZED_TERRACOTTA, 0, 4);
                $result = ItemFactory::getInstance()->get(ItemIds::GRAY_GLAZED_TERRACOTTA, 0 , 1);
                break;
            case 2:
                $look = ItemFactory::getInstance()->get(ItemIds::CYAN_GLAZED_TERRACOTTA, 0, 4);
                $result = ItemFactory::getInstance()->get(ItemIds::MAGENTA_GLAZED_TERRACOTTA, 0 , 1);
                break;
            case 3:
                $look = ItemFactory::getInstance()->get(ItemIds::GRAY_GLAZED_TERRACOTTA, 0, 4);
                $result = ItemFactory::getInstance()->get(ItemIds::LIME_GLAZED_TERRACOTTA, 0 , 1);
                break;
            case 4:
                $look = ItemFactory::getInstance()->get(ItemIds::MAGENTA_GLAZED_TERRACOTTA, 0, 4);
                $result = ItemFactory::getInstance()->get(ItemIds::ORANGE_GLAZED_TERRACOTTA, 0 , 1);
                break;
            case 5:
                $look = ItemFactory::getInstance()->get(ItemIds::LIME_GLAZED_TERRACOTTA, 0, 4);
                $result = ItemFactory::getInstance()->get(ItemIds::PINK_GLAZED_TERRACOTTA, 0 , 1);
                break;
            case 6:
                $look = ItemFactory::getInstance()->get(ItemIds::ORANGE_GLAZED_TERRACOTTA, 0, 4);
                $result = ItemFactory::getInstance()->get(ItemIds::PURPLE_GLAZED_TERRACOTTA, 0 , 1);
                break;
            case 7:
                $look = ItemFactory::getInstance()->get(ItemIds::PINK_GLAZED_TERRACOTTA, 0, 4);
                $result = ItemFactory::getInstance()->get(ItemIds::RED_GLAZED_TERRACOTTA, 0 , 1);
                break;
            case 8:
                $look = ItemFactory::getInstance()->get(ItemIds::PURPLE_GLAZED_TERRACOTTA, 0, 4);
                $result = ItemFactory::getInstance()->get(ItemIds::WHITE_GLAZED_TERRACOTTA, 0 , 1);
                break;
            case 9:
                $look = ItemFactory::getInstance()->get(ItemIds::RED_GLAZED_TERRACOTTA, 0, 4);
                $result = ItemFactory::getInstance()->get(ItemIds::SILVER_GLAZED_TERRACOTTA, 0 , 1);
                break;
            default:
                return;
        }
        if(!$inventory->contains($look)) {
            $player->playErrorSound();
            return;
        }
        $inventory->removeItem($look);
        $inventory->addItem($result);
        $player->playXpLevelUpSound();
        $player->sendForm(new TradeGeneratorForm());
    }
}