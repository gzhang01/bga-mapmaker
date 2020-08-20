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
 * states.inc.php
 *
 * mapmaker game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

// Define constants for state ids.
if (!defined('STATE_END_GAME')) {
    define("STATE_PLAYER_TURN", 2);
    define("STATE_EVALUATE_PLAYER_MOVE", 3);
    define("STATE_SAME_PLAYER", 4);
    define("STATE_NEXT_PLAYER", 5);
    define("STATE_DISTRICT_TIE_BREAK", 6);
    define("STATE_END_GAME", 99);
}

 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => STATE_PLAYER_TURN )
    ),

    STATE_PLAYER_TURN => array(
    	"name" => "playerTurn",
		"description" => clienttranslate('${actplayer} must place ${numEdges} edge(s) (${numEdgesRemaining} remaining).'),
    	"descriptionmyturn" => clienttranslate('${you} must place ${numEdges} edge(s) (${numEdgesRemaining} remaining).'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
		"possibleactions" => array("playEdge"),
    	"transitions" => array("playEdge" => STATE_EVALUATE_PLAYER_MOVE),
    ),

    STATE_EVALUATE_PLAYER_MOVE => array(
        "name" => "evaluatePlayerMove",
        "description" => "",
        "type" => "game",
        "action" => "stEvaluatePlayerMove",
        "transitions" => array(
            "districtTieBreak" => STATE_DISTRICT_TIE_BREAK,
            "samePlayer" => STATE_SAME_PLAYER, 
            "nextPlayer" => STATE_NEXT_PLAYER, 
            "endGame" => STATE_END_GAME),
    ),

    STATE_SAME_PLAYER => array(
        "name" => "samePlayer",
        "description" => "",
        "type" => "game",
        "action" => "stSamePlayer",
        "transitions" => array("continueSamePlayer" => STATE_PLAYER_TURN),
    ),

    STATE_NEXT_PLAYER => array(
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array("continueNextPlayer" => STATE_PLAYER_TURN),
    ),

    STATE_DISTRICT_TIE_BREAK => array(
        "name" => "districtTieBreak",
        "description" => clienttranslate('${actplayer} must select district winner.'),
    	"descriptionmyturn" => clienttranslate('${you} must select district winner.'),
        "type" => "activeplayer",
        "args" => "argDistrictTieBreak",
		"possibleactions" => array("selectDistrictWinner"),
    	"transitions" => array("selectDistrictWinner" => STATE_EVALUATE_PLAYER_MOVE),
    ),
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    STATE_END_GAME => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



