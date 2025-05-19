/* eslint-disable no-unused-vars */
import * as React from 'react';
import classNames from 'classnames';

const Alerts = ( props ) => {
	const { className = '', alertType, message, children = null } = props;

	const classes = classNames( className, {
		alert: true,
		success: 'success' === alertType,
		info: 'info' === alertType,
		warning: 'warning' === alertType,
		error: 'error' === alertType,
		'has-children': children,
	} );
	return (
		<>
			<div className={ classes }>
				<div className="alert-message">{ message }</div>
				{ children }
			</div>
		</>
	);
};

export default Alerts;
