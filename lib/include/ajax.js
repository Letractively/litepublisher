<script type="text/javascript">
	function loadcontent(idtag, link) {
		var cont = document.getElementById(idtag);
		var http = createRequestObject();				
		if( http ) {
			http.open('get', link);
			http.onreadystatechange = function () {
				if(http.readyState == 4) {
					cont.innerHTML = http.responseText;
				}
			}
			http.send(null);    
		}
	}

	function createRequestObject() {
		try { return new XMLHttpRequest() }
		catch(e) {
			try { return new ActiveXObject('Msxml2.XMLHTTP') }
			catch(e) {
				try { return new ActiveXObject('Microsoft.XMLHTTP') }
				catch(e) { return null; }
			}
		}
	}
</script>