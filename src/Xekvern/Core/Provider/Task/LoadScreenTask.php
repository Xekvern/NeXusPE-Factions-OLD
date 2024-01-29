<?php

declare(strict_types=1);

namespace Xekvern\Core\Provider\Task;

use Xekvern\Core\Nexus;
use Xekvern\Core\Player\NexusPlayer;
use Xekvern\Core\Provider\Event\PlayerLoadEvent;
use Xekvern\Core\Translation\Translation;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\sound\GhastSound;
use pocketmine\world\sound\LaunchSound;
use pocketmine\world\sound\XpLevelUpSound;
use Xekvern\Core\Player\Rank\Rank;

class LoadScreenTask extends Task
{

    /** @var NexusPlayer */
    private $player;

    /** @var int */
    private $time;

    /** @var int */
    private $maxTime;

    /**
     * LoadScreenTask constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player)
    {
        $this->player = $player;
        $this->time = 600;
        $this->maxTime = 600;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void
    {
        if ($this->player === null or ($this->player->isOnline() === false and $this->time !== 600)) {
            $this->getHandler()->cancel();
            return;
        }
        if ($this->player->isLoaded() === true and $this->player->spawned === true) {
            $this->player->broadcastSound(new ExplodeSound());
            $event = new PlayerLoadEvent($this->player);
            $event->call();
            $this->player->getCore()->getScheduler()->scheduleDelayedTask(new class($this->player) extends Task
            {

                /** @var NexusPlayer */
                private $player;

                /**
                 *  constructor.
                 *
                 * @param NexusPlayer $player
                 */
                public function __construct(NexusPlayer $player)
                {
                    $this->player = $player;
                }

                /**
                 * @param int $currentTick
                 */
                public function onRun(): void
                {
                    if ($this->player->isOnline() === false) { return; }
                    $welcomeMessage = implode(TextFormat::RESET . "\n", [
                        TextFormat::AQUA . "================" . TextFormat::DARK_AQUA . "================",
                        " ",
                        TextFormat::GRAY . "Welcome to " . TextFormat::BOLD . TextFormat::AQUA . "NeXus" . TextFormat::DARK_AQUA . "PE " . TextFormat::RESET . TextFormat::GRAY . "OP Factions",
                        " ",
                        TextFormat::BOLD . TextFormat::AQUA . " | " . TextFormat::RESET . TextFormat::WHITE . "Vote: " . TextFormat::LIGHT_PURPLE . "vote.nexuspe.net",
                        TextFormat::BOLD . TextFormat::AQUA . " | " . TextFormat::RESET . TextFormat::WHITE . "Store: " . TextFormat::LIGHT_PURPLE . "store.nexuspe.net",
                        TextFormat::BOLD . TextFormat::AQUA . " | " . TextFormat::RESET . TextFormat::WHITE . "Discord: " . TextFormat::LIGHT_PURPLE . "discord.nexuspe.net",
                        " ",
                        TextFormat::BOLD . TextFormat::AQUA . " » " . TextFormat::RESET . TextFormat::GRAY . "Don't forget to read the " . TextFormat::GREEN . "/rules",
                        TextFormat::BOLD . TextFormat::AQUA . " » " . TextFormat::RESET . TextFormat::GRAY . "You are currently playing on BETA",
                        "",
                        TextFormat::DARK_AQUA . "================" . TextFormat::AQUA . "================"
                    ]);
                    $this->player->broadcastSound(new XpLevelUpSound(30));
                    $this->player->broadcastSound(new LaunchSound());
                    $this->player->playSound("mob.enderdragon.growl", 1.3, 1);
                    $this->player->sendMessage($welcomeMessage);
                    $this->player->sendTitle(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "NeXus" . TextFormat::DARK_AQUA . "PE", TextFormat::RESET . TextFormat::GRAY . Nexus::GAMEMODE, 5, 50, 5);
                }
            }, 20);
            $this->getHandler()->cancel();
            return;
        }
        if ($this->time >= 0) {
            $this->time--;
            return;
        }
        $this->player->disconnect("Login Timeout", TextFormat::RED . "Loading timed out. Rejoin to load again!");
        return;
    }
}
