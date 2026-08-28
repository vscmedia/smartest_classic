<!DOCTYPE html>

<html>

<head>
	<title><?sm:$this.page.formatted_title:?></title>
  
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="keywords" content="<?sm:$this.page.meta_keywords:?>" />
	<meta name="description" content="<?sm:$this.page.meta_description:?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
  
  <?sm:stylesheet file="%MAIN_CSS_FILE%":?>
	
  <script type="text/javascript" src="/Resources/System/Javascript/scriptaculous/lib/prototype.js"></script>
	<script type="text/javascript" src="/Resources/System/Javascript/scriptaculous/src/effects.js"></script>
  <script type="text/javascript" src="/Resources/System/Javascript/vsclabs/vsc.js"></script>
  <script type="text/javascript" src="/Resources/System/Javascript/vsclabs/vsc-effects.js"></script>
  <script type="text/javascript" src="/Resources/System/Javascript/vsclabs/vsc-scrollwatcher.js"></script>
  
	<?sm:placeholder name="page_specific_javascript" editbutton="false":?>
	<?sm:placeholder name="page_specific_stylesheet":?>
	
  <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet" />
  
</head>

<body>

  <div id="fixed-width">
    <?sm:container name="page_layout":?>
  </div>
  
  <div id="footer">
    <div id="footer-inner">
      © <?sm:field name="copyright_owner":?> <?sm:$now.Y:?> | Powered by <?sm:link to="https://smartestproject.org/" with="Smartest" target="_blank":?> 
    </div>
  </div>
  
</body>

</html>
