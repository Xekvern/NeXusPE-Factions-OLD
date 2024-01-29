<?php

declare(strict_types=1);

namespace Xekvern\Core\Player\Quest\Forms;

use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Server\Item\ItemHandler;
use Xekvern\Core\Server\Item\Types\EnchantmentBook;
use Xekvern\Core\Server\Item\Types\EXPNote;
use Xekvern\Core\Server\Item\Types\HolyBox;
use Xekvern\Core\Server\Item\Types\MoneyNote;
use Xekvern\Core\Server\Item\Types\SacredStone;
use Xekvern\Core\Translation\Translation;

class QuestShopForm extends MenuForm {

    /**
     * QuestShopForm constructor.
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Quest Shop";
        $text = "Quest points: " . $player->getDataSession()->getQuestPoints();
        $options = [];
        $options[] = new MenuOption("$10,000 (1 Point)");
        $options[] = new MenuOption("500 Level EXP (2 Point)");
        $options[] = new MenuOption("Random Enchantment (10 Points)");
        $options[] = new MenuOption("Sacred Stone (30 Points)");
        $options[] = new MenuOption("Holy Box (100 Points)");
        parent::__construct($title, $text, $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     * @throws TranslationException
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        if (!$player instanceof NexusPlayer) {
            return;
        }
        $option = $this->getOption($selectedOption);
        if ($player->getInventory()->getSize() === count($player->getInventory()->getContents())) {
            $player->sendMessage(Translation::getMessage("fullInventory"));
            return;
        }
        switch ($option->getText()) {
            case "$10,000 (1 Point)":
                $points = 1;
                $item = (new MoneyNote(10000))->getItemForm();
                break;
            case "500 Level EXP (2 Point)":
                $points = 1;
                $item = (new EXPNote(500))->getItemForm();
                break;
            case "Random Enchantment (10 Points)":
                $points = 10;
                $item = (new EnchantmentBook(ItemHandler::getRandomEnchantment(), 100))->getItemForm();
                break;
            case "Sacred Stone (30 Points)":
                $points = 30;
                $item = (new SacredStone())->getItemForm();
                break;
            case "Holy Box (100 Points)":
                $kits = Nexus::getInstance()->getServerManager()->getKitHandler()->getSacredKits();
                $kit = $kits[array_rand($kits)];
                $points = 150;
                $item = (new HolyBox($kit))->getItemForm();
                break;
            default:
                return;
        }
        if ($player->getDataSession()->getQuestPoints() < $points) {
            $player->sendMessage(Translation::getMessage("notEnoughPoints"));
            return;
        }
        $player->getDataSession()->subtractQuestPoints($points);
        $player->sendMessage(Translation::getMessage("buy", ["amount" => TextFormat::GREEN . "1", "item" => TextFormat::DARK_GREEN . $item->getCustomName(), "price" => TextFormat::LIGHT_PURPLE . "$points quest points",]));
        $player->getInventory()->addItem($item);
    }
}