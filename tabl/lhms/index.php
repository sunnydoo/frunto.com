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

$title = "TopFarm-漯河民社";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?=$title?></title>
    
    <style type="text/css"> 
        .tblContainer{
            width: 100%;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            text-align: center;
        }
        
        .vizStyle {
            height: 
        }
    </style> 
    
    <script type="text/javascript" src="http://<?=TablUtil::ExternalURL?>/javascripts/api/tableau-2.min.js"></script>
    <script type="text/javascript" src="../codebase/webix.js"></script>
    
	<link rel="stylesheet" type="text/css" href="../codebase/webix.css">
	<link rel="stylesheet" href="https://cdn.materialdesignicons.com/2.7.94/css/materialdesignicons.css" type="text/css" charset="utf-8">
    
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
            viz.height = this.document.body.scrollHeight;
        }
    </script>
</head>

<body>
    <script type="text/javascript">
    var menu_data = [
	{id: "males", icon: "mdi mdi-view-dashboard", value: "公猪"},
	{id: "fenakes", icon: "mdi mdi-view-column", value:"母猪", data:[
		{ id: "houbei", value: "后备"},
		{ id: "peihuai", value: "配怀"},
        { id: "chanfang", value: "产房"},
		{ id: "cunlan", value: "存栏"}
	]},
    ];
	webix.ready(function(){
		webix.ui({
			rows: [
				{ view: "toolbar", padding:3, elements: [
					{ view: "button", type: "icon", icon: "mdi mdi-menu",
						width: 37, align: "left", css: "app_button", click: function(){
							$$("$sidebar1").toggle();
						}
					},
					{ view: "label", label: "<?=$title?>"},
					{},
					{ view: "button", type: "icon", width: 45, css: "app_button", icon: "mdi mdi-comment",  badge:4},
					{ view: "button", type: "icon", width: 45, css: "app_button", icon: "mdi mdi-bell",  badge:10}
				]
				},
				{ cols:[
					{
						view: "sidebar",
						data: menu_data,
                        expand: true,
						on:{
							onAfterSelect: function(id){
								initViz();
							}
						}
					},
					{ 
                        view: "template", 
                        content: "vizContainer" 
                    }
				]}
			]
		});
	});
</script>
    <div id="vizContainer"></div>
</body>
</html>
