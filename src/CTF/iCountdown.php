<?php

namespace CTF;

use pocketmine\scheduler\PluginTask;
use CTF\CTF;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

class iCountdown extends PluginTask {
	
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
		
		if($this->plugin->gameStarted == 0) {
			if($this->plugin->isecs == 0) {
				if($this->plugin->mins > 0) {
					$this->plugin->mins = $this->plugin->mins - 1;
					$this->plugin->isecs = 60;
				}
			}
			
			if($this->plugin->isecs == 0 && $this->plugin->mins == 0) {
				if($this->plugin->gameStarted == 0) {
					$this->plugin->startGame();
				}
			}
			
			if($this->plugin->isecs >= 10) {
				$this->plugin->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 125))->setText("", TextFormat::GREEN . "Match starting in:", TextFormat::GRAY . $this->plugin->mins . ":" . $this->plugin->isecs);
				$this->plugin->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 131))->setText("", TextFormat::GREEN . "Match starting in:", TextFormat::GRAY . $this->plugin->mins . ":" . $this->plugin->isecs);
			} else {
				$this->plugin->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 125))->setText("", TextFormat::GREEN . "Match starting in:", TextFormat::GRAY . $this->plugin->mins . ":0" . $this->plugin->isecs);
				$this->plugin->getServer()->getLevelByName("world")->getTile(new Vector3(115, 83, 131))->setText("", TextFormat::GREEN . "Match starting in:", TextFormat::GRAY . $this->plugin->mins . ":0" . $this->plugin->isecs);
			}
			$this->plugin->isecs--;
		}
	}
}
