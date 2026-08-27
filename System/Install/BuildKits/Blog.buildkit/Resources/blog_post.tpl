<div id="breadcrumbs"><?sm:breadcrumbs:?></div>

<?sm:edit_item id=$this.principal_item.id:?>

<div class="v-spacer"></div>

<div id="width-reducer-outer">
  <div id="width-reducer-inner">
    <h1 class="page-title big"><?sm:$this.blog_post.name:?></h1>
    <p class="date"><?sm:$this.blog_post.date_published.month_only:?></p>

    <?sm:if $this.blog_post.thumbnail_image.id:?>
    <div id="news-story-header" style="background-image:url(<?sm:$this.blog_post.thumbnail_image.image.1000x250.web_path:?>);height:250px;margin-bottom:20px"></div>
    <?sm:/if:?>

    <?sm:$this.blog_post.main_text:?>
  </div>
</div>

<?sm:edit_item id=$this.principal_item.id:?>
