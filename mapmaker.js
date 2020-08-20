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
            this.setupCountyAndOverlayTiles(
                gamedatas.counties, gamedatas.districts);
            this.setupEdgeTiles(gamedatas.edges);
            
            // Set up various click handlers.
            dojo.query(".edge_location").connect("onclick", this, "onPlayEdge");

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },

        setupCountyAndOverlayTiles: function(counties, districts) {
            for (var county of counties) {
                // Add county tile.
                var id = county.x + "_" + county.y;
                dojo.place(this.format_block("jstpl_county", {
                    id: id,
                }), "county_location_" + id);
                dojo.style(
                    "county_" + id, "backgroundPosition", 
                    this.getCountyBackgroundPosition(
                        county.color, parseInt(county.val)));
                
                // Add overlay tile.
                dojo.place(this.format_block("jstpl_overlay", {
                    id: id,
                }), "county_location_" + id);

                if (county.district_id !== null 
                        && districts[county.district_id] !== null) {
                    this.renderDistrict(
                        county, districts[county.district_id]);
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

        getDistrictBackgroundPosition: function(color) {
            switch (color) {
                case this.yellowPlayerColor:
                    return "-15px -75px";
                case this.greenPlayerColor:
                    return "-15px -12px";
                case this.bluePlayerColor:
                    return "-85px -63px";
                case this.redPlayerColor:
                    return "-78px -3px";
                default:
                    return;
            }
        },

        getOverlayBackgroundPosition: function(color) {
            switch (color) {
                case this.yellowPlayerColor:
                    return "-80px 0px";
                case this.greenPlayerColor:
                    return "-160px 0px";
                case this.bluePlayerColor:
                    return "-240px 0px";
                case this.redPlayerColor:
                    return "0px 0px";
                default:
                    return;
            }
        },

        setupEdgeTiles: function(edges) {
            for (var edge of edges) {
                var id = `(${edge.x1},${edge.y1})_(${edge.x2},${edge.y2})`;
                if (edge.isPlaced === "1") {
                    dojo.place(this.format_block("jstpl_edge", {
                        id: id,
                    }), "edge_location_" + id);
                } else {
                    dojo.addClass("edge_location_" + id, "isValidEdgeLocation");
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
            
            switch (stateName) {
                case "districtTieBreak":
                    for (var county of args.args.counties) {
                        var id = county["x"] + "_" + county["y"];
                        dojo.addClass("overlay_" + id, "overlay_choose_winner");
                    }
                    break;
                default:
                    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch (stateName) {
                case "districtTieBreak":
                    dojo.query(".overlay_choose_winner")
                        .removeClass(".overlay_choose_winner");
                default:
                    break;
            }
             
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the action status bar (ie: the HTML links in the status bar).
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if (this.isCurrentPlayerActive()) {
                switch(stateName) {
                    case "districtTieBreak":
                        for (var color of args.possibleWinners) {
                            var id = "district_tiebreak_"
                                        + args.districtId
                                        + "_"
                                        + color;
                            this.addActionButton(
                                id,
                                _(this.getUserReadablePlayerColor(color)),
                                "onChooseDistrictTieBreak");
                        }
                        break;
                    default:
                        break;
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

        renderDistrict: function(county, color) {
            var id = county.x + "_" + county.y;
            dojo.addClass("overlay_" + id, "overlay_active");
            dojo.style(
                "overlay_" + id, "backgroundPosition",
                this.getOverlayBackgroundPosition(color));

            // Add meeple.
            if (county.place === '1') {
                this.placeDistrictMeeple(id, this.getActivePlayerId(), color);
            }
        },

        placeDistrictMeeple: function(id, playerId, winnerColor) {
            dojo.place(this.format_block("jstpl_district_meeple", {
                id: id
            }), "districts");
            this.placeOnObject(
                "district_meeple_" + id,
                "overall_player_board_" + playerId);
            this.slideToObject("district_meeple_" + id, "county_location_" + id)
                .play();

            // Set proper background position to render meeple.
            dojo.style(
                "district_meeple_" + id, "backgroundPosition",
                this.getDistrictBackgroundPosition(winnerColor)
            );
        },

        getUserReadablePlayerColor: function(color) {
            switch (color) {
                case this.yellowPlayerColor:
                    return "yellow";
                case this.greenPlayerColor:
                    return "green";
                case this.bluePlayerColor:
                    return "blue";
                case this.redPlayerColor:
                    return "red";
                default:
                    return;
            }
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
        
        onChooseDistrictTieBreak: function (arg) {
            // Check that this action is posible.
            if (!this.checkAction("selectDistrictWinner")) {
                console.log("This action is not possible right now!");
                return;
            }

            let pieces = arg.currentTarget.id.split('_');
            var id = pieces[2];
            var color = pieces[3];
            this.ajaxcall(
                "/mapmaker/mapmaker/selectDistrictWinner.html", {
                    id: id,
                    color: color,
                }, this, function (result) { });
        },

        
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
                this.renderDistrict(
                    county, notif.args.winner_color);                
            }
        },

        notif_newScores: function(notif) {
            for (var player_id in notif.args.scores) {
                this.scoreCtrl[player_id].toValue(notif.args.scores[player_id]);
            }
        },
   });             
});
