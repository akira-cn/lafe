<div class="<%$layout.myclass%>" style="<%$layout.css%>">
	<h1>I'm a layout~ (b)</h1>
	<hr/>
	<h2>Header</h2>
	<%foreach from=$layout.header item=item key=key%>
		<%include file="<%$item.url%>" data=$item.data layout=$item.layout%>
	<%/foreach%>
	<hr/>
	<div>
	<div style="float:left">
		<h2>Left</h2>
		<%foreach from=$layout.left item=item key=key%>
			<%include file="<%$item.url%>" data=$item.data layout=$item.layout%>
		<%/foreach%>
	</div>
	<div style="float:left">
		<h2>Right</h2>
		<%foreach from=$layout.right item=item key=key%>
			<%include file="<%$item.url%>" data=$item.data layout=$item.layout%>
		<%/foreach%>
	</div>
	</div>
	<div style="clear:both">
	<h2>Footer</h2>
	<%foreach from=$layout.footer item=item key=key%>
		<%include file="<%$item.url%>" data=$item.data layout=$item.layout%>
	<%/foreach%>	
	</div>
</div>