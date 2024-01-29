<?php

declare(strict_types = 1);

namespace Xekvern\Core\Server\Item;

// ENCHANTMENTS 
use Xekvern\Core\Server\Item\Utils\ExtraVanillaItems;
use Xekvern\Core\Utils\Utils;
use Xekvern\Core\Server\Item\Enchantment\Enchantment;
// ITEMS
use Xekvern\Core\Nexus;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\FireAspectEnchantment;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\ProtectionEnchantment;
use pocketmine\item\enchantment\Rarity;
use pocketmine\item\enchantment\SharpnessEnchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\TextFormat;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\HopsEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\PerceptionEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\QuickeningEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Bow\VelocityEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Mining\AmplifyEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Mining\HasteEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Mining\JackpotEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\WitherEnchantment;
use Xekvern\Core\Server\Item\Enchantment\EnchantmentEvents;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\BlessEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\DeflectEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\DivineProtectionEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\EvadeEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\FortifyEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\ImmunityEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\NourishEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Armor\RejuvenateEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Bow\LightEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Bow\ParalyzeEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Bow\PierceEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Mining\CharmEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Mining\DrillerEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Mining\FossilizationEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Mining\LuckEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Mining\SmeltingEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\AnnihilationEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\BerserkEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\BleedEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\ContaminateEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\DoublestrikeEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\DrainEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\FlingEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\GuillotineEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\ImprisonEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\LifestealEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\LustEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\NauseateEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\PassiveEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\PyrokineticEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\ShatterEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\SlaughterEnchantment;
use Xekvern\Core\Server\Item\Enchantment\Types\Weapon\ThunderEnchantment;
use Xekvern\Core\Server\Item\Types\ChestKit;
use Xekvern\Core\Server\Item\Types\CrateKeyNote;
use Xekvern\Core\Server\Item\Types\CreeperEgg;
use Xekvern\Core\Server\Item\Types\CustomTag;
use Xekvern\Core\Server\Item\Types\Drops;
use Xekvern\Core\Server\Item\Types\EnchantmentScroll;
use Xekvern\Core\Server\Item\Types\EXPNote;
use Xekvern\Core\Server\Item\Types\GeneratorBucket;
use Xekvern\Core\Server\Item\Types\Head;
use Xekvern\Core\Server\Item\Types\HolyBox;
use Xekvern\Core\Server\Item\Types\KOTHLootbag;
use Xekvern\Core\Server\Item\Types\KOTHStarter;
use Xekvern\Core\Server\Item\Types\Lootbox;
use Xekvern\Core\Server\Item\Types\MoneyNote;
use Xekvern\Core\Server\Item\Types\MonthlyCrate;
use Xekvern\Core\Server\Item\Types\Recon;
use Xekvern\Core\Server\Item\Types\SacredStone;
use Xekvern\Core\Server\Item\Types\SellWand;
use Xekvern\Core\Server\Item\Types\SellWandNote;
use Xekvern\Core\Server\Item\Types\TNTLauncher;
use Xekvern\Core\Server\Item\Types\Vanilla\CreeperSpawnEgg;
use Xekvern\Core\Server\Item\Types\XPNote;

class ItemHandler {

    /** @var Nexus */
    private $core;

    /** @var Enchantment[] */
    private static $enchantments = [];

    /** @var array */
    private static $classifiedEnchantments = [];

    /** @var array string[] */
    private $items = [];

    const DEFAULT_ENCHANT_LIMIT = 5;
    const MAX_ENCHANT_LIMIT = 12;

    /**
     * ItemHandler constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new ItemEvents($core), $core);
        $core->getServer()->getPluginManager()->registerEvents(new EnchantmentEvents($core), $core);
        $this->init();
        ExtraVanillaItems::initHack();
        self::registerItems();
        $core->getServer()->getAsyncPool()->addWorkerStartHook(function(int $worker) : void {
			Nexus::getInstance()->getServer()->getAsyncPool()->submitTaskToWorker(new class extends AsyncTask {
				public function onRun() : void{
					ItemHandler::registerItems();
				}
			}, $worker);
		});
    }

    public function init() {
            self::registerEnchantment(new \pocketmine\item\enchantment\Enchantment("50", Rarity::COMMON, 0, 0, 1), 300);
            // Armor
            self::registerEnchantment(new HopsEnchantment(), Enchantment::HOPS);
            self::registerEnchantment(new PerceptionEnchantment(), Enchantment::PERCEPTION);
            self::registerEnchantment(new QuickeningEnchantment(), Enchantment::QUICKENING);
            self::registerEnchantment(new BlessEnchantment(), Enchantment::BLESS);
            self::registerEnchantment(new DeflectEnchantment(), Enchantment::DEFLECT);
            self::registerEnchantment(new DivineProtectionEnchantment(), Enchantment::DIVINE_PROTECTION);
            self::registerEnchantment(new EvadeEnchantment(), Enchantment::EVADE);
            self::registerEnchantment(new FortifyEnchantment(), Enchantment::FORTIFY);
            self::registerEnchantment(new ImmunityEnchantment(), Enchantment::IMMUNITY);
            self::registerEnchantment(new NourishEnchantment(), Enchantment::NOURISH);
            self::registerEnchantment(new RejuvenateEnchantment(), Enchantment::REJUVENATE);

            // Bow
            self::registerEnchantment(new VelocityEnchantment(), Enchantment::VELOCITY);
            self::registerEnchantment(new PierceEnchantment(), Enchantment::PIERCE);
            self::registerEnchantment(new ParalyzeEnchantment(), Enchantment::PARALYZE);
            self::registerEnchantment(new LightEnchantment(), Enchantment::LIGHT);

            // Pickaxe
            self::registerEnchantment(new AmplifyEnchantment(), Enchantment::AMPLIFY);
            self::registerEnchantment(new CharmEnchantment(), Enchantment::CHARM);
            self::registerEnchantment(new LuckEnchantment(), Enchantment::LUCK);
            self::registerEnchantment(new HasteEnchantment(), Enchantment::HASTE);
            self::registerEnchantment(new JackpotEnchantment(), Enchantment::JACKPOT);
            self::registerEnchantment(new SmeltingEnchantment(), Enchantment::SMELTING);
            self::registerEnchantment(new FossilizationEnchantment(), Enchantment::FOSSILIZATION);
            //self::registerEnchantment(new DrillerEnchantment(), Enchantment::DRILLER);

            // Sword & Axe
            self::registerEnchantment(new WitherEnchantment(), Enchantment::WITHER);
            self::registerEnchantment(new ThunderEnchantment(), Enchantment::THUNDER);
            self::registerEnchantment(new SlaughterEnchantment(), Enchantment::SLAUGHTER);
            self::registerEnchantment(new ShatterEnchantment(), Enchantment::SHATTER);
            self::registerEnchantment(new PyrokineticEnchantment(), Enchantment::PYROKINETIC);
            self::registerEnchantment(new PassiveEnchantment(), Enchantment::PASSIVE);
            self::registerEnchantment(new NauseateEnchantment(), Enchantment::NAUSEATE);
            self::registerEnchantment(new LustEnchantment(), Enchantment::LUST);
            self::registerEnchantment(new LifestealEnchantment(), Enchantment::LIFESTEAL);
            self::registerEnchantment(new ImprisonEnchantment(), Enchantment::IMPRISON);
            self::registerEnchantment(new GuillotineEnchantment(), Enchantment::GUILLOTINE);
            self::registerEnchantment(new FlingEnchantment(), Enchantment::FLING);
            self::registerEnchantment(new DrainEnchantment(), Enchantment::DRAIN);
            self::registerEnchantment(new DoublestrikeEnchantment(), Enchantment::DOUBLE_STRIKE);
            self::registerEnchantment(new ContaminateEnchantment(), Enchantment::CONTAMINATE);
            self::registerEnchantment(new BleedEnchantment(), Enchantment::BLEED);
            self::registerEnchantment(new BerserkEnchantment(), Enchantment::BERSERK);
            self::registerEnchantment(new AnnihilationEnchantment(), Enchantment::ANNIHILATION);

            self::registerEnchantment(new \pocketmine\item\enchantment\Enchantment("UnknownCE", Rarity::COMMON, ItemFlags::ALL, ItemFlags::NONE, 1), 50);
            self::registerEnchantment(new \pocketmine\item\enchantment\Enchantment("Unbreaking", Rarity::COMMON, ItemFlags::ALL, ItemFlags::NONE, 35), EnchantmentIds::UNBREAKING);
            //self::registerEnchantment(new \pocketmine\item\enchantment\Enchantment("Looting", Rarity::UNCOMMON, ItemFlags::SWORD, ItemFlags::NONE, 7), EnchantmentIds::LOOTING);
            self::registerEnchantment(new \pocketmine\item\enchantment\Enchantment("Fortune", Rarity::UNCOMMON, ItemFlags::DIG, ItemFlags::NONE, 3), EnchantmentIds::FORTUNE);
            self::registerEnchantment(new \pocketmine\item\enchantment\Enchantment("Efficiency", Rarity::COMMON, ItemFlags::DIG, ItemFlags::SHEARS, 21), EnchantmentIds::EFFICIENCY);
            self::registerEnchantment(new \pocketmine\item\enchantment\Enchantment("Power", Rarity::COMMON, ItemFlags::BOW, ItemFlags::NONE, 9), EnchantmentIds::POWER);
            self::registerEnchantment(new ProtectionEnchantment("Protection", Rarity::MYTHIC, ItemFlags::ARMOR, ItemFlags::NONE, 23, 0.85, null), EnchantmentIds::PROTECTION);
            self::registerEnchantment(new SharpnessEnchantment("Sharpness", Rarity::MYTHIC, ItemFlags::SWORD, ItemFlags::AXE, 19), EnchantmentIds::SHARPNESS);
            self::registerEnchantment(new FireAspectEnchantment("Fire Aspect", Rarity::MYTHIC, ItemFlags::SWORD, ItemFlags::NONE, 7), EnchantmentIds::FIRE_ASPECT);
            //self::registerEnchantment(new ProtectionEnchantment("Feather Falling", Rarity::UNCOMMON, ItemFlags::FEET, ItemFlags::NONE, 13, 2.5, [
                EntityDamageEvent::CAUSE_FALL
            ]), EnchantmentIds::PROTECTION);

            $this->registerItem(EXPNote::class);
            $this->registerItem(MoneyNote::class);
            $this->registerItem(XPNote::class);
            $this->registerItem(ChestKit::class);
            $this->registerItem(SacredStone::class);
            $this->registerItem(Head::class);
            $this->registerItem(HolyBox::class);
            $this->registerItem(SellWandNote::class);
            $this->registerItem(MonthlyCrate::class);
            $this->registerItem(Recon::class);
            $this->registerItem(CustomTag::class);
            $this->registerItem(Lootbox::class);
            $this->registerItem(Drops::class);
            $this->registerItem(GeneratorBucket::class);    
            $this->registerItem(KOTHStarter::class); 
            $this->registerItem(KOTHLootbag::class);  
            $this->registerItem(CrateKeyNote::class);    
    }

    public static function registerItems() : void {
        Utils::registerSimpleItem(ItemTypeNames::END_CRYSTAL, ExtraVanillaItems::END_CRYSTAL(), ["end_crystal"]);
        Utils::registerSimpleItem(ItemTypeNames::CREEPER_SPAWN_EGG, ExtraVanillaItems::CREEPER_SPAWN_EGG(), ["creeper_spawn_egg"]);
        Utils::registerSimpleItem(ItemTypeNames::NAME_TAG, ExtraVanillaItems::NAME_TAG(), ["name_tag"]);
        Utils::registerSimpleItem(ItemTypeNames::ENDER_EYE, ExtraVanillaItems::ENDER_EYE(), ["ender_eye"]);
        Utils::registerSimpleItem(ItemTypeNames::FIREWORK_ROCKET, ExtraVanillaItems::FIREWORKS(), ["fireworks"]);
        Utils::registerSimpleItem(ItemTypeNames::EMPTY_MAP, ExtraVanillaItems::MAP(), ["map"]);
    }

    public static function getIdentifier(int $id): ?string {
        return self::$animationIDs[$id] ?? null;
    }

    private static array $animationIDs = [];

    /**
     * @return Enchantment[]
     */
    public static function getEnchantments(): array {
        return self::$enchantments;
    }

    /**
     * @param $identifier
     *
     * @return \pocketmine\item\enchantment\Enchantment|null
     */
    public static function getEnchantment($identifier): ?\pocketmine\item\enchantment\Enchantment {
        return self::$enchantments[$identifier] ?? null;
    }

    /**
     * @param int|null $rarity
     *
     * @return \pocketmine\item\enchantment\Enchantment
     */
    public static function getRandomEnchantment(?int $rarity = null): \pocketmine\item\enchantment\Enchantment {
        if($rarity !== null) {
            /** @var \pocketmine\item\enchantment\Enchantment[] $enchantments */
            try {
                $enchantments = self::$classifiedEnchantments[$rarity];
                return $enchantments[array_rand($enchantments)];
            }catch (\ErrorException){

            }
        }
        return self::$enchantments[array_rand(self::$enchantments)];
    }

    /**
     * @param \pocketmine\item\enchantment\Enchantment $enchantment
     */
    public static function registerEnchantment(\pocketmine\item\enchantment\Enchantment $enchantment, int $id): void {
        EnchantmentIdMap::getInstance()->register($id, $enchantment);
        self::$enchantments[$id] = $enchantment;
        self::$enchantments[$enchantment->getName()] = $enchantment;
        self::$classifiedEnchantments[$enchantment->getRarity()][] = $enchantment;
    }

    /**
     * @param int $integer
     *
     * @return string
     */
    public static function getRomanNumber(int $integer): string {
        $characters = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1
        ];
        $romanString = "";
        while($integer > 0) {
            foreach($characters as $rom => $arb) {
                if($integer >= $arb) {
                    $integer -= $arb;
                    $romanString .= $rom;
                    break;
                }
            }
        }
        return $romanString;
    }

    /**
     * @param Item $item
     * @param \pocketmine\item\enchantment\Enchantment $enchantment
     *
     * @return bool
     */
    public static function canEnchant(Item $item, \pocketmine\item\enchantment\Enchantment $enchantment): bool {
        if($item->hasEnchantment($enchantment)) {
            if($item->getEnchantmentLevel($enchantment) < $enchantment->getMaxLevel()) {
                return true;
            }
            return false;
        }
        switch($enchantment->getPrimaryItemFlags()) {
            case ItemFlags::ALL:
                if($item instanceof Durable) {
                    return true;
                }
                break;
            case ItemFlags::FEET:
                if($item->getTypeId() === ItemTypeIds::LEATHER_BOOTS or 
                $item->getTypeId() === ItemTypeIds::CHAINMAIL_BOOTS or 
                $item->getTypeId() === ItemTypeIds::GOLDEN_BOOTS or 
                $item->getTypeId() === ItemTypeIds::IRON_BOOTS or 
                $item->getTypeId() === ItemTypeIds::DIAMOND_BOOTS) {
                    return true;
                }
                break;
            case ItemFlags::HEAD:
                if($item->getTypeId() === ItemTypeIds::LEATHER_CAP or 
                $item->getTypeId() === ItemTypeIds::CHAINMAIL_CHESTPLATE or 
                $item->getTypeId() === ItemTypeIds::GOLDEN_HELMET or
                $item->getTypeId() === ItemTypeIds::IRON_HELMET or 
                $item->getTypeId() === ItemTypeIds::DIAMOND_HELMET) {
                    return true;
                }
                break;
            case ItemFlags::ARMOR:
                if($item->getTypeId() === ItemTypeIds::LEATHER_TUNIC or 
                $item->getTypeId() === ItemTypeIds::CHAINMAIL_CHESTPLATE or 
                $item->getTypeId() === ItemTypeIds::GOLDEN_CHESTPLATE or 
                $item->getTypeId() === ItemTypeIds::IRON_CHESTPLATE or 
                $item->getTypeId() === ItemTypeIds::DIAMOND_CHESTPLATE
                or $item instanceof Armor) {
                    return true;
                }
                break;
            case ItemFlags::SWORD:
                if($item->getTypeId() === ItemTypeIds::WOODEN_SWORD or 
                $item->getTypeId() === ItemTypeIds::STONE_SWORD or 
                $item->getTypeId() === ItemTypeIds::IRON_SWORD or
                $item->getTypeId() === ItemTypeIds::GOLDEN_SWORD or 
                $item->getTypeId() === ItemTypeIds::DIAMOND_SWORD or 
                $item->getTypeId() === ItemTypeIds::WOODEN_AXE or
                $item->getTypeId() === ItemTypeIds::STONE_AXE or 
                $item->getTypeId() === ItemTypeIds::IRON_AXE or 
                $item->getTypeId() === ItemTypeIds::GOLDEN_AXE or 
                $item->getTypeId() === ItemTypeIds::DIAMOND_AXE) {
                    return true;
                }
                break;
            case ItemFlags::BOW:
                if($item->getTypeId() === ItemTypeIds::BOW) {
                    return true;
                }
                break;
            case ItemFlags::DIG:
                if($item->getTypeId() === ItemTypeIds::WOODEN_PICKAXE or
                $item->getTypeId() === ItemTypeIds::STONE_PICKAXE or
                $item->getTypeId() === ItemTypeIds::IRON_PICKAXE or 
                $item->getTypeId() === ItemTypeIds::GOLDEN_PICKAXE or
                $item->getTypeId() === ItemTypeIds::DIAMOND_PICKAXE) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * @param int $flag
     *
     * @return string
     */
    public static function flagToString(int $flag): string {
        return match ($flag) {
            ItemFlags::FEET => "Boots",
            ItemFlags::TORSO => "Chestplate",
            ItemFlags::ARMOR => "Armor",
            ItemFlags::HEAD => "Helmet",
            ItemFlags::SWORD => "Sword",
            ItemFlags::BOW => "Bow",
            ItemFlags::DIG => "Tools",
            default => "None",
        };
    }

    /**
     * @param int $rarity
     *
     * @return string
     */
    public static function rarityToString(int $rarity): string {
        return match ($rarity) {
            default => "Common",
            Rarity::UNCOMMON => "Uncommon",
            Rarity::RARE => "Rare",
            Rarity::MYTHIC => "Legendary",
            Enchantment::RARITY_GODLY => "Godly"
        };
    }

    /**
     * @param int $rarity
     *
     * @return string
     */
    public static function rarityToColor(int $rarity): string {
        return match ($rarity) {
            default => TextFormat::BLUE,
            Rarity::UNCOMMON => TextFormat::DARK_BLUE,
            Rarity::RARE => TextFormat::LIGHT_PURPLE,
            Rarity::MYTHIC => TextFormat::AQUA,
            Enchantment::RARITY_GODLY => TextFormat::RED
        };
    }

    /**
     * @param Item $item
     *
     * @return Item
     */
    public static function setLoreForItem(Item $item): Item {
        $common = [];
        $uncommon = [];
        $rare = [];
        $mythic = [];
        $godly = [];
        foreach($item->getEnchantments() as $enchantment) {
            $type = $enchantment->getType();
            if($type instanceof Enchantment) {
                switch($type->getRarity()) {
                    case Rarity::COMMON:
                        $common[] = $enchantment;
                        break;
                    case Rarity::UNCOMMON:
                        $uncommon[] = $enchantment;
                        break;
                    case Rarity::RARE:
                        $rare[] = $enchantment;
                        break;
                    case Rarity::MYTHIC:
                        $mythic[] = $enchantment;
                        break;
                    case Enchantment::RARITY_GODLY:
                        $godly[] = $enchantment;
                        break;
                    default:
                        break;
                }
            }
        }
        $lore = [];
        $enchantments = array_merge($common, $uncommon, $rare, $mythic, $godly);
        foreach($enchantments as $enchantment) {
            if($enchantment->getType() instanceof Enchantment) {
                $lore[] = TextFormat::RESET . ItemHandler::rarityToColor($enchantment->getType()->getRarity()) . $enchantment->getType()->getName() . " " . ItemHandler::getRomanNumber($enchantment->getLevel());
            }
        }
        $tag = $item->getNamedTag();
        if($item instanceof Durable) {   // "Unknown CE Prevention"
            if($tag !== null) {
                if(isset($tag->getValue()[EnchantmentScroll::SCROLL_AMOUNT])) {
                    $amount = $tag->getInt(EnchantmentScroll::SCROLL_AMOUNT);
                    $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Enchantments: " . TextFormat::WHITE . count($item->getEnchantments()) . "/" . $amount;
                } else {
                    $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Enchantments: " . TextFormat::WHITE . count($item->getEnchantments()) . "/" . self::DEFAULT_ENCHANT_LIMIT;
                }
            } else {
                $tag->setInt(EnchantmentScroll::SCROLL_AMOUNT, self::DEFAULT_ENCHANT_LIMIT);
                $lore[] = TextFormat::RESET . TextFormat::YELLOW . "Enchantments: " . TextFormat::WHITE . count($item->getEnchantments()) . "/" . self::DEFAULT_ENCHANT_LIMIT;
            }
        }
        $item->setLore($lore);
        return $item;
    }

    /**
     * @param string $item
     */
    public function registerItem(string $item): void {
        $this->items[$item] = $item;
    }

    /**
     * @param CompoundTag $tag
     *
     * @return string|null
     */
    public function matchItem(CompoundTag $tag): ?string {
        if($tag->getTag("ItemClass") instanceof Tag) {
            $class = $tag->getString("ItemClass");
            if(isset($this->items[$class])) {
                $item = $this->items[$class];
                return $item;
            }
        }
        return null;
    }

    /**
     * @param int $rarity
     *
     * @return float
     */
    public static function rarityToMultiplier(int $rarity): float {
        return match ($rarity) {
            Rarity::COMMON => 1,
            Rarity::UNCOMMON => 1.25,
            Rarity::RARE => 1.5,
            Rarity::MYTHIC => 2,
            default => 0,
        };
    }

    /**
     * @param int $tier
     *
     * @return int
     */
    public static function getFuelAmountByTier(int $tier): int {
        return match ($tier) {
            1 => 2,
            2 => 4,
            3 => 8,
            default => 1,
        };
    }

    public static function getSpongeFuelAmountByTier(int $tier): int {
        return match ($tier) {
            1 => 2,
            2 => 4,
            3 => 6,
            default => 1,
        };
    }

    public static function getWaterFuelAmountByTier(int $tier): int {
        return match ($tier) {
            1 => 1,
            2 => 3,
            3 => 5,
            default => 1,
        };
    }

    public static function getLavaFuelAmountByTier(int $tier): int {
        return match ($tier) {
            1 => 1,
            2 => 2,
            3 => 3,
            default => 1,
        };
    }
}