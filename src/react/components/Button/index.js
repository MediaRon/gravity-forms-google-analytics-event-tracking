/* eslint-disable no-unused-vars */
import React from 'react';
import classnames from 'classnames';
import Spinner from '../Spinner';

const Button = ({
	Component = null,
	id = '',
	className = 'button primary',
	showIcon = true,
	icon = 'save',
	loading = false,
	show = true,
	disabled = false,
	label = '',
	labelLoading = '',
	target = '_self',
	rel = 'noopener noreferrer',
	onClick = null,
	type = 'button',
	href = '#'
}) => {
	const commonProps = {
		className: classnames( `${ className }`, {
			'is-loading': loading,
		} ),
		onClick: ( e ) => {
			if ( disabled ) {
				e.preventDefault();
			} else if ( onClick !== null ) {
				onClick( e );
			}
		},
		disabled: disabled,
	};

	const content = (
		<>
			{ showIcon && loading && <Spinner /> }
			{ ! loading && label !== '' && (
				<>
					{ label }
					{ Component !== null && showIcon === true &&
						<>
							&nbsp;&nbsp;
							<Component />
						</>
					}
				</>
			) }
			{ loading && labelLoading !== '' && (
				<>
					{ labelLoading }
					{ showIcon && <>&nbsp;&nbsp;</> }
					{ Component !== null && <Component /> }
				</>
			) }
		</>
	);

	return (
		<>
			{ show && (
				type === 'link' 
					? <a {...commonProps} href={href} target={target} rel={rel}>{content}</a>
					: <button {...commonProps} type={type}>{content}</button>
			) }
		</>
	);
};

export default Button;
