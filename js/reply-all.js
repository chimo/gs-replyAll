( function( window, $ ) {
    /*global SN: false*/
    "use strict";

    $( document ).on( "click", ".gs-reply-all", function( e ) {
        e.preventDefault();

        var $this = $( this ),
            url = $this.attr( "href" ),
            $notice = $this.closest( "li.notice" );

        $.getJSON( url, { ajax: 1 }, function( data ) {
            var mentions = data.mentions;

            SN.U.NoticeInlineReplyTrigger( $notice, mentions.join( " " ) );
        } );
    } );

}( window, jQuery ) );
