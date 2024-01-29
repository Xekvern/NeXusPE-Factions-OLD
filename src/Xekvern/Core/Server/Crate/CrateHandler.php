<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Crate;

use pocketmine\world\Position;
use Xekvern\Core\Nexus;
use Xekvern\Core\Server\Crate\Types\BossCrate;
use Xekvern\Core\Server\Crate\Types\EpicCrate;
use Xekvern\Core\Server\Crate\Types\LegendaryCrate;
use Xekvern\Core\Server\Crate\Types\UltraCrate;

class CrateHandler
{

    /** @var Nexus */
    private $core;

    /** @var Crate[] */
    private $crates = [];

    /**
     * CrateHandler constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core)
    {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new CrateEvents($core), $core);
        $this->init();
    }

    public function init()
    {
        $world = $this->core->getServer()->getWorldManager()->getDefaultWorld();
        $this->addCrate(new UltraCrate(new Position(144, 109, -15, $world)));
        $this->addCrate(new EpicCrate(new Position(139, 109, -7, $world)));
        $this->addCrate(new LegendaryCrate(new Position(136, 109, 3, $world)));
        $this->addCrate(new BossCrate(new Position(139, 109, 13, $world)));
    }

    /**
     * @return Crate[]
     */
    public function getCrates(): array
    {
        return $this->crates;
    }

    /**
     * @param string $identifier
     *
     * @return Crate|null
     */
    public function getCrate(string $identifier): ?Crate
    {
        return isset($this->crates[strtolower($identifier)]) ? $this->crates[strtolower($identifier)] : null;
    }

    /**
     * @param Crate $crate
     */
    public function addCrate(Crate $crate)
    {
        $this->crates[strtolower($crate->getName())] = $crate;
    }
}