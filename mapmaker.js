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
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                         
                // TODO: Setting up players boards if needed
            }
            
            // Set up initial tiles.
            this.setupEdgeTiles(gamedatas.edges);
            this.setupCountyAndOverlayTiles(
                gamedatas.counties, gamedatas.districts);
            
            // Set up various click handlers.
            dojo.query(".mmk_edge_location")
                .connect("onclick", this, "onPlayEdge");

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();
        },

        setupCountyAndOverlayTiles: function(counties, districts) {
            for (var county of counties) {
                // Add county tile.
                var id = this.getId(county.x, county.y);
                dojo.place(this.format_block("jstpl_county", {
                    id: id,
                }), "mmk_county_location_" + id);
                dojo.style(
                    "mmk_county_" + id, "backgroundPosition", 
                    this.getCountyBackgroundPosition(
                        county.color, parseInt(county.val)));
                
                // Add overlay tile.
                dojo.place(this.format_block("jstpl_overlay", {
                    id: id,
                }), "mmk_county_location_" + id);

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
                    }), "mmk_edge_location_" + id);
                    if (edge.playerColor) {
                        dojo.addClass(
                            "mmk_edge_" + id, 
                            "mmk_edge_" + edge.playerColor);
                    }
                    this.placeEdgeBorder(
                        id,
                        this.getEdgeRotation(
                            parseInt(edge.y1), parseInt(edge.y2)),
                        edge.playerColor ? edge.playerColor : "");
                } else {
                    dojo.addClass(
                        "mmk_edge_location_" + id, 
                        [
                            "mmk_is_valid_edge_location",
                            "county_endpoint_" + this.getId(edge.x1, edge.y1),
                            "county_endpoint_" + this.getId(edge.x2, edge.y2),
                        ]);
                }
            }
        },
       
        // Places a white border around the edge to make it more visible.
        // This is modeled as an extra div below the edge so that overlaps are not apparent.
        // If playerColor is passed in, then edge is currently still active and this div is hidden (to be shown on nextPlayer).
        placeEdgeBorder: function(id, rotation, playerColor) {
            dojo.place(
                this.format_block(
                    "jstpl_edge_border", { id: id }), "mmk_edges");
            edgeId = "mmk_edge_border_" + id;
            this.placeOnObject(
                edgeId, "mmk_edge_location_" + id);
            dojo.style(
                edgeId, "transform", `rotate(${rotation})`);
            if (playerColor) {
                dojo.addClass(edgeId, "mmk_hidden mmk_hidden_" + playerColor);
            }
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {   
            switch (stateName) {
                case "nextPlayer":
                    // Remove the player color border around the edge.
                    var edgeClass = "mmk_edge_" + args.args.playerColor;
                    dojo.query("." + edgeClass).removeClass(edgeClass);
                    // Shows the underlying white border.
                    dojo.query(".mmk_hidden_" + args.args.playerColor)
                        .removeClass(
                            "mmk_hidden mmk_hidden_" + args.args.playerColor);
                    break;
                case "districtTieBreak":
                    for (var county of args.args.counties) {
                        var id = this.getId(county["x"], county["y"]);
                        dojo.addClass(
                            "mmk_overlay_" + id, "mmk_overlay_choose_winner");
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
            switch (stateName) {
                case "districtTieBreak":
                    dojo.query(".mmk_overlay_choose_winner")
                        .removeClass("mmk_overlay_choose_winner");
                default:
                    break;
            }
             
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the action status bar (ie: the HTML links in the status bar).
        onUpdateActionButtons: function( stateName, args )
        {             
            if (this.isCurrentPlayerActive()) {
                switch(stateName) {
                    case "playerTurn":
                        if (args.shouldShowConfirm) {
                            this.addActionButton(
                                "confirmTurn", _("Confirm"), "onConfirmTurn");
                        }
                        if (args.shouldShowReset) {
                            this.addActionButton(
                                "resetTurn", _("Restart turn"), "onResetTurn", null, false, "gray");
                        }
                        break;
                    case "districtTieBreak":
                        for (var color of args.possibleWinners) {
                            var id = "mmk_district_tiebreak_"
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
        
        getId: function(x, y) {
            return x + "_" + y;
        },

        getEdgeRotation: function(y1, y2) {
            if (y2 > y1) {
                return "30deg";
            }
            if (y2 < y1) {
                return "-30deg";
            }
            return "90deg";
        },

        renderDistrict: function(county, color) {
            var id = this.getId(county.x, county.y);
            dojo.addClass("mmk_overlay_" + id, "mmk_overlay_active");
            dojo.style(
                "mmk_overlay_" + id, "backgroundPosition",
                this.getOverlayBackgroundPosition(color));

            // Add meeple.
            if (county.place === '1') {
                this.placeDistrictMeeple(id, this.getActivePlayerId(), color);
            }

            // Remove valid edge location classes on neighboring edges.
            dojo.query(".county_endpoint_" + this.getId(county.x, county.y))
                .removeClass("mmk_is_valid_edge_location");
        },

        placeDistrictMeeple: function(id, playerId, winnerColor) {
            dojo.place(this.format_block("jstpl_district_meeple", {
                id: id
            }), "mmk_districts");
            this.placeOnObject(
                "mmk_district_meeple_" + id,
                "overall_player_board_" + playerId);
            this.slideToObject(
                    "mmk_district_meeple_" + id, "mmk_county_location_" + id)
                .play();

            // Set proper background position to render meeple.
            dojo.style(
                "mmk_district_meeple_" + id, "backgroundPosition",
                this.getDistrictBackgroundPosition(winnerColor)
            );
        },

        getUserReadablePlayerColor: function(color) {
            switch (color) {
                case this.yellowPlayerColor:
                    return "Yellow Porcupines";
                case this.greenPlayerColor:
                    return "Green Leaves";
                case this.bluePlayerColor:
                    return "Blue Donkeys";
                case this.redPlayerColor:
                    return "Red Elephants";
                default:
                    return;
            }
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        onPlayEdge: function(evt) {
            // Preventing default browser reaction.
            dojo.stopEvent(evt);

            // Check that this action is posible.
            if (!this.checkAction("playEdge")) {
                return;
            }
            
            var id = evt.currentTarget.id;

            // Check if this edge location is valid for this game.
            // Note that this does not assert whether the edge can be played here (i.e. due to rules constraints). It merely asserts whether the edge connects two counties that are both in the game.
            if (!dojo.hasClass(id, "mmk_is_valid_edge_location")) {
                return;
            }

            // Id is of the form "edge_location_(x1,y1)_(x2,y2)".
            let regexp = 
                /edge_location_\((.*),(.*)\)_\((.*),(.*)\)/;
            let match = regexp.exec(id);
            this.ajaxcall("/mapmaker/mapmaker/playEdge.html", {
                x1: match[1],
                y1: match[2],
                x2: match[3],
                y2: match[4],
            }, this, function(result) {});
        },
        
        onChooseDistrictTieBreak: function (arg) {
            // Check that this action is possible.
            if (!this.checkAction("selectDistrictWinner")) {
                return;
            }

            let pieces = arg.currentTarget.id.split('_');
            var id = pieces[3];
            var color = pieces[4];
            this.ajaxcall(
                "/mapmaker/mapmaker/selectDistrictWinner.html", {
                    id: id,
                    color: color,
                }, this, function (result) { });
        },

        onResetTurn: function(arg) {
            // Check that this action is possible.
            if (!this.checkAction("resetTurn")) {
                return;
            }

            this.ajaxcall(
                "/mapmaker/mapmaker/resetTurn.html", {}, this,
                function(result) {});
        },

        onConfirmTurn: function(arg) {
            if (!this.checkAction("confirmTurn")) {
                return;
            }

            this.ajaxcall(
                "/mapmaker/mapmaker/confirmTurn.html", {}, this,
                function(result) {});
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
            dojo.subscribe("playedEdge", this, "notif_playedEdge");
            this.notifqueue.setSynchronous("playedEdge", 500);
            dojo.subscribe("newDistrict", this, "notif_newDistrict");
            this.notifqueue.setSynchronous("newDistrict", 500);
            dojo.subscribe("newScores", this, "notif_newScores");
            this.notifqueue.setSynchronous("newScores", 500);
        },  
        

        notif_playedEdge: function(notif) {
            var id = `(${notif.args.x1},${notif.args.y1})_(${notif.args.x2},${notif.args.y2})`;

            // Place the edge.
            dojo.place(this.format_block("jstpl_edge", {id: id}), "mmk_edges");
            this.placeOnObject(
                "mmk_edge_" + id, "overall_player_board_" + notif.args.player_id);
            this.slideToObject("mmk_edge_" + id, "mmk_edge_location_" + id)
                .play();
            dojo.style(
                "mmk_edge_" + id, "transform",
                `rotate(${this.getEdgeRotation(
                    parseInt(notif.args.y1), parseInt(notif.args.y2))})`);
            dojo.addClass(
                "mmk_edge_" + id, "mmk_edge_" + notif.args.player_color);
            dojo.removeClass(
                "mmk_edge_location_" + id, "mmk_is_valid_edge_location");

            // Place the outline for this edge.
            this.placeEdgeBorder(
                id, 
                this.getEdgeRotation(
                    parseInt(notif.args.y1), parseInt(notif.args.y2)), 
                notif.args.player_color);
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
