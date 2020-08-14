/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * mapmaker implementation : © <George Zhang> <gkzhang01@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * mapmaker.js
 *
 * mapmaker user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.mapmaker", ebg.core.gamegui, {
        constructor: function(){
            console.log('mapmaker constructor');
            
            // Side length of spites within tokens.png.
            this.countySpriteLength = 50;

            // Player colors values.
            this.redPlayerColor = "ff0000";
            this.bluePlayerColor = "0000ff";
            this.greenPlayerColor = "008000";
            this.yellowPlayerColor = "fd9409";
            this.neutralCountyColor = "000000";
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                         
                // TODO: Setting up players boards if needed
            }
            
            // Set up initial tiles.
            this.setupCountyTiles(gamedatas.counties);
            this.setupEdgeTiles(gamedatas.edges);
            
            // Set up various click handlers.
            dojo.query(".edge_location").connect("onclick", this, "onPlayEdge");

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },

        setupCountyTiles: function(counties) {
            for (var county of counties) {
                var id = county.x + "_" + county.y;
                dojo.place(this.format_block("jstpl_county", {
                    id: id,
                }), "county_location_" + id);
                dojo.style(
                    "county_" + id, "backgroundPosition", 
                    this.getCountyBackgroundPosition(
                        county.color, parseInt(county.val)));
                if (county.winner !== null) {
                    dojo.addClass("county_" + id, "is_placed_county");
                }
                if (county.place === '1') {
                    this.placeDistrictMeeple(
                        id, this.getActivePlayerId(), county.winner);
                }
            }
        },

        getCountyBackgroundPosition: function(color, value) {
            // Spite in image is length 50, but rendering uses width / height
            // as 40. Offsetting by -5 to center the image.
            var x = -5;
            var y = -5;
            // TODO: Maybe reconfigure tokens.png to avoid this special casing.
            if (value === 10) {
                y -= this.countySpriteLength;
            } else if (value !== 0) {
                x -= this.countySpriteLength * (value - 1);
            }

            switch (color) {
                case this.yellowPlayerColor:
                    y -= 2 * this.countySpriteLength;
                    break;
                case this.greenPlayerColor:
                    y -= 4 * this.countySpriteLength;
                    break;
                case this.bluePlayerColor:
                    y -= 6 * this.countySpriteLength;
                    break;
                case this.neutralCountyColor:
                    y -= 8 * this.countySpriteLength;
                    break;
                default:
                    break;
            }
            return `${x}px ${y}px`;
        },

        getDistrictBackgroundPosition: function(winner) {
            switch (winner) {
                case this.yellowPlayerColor:
                    return `-15px -75px`;
                case this.greenPlayerColor:
                    return `-15px -12px`;
                case this.bluePlayerColor:
                    return `-85px -63px`;
                case this.redPlayerColor:
                    return `-78px -3px`;
                default:
                    return;
            }
        },

        setupEdgeTiles: function(edges) {
            for (var edge of edges) {
                var id = `(${edge.x1},${edge.y1})_(${edge.x2},${edge.y2})`;
                dojo.addClass("edge_location_" + id, "isValidEdgeLocation");
                if (edge.isPlaced === "1") {
                    dojo.place(this.format_block("jstpl_edge", {
                        id: id,
                    }), "edge_location_" + id);
                }
            }
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        getEdgeRotation: function (y1, y2) {
            if (y2 > y1) {
                return "30deg";
            }
            if (y2 < y1) {
                return "-30deg";
            }
            return "90deg";
        },

        placeDistrictMeeple: function(id, playerId, winnerColor) {
            dojo.place(this.format_block("jstpl_district", {
                id: id
            }), "districts");
            this.placeOnObject(
                "district_" + id,
                "overall_player_board_" + playerId);
            this.slideToObject("district_" + id, "county_location_" + id)
                .play();

            // Set proper background position to render meeple.
            dojo.style(
                "district_" + id, "backgroundPosition",
                this.getDistrictBackgroundPosition(winnerColor)
            );
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        onPlayEdge: function(evt) {
            console.log("onPlayEdge");

            // Preventing default browser reaction.
            dojo.stopEvent(evt);

            // Check that this action is posible.
            if (!this.checkAction("playEdge")) {
                console.log("This action is not possible right now!");
                return;
            }
            
            var id = evt.currentTarget.id;

            // Check if this edge location is valid for this game.
            // Note that this does not assert whether the edge can be played here (i.e. due to rules constraints). It merely asserts whether the edge connects two counties that are both in the game.
            if (!dojo.hasClass(id, "isValidEdgeLocation")) {
                console.log(`This is not a valid edge location: ${id}`);
                return;
            }

            console.log(id);
            // Id is of the form "edge_location_(x1,y1)_(x2,y2)".
            let regexp = 
                /edge_location_\((?<x1>.*),(?<y1>.*)\)_\((?<x2>.*),(?<y2>.*)\)/;
            let match = regexp.exec(id);
            this.ajaxcall("/mapmaker/mapmaker/playEdge.html", {
                x1: match.groups.x1,
                y1: match.groups.y1,
                x2: match.groups.x2,
                y2: match.groups.y2,
            }, this, function(result) {});
        },
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/mapmaker/mapmaker/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your mapmaker.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            dojo.subscribe("playedEdge", this, "notif_playedEdge");
            this.notifqueue.setSynchronous("playedEdge", 500);
            dojo.subscribe("newDistrict", this, "notif_newDistrict");
            this.notifqueue.setSynchronous("newDistrict", 500);
            dojo.subscribe("newScores", this, "notif_newScores");
            this.notifqueue.setSynchronous("newScores", 500);
        },  
        

        notif_playedEdge: function(notif) {
            console.log("notif_playedEdge");
            console.log(notif);

            var id = `(${notif.args.x1},${notif.args.y1})_(${notif.args.x2},${notif.args.y2})`;
            dojo.place(this.format_block("jstpl_edge", {
                id: id,
            }), "edges");
            this.placeOnObject(
                "edge_" + id, "overall_player_board_" + notif.args.player_id);
            this.slideToObject("edge_" + id, "edge_location_" + id).play();
            dojo.style(
                "edge_" + id, "transform",
                `rotate(${this.getEdgeRotation(
                    parseInt(notif.args.y1), parseInt(notif.args.y2))})`);
        },

        notif_newDistrict: function(notif) {
            // Mark all counties as played.
            for (var county of notif.args.counties) {
                var id = county[0] + "_" + county[1];
                dojo.addClass("county_" + id, "is_placed_county");
            }

            // Create and move district meeple.
            var position = notif.args.position;
            var id = position[0] + "_" + position[1];
            this.placeDistrictMeeple(
                id, notif.args.winner_id, notif.args.winner_color);
        },

        notif_newScores: function(notif) {
            for (var player_id in notif.args.scores) {
                this.scoreCtrl[player_id].toValue(notif.args.scores[player_id]);
            }
        },
   });             
});
