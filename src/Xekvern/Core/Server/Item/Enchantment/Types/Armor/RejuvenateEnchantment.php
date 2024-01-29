<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Enchantment\Types\Armor;

use Xekvern\Core\Server\Item\Enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\player\Player;
use Xekvern\Core\Translation\Messages;

class RejuvenateEnchantment extends Enchantment {

    /**
     * RejuvenateEnchantment constructor.
     */
    public function __construct() {
        parent::__construct("Rejuvenate", self::RARITY_GODLY, "Have a chance to regain armor durability.", self::DAMAGE, ItemFlags::ARMOR, 4);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level) {
            $damager = $event->getDamager();
            if(!$damager instanceof Player) {
                return;
            }
            $inventory = $damager->getArmorInventory();
            if($inventory === null) {
                return;
            }
            $chance = 250 - $level;
            if (mt_rand(1,250) >= $chance) {
                $index = mt_rand(0, 3);
                $armor = $inventory->getItem($index);
                if(!$armor instanceof Durable) {
                    return;
                }
                $repairdamage = (int)($level * mt_rand(1,8));
                $newdamage = $armor->getDamage() - $repairdamage;
                if($newdamage < 0){
                    $newdamage = 0;
                }
                if($armor === null) {
                    return;
                }
                $armor->setDamage($newdamage);
                $inventory->setItem($index, $armor);
            }
            return;
        };
    }
}