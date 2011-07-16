<div>
	<h1>I'm a layout~</h1>
	<hr/>
	<h2>Header</h2>
	<%foreach from=$layout.header item=item key=key%>
		<%include file="<%$item.url%>" data=$item.data layout=$item.layout%>
	<%/foreach%>
	<hr/>
	<h2>Body</h2>
	<%foreach from=$layout.body item=item key=key%>
		<%include file="<%$item.url%>" data=$item.data layout=$item.layout%>
	<%/foreach%>
	<hr/>
	<h2>Footer</h2>
	<%foreach from=$layout.footer item=item key=key%>
		<%include file="<%$item.url%>" data=$item.data layout=$item.layout%>
	<%/foreach%>	
	<hr/>
</div>