<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>{
			IF defined(TITLE) TITLE ELSE "ООО Рога и Копыта" ENDIF
		}</title>
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
		<script type="text/javascript" src="{INFO.PREFIX}static/js/main.js"></script>
		{INCLUDE "std/headers"}
	</head>
	{INCLUDE LAYOUT}
</html>
