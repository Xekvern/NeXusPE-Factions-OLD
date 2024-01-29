<?php

declare(strict_types = 1);

namespace Xekvern\Core\Provider;

use Xekvern\Core\Nexus;
use Xekvern\Core\Provider\Task\LoadQueueTask;
use Xekvern\Core\Provider\MySQLException;
use Xekvern\Core\Provider\Thread\MySQLThread;
use mysqli;
use mysqli_sql_exception;
use Xekvern\Core\Provider\Task\ReadResultsTask;

class MySQLProvider {

    /** @var Nexus */
    private $core;

    /** @var mysqli */
    private $database;
    
    /** @var MySQLCredentials */
    private $credentials;

    /** @var MySQLThread */
    private $thread;

    /** @var LoadQueueTask */
    private $loadQueue;

    /** @var array */
    public static array $config = [
        "host" => "51.79.173.175:3306",
        "username" => "u100872_nBvBaat71h",
        "password" => "^nj=427L+sORo6hZWg=DORPc",
        "schema" => "s100872_OPFacs"
    ];

    /**
     * MySQLProvider constructor.
     *
     * @param Nexus $core
     * @throws MySQLException
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->database = new mysqli(self::$config["host"], self::$config["username"], self::$config["password"], self::$config["schema"]);
        $this->credentials = new MySQLCredentials(self::$config["host"], self::$config["username"], self::$config["password"], self::$config["schema"]);
        $this->init();
        $this->thread = new MySQLThread($this->credentials);
        $this->thread->start();
        $core->getScheduler()->scheduleRepeatingTask(new ReadResultsTask($this->thread), 1);
        $this->loadQueue = new LoadQueueTask();
        $core->getScheduler()->scheduleRepeatingTask($this->loadQueue, 10);
    }

    public function init(): void {
        $this->database->query("CREATE TABLE IF NOT EXISTS users(
            uuid VARCHAR(36) PRIMARY KEY, 
            username VARCHAR(16), 
            rankId TINYINT DEFAULT 0, 
            permissions VARCHAR(600) DEFAULT '', 
            permanentPermissions VARCHAR(600) DEFAULT '', 
            votePoints BIGINT DEFAULT 0, 
            tags BLOB DEFAULT NULL, 
            currentTag VARCHAR(150) DEFAULT '', 
            inbox BLOB DEFAULT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS stats(
            uuid VARCHAR(36) PRIMARY KEY, 
            username VARCHAR(16), 
            faction VARCHAR(30) DEFAULT NULL, 
            factionRole TINYINT DEFAULT NULL, 
            sellWandUses SMALLINT DEFAULT 0,
            kills SMALLINT DEFAULT 0, 
            deaths SMALLINT DEFAULT 0, 
            luckyBlocks MEDIUMINT DEFAULT 0, 
            balance BIGINT DEFAULT 0, 
            bounty INT DEFAULT 0, 
            power BIGINT DEFAULT 0, 
            questPoints BIGINT DEFAULT 0, 
            onlineTime INT DEFAULT 0,
            kitCooldowns VARCHAR(4000) DEFAULT '', 
            kitTiers VARCHAR(4000) DEFAULT '', 
            crateKeys VARCHAR(4000) DEFAULT '',
            playerLevel BIGINT DEFAULT 0, 
            playerLevelProgress BIGINT DEFAULT 0
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS homes(
            uuid VARCHAR(36) NOT NULL, 
            username VARCHAR(16), 
            name VARCHAR(16) NOT NULL,
            x SMALLINT NOT NULL, 
            y SMALLINT NOT NULL, 
            z SMALLINT NOT NULL, 
            level VARCHAR(30) NOT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS factions(
            name VARCHAR(30) PRIMARY KEY, 
            x SMALLINT DEFAULT NULL, 
            y SMALLINT DEFAULT NULL, 
            z SMALLINT DEFAULT NULL, 
            members TEXT NOT NULL, 
            allies TEXT DEFAULT NULL, 
            balance BIGINT DEFAULT 0,
            strength BIGINT DEFAULT 0,
            claimValue BIGINT DEFAULT 0,
            upgrades VARCHAR(4000) DEFAULT '',
            vault BLOB DEFAULT NULL,
            permissions VARCHAR(4000) DEFAULT '',
            payoutEmail VARCHAR(254) DEFAULT ''
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS claims(
            faction VARCHAR(30) NOT NULL, 
            chunkX SMALLINT DEFAULT NULL, 
            chunkZ SMALLINT DEFAULT NULL,
            value BIGINT DEFAULT 0 NOT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS ipAddress(
            uuid VARCHAR(36), 
            username VARCHAR(16), 
            ipAddress VARCHAR(20), 
            riskLevel TINYINT
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS vaults(
            username VARCHAR(16) NOT NULL,
            alias VARCHAR(24) DEFAULT NULL,
            id SMALLINT, 
            items BLOB DEFAULT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS auctions(
            identifier INT PRIMARY KEY, 
            seller VARCHAR(16) NOT NULL, 
            item BLOB NOT NULL, 
            startTime BIGINT NOT NULL,
            buyPrice BIGINT DEFAULT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS blackAuctionHistory(
            soldTime BIGINT PRIMARY KEY, 
            buyer VARCHAR(16) NOT NULL, 
            item BLOB NOT NULL,
            buyPrice BIGINT DEFAULT NULL
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS punishments(
            username VARCHAR(16) PRIMARY KEY, 
            type TINYINT, 
            expiration BIGINT DEFAULT 0, 
            time BIGINT, 
            effector VARCHAR(16), 
            reason VARCHAR(100)
        );");
        $this->database->query("CREATE TABLE IF NOT EXISTS punishmentHistory(
            username VARCHAR(16), 
            type TINYINT, 
            expiration BIGINT DEFAULT 0, 
            time BIGINT, 
            effector VARCHAR(16), 
            reason VARCHAR(100)
        );");
    }
    
    /**
     * @return MySQLThread
     */
    public function createNewThread(): MySQLThread {
        if(!$this->thread->isRunning()) {
            $this->thread = new MySQLThread($this->credentials);
            $this->thread->start();
        }
        return $this->thread;
    }

    /**
     * @return string
     */
    public function getMainDatabaseName(): string {
        return $this->credentials->getDatabase();
    }

    /**
     * @return mysqli
     */
    public function getDatabase(): mysqli {
        return $this->database;
    }

    /**
     * @return MySQLThread
     */
    public function getConnector(): MySQLThread {
        return $this->thread;
    }

    /**
     * @return MySQLCredentials
     */
    public function getCredentials(): MySQLCredentials {
        return $this->credentials;
    }
    
    /**
     * @return LoadQueueTask
     */
    public function getLoadQueue(): LoadQueueTask {
        return $this->loadQueue;
    }
}