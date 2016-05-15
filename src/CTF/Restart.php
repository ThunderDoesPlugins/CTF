<?php

namespace CTF;

use pocketmine\scheduler\PluginTask;
use CTF\CTF;
use pocketmine\utils\TextFormat;

class Restart extends PluginTask {
	
	private $plugin;
	
	public function __construct(CTF $plugin, $team) {
		parent::__construct($plugin);
		$this->plugin = $plugin;	
		$this->team = $team;
		$this->time = 20;
	}
	
	private function getPlugin() {
		return $this->plugin;
	}
	
	public function onRun($currentTick) {
		
		if($this->time == 20) {
			$this->getPlugin()->getServer()->broadcastMessage(TextFormat::GREEN . "Server restarting in 20 seconds...");
		}
		
		if($this->time <= 10) {
			$this->getPlugin()->getServer()->broadcastMessage(TextFormat::GREEN . "Server restarting in " . $this->time . " seconds...");
		}
		
		if($this->time == 1) {
			foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $p) {
				if($this->team == 'red') {
					$msg = TextFormat::RED . "RED TEAM WON!\n Server restarting...";
				}			
				if($this->team == 'blue') {
					$msg = TextFormat::BLUE . "BLUE TEAM WON!\n Server restarting...";
				}
				if($this->team == null) {
					$msg = TextFormat::GREEN . "GAME ENDED IN A DRAW!\n Server restarting...";
				}
				$p->kick($msg, false);
			}
		}
		
		if($this->time == 0) {
			$this->plugin->getServer()->shutdown();
		}
		$this->time--;
	}
	
	
}
