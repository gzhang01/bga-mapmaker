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
        self::setGameStateValue("turn_number", 1);
        self::setGameStateValue("player_turns_taken", 0);
        self::initCounties();
        self::initEdges();

        // Init game statistics
        self::initStat("player", "districts_won", 0);
        self::initStat("player", "swing_counties_won", 0);
        self::initStat("player", "total_district_margin", 0);
        self::initStat("player", "average_district_margin", 0);
        self::initStat("player", "districts_won_player", 0);
        self::initStat("player", "total_district_margin_player", 0);
        self::initStat("player", "average_district_margin_player", 0);
        self::initStat("player", "districts_won_opponent", 0);
        self::initStat("player", "total_district_margin_opponent", 0);
        self::initStat("player", "average_district_margin_opponent", 0);

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
            "SELECT coord_x x, coord_y y, county_player color, county_lean val, district district_id, district_placement place FROM counties");
        $result['edges'] = self::getEdgesAsObjectList();
        $result['districts'] = self::getCollectionFromDb(
            "SELECT id, player_color FROM districts", true);
  
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
        $remaining = self::getRemainingCountiesCount();
        $total = self::getUniqueValueFromDB("SELECT COUNT(*) FROM `counties`");
        return ($total - $remaining) / $total * 100;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    private function getActivePlayerColor() {
        return self::getPlayerColor(self::getActivePlayerId());
    }

    private function getPlayerColor($player_id) {
        $players = self::loadPlayersBasicInfos();
        if (isset($players[$player_id])) {
            return $players[$player_id]['player_color'];
        }
        return null;
    }

    /*
        Returns counties as a double associative array, where x is the first level and y is the second level.
    */
    private function getCountiesAsDoubleKeyCollection() {
        return self::getDoubleKeyCollectionFromDB(
            "SELECT coord_x, coord_y, county_player, county_lean, district FROM counties"
        );
    }

    // Returns whether the county at $x, $y is present in $counties.
    private function isCountyPresent($counties, $x, $y) {
        return isset($counties[$x]) and isset($counties[$x][$y]);
    }

    // Returns edges as an object list.
    private function getEdgesAsObjectList() {
        return self::getObjectListFromDB(
            "SELECT county_1_x x1, county_1_y y1, county_2_x x2, county_2_y y2, is_placed isPlaced, player_color playerColor FROM edges"
        );
    }

    // Returns list of unclaimed districts.
    private function getUnclaimedDistricts() {
        return self::getObjectListFromDB(
            "SELECT id, possible_winners FROM districts 
             WHERE player_color IS NULL");
    }

    // Returns count of counties remaining in the board.
    // Should be used to determine whether the game is over (if count == 0).
    private function getRemainingCountiesCount() {
        return self::getUniqueValueFromDB("SELECT COUNT(*) FROM `counties` WHERE district IS NULL");
    }

    // Finds a given edge in the list of edges.
    private function findEdge($edges, $x1, $y1, $x2, $y2) {
        foreach ($edges as $edge) {
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
    // Returns array(array([0] => x, [1] => y))
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

    private function createsInvalidDistrict($edge, $edges) {
        // Pretend edge is placed.
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

    private function isValidDistrict($counties, $edges) {
        if (count($counties) < 4) {
            return false;
        }
        if (count($counties) >= 4 && count($counties) <= 7) {
            return true;
        }

        // Get the edges within a district.
        $edgesWithinDistrict = array();
        foreach ($edges as $edge) {
            if ($edge["isPlaced"]) {
                continue;
            }
            foreach ($counties as $county) {
                if (($edge["x1"] == $county[0] && $edge["y1"] == $county[1]) ||
                    ($edge["x2"] == $county[0] && $edge["y2"] == $county[1])) {
                    $edgesWithinDistrict[] = $edge;
                    break;
                }
            }
        }

        // Check whether any valid edges can be placed within blocks.
        // If no edge placements are valid, then this is a district.
        foreach ($edgesWithinDistrict as $edge) {
            // If this edge placement results in invalid districts, we cannot place this edge. Continue on to other edges.
            if (self::createsInvalidDistrict($edge, $edgesWithinDistrict)) {
                continue;
            }
            // Three section arm has two counties with two neighbors and one county with three neighbors. Thus one endpoint of this border must have two neighbors. Get that one.
            $counties = array(array($edge["x1"], $edge["y1"]), 
                                    array($edge["x2"], $edge["y2"]));
            $neighbors = self::getDistrictNeighbors($edgesWithinDistrict);
            $twoNeighborCounty = array();
            if (self::getNeighborCount($counties[0], $neighbors) == 2) {
                $twoNeighborCounty = $counties[0];
            } else if (self::getNeighborCount($counties[1], $neighbors) == 2) {
                $twoNeighborCounty = $counties[1];
            } else {
                return false;
            }
            
            // Expect one neighbor of this county to have 2 neighbors. Other to have 3.
            $neighborsOf2N = 
                $neighbors[$twoNeighborCounty[0]][$twoNeighborCounty[1]];
            if ((self::getNeighborCount($neighborsOf2N[0], $neighbors) == 2 &&  
                 self::getNeighborCount($neighborsOf2N[1], $neighbors) == 3) || 
                (self::getNeighborCount($neighborsOf2N[0], $neighbors) == 3 && 
                 self::getNeighborCount($neighborsOf2N[1], $neighbors) == 2)) {
                // Check that these two neighbors are neighbors of each other by validating that one is in the neighbors list of the other. If so, this is a three-prong arm and we continue searching.
                $needle = $neighborsOf2N[0];
                $haystack = 
                    $neighbors[$neighborsOf2N[1][0]][$neighborsOf2N[1][1]];
                if (in_array($needle, $haystack)) {
                    continue;
                }
            }
            return false;
        }
        return true;
    }

    // $county array([0] -> x, [1] -> y).
    // $neighbors output of self::getDistrictNeighbors().
    private function getNeighborCount($county, $neighbors) {
        return count($neighbors[$county[0]][$county[1]]);
    }

    private function setDistrictForCounties(
            $districtId, $district, $counties, $scores) {
        // Set district for counties.
        $sql = "UPDATE counties SET district='$districtId' WHERE (coord_x, coord_y) IN (";
        $sql .= implode($counties, ',') . ")";
        self::DbQuery($sql);

        // Set the index 2 county as the district placement county
        // Note: there should always be at least 4 counties in a district, so this is safe.
        $placeX = $district[2][0];
        $placeY = $district[2][1];
        self::DbQuery(
            "UPDATE counties SET district_placement='1'
             WHERE (coord_x, coord_y)=('$placeX','$placeY')");

        // Get win margin by sorting scores and taking max - second max.
        rsort($scores);
        $winMargin = $scores[0];
        if (count($scores) > 1) {
            $winMargin -= $scores[1];
        }
        $closingPlayer = self::getActivePlayerId();
        self::DbQuery(
            "UPDATE districts 
             SET win_margin='$winMargin', closing_player_id='$closingPlayer' 
             WHERE id='$districtId'");
    }

    private function notifyDistrictCreation($districtId, $winnerColor) {
        // Notify players of district formation.
        $winnerInfo = 
            self::getObjectFromDb("SELECT player_name, player_id FROM `player` WHERE player_color='$winnerColor'");
        $winnerPlayerId = $winnerInfo["player_id"];
        $district = self::getObjectListFromDB(
            "SELECT coord_x x, coord_y y, district_placement place, county_lean
             FROM counties WHERE district='$districtId'");
        self::notifyAllPlayers(
            "newDistrict", 
            clienttranslate('${player_name} formed a new district, which was won by ${winner}.'),
            array(
                "player_name" => self::getActivePlayerName(),
                "winner" => $winnerInfo["player_name"],
                "winner_id" => $winnerPlayerId,
                "winner_color" => $winnerColor,
                "counties" => $district,
        ));

        // Update player score for winner
        self::DbQuery(
            "UPDATE player 
             SET player_score=player_score+1 
             WHERE player_color='$winnerColor'");
        $newScores = self::getCollectionFromDb(
            "SELECT player_id, player_score FROM player", true);
        self::notifyAllPlayers("newScores", "", array(
            "scores" => $newScores,
        ));

        // Update tiebreak score (based on swing counties)
        $numSwingCounties = self::getNumSwingCounties($district);
        if ($numSwingCounties > 0) {
            self::DbQuery(
                "UPDATE player 
                 SET player_score_aux=player_score_aux+$numSwingCounties 
                 WHERE player_color='$winnerColor'");
            self::incStat(
                $numSwingCounties, "swing_counties_won", $winnerPlayerId);
        }

        // Update statistics for winner.
        $districtInfo = 
            self::getObjectFromDB(
                "SELECT id, win_margin, closing_player_id 
                 FROM districts WHERE id='$districtId'");
        $winMargin = $districtInfo["win_margin"];
        $closingPlayerId = $districtInfo["closing_player_id"];
        self::incStat(1, "districts_won", $winnerPlayerId);
        self::incStat($winMargin, "total_district_margin", $winnerPlayerId);
        if ($winnerPlayerId == $closingPlayerId) {
            self::incStat(1, "districts_won_player", $winnerPlayerId);
            self::incStat(
                $winMargin, "total_district_margin_player", $winnerPlayerId);
        } else {
            self::incStat(1, "districts_won_opponent", $winnerPlayerId);
            self::incStat(
                $winMargin, "total_district_margin_opponent", $winnerPlayerId);
        }
    }

    private function getNumSwingCounties($counties) {
        $count = 0;
        foreach ($counties as $county) {
            if (intval($county["county_lean"]) === 1 
                    || intval($county["county_lean"]) === 0) {
                $count += 1;
            }
        }
        return $count;
    }

    private function createNewDistrict($newDistrict, $counties) {
        // Tally the lean values for each county within the district.
        $scores = array();
        $countySqlValues = array();
        foreach ($newDistrict as $districtCounty) {
            $x = $districtCounty[0];
            $y = $districtCounty[1];
            $county = $counties[$x][$y];
            if ($county["district"] !== NULL) {
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
        
        // Create a district for these counties.
        if (count($winners) > 1) {
            $possibleWinners = implode(",", $winners);
            self::DbQuery(
                "INSERT INTO districts (possible_winners) 
                 VALUES ('$possibleWinners')");
            self::setDistrictForCounties(
                self::DbGetLastId(), $newDistrict, $countySqlValues, $scores);
            return;
        }
        $winnerColor = $winners[0];
        self::DbQuery(
            "INSERT INTO districts (player_color) VALUES ('$winnerColor')");
        $districtId = self::DbGetLastId();
        self::setDistrictForCounties(
            $districtId, $newDistrict, $countySqlValues, $scores);
        self::notifyDistrictCreation($districtId, $winnerColor);
    }

    private function removeEdgePlayerColor($playerColor) {
        self::DbQuery("UPDATE edges SET player_color=NULL
            WHERE player_color='$playerColor'");
    }

    private function finalizeStatistics() {
        $playerIds = 
            self::getObjectListFromDB("SELECT player_id from player", true);
        $statTypes = array("", "_player", "_opponent");
        foreach ($playerIds as $playerId) {
            foreach ($statTypes as $statType) {
                $districtsWon = self::getStat(
                    "districts_won" . $statType, $playerId);
                $totalMargin = self::getStat(
                    "total_district_margin" . $statType, $playerId);
                if (intval($districtsWon) !== 0) {
                    self::setStat(
                        $totalMargin / $districtsWon, 
                        "average_district_margin" . $statType, 
                        $playerId);
                }
            }
        }
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
                self::_("This district border has already been placed!"));
        }

        // Check if edge is already within district.
        if ($counties[$x1][$y1]["district"] !== NULL ||
                $counties[$x2][$y2]["district"] !== NULL) {
            throw new BgaUserException(
                self::_("Cannot place district border inside completed district!")
            );
        }

        if (self::createsInvalidDistrict($edge, $edges)) {
            throw new BgaUserException(
                self::_("A district border cannot be placed here: it would create a district of size < 4."));
        }

        $player_color = self::getActivePlayerColor();
        self::DbQuery(
            "UPDATE edges SET is_placed='1', player_color='$player_color' 
            WHERE (county_1_x, county_1_y, county_2_x, county_2_y) = ($x1,$y1,$x2,$y2)"
        );
        self::incGameStateValue("player_turns_taken", 1);
        self::notifyAllPlayers(
            "playedEdge", 
            clienttranslate('${player_name} plays an edge (${edgesPlayed}/${edgesToPlay}).'), 
            array(
                "player_id" => self::getActivePlayerId(),
                "player_name" => self::getActivePlayerName(),
                "player_color" => $player_color,
                "x1" => $x1,
                "y1" => $y1,
                "x2" => $x2,
                "y2" => $y2,
                "edgesPlayed" => self::getGameStateValue("player_turns_taken"),
                "edgesToPlay" => self::getEdgesToPlay(),
            ));

        // Check for valid district formation
        // Requery $edges since we've changed placement.
        $edges = self::getEdgesAsObjectList();
        $neighbors = self::getDistrictNeighbors($edges);
        $reachableFrom1 = 
            self::getAllReachableNeighbors($neighbors, array($x1, $y1));
        $reachableFrom2 = 
            self::getAllReachableNeighbors($neighbors, array($x2, $y2));

        // Check whether these are valid districts.
        if (self::isValidDistrict($reachableFrom1, $edges)) {
            self::createNewDistrict($reachableFrom1, $counties);
        }
        if (!self::isSameCounty($reachableFrom1, $reachableFrom2) &&
                self::isValidDistrict($reachableFrom2, $edges)) {
            self::createNewDistrict($reachableFrom2, $counties);
        }

        $this->gamestate->nextState("playEdge");
    }

    function isSameCounty($county1, $county2) {
        if (count($county1) !== count($county2)) {
            return false;
        }
        foreach ($county1 as $county) {
            if (!in_array($county, $county2)) {
                return false;
            }
        }
        return true;
    }

    function selectDistrictWinner($id, $color) {
        self::checkAction("selectDistrictWinner");
        self::DbQuery(
            "UPDATE districts SET player_color='$color' WHERE id='$id'");
        self::notifyDistrictCreation($id, $color);
        $this->gamestate->nextState("selectDistrictWinner");
    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argPlayerTurn() {
        $edgesToPlay = self::getEdgesToPlay();
        $edgesPlayed = self::getGameStateValue("player_turns_taken");
        $edgesRemain = $edgesToPlay - $edgesPlayed;
        $str = 
            $edgesToPlay == 1 ? 
                self::_(
                    "must place 1 district border ($edgesRemain remaining)") :
                self::_(
                    "must place $edgesToPlay district borders ($edgesRemain remaining)");
        return array("str" => $str);
    }

    function argNextPlayer() {
        return array(
            "playerColor" => 
                self::getPlayerColor(self::getActivePlayerId()));
    }

    function argDistrictTieBreak() {
        $unclaimedDistricts = self::getUnclaimedDistricts();
        if (count($unclaimedDistricts) == 0) {
            throw new BgaVisibleSystemException(
                "State error: no districts available for tie break.");
        }
        $districtId = $unclaimedDistricts[0]["id"];
        $counties = self::GetObjectListFromDb(
            "SELECT coord_x x, coord_y y FROM counties 
             WHERE district='$districtId'");
        $possibleWinners = 
            explode(",", $unclaimedDistricts[0]["possible_winners"]);
        return array(
            "possibleWinners" => $possibleWinners,
            "counties" => $counties,
            "districtId" => $districtId,
        );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stEvaluatePlayerMove() {
        // Determine if there's a district tie break decision to make.
        $unclaimedDistricts = self::getUnclaimedDistricts();
        if (count($unclaimedDistricts) > 0) {
            $this->gamestate->nextState("districtTieBreak");
            return;
        }

        // Determine if the game is over.
        if (self::getRemainingCountiesCount() == 0) {
            self::finalizeStatistics();
            $this->gamestate->nextState("endGame");
            return;
        }

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
        $player_color_to_remove = 
            self::getPlayerColor(self::getActivePlayerId());

        // Activate next player.
        $player_id = self::activeNextPlayer();

        self::giveExtraTime($player_id);
        self::removeEdgePlayerColor($player_color_to_remove);
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
        if ($from_version <= 2010050247) {
            $sql = "ALTER TABLE DBPREFIX_edges ADD `player_color` VARCHAR(6) DEFAULT NULL";
            self::applyDbUpgradeToAllDB($sql);
        }
    }    
}
