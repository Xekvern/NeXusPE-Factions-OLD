<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Update;

use libs\muqsit\arithmexp\Util;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Server\Update\Task\SaveTask;
use Xekvern\Core\Server\Update\Task\UpdateTask;
use Xekvern\Core\NexusException;
use Xekvern\Core\Player\Faction\Faction;
use Xekvern\Core\Server\Update\Task\AutoSellTask;
use Xekvern\Core\Server\Update\Task\LeaderboardsTask;
use Xekvern\Core\Server\Update\Utils\Scoreboard;
use Xekvern\Core\Utils\Utils;

class UpdateHandler {

    /** @var Nexus */
    private $core;

    /**
     * UpdateHandler constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getScheduler()->scheduleRepeatingTask(new UpdateTask($core), 1);
        $core->getScheduler()->scheduleRepeatingTask(new LeaderboardsTask($core), 20);
        $core->getScheduler()->scheduleRepeatingTask(new AutoSellTask($core), 1);
        $core->getScheduler()->scheduleRepeatingTask(new SaveTask($core), 12000);
    }

    /**
     * @param NexusPlayer $player
     *
     * @throws NexusException
     */
    public function updateScoreboard(NexusPlayer $player): void {
        $scoreboard = $player->getScoreboard();
        if($scoreboard === null or (!$player->isLoaded())) {
            return;
        }
        if($scoreboard->isSpawned() === false) {
            if($player->isLoaded()) {
                $player->initializeScoreboard();
            }
            else {
                return;
            }
        }
        if($player->isUsingFMapHUD() === true) {
            return;
        }
        if($player->isTagged() === true) {
            $this->updateCombatHUD($scoreboard, $player);
            return;
        }
        $this->updateRegularHUD($scoreboard, $player);
    }

    /**
     * @param Scoreboard $scoreboard
     * @param NexusPlayer $player
     *
     * @throws NexusException
     */
    public function updateRegularHUD(Scoreboard $scoreboard, NexusPlayer $player): void {
        $scoreboard->setScoreLine(2, " " . $player->getDataSession()->getRank()->getColoredName() . TextFormat::RESET . TextFormat::WHITE . " " . $player->getName());
        $scoreboard->setScoreLine(4, TextFormat::BOLD . TextFormat::AQUA . " Stats");
        $scoreboard->setScoreLine(5, TextFormat::BOLD . TextFormat::AQUA . " |" . TextFormat::RESET . TextFormat::WHITE . " Balance: " . TextFormat::RESET . TextFormat::YELLOW . "$" . Utils::shrinkNumber($player->getDataSession()->getBalance(), 2));
        $scoreboard->setScoreLine(6, TextFormat::BOLD . TextFormat::AQUA . " |" . TextFormat::RESET . TextFormat::WHITE . " Power: " . TextFormat::RESET . TextFormat::YELLOW . Utils::shrinkNumber($player->getDataSession()->getPower(), 2));
        $scoreboard->setScoreLine(7, TextFormat::BOLD . TextFormat::AQUA . " |" . TextFormat::RESET . TextFormat::WHITE . " Level: " . TextFormat::RESET . TextFormat::YELLOW . $player->getDataSession()->getCurrentLevel());
        $scoreboard->setScoreLine(8, " ");
        if($this->core->isInGracePeriod()) {
            $scoreboard->setScoreLine(9, TextFormat::BOLD . TextFormat::RED . " Grace Period");
            $scoreboard->setScoreLine(10, TextFormat::BOLD . TextFormat::RED . " |" . TextFormat::RESET . TextFormat::DARK_AQUA . " " . Utils::secondsToTime($this->core->getGracePeriod()));
            $scoreboard->setScoreLine(11, " ");
            $scoreboard->setScoreLine(12, TextFormat::RESET . TextFormat::ITALIC . TextFormat::LIGHT_PURPLE . " store.nexuspe.net");
            $scoreboard->setScoreLine(13, TextFormat::RESET . TextFormat::ITALIC . TextFormat::LIGHT_PURPLE . " vote.nexuspe.net");
        } else {
            $scoreboard->setScoreLine(9, TextFormat::RESET . TextFormat::ITALIC . TextFormat::LIGHT_PURPLE . " store.nexuspe.net");
            $scoreboard->setScoreLine(10, TextFormat::RESET . TextFormat::ITALIC . TextFormat::LIGHT_PURPLE . " vote.nexuspe.net");
            if($scoreboard->getLine(11) !== null) {
                $scoreboard->removeLine(11);
            }
            if($scoreboard->getLine(12) !== null) {
                $scoreboard->removeLine(12);
            }
            if($scoreboard->getLine(13) !== null) {
                $scoreboard->removeLine(13);
            }
        }
    }

    /**
     * @param Scoreboard $scoreboard
     * @param NexusPlayer $player
     *
     * @throws NexusException
     */
    public function updateCombatHUD(Scoreboard $scoreboard, NexusPlayer $player): void {
        $scoreboard->setScoreLine(2, " " . $player->getDataSession()->getRank()->getColoredName() . TextFormat::RESET . TextFormat::WHITE . " " . $player->getName());
        $scoreboard->setScoreLine(4, TextFormat::BOLD . TextFormat::RED . " In Combat");
        $scoreboard->setScoreLine(5, TextFormat::BOLD . TextFormat::RED . " |" . TextFormat::RESET . TextFormat::WHITE . " Target: " . TextFormat::RESET . TextFormat::YELLOW . $player->getLastHit()); // Storing last hit @ NexusPlayer.php
        $scoreboard->setScoreLine(6, TextFormat::BOLD . TextFormat::RED . " |" . TextFormat::RESET . TextFormat::WHITE . " Ping: " . TextFormat::RESET . TextFormat::YELLOW . $player->getNetworkSession()->getPing() . "ms");
        $scoreboard->setScoreLine(7, TextFormat::BOLD . TextFormat::RED . " |" . TextFormat::RESET . TextFormat::WHITE . " Combat Time: " . TextFormat::RESET . TextFormat::YELLOW . $player->combatTagTime() . "s");
        $scoreboard->setScoreLine(8, " ");
        $scoreboard->setScoreLine(9, TextFormat::RESET . TextFormat::ITALIC . TextFormat::LIGHT_PURPLE . " store.nexuspe.net");
        $scoreboard->setScoreLine(10, TextFormat::RESET . TextFormat::ITALIC . TextFormat::LIGHT_PURPLE . " vote.nexuspe.net");
        if($scoreboard->getLine(11) !== null) {
            $scoreboard->removeLine(11);
        }
        if($scoreboard->getLine(12) !== null) {
            $scoreboard->removeLine(12);
        }
        if($scoreboard->getLine(13) !== null) {
            $scoreboard->removeLine(13);
        }
    }
}