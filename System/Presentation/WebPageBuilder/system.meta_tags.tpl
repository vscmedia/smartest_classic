  <meta name="generator" content="Smartest" />
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="keywords" content="<?sm:$this.page.meta_keywords:?>" />
  <meta name="author" content="" />
  <meta name=viewport content="width=device-width, initial-scale=1">
<?sm:if $this.page.type = 'ITEMCLASS' && $this.principal_item:?>
  <meta property="og:title" content="<?sm:$this.principal_item.social_media_headline:?>" />
  <?sm:if $this.principal_item._thumbnail.id:?><meta property="og:image" content="<?sm:$this.principal_item._thumbnail.image.absolute_web_path:?>" /><?sm:/if:?>
  <meta property="og:description" content="<?sm:$this.principal_item._description:?>" />
  
  <meta name="twitter:card" content="summary" />
  <meta name="twitter:title" content="<?sm:$this.principal_item.name:?>" />
  <meta name="twitter:description" content="<?sm:$this.principal_item._description:?>" />
  <?sm:if $this.principal_item._thumbnail.id:?><meta name="twitter:image" content="<?sm:$this.principal_item._thumbnail.image.absolute_web_path:?>" /><?sm:/if:?>
<?sm:else:?>
  <meta property="og:title" content="<?sm:$this.page.formatted_title:?>" />
  <meta property="og:description" content="<?sm:$this.page.meta_description:?>" />
  <meta name="description" content="<?sm:$this.page.meta_description:?>" />
<?sm:/if:?>