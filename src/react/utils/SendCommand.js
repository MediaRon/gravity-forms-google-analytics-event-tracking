import axios from 'axios';
import qs from 'qs';

export default function SendCommand( action, data ) {
	const params = {
		action,
	};

	const defaultData = {
		nonce: false,
		action,
	};

	if ( 'undefined' === typeof data ) {
		data = {};
	}

	for ( const opt in defaultData ) {
		if ( ! data.hasOwnProperty( opt ) ) {
			data[ opt ] = defaultData[ opt ];
		}
	}

	const options = {
		method: 'post',
		// eslint-disable-next-line no-undef
		url: ajaxurl,
		params,
		// eslint-disable-next-line no-shadow
		paramsSerializer( params ) {
			return qs.stringify( params, { arrayFormat: 'brackets' } );
		},
		data: qs.stringify( data ),
	};

	return axios( options );
}
