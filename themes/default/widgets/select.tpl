<tr>
 <td><b>{param.title}</b></td>
 <td>
  <select name="{param.name}"  class="r_field">
   {foreach param.options as option}
   <option value="{option.value}" {if param.value = option.value}selected{endif}>{option.title}</option>
   {endeach}
  </select>
 </td>
</tr>
