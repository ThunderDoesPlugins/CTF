<?php

namespace CTF;

use pocketmine\scheduler\PluginTask;
use CTF\CTF;
use pocketmine\utils\TextFormat;

class Timer extends PluginTask {
	
	private $plugin;
	
	public function __construct(CTF $plugin, $mins, $mode) {
		parent::__construct($plugin);
		$this->plugin = $plugin;	
		$this->plugin->mins = $mins;
		$this->mode = $mode;
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
		}
		
		if($this->plugin->secs == 0 && $this->plugin->mins == 0) {
			if($this->mode == "icountdown") {
				$this->plugin->startGame();
			}
			
			if($this->mode == "countdown") {
				
					if(count($this->plugin->inGame) <= 1) {
						
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
		}
		$this->plugin->secs--;
	}
	
	
}
