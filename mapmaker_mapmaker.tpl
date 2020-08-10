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


<div id="board">
    <!-- BEGIN county_location -->
        <div id="county_location_{X}_{Y}"
             class="county_location"
             style="left: {LEFT}px; top: {TOP}px;">
        </div>
    <!-- END county_location -->
    <!-- BEGIN edge_location -->
        <div id="edge_location_({X1},{Y1})_({X2},{Y2})"
             class="edge_location"
             style="left: {LEFT}px; top: {TOP}px;">
        </div>
    <!-- END edge_location -->
</div>

<div id="counties"></div>
<div id="edges"></div>


<script type="text/javascript">
    var jstpl_county = '<div id="county_${id}" class="county"></div>';
    var jstpl_edge = '<div id="edge_${id}" class="edge"></div>';
</script>  

{OVERALL_GAME_FOOTER}
