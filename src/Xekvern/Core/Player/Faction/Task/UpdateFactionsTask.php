<?php
declare(strict_types=1);

namespace Xekvern\Core\Player\Faction\Task;

use Xekvern\Core\Nexus;
use pocketmine\scheduler\Task;
use Xekvern\Core\Player\Faction\FactionHandler;

class UpdateFactionsTask extends Task {

    /** @var FactionHandler */
    private $manager;

    /**
     * UpdateFactionsTask constructor.
     *
     * @param FactionHandler $manager
     */
    public function __construct(FactionHandler $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $tick
     */
    public function onRun(): void {
        $count = 0;
        foreach($this->manager->getFactions() as $faction) {
            if($faction->needsUpdate()) {
                $faction->updateAsync();
                ++$count;
            }
        }
        $claims = 0;
        foreach($this->manager->getClaims() as $claim) {
            if($claim->needsUpdate()) {
                $claim->updateAsync();
                ++$claims;
            }
        }
        Nexus::getInstance()->getLogger()->notice("[Auto Save] Successfully saved $count factions and $claims claims!");
    }
}