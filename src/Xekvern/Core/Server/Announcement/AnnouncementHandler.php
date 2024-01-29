<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Announcement;

use Xekvern\Core\Nexus;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Announcement\Task\BroadcastMessagesTask;
use Xekvern\Core\Server\Announcement\Task\RestartTask;

class AnnouncementHandler {

    /** @var Nexus */
    private $core;

    /** @var RestartTask */
    private $restarter;

    /** @var string[] */
    private $messages;

    /** @var int */
    private $currentId = 0;

    /**
     * AnnouncementHandler constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->restarter = new RestartTask($core);
        $this->init();
        $core->getScheduler()->scheduleRepeatingTask(new BroadcastMessagesTask($core), 4800);
        $core->getScheduler()->scheduleRepeatingTask($this->restarter, 20);
    }

    public function init(): void {
        $this->messages = [
            "Interested in purchasing something from our store? Visit " . TextFormat::AQUA . "store.nexuspe.net" . TextFormat::RESET . " to view our packages.",
            "Vote and receive vote points for some rewards at " . TextFormat::AQUA . "vote.nexuspe.net.",
            "Follow our discord server " . TextFormat::AQUA . "discord.nexuspe.net" . TextFormat::RESET . " for important updates and announcements.",
            "Need to know to server IP and port? it is " . TextFormat::AQUA . "hub.nexuspe.net and 19132.",
            "Please check /rules to ensure you aren't violating any!",
            "Use the command " . TextFormat::RED . "/level" . TextFormat::RESET . TextFormat::WHITE . " to view your level progress.",
            "New to the server? Use /info to learn more about our features!",
            "Earn rewards for your faction by capturing the outpost! Use the command " . TextFormat::DARK_RED . "/outpost" . TextFormat::RESET . TextFormat::WHITE . " to view the outpost information.",
        ];
    }

    /**
     * @return string
     */
    public function getNextMessage(): string {
        if(isset($this->messages[$this->currentId])) {
            $message = $this->messages[$this->currentId];
            $this->currentId++;
            return $message;
        }
        $this->currentId = 0;
        return $this->messages[$this->currentId];
    }

    /**
     * @return RestartTask
     */
    public function getRestarter(): RestartTask {
        return $this->restarter;
    }
}
