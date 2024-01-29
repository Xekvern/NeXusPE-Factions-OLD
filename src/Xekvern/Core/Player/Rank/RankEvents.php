<?php

declare(strict_types = 1);

namespace Xekvern\Core\Player\Rank;

use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\utils\TextFormat;

class RankEvents implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * RankEvents constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }
    /**
     * @priority NORMAL
     * @param PlayerChatEvent $event
     */
    public function onPlayerChat(PlayerChatEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $mode = $player->getChatMode();
        $faction = $player->getDataSession()->getFaction();
        if($faction === null and ($mode === NexusPlayer::FACTION or $mode === NexusPlayer::ALLY)) {
            $mode = NexusPlayer::PUBLIC;
            $player->setChatMode($mode);
        }
        if($mode === NexusPlayer::PUBLIC) {
            $message = $event->getMessage();
            if($player->getDisguiseRank() !== null) {
                $event->setFormatter(new LegacyRawChatFormatter($player->getDisguiseRank()->getChatFormatFor($player, $message, [
                    "faction_rank" => "",
                    "faction" => "",
                    "factionRanking" => "",
                    "kills" => $player->getDataSession()->getKills(),
                    "level" => $player->getDataSession()->getCurrentLevel(),
                    "tag" => $player->getDataSession()->getCurrentTag()
                ])));
            }
            else {
                $event->setFormatter(new LegacyRawChatFormatter($player->getDataSession()->getRank()->getChatFormatFor($player, $message, [
                    "faction_rank" => $player->getDataSession()->getFactionRoleToString(),
                    "faction" => ($faction = $player->getDataSession()->getFaction()) !== null ? $faction->getName() : "",
                    "factionRanking" => ($faction = $player->getDataSession()->getFaction()) !== null ? TextFormat::GRAY . " [" . $this->core->getPlayerManager()->getFactionHandler()->formatRanking($faction->getName()). TextFormat::GRAY . "] " . TextFormat::RESET : "",
                    "kills" => $player->getDataSession()->getKills(),
                    "level" => $player->getDataSession()->getCurrentLevel(),
                    "tag" => $player->getDataSession()->getCurrentTag()
                ])));
            }
            return;
        }
        $event->cancel();
        if($mode === NexusPlayer::STAFF) {
            /** @var NexusPlayer $staff */
            foreach($this->core->getServer()->getOnlinePlayers() as $staff) {
                if(!$staff->isLoaded()) {
                    continue;
                }
                $rank = $staff->getDataSession()->getRank();
                if($rank->getIdentifier() >= Rank::TRIAL_MODERATOR and $rank->getIdentifier() <= Rank::OWNER) {
                    $staff->sendMessage(TextFormat::DARK_GRAY . "[" . $player->getDataSession()->getRank()->getColoredName() . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::WHITE . $player->getName() . TextFormat::GRAY . ": " . $event->getMessage());
                }
            }
            return;
        }
        if($player->getChatMode() === NexusPlayer::FACTION) {
            $onlinePlayers = $faction->getOnlineMembers();
            foreach($onlinePlayers as $onlinePlayer) {
                if(!$onlinePlayer->isLoaded()) {
                    continue;
                }
                $onlinePlayer->sendMessage(TextFormat::DARK_GRAY . "[" . TextFormat::BOLD . TextFormat::RED . "FC" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::WHITE . $player->getName() . TextFormat::GRAY . ": " . $event->getMessage());
            }
        }
        else {
            $allies = $faction->getAllies();
            $onlinePlayers = $faction->getOnlineMembers();
            foreach($allies as $ally) {
                if(($ally = $this->core->getPlayerManager()->getFactionHandler()->getFaction($ally)) === null) {
                    continue;
                }
                $onlinePlayers = array_merge($ally->getOnlineMembers(), $onlinePlayers);
            }
            foreach($onlinePlayers as $onlinePlayer) {
                if(!$onlinePlayer->isLoaded()) {
                    continue;
                }
                $onlinePlayer->sendMessage(TextFormat::DARK_GRAY . "[" . TextFormat::BOLD . TextFormat::GOLD . "AC" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::WHITE . $player->getName() . TextFormat::GRAY . ": " . $event->getMessage());
            }
        }
    }

    /**
     * @priority NORMAL
     * @param EntityRegainHealthEvent $event
     */
    public function onEntityRegainHealth(EntityRegainHealthEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        if($event->getRegainReason() === EntityRegainHealthEvent::CAUSE_MAGIC) {
            $event->setAmount($event->getAmount() * 1.25);
        }
        $player = $event->getEntity();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $hp = round($player->getHealth(), 1);
        if($player->getCESession()->isHidingHealth()) {
            $hp = TextFormat::OBFUSCATED . $hp . TextFormat::RESET;
        }
        $player->setScoreTag(TextFormat::WHITE . $hp . TextFormat::RED . TextFormat::BOLD . " HP " . TextFormat::RESET . TextFormat::DARK_GRAY . "| " . TextFormat::GRAY . $player->getOS());
    }

    /**
     * @priority NORMAL
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getEntity();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $hp = round($player->getHealth(), 1);
        if($player->getCESession()->isHidingHealth()) {
            $hp = TextFormat::OBFUSCATED . $hp . TextFormat::RESET;
        }
        $player->setScoreTag(TextFormat::WHITE . $hp . TextFormat::RED . TextFormat::BOLD . " HP " . TextFormat::RESET . TextFormat::DARK_GRAY . "| " . TextFormat::GRAY . $player->getOS());
    }
}