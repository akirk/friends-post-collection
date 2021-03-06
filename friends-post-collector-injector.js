var div = document.createElement( 'div' );
div.setAttribute( 'id', 'friends-post-collector-loader' );
div.style.position = 'fixed';
div.style.top = 0;
div.style.right = 0;
div.style.width = "100%";
div.style.backgroundColor = "#000";
div.style.color = "#EEE";
div.style.textAlign = "center";
div.style.fontFamily = "sans-serif";
div.style.padding = "2em";
div.style.zIndex = "6999999";
div.textContent = 'Sending the article to your blog...';
document.body.appendChild( div );

function post_content( url ) {
	if ( url.indexOf( '?' ) > 0 ) {
		url += '&';
	} else {
		url += '?';
	}
	url += 'friends-save-url=' + encodeURIComponent( location.href );
	var form = document.createElement( "form" );
	form.setAttribute( "method", 'post' );
	form.setAttribute( "action", url );
	form.setAttribute( "target", "_self" );
	form.setAttribute( "accept-charset", "UTF-8" );

	function input( key, value ) {
		var input = document.createElement( "input" );
		input.setAttribute( "type", "hidden" );
		input.setAttribute( "name", key );
		input.setAttribute( "value", value );
		return input;
	}

	try {
		var bodyCopy = document.cloneNode( true );
		var loader = bodyCopy.getElementById( 'friends-post-collector-loader' );
		if ( loader ) {
			loader.parentNode.removeChild( loader );
		}

		['script', 'style', 'canvas', 'select', 'textarea'].forEach( function( tagName ) {
			var elems = bodyCopy.getElementsByTagName( tagName );
			for ( var i = elems.length - 1; i >= 0; i-- ) {
				elems[i].parentNode.removeChild( elems[i] );
			}
		} );

		form.appendChild( input( 'body', bodyCopy.documentElement.outerHTML ) );
		form.appendChild( input( 'friends-save-url', location.href ) );

		document.body.appendChild( form );
		form.submit();
	} catch ( e ) {
		document.getElementById( 'friends-post-collector-loader' ).textContent = e;
	}
};

post_content( document.getElementById( 'friends-post-collector-script' ).getAttribute( 'data-post-url' ) );
