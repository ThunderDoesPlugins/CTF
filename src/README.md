**API METHODS**
  
  - $this->startGame() : Start Game
  - $this->getTeam($player) : Get player's team.
  - $this->selectTeam($player) : Select's a player's team.
  - $this->stopGame() : Stops server if one team has 5 points
  - $this->capturedFlag($player, $team) : When player gets other team's flag, broadcasts to server and adds point to team
  - $this->addPoint($team) : adds point to team
  - $this->placedFlag($team) : When team places flag at the correct spot at their base, adds point DIFFERENT FROM capturedFlag