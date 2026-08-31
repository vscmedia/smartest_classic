<!DOCTYPE html>

<html>

  <head>
    <title><?sm:$this.page.formatted_title:?></title>
    <meta property="og:title" content="<?sm:$this.page.formatted_title:?>" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="keywords" content="<?sm:$this.page.meta_keywords:?>" />
    <meta name="description" content="<?sm:$this.page.meta_description:?>" />
    <meta name="og:description" content="<?sm:$this.page.meta_description:?>" />
    <?sm:site_favicon:?>
	  %%CSSLINK%%
    <?sm:placeholder name="page_specific_javascript" editbutton="false":?>
    <?sm:placeholder name="page_specific_stylesheet":?>
    <?sm:if $this.user_agent.is_mobile:?><meta name="viewport" content="width=device-width; initial-scale=1.0; minimum-scale = 0.8, maximum-scale = 2.0"/><?sm:/if:?>
  </head>

  <body>

    <header id="site-header">
      <div id="header">
        <img src="<?sm:$domain:?>Resources/System/Images/logo-blacktext.png" alt="Smartest" id="smartest-welcome-logo" />
        <p class="site-kicker">Smartest Classic</p>
        <h1><?sm:$this.site.name:?></h1>
      </div>
    </header>

    <nav id="navstripe" aria-label="Main navigation">
      <div id="nav">
        <ul>
          <?sm:repeat from="pagegroup:main_nav" item="top_level_nav_page" assignhighlight="currentpage":?>
          <li><?sm:link to=$top_level_nav_page highlightpage=$currentpage highlightclass="current":?></li>
          <?sm:/repeat:?>
        </ul>
      </div>
    </nav>

    <main id="container">

      <?sm:if $this.user_agent.is_tablet:?>
      <!--You are viewing this page on a tablet.-->
      <?sm:elseif $this.user_agent.is_phone:?>
      <!--You are viewing this page on a phone.-->
      <?sm:elseif $this.user_agent.is_desktop:?>
      <!--You are viewing this page on a desktop computer.-->
      <?sm:/if:?>

      <section id="welcome-panel">
        <p class="eyebrow">Your site is ready</p>
        <h2>It worked. Now make it yours.</h2>
        <p class="text">This is the first page of your new Smartest site. Use the dashboard to add pages, files and content, or start by editing the template and stylesheet below.</p>
        <p class="template-path"><code>Presentation/Masters/%%DEFAULTTEMPLATENAME%%.tpl</code></p>
      </section>
      <?sm:container name="page_layout":?>
      <div class="breaker"></div>

      <footer id="footer">
        All content &copy; <?sm:$this.site.organization_name:?> <?sm:$now.Y:?>, All rights reserved, except where noted.
      </footer>

    </main>

  </body>

</html>
