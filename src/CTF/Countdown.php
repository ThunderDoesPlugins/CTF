<?php

namespace CTF;

use pocketmine\scheduler\PluginTask;
use CTF\CTF;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

class Countdown extends PluginTask {
	
	private $plugin;
	
	public function __construct(CTF $plugin, $mins) {
		parent::__construct($plugin);
		$this->plugin = $plugin;	
		$this->plugin->mins = $mins;
	}
	
	private function getPlugin() {
		return $this->plugin;
	}
	
	public function onRun($currentTick) {
		
		if($this->plugin->secs == 0) {
			if($this->plugin->mins > 0) {
				$this->plugin->mins = $this->plugin->mins - 1;
				$this->plugin->secs = 60;
			}
			
			//$this->plugin->gameStatus = 0;
			
			if($this->plugin->mins == 0 && $this->plugin->secs == 0) {
				$this->plugin->secs = $this->plugin->matchLength;
				$this->plugin->mins = 60;
				$this->plugin->gameStatus = 0;
				if($this->plugin->redPoints > $this->plugin->bluePoints) {
					$this->plugin->stopGame('red');
				} 
				if($this->plugin->redPoints < $this->plugin->bluePoints) {
					$this->plugin->stopGame('blue');
				} 
				if($this->plugin->redPoints == $this->plugin->bluePoints) {
					$this->plugin->stopGame();
				} 
			}
		}
		
		if($this->plugin->gameStatus == 1) {
			if($this->plugin->secs >= 10) {
				$this->plugin->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 125))->setText("", TextFormat::GREEN . "Match ending in:", TextFormat::GRAY . $this->plugin->mins . ":" . $this->plugin->secs);
				$this->plugin->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 131))->setText("", TextFormat::GREEN . "Match ending in:", TextFormat::GRAY . $this->plugin->mins . ":" . $this->plugin->secs);
			} else {
				$this->plugin->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 125))->setText("", TextFormat::GREEN . "Match ending in:", TextFormat::GRAY . $this->plugin->mins . ":0" . $this->plugin->secs);
				$this->plugin->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 131))->setText("", TextFormat::GREEN . "Match ending in:", TextFormat::GRAY . $this->plugin->mins . ":0" . $this->plugin->secs);
			}
			$this->plugin->secs--;
		}
	}
}
	
	

