<?php

namespace Xekvern\Core\Server\Watchdog\Handler\Types\Hacks;

use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Player\Rank\Rank;
use Xekvern\Core\Server\Watchdog\Handler\Handler;
use Xekvern\Core\Server\Watchdog\Task\CheatLogTask;
use pocketmine\block\Ladder;
use pocketmine\block\Slab;
use pocketmine\block\SnowLayer;
use pocketmine\block\Stair;
use pocketmine\block\utils\Fallable;
use pocketmine\block\Vine;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\utils\TextFormat;

class NoClipHandler extends Handler {

    /**
     * @param NexusPlayer $player
     * @param Event $event
     */
    public function check(NexusPlayer $player, Event $event): void {
        if($event instanceof PlayerMoveEvent) {
            $blockA = $player->getWorld()->getBlockAt($player->getPosition()->x, $player->getPosition()->y, $player->getPosition()->z);
            $blockB = $player->getWorld()->getBlockAt($player->getPosition()->x, $player->getPosition()->y + 1, $player->getPosition()->z);
            $AABB = $player->getBoundingBox();
            if($player->isSpectator()) {
                return;
            }
            if($player->isCreative()) {
                return;
            }
            if($player->hasVanished()) {
                return;
            }
            $pearlHandler = $this->core->getServerManager()->getWatchdogHandler()->getHandlerManager()->getPearlHandler();
            $throw = $pearlHandler->getMostRecentThrowFrom($player->getName());
            $pos = null;
            if($throw !== null) {
                if($throw->getLandingTime() + 4 <= time()) {
                    $pos = $throw->getLandingLocation();
                }
            }
            if(($blockA->collidesWithBB($AABB) or $blockB->collidesWithBB($AABB))) {
                if(!$blockA->isSolid() or !$blockB->isSolid()) {
                    return;
                }
                if($pos !== null) {
                    $message = TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "BOOP" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::YELLOW . "Suspicious activity from {$player->getName()}! Could be enderpearl glitching! Reverted movement!";
                    foreach($this->core->getServer()->getOnlinePlayers() as $onlinePlayer) {
                        if($onlinePlayer->isLoaded() === false) {
                            continue;
                        }
                        if($onlinePlayer->getDataSession()->getRank()->getIdentifier() < Rank::TRIAL_MODERATOR or $onlinePlayer->getDataSession()->getRank()->getIdentifier() > Rank::OWNER) {
                            continue;
                        }
                        $onlinePlayer->sendMessage($message);
                    }
                    $this->core->getServer()->getAsyncPool()->increaseSize(2);
                    $this->core->getServer()->getAsyncPool()->submitTaskToWorker(new CheatLogTask("[Faction] " . date("[n/j/Y][G:i:s]", time()) . TextFormat::clean($message)), 1);
                    $this->core->getLogger()->info($message);
                    $player->teleport($event->getFrom());
                    return;
                }
                if($blockA instanceof Slab or $blockA instanceof Stair or $blockA instanceof SnowLayer or $blockA instanceof Ladder or $blockA instanceof Vine) {
                    return;
                }
                if($blockB instanceof Slab or $blockB instanceof Stair or $blockB instanceof SnowLayer or $blockB instanceof Ladder or $blockB instanceof Vine) {
                    return;
                }
                /**
                 * TO DO: Check whether the player has moved from one sand to another. (this is badddd)
                 */
                if($blockA instanceof Fallable or $blockB instanceof Fallable) {
                    return;
                }
                $reason = "No-clip";
                $this->handleViolations($player, $reason);
                $event->cancel();
            }
        }
    }
}