(function ( $ ) {
	"use strict";

	var version = $().jquery,
		versionParts = version.split('.');

	if (parseInt(versionParts[1]) < 7 && parseInt(versionParts[0]) == 1) {
		$(document).live('gform_confirmation_loaded',function(event,form_id){
			gf_event_track(form_id);
		});
	} 
	else {
		$(document).on('gform_confirmation_loaded', function(event,form_id){
			gf_event_track(form_id);
		});
	}

}(jQuery));


function gf_event_track(form_id,callback) {
	var theLabel = window.gf_event_form_labels[parseInt(form_id)];

    ga('send',{
        'hitType': 'event',
        'eventCategory': 'Forms',
        'eventAction': 'Submission',
        'eventLabel': theLabel,
        'hitCallback': function(){
        	if (typeof callback=="function") callback();
        }
    });
}