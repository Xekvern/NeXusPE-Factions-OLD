<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item\Enchantment\Types\Weapon;

use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Translation\Translation;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use Xekvern\Core\Server\Item\Enchantment\Enchantment;
use Xekvern\Core\Server\Item\ItemHandler;

class WitherEnchantment extends Enchantment {

    /**
     * WitherEnchantment constructor.
     */
    public function __construct() {
        parent::__construct("Wither", Rarity::RARE, "Have a chance to give off a wither effect to your opponent.", self::DAMAGE, ItemFlags::SWORD, 5);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if((!$entity instanceof NexusPlayer) or (!$damager instanceof NexusPlayer)) {
                return;
            }
            if($entity->getEffects()->has(VanillaEffects::WITHER())) {
                return;
            }
            if($entity instanceof NexusPlayer and $entity->getCESession()->isDivineProtected()) {
                return;
            }
            $random = mt_rand(1, 200);
            $chance = $level * 3;
            if($chance >= $random) {
                $entity->getEffects()->add(new EffectInstance(VanillaEffects::WITHER(), $level * 40, 1));
                $entity->sendMessage(Translation::RED . "You are withering.");
                $damager->sendMessage(Translation::GREEN . "Wither " . ItemHandler::getRomanNumber($level) . " has Activated");
            }
        };
    }
}