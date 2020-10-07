<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * mapmaker implementation : © <George Zhang> <gkzhang01@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * mapmaker.action.php
 *
 * mapmaker main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/mapmaker/mapmaker/myAction.html", ...)
 *
 */
  
  
  class action_mapmaker extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "mapmaker_mapmaker";
            self::trace( "Complete reinitialization of board game" );
      }
  	}

    public function playEdge() {
      self::setAjaxMode();
      $x1 = (int) self::getArg("x1", AT_int, true);
      $y1 = (int) self::getArg("y1", AT_int, true);
      $x2 = (int) self::getArg("x2", AT_int, true);
      $y2 = (int) self::getArg("y2", AT_int, true);
      $result = $this->game->playEdge($x1, $y1, $x2, $y2);
      self::ajaxResponse();
    }

    public function selectDistrictWinner() {
      self::setAjaxMode();
      $id = self::getArg("id", AT_int, true);
      $color = self::getArg("color", AT_alphanum, true);
      $result = $this->game->selectDistrictWinner($id, $color);
      self::ajaxResponse();
    }

    public function resetTurn() {
      self::setAjaxMode();
      $result = $this->game->resetTurn();
      self::ajaxResponse();
    }

    public function confirmTurn() {
      self::setAjaxMode();
      $result = $this->game->confirmTurn();
      self::ajaxResponse();
    }

  }
  

