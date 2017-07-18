<body {ifdef SESSION.user} onload="onloadPage();" {endif}>
<table id="nav-header" width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td class="logoline" width="1%">{/* <a href="{REF/ROOT}"><img src="{INFO.SKINDIR}/images/logo_red.png" alt="Index" title="Домой" /></a> */}</td>
		<td class="logoline" valign="middle">
			<table align="right" cellpadding="5" cellspacing="0" style="margin-right: 50px;" width="300">
				<tr>
					<td width="1%">{/* <img src="{INFO.SKINDIR}/images/logo-small.png" alt="" /> */}</td>
					<td><span class="contact_info"><b>ООО «Оргтехсервис»</b>{#ifdef TITLE}<br/>{TITLE}{#endif}</span></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="topmenu_t"></td>
	</tr>
	<tr>
		<td colspan="2" class="topmenu" nowrap="nowrap">
		{ifdef SESSION.user}
			<a href="{INFO.PREFIX}admin/lan/index.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />Дома</a>
			<a href="{INFO.PREFIX}admin/task/repairs.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />Ремонты</a>
			<a href="{INFO.PREFIX}admin/connections/index.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />Подключения</a>
			<a href="{INFO.PREFIX}admin/mikrotik/mac.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />MAC-адреса</a>
			<a href="{INFO.PREFIX}admin/mikrotik/log.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />Лог авторизации</a>
			<a href="{INFO.PREFIX}admin/switch/index.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />Коммутаторы</a>
			<a href="{INFO.PREFIX}admin/users/index.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />Сотрудники</a>
			<a href="{INFO.PREFIX}admin/schedule/newrequests.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />Заявки {if INFO.newConnectionRequests > 0} ({escape(INFO.newConnectionRequests)}){endif}</a>
			<a href="{INFO.PREFIX}admin/news/index.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />Новости</a>
			<a href="{INFO.PREFIX}admin/rrd/index.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />Графики</a>
			<a href="{INFO.PREFIX}admin/firms2/index.php"><img alt="*" src="{INFO.SKINDIR}/images/dot.gif" />Юр.лица</a>
		{else}&nbsp;{endif}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="topmenu_b"></td>
	</tr>
</table>

<table width="100%" cellpadding="8" cellspacing="0">
<tr>
{#include "/leftbar"}
<td valign="top">{#include CONTENT}</td>{/* page_content */}
{#include "/rightbar"}
</tr>
</table>
{#include "/copyright"}
{/* #include "ga" */}

<div id="alert" onclick="$('#alert').hide();">&nbsp;</div>
</body>
