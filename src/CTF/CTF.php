<?php

namespace CTF;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDropItemEvent;	
use pocketmine\command\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\block\Wool;
use pocketmine\block\Block;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\inventory\Inventory;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\tile\Sign;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\BubbleParticle;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerHungerChangeEvent;

class CTF extends PluginBase implements Listener {
	
	// EDITABLE:
	public $matchLength = 10;
	public $maxPlayers = 20;
	public $maxPoints = 10;
	///////////////////////
	
	public $gameStatus;
	public $countdown;
	public $icountdown;
	
	public $popup;
	
	public $mins;
	public $secs = 60;
	public $isecs = 60;
	
	public $gameStarted = 0;
	public $bluePoints = 0;
	public $redPoints = 0;
	public $red = 0;
	public $blue = 0;

	public $spectators = [];
	public $inGame = [];
	
	
	public $redTeamMembers = [];
	public $blueTeamMembers = [];
	
	public $redCaptured = false;
	public $blueCaptured = false;
	
	public $redStealer = "";
	public $blueStealer = "";
	
	public $perms;

	public function onEnable(){	
		$this->getServer()->getNetwork()->setName(TextFormat::GREEN . "EPICMC " . TextFormat::WHITE . "[BETA]");
		$this->getServer()->getLevelByName("world")->getTile(new Vector3(114, 83, 125))->setText("", TextFormat::RED . "[RED]", TextFormat::WHITE . "0");
		$this->getServer()->getLevelByName("world")->getTile(new Vector3(114, 83, 131))->setText("", TextFormat::RED . "[RED]", TextFormat::WHITE . "0");
		$this->getServer()->getLevelByName("world")->getTile(new Vector3(116, 83, 125))->setText("", TextFormat::BLUE . "[BLUE]", TextFormat::WHITE . "0");
		$this->getServer()->getLevelByName("world")->getTile(new Vector3(116, 83, 131))->setText("", TextFormat::BLUE . "[BLUE]", TextFormat::WHITE . "0");
		$this->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 125))->setText("", TextFormat::GREEN . "Waiting for players...", TextFormat::GREEN .  "(" . TextFormat::GRAY . count($this->inGame) . "/" . $this->maxPlayers . TextFormat::GREEN . ")");
		$this->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 131))->setText("", TextFormat::GREEN . "Waiting for players...", TextFormat::GREEN .  "(" . TextFormat::GRAY . count($this->inGame) . "/" . $this->maxPlayers . TextFormat::GREEN . ")");
		$this->getServer()->getLevelByName("world")->setTime(0);
		$this->getServer()->getLevelByName("world")->stopTime();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getLogger()->info(TextFormat::GREEN."EPICMC CTF has been enabled!");
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Popup($this), 1);
		$this->saveDefaultConfig();
		$this->reloadConfig();
		$this->getConfig()->save();
		$this->perms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
	}

	public function onDrop(PlayerDropItemEvent $event) {
		$event->setCancelled();
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		if(strtolower($cmd->getName()) == "start") {
			if($sender->hasPermission("ctf.command.start")) {
				$this->startGame();
			}
		} 
	}

	public function onHungerChange(PlayerHungerChangeEvent $event) {	
		$player = $event->getPlayer();
		
		if($this->getTeam($player) == 'spectator') {
			$event->setCancelled();
		} else {
			if($this->gameStarted == 0) {
				$event->setCancelled();
			}
		}
	}


	public function onChat(PlayerChatEvent $event) {
		$player = $event->getPlayer();
		$group = $this->perms->getUserDataMgr()->getGroup($player)->getName();
		
		if($this->getTeam($player) == 'red') {
			
			if($group != "PLAYER") {
				$event->setFormat(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "[$group] " . TextFormat::RESET . TextFormat::RED . "[RED] " . $player->getName() . TextFormat::GRAY . " > " . TextFormat::WHITE . $event->getMessage());
			} else {
				$event->setFormat(TextFormat::RED . "[RED] " . $player->getName() . TextFormat::GRAY . " > " . TextFormat::WHITE . $event->getMessage());
			} 
		}
		if($this->getTeam($player) == 'blue') {
			
			if($group != "PLAYER") {
				$event->setFormat(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "[$group] "  . TextFormat::RESET .  TextFormat::BLUE . "[BLUE] " . $player->getName() . TextFormat::GRAY . " > " . TextFormat::WHITE . $event->getMessage());
			} else {
				$event->setFormat(TextFormat::BLUE . "[BLUE] " . $player->getName() . TextFormat::GRAY . " > " . TextFormat::WHITE . $event->getMessage());
			}
		}
		
		if($this->getTeam($player) == 'spectator') {
			
			if($group != "PLAYER") {
				$event->setFormat(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "[$group] "  . TextFormat::RESET .  TextFormat::GRAY . "[SPECTATOR] " . $player->getName() . " > " . $event->getMessage());
			} else {
				$event->setFormat(TextFormat::GRAY . "[SPECTATOR] " . $player->getName() . " > " . $event->getMessage());
			}
		}
	}
	public function onDie(PlayerDeathEvent $event){
		$player = $event->getPlayer();
		$name = $event->getPlayer()->getName();
		$cause = $event->getEntity()->getLastDamageCause();
		
		$event->setDrops([]);
		$event->setDeathMessage("");
		
		if($this->redStealer == $player->getName()) {
			$this->redCaptured = false;
			$this->redStealer = "";
			$player->getLevel()->setBlock(new Vector3(82, 65, 77), Block::get(35, 14, new Position(82, 65, 77, $player->getLevel())));
			$this->getServer()->broadcastMessage(TextFormat::RED."RED flag was returned!");
		}
		if($this->blueStealer == $player->getName()) {
			$this->blueCaptured = false;
			$this->blueStealer = "";
			$player->getLevel()->setBlock(new Vector3(147, 65, 77), Block::get(35, 11, new Position(147, 65, 77, $player->getLevel())));
			$this->getServer()->broadcastMessage(TextFormat::BLUE."BLUE flag was returned!");
		}
			
		if($cause instanceof EntityDamageByEntityEvent) {
			$killer = $cause->getDamager();
			$namek = $killer->getName();
			
			if($this->getTeam($player) == 'red') {
				$team = "red";

				$this->getServer()->broadcastMessage(TextFormat::BLUE. $namek . TextFormat::GRAY . " -[-- " . TextFormat::RED . $name);
			}
			if($this->getTeam($player) == 'blue') {
				$team = "blue";
			
				$this->getServer()->broadcastMessage(TextFormat::RED. $namek . TextFormat::GRAY . " -[-- " . TextFormat::BLUE . $name);
			}
		}
	}

	public function onPreJoin(PlayerPreLoginEvent $event){
		if(count($this->getServer()->getOnlinePlayers()) == $this->getServer()->getMaxPlayers()){
			$event->getPlayer()->kick("Server full. Visit " . TextFormat::GREEN . "epicmc.us/play" .TextFormat::WHITE . " to see all available servers.");
		}
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		
		$player->getInventory()->clearAll();	
		$event->setJoinMessage("");
		
		$this->selectTeam($player);
			
		if($this->getTeam($player) == "spectator") {
			$player->teleport(new Vector3(115, 81 + 1, 127));
		}
			
		/*	if(count($this->getServer()->getOnlinePlayers()) >= 2 and $this->gameStarted == 0){
			$this->startGame();
		}*/
	}

	public function onRespawn(PlayerRespawnEvent $event) {
		$player = $event->getPlayer();

		if($this->gameStarted == 1) {
			if($this->getTeam($player) == 'red'){
				$event->setRespawnPosition(new Position(66, 76, 100, $player->getLevel()));
			}
		
			if($this->getTeam($player) == 'blue'){
				$event->setRespawnPosition(new Position(163, 76, 54, $player->getLevel()));
			}
		} else {
			if($this->getTeam($player) == 'red') {
				$player->teleport(new Vector3(102, 82, 128));
			}

			if($this->getTeam($player) == 'blue') {
				$player->teleport(new Vector3(128, 82, 128));
			}

		}	
		$player->getInventory()->setHelmet(new Item(298, 0, 1));
		$player->getInventory()->setChestplate(new Item(299, 0, 1));
		$player->getInventory()->setLeggings(new Item(300, 0, 1));
		$player->getInventory()->setBoots(new Item(301, 0, 1));
		$player->getInventory()->addItem(new Item(272, 0, 1));
		$player->getInventory()->addItem(new Item(260, 0, 15));
	}

	public function onDamage(EntityDamageEvent $event){
		$playera = $event->getEntity();
		
		if($this->gameStarted == 0){
			$event->setCancelled();
		}
		
		if($event instanceof EntityDamageByEntityEvent){
			
			if($playera instanceof Player) {
				
				if($this->gameStarted == 1) {
					
					if(isset($this->spectators[$playera->getName()])) {
						$event->setCancelled();
					}
				}
				$playerb = $event->getDamager();
					
				if($playerb instanceof Player) {
					$item = $playerb->getInventory()->getItemInHand();
					$item->setDamage(10);
						
					if($this->getTeam($playera) == $this->getTeam($playerb)){
						$event->setCancelled();
					}
				}		
			}
		}
	}

	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();

		$event->setQuitMessage("");
		
		if($this->redStealer == $player->getName()) {
			$this->redStealer = "";
			$this->redCaptured = false;
			$player->getLevel()->setBlock(new Vector3(82, 65, 77), Block::get(35, 14, new Position(82, 65, 77, $player->getLevel())));
		}
		if($this->blueStealer == $player->getName()) {
			$this->blueStealer = "";
			$this->blueCaptured = false;
			$player->getLevel()->setBlock(new Vector3(147, 65, 77), Block::get(35, 11, new Position(147, 65, 77, $player->getLevel())));
		}
		
		if($this->gameStarted == 1) {
			if(count($this->inGame) <= 1) {
				
				if($this->redPoints > $this->bluePoints) {
					$this->stopGame('red');
				} 
				if($this->redPoints < $this->bluePoints) {
					$this->stopGame('blue');
				} 
				if($this->redPoints == $this->bluePoints) {
					$this->stopGame();
				} 
			}
		}
		
		if(isset($this->inGame[$player->getName()])) {
			if(count($this->inGame) < ($this->maxPlayers / 2) - 1) {
				if($this->gameStarted == 0) {
					if($this->countdown != null) {
						$this->getServer()->getScheduler()->cancelTask($this->countdown);
					}
				}
			}
			unset($this->inGame[$player->getName()]);
		}
		
		if(isset($this->spectators[$player->getName()])) {
			unset($this->spectators[$player->getName()]);
		}
		
		if(isset($this->redTeamMembers[$player->getName()])) {
			unset($this->redTeamMembers[$player->getName()]);
		}
		if(isset($this->blueTeamMembers[$player->getName()])) {
			unset($this->blueTeamMembers[$player->getName()]);
		}
		
		if($this->getTeam($player) == 'red'){
			$this->red--;
		}
		
		if($this->getTeam($player) == 'blue'){
			$this->blue--;
		}
	}

    public function onPlayerBreakBlock(BlockBreakEvent $event){
    	$player = $event->getPlayer();
		
		//$player->sendMessage("X: " .  $event->getBlock()->x . " Y: " . $event->getBlock()->y . " Z: " . $event->getBlock()->z);
    	if($this->gameStarted == 1){
			
			if ($event->getBlock()->getName() !== "Blue Wool" or $event->getBlock()->getName() !== "Red Wool"){
				
				if($event->getPlayer()->isOp() == true){
					return true;
				} else {
					$event->setCancelled();
				}
			}
			if($this->getTeam($player) == 'red'){
				if ($event->getBlock()->getName() == "Blue Wool"){
					$team = 'red';
					$player = $event->getPlayer();
					
					$this->capturedFlag($player, $team);
					$event->setCancelled();
				}
			}
			
			if($this->getTeam($player) == 'blue'){
				
				if ($event->getBlock()->getName() == "Red Wool"){
					$team = 'blue';
					$player = $event->getPlayer();
					$this->capturedFlag($player, $team);
					$event->setCancelled();
				}
			}
		}
		if(!$player->isOp()){
			$event->setCancelled();
		}
	}

    public function onPlayerBlockPlace(BlockPlaceEvent $event){
    	$player = $event->getPlayer();
    	
		if($this->gameStarted == 1){
			if ($event->getBlock()->getName() !== "Blue Wool" or $event->getBlock()->getName() !== "Red Wool"){
				
				if($event->getPlayer()->isOp() == true){
					return true;
				} else {
					$event->setCancelled();
				}
			}
			
			if ($event->getBlock()->getName() == "Blue Wool"){
				if($event->getBlock()->getX() == 66 and $event->getBlock()->getY() == 80 and $event->getBlock()->getZ() == 114){
					$team = 'red';
					$player = $event->getPlayer();
					$this->placedFlag($player, $team);
					$event->setCancelled();
				} else {
					$event->setCancelled();
				}
			}
			
        	if ($event->getBlock()->getName() == "Red Wool"){
				
				if($event->getBlock()->getX() == 163 and $event->getBlock()->getY() == 80 and $event->getBlock()->getZ() == 41){
					$team = 'blue';
					$player = $event->getPlayer();
					$this->placedFlag($player, $team);
					$event->setCancelled();
				} else {
					$event->setCancelled();
				}
        	}
        	
			if($this->redPoints == $this->maxPoints or $this->bluePoints == $this->maxPoints){
				$this->getServer()->getScheduler()->cancelTask($this->timer["timer"]);
        		$this->stopGame($this->getTeam($player));
        	}
        }
		if(!$player->isOp()){
			$event->setCancelled();
		}
	}
	
	public function stopTask($id) {
		unset($this->timer[$id]);
		$this->getServer()->getScheduler()->cancelTask($id);
	}
	
	public function saveTask($id) {
		$this->timer[$id] = $id;
	}
	
	public function getTimer() {
		
		return $this->timer;
	}

	public function setCap(Player $player, $customColor) {
		$chestPlate = Item::get(298);

		$tempTag = new CompoundTag("", []);

		$tempTag->customColor = new IntTag("customColor", $customColor);

		$chestPlate->setCompoundTag($tempTag);

		$player->getInventory()->setHelmet($chestPlate);
	}

	public function setChestplate(Player $player, $customColor) {
		$chestPlate = Item::get(299);

		$tempTag = new CompoundTag("", []);

		$tempTag->customColor = new IntTag("customColor", $customColor);

		$chestPlate->setCompoundTag($tempTag);

		$player->getInventory()->setChestplate($chestPlate);
	}

	public function setPants(Player $player, $customColor) {
		$chestPlate = Item::get(300);

		$tempTag = new CompoundTag("", []);

		$tempTag->customColor = new IntTag("customColor", $customColor);

		$chestPlate->setCompoundTag($tempTag);

		$player->getInventory()->setLeggings($chestPlate);
	}

	public function setBoots(Player $player, $customColor) {
		$chestPlate = Item::get(301);

		$tempTag = new CompoundTag("", []);

		$tempTag->customColor = new IntTag("customColor", $customColor);

		$chestPlate->setCompoundTag($tempTag);

		$player->getInventory()->setBoots($chestPlate);
	}

	public function setTeam($player, $team) {
		$name = $player->getName();
		$group = $this->perms->getUserDataMgr()->getGroup($player)->getName();
		
		switch(strtolower($team)) {
			case "red":
			if($group != "PLAYER") {
				$player->setDisplayName(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "[$group] " . TextFormat::RESET . TextFormat::RED."[RED] " . $name);
				$player->setNameTag(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "[$group] " . TextFormat::RESET . TextFormat::RED."[RED] " . $name);
			} else {
				$player->setDisplayName(TextFormat::RED."[RED] " . $name . "");
				$player->setNameTag(TextFormat::RED."[RED] " . $name . "");
			}
			$this->redTeamMembers[$player->getName()] = $player;
			break;
			
			case "blue":
			if($group != "PLAYER") {
				$player->setDisplayName(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "[$group] " . TextFormat::RESET . TextFormat::BLUE."[BLUE] " . $name);
				$player->setNameTag(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "[$group] " . TextFormat::RESET . TextFormat::BLUE."[BLUE] " . $name);
			} else {
				$player->setDisplayName(TextFormat::BLUE."[BLUE] " . $name . "");
				$player->setNameTag(TextFormat::BLUE."[BLUE] " . $name . "");
			}
			$this->blueTeamMembers[$player->getName()] = $player;
			break;
		}
	}

	public function startGame() {
		$count = count($this->inGame);
		
		$this->gameStatus = true;
		$this->getServer()->getLevelByName("world")->setBlock(new Vector3(82, 65, 77), Block::get(35, 14, new Position(82, 65, 77, $this->getServer()->getLevelByName("world"))));
		$this->getServer()->getLevelByName("world")->setBlock(new Vector3(147, 65, 77), Block::get(35, 11, new Position(147, 65, 77, $this->getServer()->getLevelByName("world"))));

		$this->getServer()->getScheduler()->cancelTask($this->icountdown);
			foreach($this->inGame as $p) {
				if($this->getTeam($p) == 'red') {
					$p->teleport(new Vector3( 66, 75, 114));
				
				} 
				
				if($this->getTeam($p) == 'blue') {
					$p->teleport(new Vector3(163, 75, 41));
				}
			}
			$this->gameStarted++;
			$this->getServer()->broadcastMessage(TextFormat::GREEN."THE GAME HAS STARTED! FIRST TEAM TO " . $this->maxPoints . " POINTS WINS");
			$timer = new Countdown($this, $this->matchLength);
			$this->countdown = $timer->getTaskId();
			$this->secs = 60;
			$this->getServer()->getScheduler()->scheduleRepeatingTask($timer, 20);
	}

	public function capturedFlag($player, $team){
		$name = $player->getName();
		
		if($team == 'red'){
			$this->blueCaptured = true;
			$this->blueStealer = $player->getName();
			
			$player->getInventory()->addItem(Item::get(35, 11, 1));	
			$player->getLevel()->setBlock(new Vector3(147, 65, 77), Block::get(0, 0, new Position(147, 65, 77, $player->getLevel())));
			$this->getServer()->broadcastMessage(TextFormat::RED. $name . TextFormat::GRAY . " stole " . TextFormat::BLUE . "BLUE " . TextFormat::GRAY . "flag!");
		}
		
		if($team == 'blue'){
			$this->redCaptured = true;
			$this->redStealer = $player->getName();
			
			$player->getInventory()->addItem(Item::get(35, 14, 1));
			$player->getLevel()->setBlock(new Vector3(82, 65, 77), Block::get(0, 0, new Position(82, 65, 77, $player->getLevel())));
			$this->getServer()->broadcastMessage(TextFormat::BLUE. $name . TextFormat::GRAY . " stole " . TextFormat::RED . "RED " . TextFormat::GRAY . "flag!");
		}
	}

	public function placedFlag($player, $team){
		$name = $player->getName();
		
		$this->addPoint($team);
		
		if($team == 'red'){
			$this->blueCaptured = false;
			$this->blueStealer = "";
			$player->getLevel()->setBlock(new Vector3(147, 65, 77), Block::get(35, 11, new Position(147, 65, 77, $player->getLevel())));
			$player->getLevel()->getTile(new Vector3(114, 83, 125))->setText("", TextFormat::RED . "[RED]", TextFormat::WHITE . $this->redPoints);
			$player->getLevel()->getTile(new Vector3(114, 83, 131))->setText("", TextFormat::RED . "[RED]", TextFormat::WHITE . $this->redPoints);
			$this->getServer()->broadcastMessage(TextFormat::RED. $name . TextFormat::GRAY . " captured " . TextFormat::BLUE . "BLUE " . TextFormat::GRAY . "flag!");
		}
		
		if($team == 'blue'){
			$this->redCaptured = false;
			$this->redStealer = "";
			$player->getLevel()->setBlock(new Vector3(82, 65, 77), Block::get(35, 14, new Position(82, 65, 77, $player->getLevel())));
			$player->getLevel()->getTile(new Vector3(116, 83, 125))->setText("", TextFormat::BLUE . "[BLUE]", TextFormat::WHITE . $this->bluePoints);
			$player->getLevel()->getTile(new Vector3(116, 83, 131))->setText("", TextFormat::BLUE . "[BLUE]", TextFormat::WHITE . $this->bluePoints);
			$this->getServer()->broadcastMessage(TextFormat::BLUE. $name . TextFormat::GRAY . " captured " . TextFormat::RED . "RED " . TextFormat::GRAY . "flag!");
		}	
		$player->getInventory()->clearAll();
		$player->getInventory()->setHelmet(new Item(298, 0, 1));
		$player->getInventory()->setChestplate(new Item(299, 0, 1));
		$player->getInventory()->setLeggings(new Item(300, 0, 1));
		$player->getInventory()->setBoots(new Item(301, 0, 1));
		$player->getInventory()->addItem(new Item(272, 0, 1));
		$player->getInventory()->addItem(new Item(260, 0, 15));

	}

	public function addPoint($team){
		if($team == 'blue'){
			$this->bluePoints++;
		}
		if($team == 'red'){
			$this->redPoints++;
		}
	}

	public function stopGame($team = null){
		if(strtolower($team) == 'red'){
			$this->getServer()->broadcastMessage(TextFormat::RED."RED TEAM WON!");
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new Restart($this, $team), 20);
		}
		if(strtolower($team) == 'blue'){
			$this->getServer()->broadcastMessage(TextFormat::BLUE."BLUE TEAM WON!");
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new Restart($this, $team), 20);
		}
		if(strtolower($team) == null){
			$this->getServer()->broadcastMessage(TextFormat::GREEN."THE GAME ENDED IN A DRAW!");
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new Restart($this, $team), 20);
		}
	}

	public function getTeam($player){
		if(isset($this->redTeamMembers[$player->getName()])) {
			return "red";
		}
		if(isset($this->blueTeamMembers[$player->getName()])) {
			return "blue";
		}
		if(isset($this->spectators[$player->getName()])) {
			return "spectator";
		}
	}

	public function selectTeam($player){
		$name = $player->getName();
		$red = count($this->redTeamMembers);
		$blue = count($this->blueTeamMembers);
		$group = $this->perms->getUserDataMgr()->getGroup($player)->getName();

		if(count($this->inGame) >= $this->maxPlayers) {
			$this->spectators[$player->getName()] = $player;
			if($group != "PLAYER") {
				$player->setDisplayName(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "[$group]" . TextFormat::RESET . TextFormat::GRAY."[SPECTATOR] " . $name);
				$player->setNameTag(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "[$group]" . TextFormat::RESET . TextFormat::GRAY."[SPECTATOR] " . $name);
			} else {
			$player->setDisplayName(TextFormat::GRAY."[SPECTATOR] " . $name);
			$player->setNameTag(TextFormat::GRAY."[SPECTATOR] " . $name);
			}
			$player->sendMessage(TextFormat::GRAY . "Match already started. You are a spectator!");
		} else {
			if(count($this->inGame) == ($this->maxPlayers / 2) - 1) {
				$timer = new iCountdown($this, 1);
				$this->icountdown = $timer->getTaskId();
				$this->getServer()->getScheduler()->scheduleRepeatingTask($timer, 20);
			}

			if($this->gameStarted == 0) {
					
				if($red < $blue){
					$name = $player->getName();
					$player->sendMessage(TextFormat::RED."You're on RED Team!");
					$player->sendPopup(TextFormat::RED."You have $red players on your team!");

					$this->setTeam($player, "red");
					$this->red++;
					$this->inGame[$player->getName()] = $player;
					$player->getInventory()->clearAll();
					$player->getInventory()->setHelmet(new Item(298, 0, 1));
					$player->getInventory()->setChestplate(new Item(299, 0, 1));
					$player->getInventory()->setLeggings(new Item(300, 0, 1));
					$player->getInventory()->setBoots(new Item(301, 0, 1));
					$player->getInventory()->addItem(new Item(272, 0, 1));
					$player->getInventory()->addItem(new Item(260, 0, 15));

					if(count($this->inGame) == $this->maxPlayers) {
						$this->startGame();
					} else {
						
						if($this->getTeam($player) == 'red') {
							$player->teleport(new Vector3(102, 82, 128));
						}
			
						if($this->getTeam($player) == 'blue') {
							$player->teleport(new Vector3(128, 82, 128));
						}
					}
				}
				
				if($blue < $red) {
					$player->sendMessage(TextFormat::BLUE."You're on BLUE Team!");
					$player->sendPopup(TextFormat::BLUE."You have $blue players on your team!");
					$name = $player->getName();

					$this->setTeam($player, "blue");
					$this->blue++;
					$this->inGame[$player->getName()] = $player;
					$player->getInventory()->clearAll();
					$player->getInventory()->setHelmet(new Item(298, 0, 1));
					$player->getInventory()->setChestplate(new Item(299, 0, 1));
					$player->getInventory()->setLeggings(new Item(300, 0, 1));
					$player->getInventory()->setBoots(new Item(301, 0, 1));
					$player->getInventory()->addItem(new Item(272, 0, 1));
					$player->getInventory()->addItem(new Item(260, 0, 15));

					if(count($this->inGame) == $this->maxPlayers) {
						$this->startGame();
					} else {
						
						if($this->getTeam($player) == 'red') {
							$player->teleport(new Vector3(102, 82, 128));
						}
			
						if($this->getTeam($player) == 'blue') {
							$player->teleport(new Vector3(128, 82, 128));
						}
					}
				}
				
				if($blue == $red) {
					$player->sendMessage(TextFormat::BLUE."You're on BLUE Team!");
					$player->sendPopup(TextFormat::BLUE."You have $blue players on your team!");
					$name = $player->getName();

					$this->setTeam($player, "blue");
					$this->blue++;
					$this->inGame[$player->getName()] = $player;
					$player->getInventory()->clearAll();
					$player->getInventory()->setHelmet(new Item(298, 0, 1));
					$player->getInventory()->setChestplate(new Item(299, 0, 1));
					$player->getInventory()->setLeggings(new Item(300, 0, 1));
					$player->getInventory()->setBoots(new Item(301, 0, 1));
					$player->getInventory()->addItem(new Item(272, 0, 1));
					$player->getInventory()->addItem(new Item(260, 0, 15));

					if(count($this->inGame) == $this->maxPlayers) {
						foreach($this->inGame as $p) {
							$this->startGame();
						}
					} else {
						
						if($this->getTeam($player) == 'red') {
							$player->teleport(new Vector3(102, 82, 128));
						}
		
						if($this->getTeam($player) == 'blue') {
							$player->teleport(new Vector3(128, 82, 128));
						}
					}
				}	
			} else {
				$this->spectators[$player->getName()] = $player;
				$player->sendMessage(TextFormat::GRAY . "Match already started. You are a spectator!");
			}
		}
		
	}

	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		
		if($player->getY() < 50) {
			$player->kill();
		}
		if($player->getName() == $this->redStealer) {
			$player->getLevel()->addParticle(new FlameParticle(new Vector3($player->x, $player->y + 2, $player->z)));
		}
		if($player->getName() == $this->blueStealer) {
			$player->getLevel()->addParticle(new BubbleParticle(new Vector3($player->x, $player->y + 2, $player->z)));
		}
		
		if($this->gameStarted !== 1){
			if(in_array($player->getName(), $this->inGame)) {
				$event->getPlayer()->sendPopup(TextFormat::GREEN."Waiting for players to join...");
			}
		}
	}
}


