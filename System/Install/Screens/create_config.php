<?php

$urlp = explode('?', $_SERVER['REQUEST_URI']);
$request = $urlp[0];
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_path = is_string($request_path) ? trim($request_path, '/') : '';
$path_parts = strlen($request_path) ? explode('/', $request_path) : array();
$smartest_index = array_search('smartest', $path_parts, true);

if($smartest_index !== false){
    $controller_domain = implode('/', array_slice($path_parts, 0, $smartest_index));
}else{
    $controller_domain = $request_path;
}

if($request == '/' || !strlen($controller_domain)){
    $show_cd = false;
}else{
    $show_cd = true;
}

if($stage->getParameter('db_connection_parameters')){
    $db_username = $stage->getParameter('db_connection_parameters')->getParameter('username');
    $db_database = $stage->getParameter('db_connection_parameters')->getParameter('database');
    $db_host = $stage->getParameter('db_connection_parameters')->getParameter('host');
}else{
    $db_username = 'username';
    $db_database = '';
    $db_host = 'localhost';
}

?>

<?php if($stage->hasParameter('errors') && $stage->getParameter('errors')->hasData()): ?>
<ul class="errors-list">
    <?php foreach($stage->getParameter('errors')->getParameters() as $error): ?>
    <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<p>Step 2 of 4: Basic configuration</p>

<form action="" method="post" id="installerForm">

<input type="hidden" name="execute" value="1" />
<input type="hidden" name="action" value="createConfigs" />

<div class="form-section-label">Database</div>

<p>Please enter connection details for an empty database that you have created specifically for your new Smartest installation. If you haven't created one yet, do that first, then come back and do this.</p>

<div class="form-row">
    <div class="form-row-label">Username</div>
    <input type="text" name="db_username" value="<?php echo htmlspecialchars($db_username, ENT_QUOTES, 'UTF-8'); ?>" />
</div>

<div class="form-row">
    <div class="form-row-label">Password</div>
    <input type="text" name="db_password" />
</div>

<div class="form-row">
    <div class="form-row-label">Database Name</div>
    <input type="text" name="db_database" value="<?php echo htmlspecialchars($db_database, ENT_QUOTES, 'UTF-8'); ?>" />
</div>

<div class="form-row">
    <div class="form-row-label">Host</div>
    <input type="text" name="db_host" value="<?php echo htmlspecialchars($db_host, ENT_QUOTES, 'UTF-8'); ?>" />
</div>

<?php if($show_cd): ?>
    
<div class="form-section-label">Address</div>

<div class="form-row">
    <div class="form-row-label">URL Path</div>
    http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8'); ?>/<input type="text" name="controller_domain" style="width:150px" value="<?php echo htmlspecialchars($controller_domain, ENT_QUOTES, 'UTF-8'); ?>" />/smartest
    <div class="hint">You only need to put something in here if you are not running Smartest with its own host name, for example http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8'); ?><strong>/running/in/a/folder/</strong>smartest</div>
</div>

<?php else: ?>



<?php endif; ?>

<div class="button normal-button"><a href="javascript:document.getElementById('installerForm').submit();">Next</a></div>

</form>
