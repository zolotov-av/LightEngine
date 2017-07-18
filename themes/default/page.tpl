<html>
 <head>
  <title>{TITLE}</title>
  {FOREACH HTTP as meta}
  <meta http-equiv="{escape(meta.name)}" content="{escape(meta.value)}" />
  {ENDEACH}
  {FOREACH META as meta}
  <meta name="{escape(meta.name)}" content="{escape(meta.value)}" />
  {ENDEACH}
  {FOREACH LINKS as link}
  <link rel="{escape(link.rel)}" href="{escape(link.URL)}" />
  {ENDEACH}
  <script type="text/javascript">
   var ajax_prefix = '{INFO.PREFIX}';
  </script>
  <script type="text/javascript" src="{INFO.PREFIX}js/main.js"></script>
 </head>
 <body>
  <h1>{TITLE}</h1>
  <p>Example page</p>
 </body>
</html>
