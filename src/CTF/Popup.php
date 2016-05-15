<?php

namespace CTF;

use pocketmine\scheduler\PluginTask;
use CTF\CTF;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

class Popup extends PluginTask {
	
	private $plugin;
	
	public function __construct(CTF $plugin) {
		parent::__construct($plugin);
		$this->plugin = $plugin;
		$this->popup = "";
	}
	
	private function getPlugin() {
		return $this->plugin;
	}
	
	public function onRun($currentTick) {
		
		if($this->getPlugin()->gameStarted == 1) {
			if($this->plugin->secs >= 10) {
				$this->countdown = $this->plugin->mins . ":" . $this->plugin->secs;
			} else {
				$this->countdown = $this->plugin->mins . ":0" . $this->plugin->secs;
			}
			
			if($this->plugin->gameStatus == 0) {
				
				if($this->plugin->redPoints > $this->plugin->bluePoints) {
					$this->popup = TextFormat::RED . "RED TEAM WON!";
				}
				
				if($this->plugin->redPoints < $this->plugin->bluePoints) {
					$this->popup = TextFormat::BLUE . "BLUE TEAM WON!";
				}
				
				if($this->plugin->redPoints == $this->plugin->bluePoints) {
					$this->popup = TextFormat::GREEN . "GAME ENDED IN A DRAW!";
				}
			}
			$this->popup = TextFormat::BLUE . $this->getPlugin()->bluePoints . TextFormat::GRAY . " "  . $this->countdown  . " " . TextFormat::RED . $this->getPlugin()->redPoints;
		} 
		
		if($this->getPlugin()->gameStarted == 0) {
			if(count($this->plugin->inGame) >= ($this->plugin->maxPlayers / 2)) {
				if($this->plugin->isecs >= 10) {
					$this->popup = $this->popup = TextFormat::GREEN . "Waiting for players... (" . TextFormat::GRAY . count($this->plugin->inGame) . "/" . $this->plugin->maxPlayers . TextFormat::GREEN . ") " . TextFormat::GRAY . $this->plugin->mins . ":" . $this->plugin->isecs;
				} else {
					$this->popup = $this->popup = TextFormat::GREEN . "Waiting for players... (" . TextFormat::GRAY . count($this->plugin->inGame) . "/" . $this->plugin->maxPlayers . TextFormat::GREEN . ") " . TextFormat::GRAY . $this->plugin->mins . ":0" . $this->plugin->isecs;
				}
			} 
			
			if(count($this->plugin->inGame) < ($this->plugin->maxPlayers / 2)) {
				$this->plugin->getServer()->getScheduler()->cancelTask($this->plugin->icountdown);
				$this->plugin->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 125))->setText("", TextFormat::GREEN . "Waiting for players...", TextFormat::GREEN .  "(" . TextFormat::GRAY . count($this->plugin->inGame) . "/" . $this->plugin->maxPlayers . TextFormat::GREEN . ")");
				$this->plugin->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 131))->setText("", TextFormat::GREEN . "Waiting for players...", TextFormat::GREEN .  "(" . TextFormat::GRAY . count($this->plugin->inGame) . "/" . $this->plugin->maxPlayers . TextFormat::GREEN . ")");
				$this->popup = TextFormat::GREEN . "Waiting for players... (" . TextFormat::GRAY . count($this->plugin->inGame) . "/" . $this->plugin->maxPlayers . TextFormat::GREEN . ")";
			}
			//$this->popup = TextFormat::GREEN . "Waiting for players... (" . TextFormat::GRAY . count($this->plugin->inGame) . "/" . $this->plugin->maxPlayers . TextFormat::GREEN . ")";
		}
			
		foreach($this->getPlugin()->inGame as $p) {
			$p->sendPopup($this->popup);
		} 
	}
	
	
}
