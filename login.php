
<?php 

    require('priv/ulogin/config/all.inc.php');
    require('priv/ulogin/main.inc.php');  

    echo "Start of PHP";

    if (!sses_running())
        sses_start();

    $msg = '';

    function appLogin($uid, $username, $ulogin){
        $_SESSION['uid'] = $uid;
        $_SESSION['username'] = $username;
    }
    
    echo "\nbefore uLogin";
    $ulogin = new uLogin(appLogin);
    echo "\nafter uLogin";

    if( $_SERVER['REQUEST_METHOD'] == 'POST' ) { 
        if (isset($_POST['nonce']) && ulNonce::Verify('login', $_POST['nonce'])){        
            $ulogin->Authenticate($_POST['user'],  $_POST['pwd']);
            if ($ulogin->IsAuthSuccess()){
                $project = $ulogin->Project($_SESSION['uid']);
                header("Location:http://www.frunto.com/tabl/$project/");
            }
            else 
            {
                $msg = '账号或密码有误，请重新输入';
            }
        }else
            $msg = '未获得访问授权，请联系管理员support@frunto.com';
    }
    
    echo "End of PHP" ;
?>

<!doctype html>
<!--[if IE 7 ]>    <html lang="zh-gb" class="isie ie7 oldie no-js"> <![endif]-->
<!--[if IE 8 ]>    <html lang="zh-gb" class="isie ie8 oldie no-js"> <![endif]-->
<!--[if IE 9 ]>    <html lang="zh-gb" class="isie ie9 no-js"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!-->
<html lang="zh-gb" class="no-js">
<!--<![endif]-->
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<!--[if lt IE 9]> 
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <![endif]-->
<title>成都方糖科技</title>
<meta name="description" content="养猪,数据">
<meta name="author" content="成都方糖科技">
<link rel="stylesheet" href="css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="css/isotope.css" media="screen" />
<link rel="stylesheet" href="js/fancybox/jquery.fancybox.css" type="text/css" media="screen" />
<link href="css/animate.css" rel="stylesheet" media="screen">
<!-- Owl Carousel Assets -->
<link href="js/owl-carousel/owl.carousel.css" rel="stylesheet">
<link rel="stylesheet" href="css/styles.css" />
<!-- Font Awesome -->
<link href="font/css/font-awesome.min.css" rel="stylesheet">
</head>

<body>
<header class="header">
  <div class="container">
    <nav class="navbar navbar-inverse" role="navigation">
      <div class="navbar-header">
        <button type="button" id="nav-toggle" class="navbar-toggle" data-toggle="collapse" data-target="#main-nav"> <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
        <a href="#" class="navbar-brand scroll-top logo  animated bounceInLeft"><b><i>FRUNTO</i></b></a> </div>
      <!--/.navbar-header-->
      <div id="main-nav" class="collapse navbar-collapse">
        <ul class="nav navbar-nav" id="mainNav">
          <li class="active" id="firstLink"><a href="index.html#home" class="scroll-link">主页</a></li>
          <li><a href="index.html#services" class="scroll-link">服务</a></li>
          <li><a href="index.html#aboutUs" class="scroll-link">团队</a></li>
          <li><a href="index.html#work" class="scroll-link">案例</a></li>
          <li><a href="index.html#plans" class="scroll-link">培训</a></li>
          <li><a href="index.html#contactUs" class="scroll-link">联系</a></li>
        </ul>
      </div>
      <!--/.navbar-collapse--> 
    </nav>
    <!--/.navbar--> 
  </div>
  <!--/.container--> 
</header>

 
<section id="loginTopfarm">
  <div class="container">

      <div class="row mrgn30"> 
          <div class="col-sm-4 col-md-offset-4 col-md-4">
              
		  		<!--NOTE: Update your email Id in "contact_me.php" file in order to receive emails from your contact form-->
					<form name="sentMessage" id="contactForm" method="post"  action="login.php"> 
                    <h2>TopFarm 欢迎您</h2>
                    <p><?=$msg?></p>
					<div class="control-group">
					<div class="controls">
					<input type="text" class="form-control" 
					placeholder="账号" id="user" name="user" required
					data-validation-required-message="请输入账号" />
					<p class="help-block"></p>
					</div>
					</div> 	
					<div class="control-group">
					<div class="controls">
					<input type="password" class="form-control"  
					id="pwd" name="pwd" placeholder="密码" required
					data-validation-required-message="请输入密码" />
                
                    <input type="hidden" id="nonce" name="nonce" value="<?php echo ulNonce::Create('login');?>">
       
					</div>
					</div> 		 
                                        
					<button type="submit" class="btn btn-primary font-big">登 录</button><br />
					</form>
		  </div>
      </div>
    </div>
</section>
    
<footer>
<div class="container">
        <div class="row">
            <div class="col-md-6 text-left col-sm-12">
            <p><img src="images/frunto.png"></p>
            <h3>定义养猪的标准表达方式</h3>
                
            </div>
            <div class="col-md-6 col-sm-12">
                <p><span>地址:</span> 成都天府软件园D5-B075</li></p>
                <p><span>Email:</span><a href="mailto: support@frunto.com"> support@frunto.com</a></p>
                <p><span>网页:</span><a href="http://www.frunto.com"> www.frunto.com</a></p>
            </div>
    </div>
    </div>
    </footer>
    
<!--/.page-section-->
<section class="copyright">
  <div class="container">
    <div class="row">
      <div class="col-sm-12 text-center">Copyright &copy; 2017 - 2018 成都方糖科技版权所有 &nbsp; <a href="http://www.miit.gov.cn/" target="_blank">蜀ICP备17000197号</a>
</div>
    </div>
    <!-- / .row --> 
  </div>
</section>
<a href="#top" class="topHome"><i class="fa fa-chevron-up fa-2x"></i></a> 

<!--[if lte IE 8]><script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script><![endif]--> 
<script src="js/modernizr-latest.js"></script> 
<script src="js/jquery-1.8.2.min.js" type="text/javascript"></script> 
<script src="js/bootstrap.min.js" type="text/javascript"></script> 
<script src="js/jquery.isotope.min.js" type="text/javascript"></script> 
<script src="js/fancybox/jquery.fancybox.pack.js" type="text/javascript"></script> 
<script src="js/jquery.nav.js" type="text/javascript"></script> 
<script src="js/jquery.fittext.js"></script> 
<script src="js/waypoints.js"></script> 
 <script src="contact/jqBootstrapValidation.js"></script>
 <script src="contact/contact_me.js"></script>
<script src="js/custom.js" type="text/javascript"></script> 
<script src="js/owl-carousel/owl.carousel.js"></script>
</body>
</html>
