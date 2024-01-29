<?php

namespace Xekvern\Core\Player\Gamble;

use Xekvern\Core\Player\Gamble\Task\DrawLotteryTask;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;

class GambleHandler {

    const TICKET_PRICE = 10000;

    /** @var Nexus */
    private $core;

    /** @var int[] */
    private $coinFlips = [];

    /** @var string[] */
    private $coinFlipRecord = [];

    /** @var int[] */
    private $pot = [];

    /** @var DrawLotteryTask */
    private $drawer;

    /**
     * GambleHandler constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->core->getServer()->getPluginManager()->registerEvents(new GambleEvents($core), $core);
        $this->drawer = new DrawLotteryTask($this);
        $core->getScheduler()->scheduleRepeatingTask($this->drawer, 20);
    }

    /**
     * @return int[]
     */
    public function getCoinFlips(): array {
        return $this->coinFlips;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return int|null
     */
    public function getCoinFlip(NexusPlayer $player): ?int {
        return $this->coinFlips[$player->getName()] ?? null;
    }

    /**
     * @param NexusPlayer $player
     * @param int $amount
     */
    public function addCoinFlip(NexusPlayer $player, int $amount): void {
        if(isset($this->coinFlips[$player->getName()])) {
            return;
        }
        $this->coinFlips[$player->getName()] = $amount;
    }

    /**
     * @param NexusPlayer $player
     */
    public function removeCoinFlip(NexusPlayer $player): void {
        if(!isset($this->coinFlips[$player->getName()])) {
            return;
        }
        unset($this->coinFlips[$player->getName()]);
    }

    /**
     * @param NexusPlayer $player
     * @param $wins
     * @param $losses
     */
    public function getRecord(NexusPlayer $player, &$wins, &$losses): void {
        $record = $this->coinFlipRecord[$player->getName()];
        $reward = explode(":", $record);
        $wins = $reward[0];
        $losses = $reward[1];
    }

    /**
     * @param NexusPlayer $player
     */
    public function createRecord(NexusPlayer $player): void {
        $this->coinFlipRecord[$player->getName()] = "0:0";
    }

    /**
     * @param NexusPlayer $player
     */
    public function addWin(NexusPlayer $player): void {
        $record = $this->coinFlipRecord[$player->getName()];
        $reward = explode(":", $record);
        $wins = intval($reward[0]) + 1;
        $losses = $reward[1];
        $this->coinFlipRecord[$player->getName()] = "$wins:$losses";
    }

    /**
     * @param NexusPlayer $player
     */
    public function addLoss(NexusPlayer $player): void {
        $record = $this->coinFlipRecord[$player->getName()];
        $reward = explode(":", $record);
        $wins = $reward[0];
        $losses = intval($reward[1]) + 1;
        $this->coinFlipRecord[$player->getName()] = "$wins:$losses";
    }

    /**
     * @param NexusPlayer $player
     * @param int $draws
     */
    public function addDraws(NexusPlayer $player, int $draws) {
        if(!isset($this->pot[$player->getName()])) {
            $this->pot[$player->getName()] = $draws;
            return;
        }
        $this->pot[$player->getName()] += $draws;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return int
     */
    public function getDrawsFor(NexusPlayer $player): int {
        if(!isset($this->pot[$player->getName()])) {
            $this->pot[$player->getName()] = 0;
        }
        return $this->pot[$player->getName()];
    }

    /**
     * @return int
     */
    public function getTotalDraws(): int {
        $amount = 0;
        foreach($this->pot as $entries) {
            $amount += $entries;
        }
        return $amount;
    }

    /**
     * @return int[]
     */
    public function getPot(): array {
        return $this->pot;
    }

    /**
     * @return string|null
     */
    public function draw(): ?string {
        $total = $this->getTotalDraws();
        if($total <= 0 or empty($this->pot)) {
            return null;
        }
        foreach($this->pot as $name => $draws) {
            if(mt_rand(1, $total) <= $draws) {
                return $name;
            }
        }
        return $this->draw();
    }

    /**
     * @return DrawLotteryTask
     */
    public function getDrawer(): DrawLotteryTask {
        return $this->drawer;
    }
}