<script type="text/javascript">

  var IS = new InfiniteScroller;

  document.observe('scrolled:vertically', function(evt){
    
    var d = evt.memo.totalHeight - evt.memo.currentScrollTop;
    var s = window.innerHeight + 100;
    
    if(d <= s){
      IS.getNextPosts();
    }

  });
</script>

<div id="blog-banner-image"><?sm:placeholder name="banner_image" width="1000" height="200":?></div>

<div class="v-spacer"></div>

<div id="width-reducer-outer">
  <div id="width-reducer-inner">

    <h1 class="page-title big">The Sociological Review Blog</h1>

    <div id="blog-posts">
    <?sm:repeat from="blog_posts_main" limit="5":?>
      <div class="blog-post text <?sm:cycle value="left,right":?>" data-id="<?sm:$repeated_item.id:?>">
        <div class="post-details" style="width:690px">
          <h2><?sm:link to=$repeated_item with=$repeated_item.name:?></h2>
          <p class="date"><?sm:edit_item id=$repeated_item.id:?><?sm:$repeated_item.date_published.month_only:?></p>
          <?sm:$repeated_item.synopsis.paragraphs:?>
          <p><?sm:link to=$repeated_item with="Read more" class="button" fa_iconname="arrow-circle-right":?></p>
        </div>
      </div>
    <?sm:/repeat:?>
    </div>
    
    <div id="no-more-posts" style="text-align:center;display:none;color:#ccc;" class="text"><p><em>No more posts</em></p></div>

  </div>
</div>