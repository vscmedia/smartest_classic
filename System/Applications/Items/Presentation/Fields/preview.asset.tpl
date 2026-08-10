{if $value.id && $value.is_binary_image}
{$value.image.constrain_200x200}
{else}
{$value}
{/if}