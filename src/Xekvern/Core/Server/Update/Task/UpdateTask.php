<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Update\Task;

use libs\muqsit\arithmexp\Util;
use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\NexusException;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Utils\Utils;

class UpdateTask extends Task {

    /** @var Nexus */
    private $core;

    /** @var NexusPlayer[] */
    private $players;
    
    /**
     * UpdateTask constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->players = $core->getServer()->getOnlinePlayers();
    }

    /**
     * @param int $tick
     *
     * @throws NexusException
     */
    public function onRun(): void {
        if(empty($this->players)) {
            $this->players = $this->core->getServer()->getOnlinePlayers();
        }
        $player = array_shift($this->players);
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isOnline() === false) {
            return;
        }
        if($player->isLoaded() === false) {
            return;
        }
        try {
            $this->core->getServerManager()->getUpdateHandler()->updateScoreboard($player);
        }catch (\Error){}
    }
}