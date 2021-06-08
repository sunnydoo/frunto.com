<?php

// This file demonstrated how to authenticate a user with 'Trusted Ticket' 
// of Tableau Server. 
//
// Author:  Wang Robin
// Email :  wang.robin@frunto.com
// All Rights researved by http://www.frunto.com
//

// You should first get Tableau Account for current user
// Be aware that multiple ERP users with same permission can share same Tableau Account. 

function get_user() {
    // 
    // Get a 'Tableau Account' for current login user 
    // According to current login ERP credential.
    //
    
    return "FruntoAdmin";
}

function get_server() {
    return "172.21.16.12";
}

function get_view() {
    return "views/RunFarms/sheet8";
}

function get_trusted_ticket($server, $user) {
       
    $url = "http://$server/trusted";
    $post_data = array ("username" => $user);
    
    $ch = curl_init();
    if( $ch === FALSE ) 
        throw new Exception("Server Configuration Error: Unable to Initialize cURL."); 

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    $output = curl_exec($ch);
    
    if( $output === FALSE or $output == "-1" )
         throw new Exception("Tableau Server reported authentication error, please find Tableau Support for assistance.");

    curl_close($ch);
    return $output;
}

function get_trusted_url( $server, $user, $view_url) {
    $params = ':embed=yes&toolbar=no';

try{
    $ticket = get_trusted_ticket($server, $user);
    if (strcmp($ticket, "-1") != 0) {
        return "http://82.157.19.106/trusted/$ticket/$view_url?$params";
    }
    else 
        return 0;
}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
//end of try catch

}

?>

<!DOCTYPE html>
<html>
<head>
    <title>TopFarm - 定义养猪的标准表达</title>
    
    <script type="text/javascript" src="http://82.157.19.106/javascripts/api/tableau-2.min.js"></script>
    <script type="text/javascript">
        function initViz() {
            var containerDiv = document.getElementById("vizContainer");
            var url = "<?php echo get_trusted_url(get_server(), get_user(), get_view())?>";
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
    <H1>Farms Run </H1>
    <p>&nbsp;</p>
    <div>成都方糖科技TopFarm</div>
    <p>&nbsp;</p>

    <div id="vizContainer" style="width:1600px; height:1200px;text-align:center"></div>    
</body>

</html>
