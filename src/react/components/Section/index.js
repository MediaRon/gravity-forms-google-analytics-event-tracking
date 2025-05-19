/* eslint-disable no-unused-vars */
import classnames from 'classnames';

const Section = ( props ) => {
	const {
		id = '',
		title = '',
		description = null,
		children = null,
		Logo = null,
	} = props;

	const classes = classnames( {
		'gform-settings-panel': true,
		'gform-settings-panel--full': true,
		'gform-settings-panel--with-title': title,
	} );
	return (
		<>
			<fieldset id={ id } className={ classes }>
				<>
					{ title && (
						<legend className="gform-settings-panel__title gform-settings-panel__title--header">
							{ title }
						</legend>
					) }
					<div className="gform-settings-panel__content">
						{ Logo &&
							<Logo />
						}
						{ description && (
							<div className="gform-settings-description gform-kitchen-sink">
								{ '' === description ? () => description : <p>{ description }</p> }
							</div>
						) }
						{ props.children }
					</div>
				</>
			</fieldset>
		</>
	);
};

export default Section;
