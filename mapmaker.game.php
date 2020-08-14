<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * mapmaker implementation : © <George Zhang> <gkzhang01@gmail.com>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * mapmaker.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class mapmaker extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            "turn_number" => 10,
            "player_turns_taken" => 11,
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );        
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "mapmaker";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        self::setGameStateValue("turn_number", 1);
        self::setGameStateValue("player_turns_taken", 0);
        self::initCounties();
        self::initEdges();
       

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    private function initCounties() {
        $playerColors = self::getObjectListFromDB(
            "SELECT player_color FROM player", true
        );
        $leanValues = 
                array(1, 10, 2, 2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 8, 8, 9, 9);
        $counties = self::getCountyCoordinates(count($playerColors));
        shuffle($counties);

        $sql = "INSERT INTO counties (coord_x, coord_y, county_player, county_lean) VALUES ";
        $sqlValues = array();

        // Add neutral county.
        $location = array_shift($counties);
        $sqlValues[] = "('$location[0]','$location[1]','000000','0')";
        
        // Add player counties.
        foreach($playerColors as $color) {
            foreach($leanValues as $value) {
                $location = array_shift($counties);
                $sqlValues[] = 
                        "('$location[0]','$location[1]','$color','$value')";
            }
        }
        $sql .= implode($sqlValues, ',');
        self::DbQuery($sql);
    }

    private function initEdges() {
        $counties = self::getCountiesAsDoubleKeyCollection();
        $neighbors = array(array(0, 1), array(1, 0), array(1, -1));

        $sql = "INSERT INTO edges (county_1_x, county_1_y, county_2_x, county_2_y) VALUES ";
        $sqlValues = array();
        foreach ($counties as $x => $data) {
            foreach ($data as $y => $county) {
                foreach ($neighbors as $neighbor) {
                    $newX = $x + $neighbor[0];
                    $newY = $y + $neighbor[1];
                    if (self::isCountyPresent($counties, $newX, $newY)) {
                        $sqlValues[] = "('$x','$y','$newX','$newY')";
                    }
                }
            }
        }
        $sql .= implode($sqlValues, ',');
        self::DbQuery($sql);
    }
    
    private function getCountyCoordinates($numPlayers) {
        $coords = array();
        for ($x = -3; $x <= 3; $x++) {
            for ($y = -3 - min($x, 0); $y <= 3 - max($x, 0); $y++) {
                $coords[] = array($x, $y);
            }
        }
        if ($numPlayers >= 3) {
            $three_player_extras = array(
                array(-4, 1), array(-4, 2), array(-4, 3), array(-3, -1), array(-3, 4), array(-2, -2), array(-2, 4), array(-1, -3), array(-1, 4), array(1, -4), array(1, 3), array(2, -4), array(2, 2), array(3, -4), array(3, 1), array(4, -3), array(4, -2), array(4, -1)
            );
            $coords = array_merge($coords, $three_player_extras);
        }
        if ($numPlayers >= 4) {
            $four_player_extras = array(
                array(-5, 2), array(-5, 3), array(-4, 0), array(-4, 4), array(-3, -2), array(-3, 5), array(-2, -3), array(-2, 5), array(0, -4), array(0, 4), array(2, -5), array(2, 3), array(3, -5), array(3, 2), array(4, -4), array(4, 0), array(5, -3), array(5, -2)
            );
            $coords = array_merge($coords, $four_player_extras);
        }
        return $coords;
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        $result['counties'] = self::getObjectListFromDB(
            "SELECT coord_x x, coord_y y, county_player color, county_lean val, district_player winner, district_placement place FROM counties"
        );
        $result['edges'] = self::getEdgesAsObjectList();
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        Returns counties as a double associative array, where x is the first level and y is the second level.
    */
    private function getCountiesAsDoubleKeyCollection() {
        return self::getDoubleKeyCollectionFromDB(
            "SELECT coord_x, coord_y, county_player, county_lean,  district_player FROM counties"
        );
    }

    // Returns whether the county at $x, $y is present in $counties.
    private function isCountyPresent($counties, $x, $y) {
        return isset($counties[$x]) and isset($counties[$x][$y]);
    }

    // Returns edges as an object list.
    private function getEdgesAsObjectList() {
        return self::getObjectListFromDB(
            "SELECT county_1_x x1, county_1_y y1, county_2_x x2, county_2_y y2, is_placed isPlaced FROM edges"
        );
    }


    // Finds a given edge in the list of edges.
    private function findEdge($edges, $x1, $y1, $x2, $y2) {
        foreach ($edges as $edge) {
            // var_dump($edge);
            // die("ok");
            if ($edge["x1"] == $x1 && $edge["y1"] == $y1 
                    && $edge["x2"] == $x2 && $edge["y2"] == $y2) {
                return $edge;
            }
        }
        throw new BgaVisibleSystemException("Edge could not be found!");
    }

    private function getEdgesToPlay() {
        return min(self::getGameStateValue("turn_number"), 4);
    }

    private function addNeighbor($neighbors, $x1, $y1, $x2, $y2) {
        if (!isset($neighbors[$x1])) {
            $neighbors[$x1] = array();
        }
        if (!isset($neighbors[$x1][$y1])) {
            $neighbors[$x1][$y1] = array();
        }
        array_push($neighbors[$x1][$y1], array($x2, $y2));
        return $neighbors;
    }

    // Processes list of edges and returns an associative array of neighbors.
    // Input: Array<Edge>
    // Output: Array<x, Array<y, Array<neighbors>>> 
    //         where neighbors is Array<x, y>
    private function getDistrictNeighbors($edges) {
        $neighbors = array();
        foreach ($edges as $edge) {
            if ($edge["isPlaced"]) {
                continue;
            }
            $neighbors = self::addNeighbor(
                $neighbors, $edge["x1"], $edge["y1"], $edge["x2"], $edge["y2"]);
            $neighbors = self::addNeighbor(
                $neighbors, $edge["x2"], $edge["y2"], $edge["x1"], $edge["y1"]);
        }
        return $neighbors;
    }

    // Gets all reachables neighbors from node ($x, $y).
    // Expects $neighbors as returned by self::getDistrictNeighbors().
    private function getAllReachableNeighbors($neighbors, $node) {
        $reachable = array();
        $queue = array($node);
        while (count($queue) != 0) {
            $node = array_pop($queue);
            array_push($reachable, $node);
            if (!isset($neighbors[$node[0]]) 
                    || !isset($neighbors[$node[0]][$node[1]])) {
                continue;
            }
            foreach ($neighbors[$node[0]][$node[1]] as $next) {
                if (!in_array($next, $reachable) && !in_array($next, $queue)) {
                    array_push($queue, $next);
                }
            }
        }
        return $reachable;
    }

    private function createsInvalidDistrict($edge) {
        // Pretend edge is placed.
        $edges = self::getEdgesAsObjectList();
        $index = array_search($edge, $edges);
        if ($index === false) {
            throw new BgaVisibleSystemException(
                "Played edge wasn't found in list of edges!");
        }
        $edges[$index]["isPlaced"] = "1";

        // Get neighbors for each county.
        $neighbors = self::getDistrictNeighbors($edges);

        // Get reachable neighbors from endpoints of edge.
        $reachableNeighbors1 = self::getAllReachableNeighbors(
            $neighbors, array($edge["x1"], $edge["y1"]));
        $reachableNeighbors2 = self::getAllReachableNeighbors(
            $neighbors, array($edge["x2"], $edge["y2"]));
        return count($reachableNeighbors1) < 4 
            || count($reachableNeighbors2) < 4;
    }

    private function isValidDistrict($counties) {
        if (count($counties) < 4) {
            return false;
        }
        if (count($counties) >= 4 && count($counties) <= 7) {
            return true;
        }

        // TODO: Handle edge cases of >8 counties but still make a district.
    }

    private function createNewDistrict($newDistrict, $counties) {
        // Tally the lean values for each county within the district.
        $scores = array();
        $countySqlValues = array();
        foreach ($newDistrict as $districtCounty) {
            $x = $districtCounty[0];
            $y = $districtCounty[1];
            $county = $counties[$x][$y];
            if ($county["district_player"] !== NULL) {
                throw new BgaVisibleSystemException(
                    "District player for new district creation was not null!");
            }
            $id = $county["county_player"];
            if (!isset($scores[$id])) {
                $scores[$id] = 0;
            }
            $scores[$id] += $county["county_lean"];

            // Start constructing values we'll need for sql query.
            $countySqlValues[] = "('$x','$y')";
        }

        // Determine which player wins this district.
        $winners = array_keys($scores, max($scores));
        if (count($winners) > 1) {
            // TODO: Handle case of more than one winner!
        }
        $winnerColor = $winners[0];
        
        // Update district_player for these counties.
        $sql = "UPDATE counties SET district_player='$winnerColor' WHERE (coord_x, coord_y) IN (";
        $sql .= implode($countySqlValues, ',') . ")";
        self::DbQuery($sql);

        // Set the index 2 county as the district placement county
        // Note: there should always be at least 4 counties in a district, so this is safe.
        $placeX = $newDistrict[2][0];
        $placeY = $newDistrict[2][1];
        self::DbQuery("UPDATE counties SET district_placement='1' WHERE (coord_x, coord_y)=('$placeX','$placeY')");

        // Notify players of district formation.
        $winnerInfo = 
            self::getObjectFromDb("SELECT player_name, player_id FROM `player` WHERE player_color='$winnerColor'");
        self::notifyAllPlayers(
            "newDistrict", 
            clienttranslate('${player_name} formed a new district, which was won by ${winner}.'),
            array(
                "player_name" => self::getActivePlayerName(),
                "winner" => $winnerInfo["player_name"],
                "winner_id" => $winnerInfo["player_id"],
                "winner_color" => $winnerColor,
                "position" => array($placeX, $placeY),
                "counties" => $newDistrict,
        ));

        // Update player score for winner
        self::DbQuery("UPDATE player SET player_score=player_score+1 WHERE player_color='$winnerColor'");
        $newScores = self::getCollectionFromDb(
            "SELECT player_id, player_score FROM player", true);
        self::notifyAllPlayers("newScores", "", array(
            "scores" => $newScores,
        ));
    }



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    function playEdge($x1, $y1, $x2, $y2) {
        self::checkAction("playEdge");
        $edges = self::getEdgesAsObjectList();
        $counties = self::getCountiesAsDoubleKeyCollection();
        $edge = self::findEdge($edges, $x1, $y1, $x2, $y2);

        // Check if edge has already been played.
        if ($edge["isPlaced"]) {
            throw new BgaUserException(
                self::_("This edge has already been placed!"));
        }

        if (self::createsInvalidDistrict($edge)) {
            throw new BgaUserException(
                self::_("An edge cannot be placed here: it would create a district of size < 4."));
        }

        self::DbQuery(
            "UPDATE edges SET is_placed='1' WHERE (county_1_x, county_1_y, county_2_x, county_2_y) = ($x1,$y1,$x2,$y2)"
        );
        self::incGameStateValue("player_turns_taken", 1);
        self::notifyAllPlayers(
            "playedEdge", 
            clienttranslate('${player_name} plays an edge (${edgesPlayed}/${edgesToPlay}).'), 
            array(
                "player_id" => self::getActivePlayerId(),
                "player_name" => self::getActivePlayerName(),
                "x1" => $x1,
                "y1" => $y1,
                "x2" => $x2,
                "y2" => $y2,
                "edgesPlayed" => self::getGameStateValue("player_turns_taken"),
                "edgesToPlay" => self::getEdgesToPlay(),
            ));

        // Check for valid district formation
        $neighbors = self::getDistrictNeighbors(self::getEdgesAsObjectList());
        $reachableFrom1 = 
            self::getAllReachableNeighbors($neighbors, array($x1, $y1));
        $reachableFrom2 = 
            self::getAllReachableNeighbors($neighbors, array($x2, $y2));
        $remainingCounties = 
            self::getUniqueValueFromDB("SELECT COUNT(*) FROM `counties` WHERE district_player IS NULL");
        // If cells are cut off from each other, we want to check for valid districts and create them if necessary. Even if they are not cut off, if the reachable areas are less than total counties remaining, it's still possible that this edge placement means no other edges can be placed within this region. This too may make a county.
        if (array_search(array($x2, $y2), $reachableFrom1) !== false 
                || count($reachableFrom1) < $remainingCounties) {
            if (self::isValidDistrict($reachableFrom1)) {
                self::createNewDistrict($reachableFrom1, $counties);
            }
            if (self::isValidDistrict($reachableFrom2)) {
                self::createNewDistrict($reachableFrom2, $counties);
            }
        }

        $this->gamestate->nextState("playEdge");
    }

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argPlayerTurn() {
        $edgesToPlay = self::getEdgesToPlay();
        return array(
            "numEdges" => $edgesToPlay,
        );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stEvaluatePlayerMove() {
        // Determine whether player has played all edges.
        $turnsTaken = self::getGameStateValue("player_turns_taken");
        if ($turnsTaken < self::getEdgesToPlay()) {
            $this->gamestate->nextState("samePlayer");
        } else {
            $this->gamestate->nextState("nextPlayer");
        }
    }

    function stSamePlayer() {
        $this->gamestate->nextState("continueSamePlayer");
    }

    function stNextPlayer() {
        // Activate next player.
        $player_id = self::activeNextPlayer();

        self::giveExtraTime($player_id);
        self::setGameStateValue("player_turns_taken", 0);
        self::incGameStateValue("turn_number", 1);
        $this->gamestate->nextState("continueNextPlayer");
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
