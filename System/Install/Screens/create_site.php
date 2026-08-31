<p>Step 4 of 4: Set up your site <span class="hint">(all fields are required)</span></p>

<?php if($stage->hasParameter('errors') && $stage->getParameter('errors')->hasData()): ?>
<ul class="errors-list">
    <?php foreach($stage->getParameter('errors')->getParameters() as $error): ?>
    <li><?php echo $error ?></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php

$buildkits = $stage->hasParameter('buildkits') ? $stage->getParameter('buildkits') : array();
$default_buildkit = class_exists('SmartestBuildKitUtilities') ? SmartestBuildKitUtilities::getDefaultInstallerBuildKitShortName() : 'sm_blank_site';

?>

<form action="" method="post" id="installerForm">
    
    <input type="hidden" name="execute" value="1" />
    <input type="hidden" name="action" value="createSite" />
    
    <div class="hint" style="padding-bottom:10px">Finally, input some basic details about the website you are creating. You can easily change these later if you change your mind.</div>
    
    <div class="form-row">
        <div class="form-row-label">Name of your site</div>
        <input type="text" name="site_name" />
    </div>
    
    <div class="form-row">
        <div class="form-row-label">Hostname of your site</div>
        <input type="text" name="site_host" value="<?php echo $_SERVER['HTTP_HOST']; ?>" style="width:240px" />
    </div>
    
    <?php if(count($buildkits)): ?>
    <div class="form-row">
        <div class="form-row-label">Build Kit</div>
        <select name="use_buildkit">
            <?php if(isset($buildkits[$default_buildkit])): ?>
            <option value="<?php echo htmlspecialchars($buildkits[$default_buildkit]->getShortName(), ENT_QUOTES, 'UTF-8'); ?>">None</option>
            <?php endif; ?>
            <?php foreach($buildkits as $buildkit): if($buildkit->getShortName() == $default_buildkit || $buildkit->isHidden()){ continue; } ?>
            <option value="<?php echo htmlspecialchars($buildkit->getShortName(), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($buildkit->getLabel(), ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
        </select>
        <div class="hint">A Build Kit can create starter files, models, templates, pages and sample content for this site. Choose None for a blank Smartest site.</div>
    </div>
    <?php else: ?>
    <input type="hidden" name="use_buildkit" value="<?php echo htmlspecialchars($default_buildkit, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php endif; ?>
    
    <div class="button normal-button"><a href="javascript:document.getElementById('installerForm').submit();">Finish &amp; Log In</a></div>

</form>
