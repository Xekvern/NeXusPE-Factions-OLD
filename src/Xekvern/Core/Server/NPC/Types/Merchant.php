<?php

namespace Xekvern\Core\Server\NPC\Types;

use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Server\NPC\NPC;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use Xekvern\Core\Server\Price\Inventory\ShopMainInventory;
use Xekvern\Core\Utils\Utils;

class Merchant extends NPC {

    /**
     * Merchant constructor.
     */
    public function __construct() {
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "skins" . DIRECTORY_SEPARATOR . "merchant.png";
        $skin = Utils::createSkin(Utils::getSkinDataFromPNG($path));
        $position = new Position(156.0531, 107, 21.0815, Server::getInstance()->getWorldManager()->getDefaultWorld());
        $nameTag = $this->updateNameTag();
        parent::__construct($skin, $position, $nameTag);
    }

    /**
     * @param Player $player
     */
    public function tick(Player $player): void {
        if($this->hasSpawnedTo($player)) {
            $this->setNameTag($player);
        }
    }

    /**
     * @return string
     */
    public function updateNameTag(): string {
        $this->nameTag = TextFormat::BOLD . TextFormat::YELLOW . "Merchant\n" . TextFormat::GRAY . "<Interact Me>";
        return $this->nameTag;
    }

    /**
     * @param Player $player
     */
    public function tap(Player $player): void {
        if($player instanceof NexusPlayer) {
            $player->sendDelayedWindow(new ShopMainInventory(Nexus::getInstance()->getServerManager()->getPriceHandler()->getPlaces()));
        }
    }
}