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
 * mapmaker.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in mapmaker_mapmaker.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_mapmaker_mapmaker extends game_view
  {
    function getGameName() {
        return "mapmaker";
    }    
  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/


        /*
        
        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );
        
        */
        
        $this->page->begin_block("mapmaker_mapmaker", "county_location");
        for ($x = -5; $x <= 5; $x++) {
          for ($y = -5; $y <= 5; $y++) {
            $pixelLocation = self::getCountyPixelLocation($x, $y);
            $this->page->insert_block("county_location", array(
              "X" => $x,
              "Y" => $y,
              "LEFT" => $pixelLocation[0],
              "TOP" => $pixelLocation[1],
            ));
          }
        }

        $this->page->begin_block("mapmaker_mapmaker", "edge_location");
        $neighbors = array(array(1, 0), array(0, 1), array(1, -1));
        for ($x1 = -5; $x1 <= 5; $x1++) {
          for ($y1 = -5; $y1 <= 5; $y1++) {
            foreach ($neighbors as $neighbor) {
              $x2 = $x1 + $neighbor[0];
              $y2 = $y1 + $neighbor[1];
              $pixelLocation1 = self::getCountyPixelLocation($x1, $y1);
              $pixelLocation2 = self::getCountyPixelLocation($x2, $y2);
              $this->page->insert_block("edge_location", array(
                "X1" => $x1, 
                "Y1" => $y1,
                "X2" => $x2,
                "Y2" => $y2,
                "LEFT" => ($pixelLocation1[0] + $pixelLocation2[0]) / 2 + 3,
                "TOP" => ($pixelLocation1[1] + $pixelLocation2[1]) / 2 + 15,
                "DEG" => self::getEdgeRotation($y1, $y2),
              ));
            }
          }
        }

        /*********** Do not change anything below this line  ************/
    }
    
    private function getCountyPixelLocation($x, $y) {
      $scale = 66.9;
      $angle = M_PI / 3;
      return array(round($x * $scale + $y * $scale * cos($angle)) + 355,
          round(-1 * $y * $scale * sin($angle)) + 354);
    }

    private function getEdgeRotation($y1, $y2) {
      if ($y2 > $y1) {
        return 30;
      }
      if ($y2 < $y1) {
        return -30;
      }
      return 90;
    }
  }
  

