<tr>
 <td colspan="2"><b>{param.title}</b><br />

  <table>
   <tbody id="sort_{param.name}_body">
    {foreach param.value as item}
    <tr id="sort_{param.name}_row_{item.id}">
     <td>
      {escape(item.title)}
      <input type="hidden" id="sort_{param.name}_item_{item.id}" name="{param.name}[{item.id}]" value="0" />
     </td>
     <td><a href="#" onclick="return prevent(sort_up('{param.name}', {item.id}), event);">up</a></td>
     <td><a href="#" onclick="return prevent(sort_down('{param.name}', {item.id}), event);">down</a></td>
    </tr>
    {endeach}
   </tbody>
  </table>

 </td>
</tr>
