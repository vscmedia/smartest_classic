<?sm:if count($blog_posts):?>

<?sm:repeat from=$blog_posts:?>
  <div class="blog-post text <?sm:cycle value="left,right":?>" data-id="<?sm:$repeated_item.id:?>">
    <div class="post-details" style="width:690px">
      <h2><?sm:link to=$repeated_item with=$repeated_item.name:?></h2>
      <p class="date"><?sm:$repeated_item.date_published.month_only:?></p>
      <?sm:$repeated_item.synopsis.paragraphs:?>
      <p><?sm:link to=$repeated_item with="Read more" class="button" fa_iconname="arrow-circle-right":?></p>
    </div>
  </div>
<?sm:/repeat:?>

<?sm:/if:?>