jQuery( function( $ ) {
	var $document = $( document );

	wp = wp || {};
	$document.on( 'click', 'a.friends-post-collection-mark-publish', function() {
		var $this = $(this);
		wp.ajax.send( 'friends-post-collection-mark-publish', {
			data: {
				id: $this.data( 'id' ),
			},
			success: function( response ) {
				$this.text( response.new_text ).removeClass( 'friends-post-collection-mark-publish' ).addClass( 'friends-post-collection-mark-private' );
			}
		} );
		return false;
	} );
	$document.on( 'click', 'a.friends-post-collection-mark-private', function() {
		var $this = $(this);
		wp.ajax.send( 'friends-post-collection-mark-private', {
			data: {
				id: $this.data( 'id' ),
			},
			success: function( response ) {
				$this.text( response.new_text ).removeClass( 'friends-post-collection-mark-private' ).addClass( 'friends-post-collection-mark-publish' );
			}
		} );
		return false;
	} );
	$document.on( 'click', 'a.friends-post-collection-change-author', function() {
		var $this = $(this);
		wp.ajax.send( 'friends-post-collection-change-author', {
			data: {
				id: $this.data( 'id' ),
				author: $this.data( 'author' ),
				first: $this.data( 'first' ),
			},
			success: function( response ) {
				$this.text( response.new_text ).data( 'author', response.old_author );
			}
		} );
		return false;
	} );
} );
