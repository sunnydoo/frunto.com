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
    
    return "sarah";
}

function get_server() {
    return "123.206.18.252";
}

function get_view() {
    return "views/UICustomization/1";
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
    $params = ':embed=yes&:toolbar=yes';

    $ticket = get_trusted_ticket($server, $user);
    if (strcmp($ticket, "-1") != 0) {
        return "http://$server/trusted/$ticket/$view_url?$params";
    }
    else 
        return 0;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>UI Customization by Parameters Demo</title>
    
    <script type="text/javascript" src="https://public.tableau.com/javascripts/api/tableau-2.min.js"></script>
    <script type="text/javascript">
        function initViz() {
            //Change this value to customize UI.
            var UI_CUSTOMIZATION = "cneast";
            
            var viz;
            var containerDiv = document.getElementById("vizContainer");
            var url = "<?php echo get_trusted_url(get_server(), get_user(), get_view())?>";
            var options = {
                    hideTabs: true,
                    hideToolbar: true,
                    onFirstInteractive: function () {
                        var wb = viz.getWorkbook();
                        wb.changeParameterValueAsync("Permission", UI_CUSTOMIZATION);
                        console.log("Run this code when the viz has finished loading.");
                    }
            };
            
            // Create a viz object and embed it in the container div.
            viz = new tableau.Viz(containerDiv, url, options); 
           
        }
    </script>
</head>

<body onload="initViz();">
    <H1>New Hope Liuhe BI Platform</H1>
    <p>&nbsp;</p>
    <div>PlaceHolder: Financial Reports</div>
    <p>&nbsp;</p>

    <div id="vizContainer" style="width:1000px; height:800px;"></div>    
</body>

</html>