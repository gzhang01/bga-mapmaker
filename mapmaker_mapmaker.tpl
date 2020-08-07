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
</div>

<div id="counties"></div>
<div id="border"></div>


<script type="text/javascript">
    var jstpl_county = '<div id="county_${x_y}" class="county"></div>';
</script>  

{OVERALL_GAME_FOOTER}
