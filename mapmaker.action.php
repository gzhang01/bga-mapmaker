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
  	
  	// TODO: defines your action entry points there

    public function playEdge() {
      self::setAjaxMode();
      $x1 = (int) self::getArg("x1", AT_int, true);
      $y1 = (int) self::getArg("y1", AT_int, true);
      $x2 = (int) self::getArg("x2", AT_int, true);
      $y2 = (int) self::getArg("y2", AT_int, true);
      $result = $this->game->playEdge($x1, $y1, $x2, $y2);
      self::ajaxResponse();
    }

    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

  }
  

