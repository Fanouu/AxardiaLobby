<?php

namespace LobbySysteme;

use pocketmine\event\CancellableTrait;
use pocketmine\event\Cancellable;
use pocketmine\plugin\PluginBase;
use pocketmine\item\ItemFactory;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\player\GameMode;
use pocketmine\world\Position;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\entity\Entity;
use pocketmine\world\World;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\EntityDataHelper;

use LobbySysteme\Forms\SimpleForm;
use LobbySysteme\spawnNPC;
use LobbySysteme\NPCEntity;

class core extends PluginBase implements Listener, Cancellable{
    use CancellableTrait;

    public static $cooldNoSpam = [];
    public static $me;

    protected function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("§5- §dAxardia Lobby §5-");
        @mkdir($this->getDataFolder());
        self::$me = $this;
        $this->saveResource("settings.yml");
        $this->setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);

        $this->getServer()->getCommandMap()->registerAll('Commands', [
            new spawnNPC($this)
        ]);

        EntityFactory::getInstance()->register(NPCEntity::class, function(World $world, CompoundTag $nbt): Entity{
            return new NPCEntity(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ['NPCEntity']);
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $pname = $player->getName();
        $setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);

        $event->setJoinMessage("");
        $msg = str_replace("{player}", $pname, $setting->get("JoinMessage"));
        $player->getServer()->broadcastMessage($msg);

        $player->setHealth("20");
        $player->getInventory()->clearAll();
        //$player->setGamemode(GameMode::SURVIVAL);
        
        $item = ItemFactory::getInstance()->get(ItemIds::ARROW);
        $item->setCustomName("§r§5- §dDiscord §5-");
        $item->setLore(["§r§5[§dAxardia §fLobby§5]"]);
        $player->getInventory()->setItem(0, $item);
        
        $item = ItemFactory::getInstance()->get(369, 0);
        $item->setCustomName("§r§5- §dJump Boost §5-");
        $item->setLore(["§r§5[§dAxardia §fLobby§5]"]);
        $player->getInventory()->setItem(3, $item);

        $item = ItemFactory::getInstance()->get(ItemIds::COMPASS);
        $item->setCustomName("§r§5- §dServeur §5-");
        $item->setLore(["§r§5[§dAxardia §fLobby§5]"]);
        $player->getInventory()->setItem(4, $item);

        $item = ItemFactory::getInstance()->get(351, 12);
        $item->setCustomName("§r§5- §dHide Players §5-");
        $item->setLore(["§r§5[§dAxardia §fLobby§5]"]);
        $player->getInventory()->setItem(5, $item);

        $item = ItemFactory::getInstance()->get(341, 0);
        $item->setCustomName("§r§5- §dSettings §5-");
        $item->setLore(["§r§5[§dAxardia §fLobby§5]"]);
        $player->getInventory()->setItem(8, $item);

        $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation(), 0, 0);
        //$player->setDirection(1);

    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $pname = $player->getName();
        $setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);

        $event->setQuitMessage(" ");
        $msg = str_replace("{player}", $pname, $setting->get("QuitMessage"));
        $player->getServer()->broadcastMessage($msg);
    }

    public function BlockBreak(BlockBreakEvent $event){
        $setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);

        if ($setting->get("BlockBreak") == true){
            $event->uncancel();
        }else if($setting->get("BlockBreak") == false && !$this->getServer()->isOp($event->getPlayer()->getName())){
            $event->cancel();
            $event->getPlayer()->sendMessage("§5» §cVous ne pouvez pas faire ceci");
        }
    }

    public function BlockPlace(BlockPlaceEvent $event){
        $setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);

        if ($setting->get("BlockPlace") == true){
            $event->uncancel();
        }else if($setting->get("BlockPlace") == false && !$this->getServer()->isOp($event->getPlayer()->getName())){
            $event->cancel();
            $event->getPlayer()->sendMessage("§5» §cVous ne pouvez pas faire ceci");
        }
    }

    public function PlayerDamage(EntityDamageEvent $event){
        $setting = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
        $entity = $event->getEntity();

        if($setting->get("PlayerDamage") == true){
            $event->uncancel();
        }else if($setting->get("PlayerDamage") == false){
            $event->cancel();    
        }
    }

    public function PlayerInteract(PlayerInteractEvent $event){
        $item = $event->getItem()->getId();
        $pname = $event->getPlayer()->getName();
        if($item == ItemIds::COMPASS){
            if(!isset(self::$cooldNoSpam[$pname]) || self::$cooldNoSpam[$pname] - time() <= 0){
                $this->openForm($event->getPlayer());
                self::$cooldNoSpam[$pname] = time() + 0.1;
            }
        }
    }

    public function openForm($player){
        $form = self::createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if($result === null){
                return true;
            }
            switch($result){
                case 0:
                    $player->sendPopup("§5- §aRedirection... §5-");
                    $player->transfer("axardia.eu", "19133");
                break;

                case 1:
                    $player->sendPopup("§5» §l§cImpossible de rejoidre le serveur, connection refusé (server non ouvert) !");
                break;

                case 2:
                    $player->sendPopup("§5» §l§cImpossible de rejoidre le serveur, connection refusé (server non ouvert) !");
                break;
            }
            return true;

        });
        $pvpBoxMax = self::PMQuery("axardia.eu", 19133)["MaxPlayers"];
        $pvpBox = self::PMQuery("axardia.eu", 19133)["Players"];
        $form->setTitle("§5- §dServeur §5-");
        $form->setContent("Choisissez une option! ");
        $form->addButton("§5» §aPvPBox (ouvert) \n §r§e§l{$pvpBox}§r§f/§e§l$pvpBoxMax");
        $form->addButton("§5» §cPractice (whitelisted)");
        $form->addButton("§5» §cFaction (whitelisted)");
        $player->sendForm($form);
    }

    public static function createSimpleForm(callable $function = null) : SimpleForm {
        return new SimpleForm($function);
    }

    public static function PMQuery(string $host, int $port, int $timeout = 4){
        $socket = @fsockopen('udp://'.$host, $port, $errno, $errstr, $timeout);

		if($errno and $socket !== false) {
			fclose($socket);
			throw new PmQueryException($errstr, $errno);
		}elseif($socket === false) {
			throw new PmQueryException($errstr, $errno);
		}

		stream_Set_Timeout($socket, $timeout);
		stream_Set_Blocking($socket, true);

		// hardcoded magic https://github.com/facebookarchive/RakNet/blob/1a169895a900c9fc4841c556e16514182b75faf8/Source/RakPeer.cpp#L135
		$OFFLINE_MESSAGE_DATA_ID = \pack('c*', 0x00, 0xFF, 0xFF, 0x00, 0xFE, 0xFE, 0xFE, 0xFE, 0xFD, 0xFD, 0xFD, 0xFD, 0x12, 0x34, 0x56, 0x78);
		$command = \pack('cQ', 0x01, time()); // DefaultMessageIDTypes::ID_UNCONNECTED_PING + 64bit current time
		$command .= $OFFLINE_MESSAGE_DATA_ID;
		$command .= \pack('Q', 2); // 64bit guid
		$length = \strlen($command);

		if($length !== fwrite($socket, $command, $length)) {
			//self::$me->getLogger()->warning("Failed to write on socket.");
		}

		$data = fread($socket, 4096);

		fclose($socket);

		if(empty($data) or $data === false) {
			//self::$me->getLogger()->warning("Server failed to respond");
		}
		if(substr($data, 0, 1) !== "\x1C") {
			//self::$me->getLogger()->warning("First byte is not ID_UNCONNECTED_PONG.");
		}
		if(substr($data, 17, 16) !== $OFFLINE_MESSAGE_DATA_ID) {
			//self::$me->getLogger()->warning("Magic bytes do not match.");
		}

		// TODO: What are the 2 bytes after the magic?
		$data = \substr($data, 35);

		// TODO: If server-name contains a ';' it is not escaped, and will break this parsing
		$data = \explode(';', $data);

		return [
			'GameName' => $data[0] ?? null,
			'HostName' => $data[1] ?? null,
			'Protocol' => $data[2] ?? null,
			'Version' => $data[3] ?? null,
			'Players' => $data[4] ?? null,
			'MaxPlayers' => $data[5] ?? null,
			'ServerId' => $data[6] ?? null,
			'Map' => $data[7] ?? null,
			'GameMode' => $data[8] ?? null,
			'NintendoLimited' => $data[9] ?? null,
			'IPv4Port' => $data[10] ?? null,
			'IPv6Port' => $data[11] ?? null,
			'Extra' => $data[12] ?? null, // TODO: What's in this?
      'Online' => $data,
		];
    }
}