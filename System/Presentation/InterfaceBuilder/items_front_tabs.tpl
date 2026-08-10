<ul class="tabset" id="items-front-tabs">
    <li{if $action == "getItemClasses"} class="current"{/if}><a href="{$domain}smartest/models">Items by model</a></li>
    <li{if $action == "startPage" && $module == "data"} class="current"{/if}><a href="{$domain}smartest/data">Data resources</a></li>
    {if !empty($recent_items)}<li{if $action == "recentItems"} class="current"{/if}><a href="{$domain}datamanager/recentItems">Recent items</a></li>{/if}
    <li{if $action == "startPage" && $module == "sets"} class="current"{/if}><a href="{$domain}smartest/sets">Sets</a></li>
    {* <li{if $method == "itemPublishQueue"} class="current"{/if}><a href="{$domain}datamanager/itemPublishQueue">Publish queue</a></li> *}
</ul>