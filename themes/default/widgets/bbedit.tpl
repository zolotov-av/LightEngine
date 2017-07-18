<tr>
 <td colspan="2"><b>{param.title}</b><br />
  {#let NAME=param.name, ID=param.name, TEXT=param.value}

<div class="bbedit">
 <div class="bbedit_toolbar">
  <img onclick="bbcode_insert_tag('{ID}', '[b]', '[/b]');" title="Bold" src="{INFO.PREFIX}img/bbedit/bold.png" width="20" height="20" alt="Bold" />
  <img onclick="bbcode_insert_tag('{ID}', '[i]', '[/i]');" title="Italic" src="{INFO.PREFIX}img/bbedit/italic.png" width="20" height="20" title="Italic" />
  <img onclick="bbcode_insert_tag('{ID}', '[img]', '[/img]');" title="Картинка" src="{INFO.PREFIX}img/bbedit/img.png" width="20" height="20" alt="Картинка" />
  <img onclick="bbcode_insert_tag('{ID}', '[pic=&quot;&quot;]', '[/pic]');" title="Картинка с подписью" src="{INFO.PREFIX}img/bbedit/pic.png" width="20" height="20" alt="Картинка с подписью" />
  <img onclick="bbcode_insert_tag('{ID}', '[url=&quot;&quot;]', '[/url]');" title="URL" src="{INFO.PREFIX}img/bbedit/link.png" width="20" height="20" title="URL" />
  <img onclick="bbcode_insert_tag('{ID}', '[quote=&quot;&quot;]', '[/quote]');" title="Цитата" src="{INFO.PREFIX}img/bbedit/quote.png" width="20" height="20" alt="Цитата" />
  <img onclick="bbcode_insert_tag('{ID}', '[code]', '[/code]');" title="Код" src="{INFO.PREFIX}img/bbedit/code.png" width="40" height="20" alt="Код" />
  <img onclick="bbcode_insert_tag('{ID}', '[nobb]', '[/nobb]');" title="Отключение BB-кодов" src="{INFO.PREFIX}img/bbedit/nobb.png" width="40" height="20" alt="Отключение BB-кодов" />
  <img onclick="bbedit_on_preview('{ID}');" title="Предпросмотр" id="{ID}_preview_btn" width="100" height="20" src="{INFO.PREFIX}img/bbedit/preview.png" />
  <button onclick="bbedit_on_edit('{ID}'); return false;" id="{ID}_edit_btn" style="display: none;">править</button>
 </div>
 <div class="bbedit_preview" {#ifdef BBEDIT_HEIGHT}style="height: {BBEDIT_HEIGHT};"{#endif}>
  <div class="bbedit_preview_content" id="{ID}_preview"></div>
 </div>
 <div class="bbedit_editor" id="{ID}_area">
  <textarea name="{NAME}" id="{ID}" rows="10" {#ifdef BBEDIT_HEIGHT}style="height: {BBEDIT_HEIGHT};"{#endif} onkeydown="return bbedit_onkeydown('{ID}', event);" onkeyup="return bbedit_onkeyup('{ID}', event);">{TEXT}</textarea>
 </div>
</div>

  {#endlet}
 </td>
</tr>
