<!DOCTYPE html>

<html>

<head>
  <title><?sm:$this.page.formatted_title:?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="keywords" content="<?sm:$this.page.meta_keywords:?>" />
  <meta name="description" content="<?sm:$this.page.meta_description:?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?sm:site_favicon:?>
  <?sm:stylesheet file="%%MAIN_CSS_FILE%%":?>
  <?sm:placeholder name="page_specific_javascript" editbutton="false":?>
  <?sm:placeholder name="page_specific_stylesheet":?>
</head>

<body>
  <header id="site-header">
    <div class="inner">
      <h1><?sm:$this.site.name:?></h1>
      <nav>
        <ul>
          <?sm:repeat from="pagegroup:main_nav" item="top_level_nav_page" assignhighlight="currentpage":?>
          <li><?sm:link to=$top_level_nav_page highlightpage=$currentpage highlightclass="current":?></li>
          <?sm:/repeat:?>
        </ul>
      </nav>
    </div>
  </header>

  <main id="content">
    <?sm:container name="page_layout":?>
  </main>

  <footer id="site-footer">
    <div class="inner">All content &copy; <?sm:$this.site.organization_name:?> <?sm:$now.Y:?></div>
  </footer>
</body>

</html>
