<?php

declare(strict_types = 1);

namespace Xekvern\Core\Command\Forms;

use Xekvern\Core\Player\NexusPlayer;
use libs\form\CustomForm;
use libs\form\element\Label;
use pocketmine\utils\TextFormat;

class LevelInfoForm extends CustomForm {

    /**
     * LevelInfoForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $level = $player->getDataSession()->getCurrentLevel();
        $title = TextFormat::BOLD . TextFormat::AQUA . "Level $level";
        $rewards = implode(TextFormat::RESET . "\n", [
            "NOTES:",
            "- Each level SLIGHTLY boosts your xp gain from generators",
            "- More buffs will be added with time as well as more level rewards",
            " ",
            "Level 1:",
            "- Access to create a faction using /f create",
            " ",
            "Level 2:",
            "- Access to opening holy boxes.",
            " ",
            "Level 3:",
            "- Access to Lapis Lazuli Generator at Shop.",
            "- Access to Redstone Dust Generator at Shop.",
            " ",
            "Level 10",
            "- 3x Ultra Crate Keys",
            "- Access to Iron Generator at Shop.",
            " ",
            "Level 20:",
            "- 5x Ultra Crate Keys",
            "- Access to /sell auto",
            "- $3,500,000",
            "- Access to Diamond Generator at Shop.",
            " ", 
            "Level 30:",
            "- 5x Souls",
            "- 3x Epic Crate Keys",
            " ",
            "Level 40:",
            "- 10x Sacred Stone",
            "- 5x Epic Crate Keys",
            " ",
            "Level 50:",
            "- 2x Legendary Crate Keys",
            "- LEVEL50 TAG",
            "- Access to Emerald Generator at Shop.",
            " ",
            "Level 60:",
            "- 1x KOTH Starter",
            "- 1x Custom Tag",
            "- 3x Legendary Crate Keys",
            " ",
            "Level 70:",
            "- 3x Random Holy Box",
            "- 5x Legendary Crate Keys",
            " ",
            "Level 80:",
            "- 10x Legendary Crate Keys",
            "- 1x King Kit",
            "- 1x Deity Kit",
            "- Access to /fly"
        ]);
        if($player->getDataSession()->getLevelProgress() === "Maxed") {
            $text = TextFormat::AQUA . "Progress: (Maxed)"  . TextFormat::AQUA . "\nRewards: \n" . TextFormat::RESET . $rewards;
        } else {
            $text = TextFormat::AQUA . "Progress: " . TextFormat::WHITE . "(" . number_format($player->getDataSession()->getLevelProgress()) . "/" . number_format($player->getDataSession()->getLevelTargetValue($player->getDataSession()->getCurrentLevel())) . ") XP" . TextFormat::AQUA . "\nRewards: \n" . TextFormat::RESET . $rewards;
        }
        $elements = [];
        $elements[] = new Label("Info", $text);
        parent::__construct($title, $elements);
    }
}