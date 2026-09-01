<div class="work-area">
{if $found_image}
<img src="{$image.constrain_1050x632.web_path}" alt="previewed image" style="width:{$image.constrain_525x316.width}px;height:{$image.constrain_525x316.height}px;display:block" />
{elseif $found_file}
<div class="instruction">
  <strong>{$file_name}</strong> is present in the images folder{if $file_size} ({$file_size}){/if}, but Smartest cannot render a preview for this {$file_suffix} file before it has been imported.
</div>
{if $file_suffix == "HEIC" || $file_suffix == "HEIF"}
<div class="warning">HEIC/HEIF files can be imported and then converted to JPEG from the file edit screen before being used on web pages.</div>
{/if}
{else}
<em>The image file you requested could not be found</em>
{/if}
</div>
