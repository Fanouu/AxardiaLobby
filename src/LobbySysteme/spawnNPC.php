<?php

namespace LobbySysteme;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\EntityDataHelper;
use pocetmine\entity\EntityLegacyIds;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\utils\SingletonTrait;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\entity\Location;

use LobbySysteme\core;
use LobbySysteme\NPCEntity;

class spawnNPC extends Command{

    use SingletonTrait;

    private $plugin;

    public function __construct(core $plugin) {

        parent::__construct("spawnnpc", "...", "/spawnnpc [types]", []);
        $this->plugin = $plugin;

    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){

        if($sender instanceof Player){
            if($sender->hasPermission("npc.cmd")) return $sender->sendMessage("§cTu na pas les permissions requise");
            if(!isset($args[0])) return $sender->sendMessage("§cVeillez donner un argument: PvpBox, Faction, Practice");
            $x = $sender->getPosition()->x;
            $y = $sender->getPosition()->y;
            $z = $sender->getPosition()->z;

            $position = new Position($x, $y, $z, $sender->getWorld());
            $nbt = $this->createBaseNBT($sender->getLocation(), null, $sender->getLocation()->getYaw(), $sender->getLocation()->getPitch());

            $nbt->setTag("Skin", CompoundTag::create()
                ->setString("Name", $sender->getSkin()->getSkinId())
                ->setByteArray("Data", $sender->getSkin()->getSkinData())
                ->setByteArray("CapeData", $sender->getSkin()->getCapeData())
                ->setString("GeometryName", $sender->getSkin()->getGeometryName())
                ->setByteArray("GeometryData", $sender->getSkin()->getGeometryData())
            );
            $nbt->setString("NPCType", $args[0]);

            $entity = $this->createNPC($sender->getLocation(), $nbt);
            $entity->teleport($position);
            $entity->setScale(1.0);
            $entity->setImmobile(true);
            $entity->setNameTagAlwaysVisible(true);
            $entity->setNPCType($args[0]);
            $entity->spawnToAll();

            $sender->sendMessage("§1Entity was succesfully creatad with ID's: §c" . $entity->getId() . "§1 and type's! §c" . $entity->getNPCTypes());


            #}else{
            #    $sender->sendMessage("§cTu na pas les permissions requise");
            #}
        }
    }

     public function createBaseNBT(Vector3 $pos, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0): CompoundTag {
        return CompoundTag::create()
            ->setTag("Pos", new ListTag([
                new DoubleTag($pos->x),
                new DoubleTag($pos->y),
                new DoubleTag($pos->z)
            ]))
            ->setTag("Motion", new ListTag([
                new DoubleTag($motion !== null ? $motion->x : 0.0),
                new DoubleTag($motion !== null ? $motion->y : 0.0),
                new DoubleTag($motion !== null ? $motion->z : 0.0)
            ]))
            ->setTag("Rotation", new ListTag([
                new FloatTag($yaw),
                new FloatTag($pitch)
            ]));
    }

    public function createNPC(Location $location, CompoundTag $nbt): ?Entity{
        return new NPCEntity($location, Human::parseSkinNBT($nbt), $nbt);
    }
}