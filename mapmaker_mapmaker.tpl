{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- mapmaker implementation : © <George Zhang> <gkzhang01@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
--------
-->


<div id="mmk_board">
    <!-- BEGIN county_location -->
        <div id="mmk_county_location_{X}_{Y}"
             class="mmk_county_location"
             style="left: {LEFT}px; top: {TOP}px;">
        </div>
    <!-- END county_location -->
    <!-- BEGIN edge_location -->
        <div id="mmk_edge_location_({X1},{Y1})_({X2},{Y2})"
             class="mmk_edge_location"
             style="left: {LEFT}px; top: {TOP}px; transform: rotate({DEG}deg)">
        </div>
    <!-- END edge_location -->
    <div id="mmk_edges"></div>
    <div id="mmk_districts"></div>
</div>


<script type="text/javascript">
    var jstpl_county = '<div id="mmk_county_${id}" class="mmk_county"></div>';
    var jstpl_district_meeple = 
        '<div id="mmk_district_meeple_${id}" class="mmk_district_meeple"></div>';
    var jstpl_edge = '<div id="mmk_edge_${id}" class="mmk_edge"></div>';
    var jstpl_edge_border = 
        '<div id="mmk_edge_border_${id}" class="mmk_edge mmk_edge_border"></div>';
    var jstpl_overlay = '<div id="mmk_overlay_${id}" class="mmk_overlay"></div>';
</script>  

{OVERALL_GAME_FOOTER}
