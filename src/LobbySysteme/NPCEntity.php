<?php

namespace LobbySysteme;

use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\player\Player;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\entity\EntityFactory;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

use LobbySysteme\core;

class NPCEntity extends Human{
    
    private $plugin;

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $skinTag = $nbt->getCompoundTag("Skin");
        $this->setHealth(1);
        $this->setMaxHealth(1);
        $this->setScale(1.0);
        $this->skinTag = $skinTag;
        $this->types = "default";
        
        $nbt->setString("NPCType", $this->types);
    }

    public function getName() : string{
        return "NPCEntity";
    }

    public function attack(EntityDamageEvent $event): void{

        if($event instanceof EntityDamageByEntityEvent){
            $pdamager = $event->getDamager();
            if($pdamager instanceof Player){
                $event->cancel();

                if($pdamager->getInventory()->getItemInHand()->getId() === 511){
                    $this->flagForDespawn();
                }
            }
        }else{
            $event->cancel();
        }
    }

    public function onUpdate(int $currentTick): bool{
      if($this->saveNBT()->getString("NPCType") === null){
        $this->saveNBT()->setString("NPCType", "PvpBox");
      }
        if($this->getNPCTypes() === "PvpBox"){
            $pvpBoxMax = core::PMQuery("axardia.eu", 19133)["MaxPlayers"];
            $pvpBox = core::PMQuery("axardia.eu", 19133)["Players"];
            if($pvpBox !== null){
                $this->setNameTag("§6» §2PvpBox (ouvert) \n§r§e§l{$pvpBox}§r§f/§e§l$pvpBoxMax");
            }else{
                $this->setNameTag("§6» §4PvpBox (offline)");
            }
        }

        if($this->getNPCTypes() === "Practice"){
            $practiceMax = core::PMQuery("axardia.eu", 19135)["MaxPlayers"];
            $practice = core::PMQuery("axardia.eu", 19135)["Players"];
            if($practice !== null){
                $this->setNameTag("§6» §cPractice (whitelisted) \n§r§e§l{$practice}§r§f/§e§l$practiceMax");
            }else{
                $this->setNameTag("§6» §4Practice (offline) \n§r§e§l-§r§f/§e§l-");
            }
        }

        if($this->getNPCTypes() === "Faction"){
            $factionMax = core::PMQuery("axardia.eu", 19134)["MaxPlayers"];
            $faction = core::PMQuery("axardia.eu", 19134)["Players"];
            if($faction !== null){
                $this->setNameTag("§6» §cFaction (whitelisted) \n§r§e§l{$faction}§r§f/§e§l$factionMax");
            }else{
                $this->setNameTag("§6» §4Faction (offline) \n§r§e§l-§r§f/§e§l-");
            }
        }
        $this->setNameTagAlwaysVisible(true);
        return parent::onUpdate($currentTick);
    }

    public function setSkinTag(CompoundTag $tag): void {
        $this->skinTag = $tag;
    }

    public function getSkinTag(): CompoundTag {
        return $this->skinTag;
    }

    public function Motion(EntityMotionEvent $event):void {
        $event->cancel();
    }

    public function setNPCType($argument){
      $this->saveNBT()->setString("NPCType", $argument);
        $this->types = $argument;
    }

    public function getNPCTypes(){
        $this->setNPCType($this->types);
        return $this->saveNBT()->getString("NPCType");
    }
    
    public function saveNBT() : CompoundTag{
		$nbt = CompoundTag::create()
			->setTag("Pos", new ListTag([
				new DoubleTag($this->location->x),
				new DoubleTag($this->location->y),
				new DoubleTag($this->location->z)
			]))
			->setTag("Motion", new ListTag([
				new DoubleTag($this->motion->x),
				new DoubleTag($this->motion->y),
				new DoubleTag($this->motion->z)
			]))
			->setTag("Rotation", new ListTag([
				new FloatTag($this->location->yaw),
				new FloatTag($this->location->pitch)
			]));

		if(!($this instanceof Player)){
			EntityFactory::getInstance()->injectSaveId(get_class($this), $nbt);

			if($this->getNameTag() !== ""){
				$nbt->setString("CustomName", $this->getNameTag());
				$nbt->setByte("CustomNameVisible", $this->isNameTagVisible() ? 1 : 0);
			}
		}

		$nbt->setFloat("FallDistance", $this->fallDistance);
		$nbt->setShort("Fire", $this->fireTicks);
		$nbt->setByte("OnGround", $this->onGround ? 1 : 0);
		$nbt->setString("NPCType", $this->types);
		
		return $nbt;
	}

}