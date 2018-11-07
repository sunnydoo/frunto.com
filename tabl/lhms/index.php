<?php

// The main project of 漯河民社 
//
// Author:  Wang Robin
// Email :  wang.robin@frunto.com
// All Rights researved by http://www.frunto.com

require_once(dirname(__FILE__, 2)."/TablUtil.php");
TablUtil::return_if_invalid_auth(basename(__DIR__));

$current_view = "views/TopFarm/sheet0";
$url = TablUtil::get_trusted_url($current_view);

?>

<!DOCTYPE html>
<html>
<head>
    <title>漯河民社 - TopFarm</title>
    
    <script type="text/javascript" src="http://<?=TablUtil::ExternalURL?>/javascripts/api/tableau-2.min.js"></script>
    <script type="text/javascript">
        function initViz() {
            var containerDiv = document.getElementById("vizContainer");
            var url = "<?=$url?>";
            var options = {
                    hideTabs: false,
                    hideToolbar: false,
                    onFirstInteractive: function () {
                        console.log("Run this code when the viz has finished loading.");
                    }
            };
            
            // Create a viz object and embed it in the container div.
            var viz = new tableau.Viz(containerDiv, url, options); 
           
        }
    </script>
</head>

<body onload="initViz();">
    <H1>漯河民社</H1>
    <p>&nbsp;</p>
    <div>成都方糖科技TopFarm</div>
    <p>&nbsp;</p>

    <div id="vizContainer" style="width:1600px; height:1200px;text-align:center"></div>    
</body>

</html>
