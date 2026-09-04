<p>Step 1 of 4: Permissions</p>

<p>Welcome to Smartest! Before we start, Smartest needs to be able to write files into the following places:</p>

<ul>

<?php

$errors = $stage->g('perms')->g('errors');

foreach($errors as $file){
	echo '<li>'.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'</li>';
}

?>

</ul>

<?php if ($stage->g('perms')->g('script_created')): ?>
  <?php $u = posix_getpwuid(fileowner($errors[0].'.')); ?>
  <?php $owner_name = is_array($u) && isset($u['name']) ? $u['name'] : 'the web server user'; ?>
  
<p><strong>Smartest has helpfully created a shell script to do all this for you.</strong></p>

<p>To check the script before you run it, type:</p>
<p>
  <code>less <?php echo htmlspecialchars($stage->g('perms')->g('script_name'), ENT_QUOTES, 'UTF-8'); ?></code>
</p>

<p><strong>To run the script, type the following</strong> (preferably as user <strong><?php echo htmlspecialchars($owner_name, ENT_QUOTES, 'UTF-8'); ?></strong> or as <strong>root</strong>).</p>
<p>
  <code>bash <?php echo htmlspecialchars($stage->g('perms')->g('script_name'), ENT_QUOTES, 'UTF-8'); ?></code>
</p>

<p>If your web server uses a private temporary directory and you cannot see the script from your shell, run this instead:</p>
<p>
  <code><?php echo htmlspecialchars($stage->g('perms')->g('permissions_command'), ENT_QUOTES, 'UTF-8'); ?></code>
</p>

<p>Alternatively if you're not comfortable using a terminal, get a friend or your server administrator to do it.</p>
<p>Once you've done this, click "Next".</p>
<?php else: ?>
<?php if ($stage->g('perms')->g('script_error')): ?>
<p><strong><?php echo htmlspecialchars($stage->g('perms')->g('script_error'), ENT_QUOTES, 'UTF-8'); ?></strong></p>
<?php endif; ?>
<?php if ($stage->g('perms')->g('permissions_command')): ?>
<p>To make all required directories writable in one go, log into your server with a terminal and type this:</p>
<p>
  <code><?php echo htmlspecialchars($stage->g('perms')->g('permissions_command'), ENT_QUOTES, 'UTF-8'); ?></code>
</p>
<?php else: ?>
<p>To make a directory writable, log into your server with a terminal and type this:</p>
<p><code>chmod 777 <?php echo htmlspecialchars($errors[0], ENT_QUOTES, 'UTF-8'); ?></code></p>
<?php endif; ?>
<p>Alternatively if you're not comfortable using a terminal, get a friend or your server administrator to do it.</p>
<p>Once you've done this for each of the folders listed above, click "Next".</p>
<?php endif; ?>

<div class="button normal-button"><a href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">Next</a></div>
