/* eslint-disable no-unused-vars */
import * as React from 'react';
import classnames from 'classnames';
import Spinner from './Spinner';

const Loading = ( props ) => {
	const { className, message } = props;

	const classes = classnames( className, {
		saving: true,
	} );
	return (
		<>
			<span className={ classes }>
				{ message } <Spinner />
			</span>
		</>
	);
};

export default Loading;
