import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import SendCommand from '../../utils/SendCommand';
import { DownloadCloud, Loader, ShieldCheck } from 'lucide-react';
import classnames from 'classnames';

const Plugin = ( props ) => {
	const {
		icon,
		pluginName,
		path,
		orgUrl,
		description,
		nonce,
		installNonce,
		activateNonce,
	} = props;

	const [ loading, setLoading ] = useState( false );
	const [ installed, setInstalled ] = useState( gforms_gfgaet_admin_settings_strings.is_gforms_ga_installed );
	const [ activated, setActivated ] = useState( gforms_gfgaet_admin_settings_strings.is_gforms_ga_activated );
	const [ installing, setInstalling ] = useState( false );
	const [ activating, setActivating ] = useState( false );
	const [ instanceRef, setInstanceRef ] = useState( null );
	const [ error, setError ] = useState( null );
	/**
	 * Get a button label for a plugin card.
	 * @return {string} The button label.
	 */
	const getButtonLabel = () => {
		if ( loading ) {
			return __( 'Loading…', 'gravity-forms-google-analytics-event-tracking' );
		}
		if ( installing ) {
			return __( 'Installing…', 'gravity-forms-google-analytics-event-tracking' );
		}
		if ( activating ) {
			return __( 'Activating…', 'gravity-forms-google-analytics-event-tracking' );
		}
		if ( ! installed ) {
			return __( 'Install', 'gravity-forms-google-analytics-event-tracking' );
		}
		if ( ! activated ) {
			return __( 'Activate', 'gravity-forms-google-analytics-event-tracking' );
		}
		return __( 'Active', 'gravity-forms-google-analytics-event-tracking' );
	};

	/**
	 * Get a button label for a plugin card.
	 * @return {string} The button label.
	 */
	const getStatusLabel = () => {
		if ( loading ) {
			return '';
		}
		if ( ! installed ) {
			return __( 'Status: Not installed', 'gravity-forms-google-analytics-event-tracking' );
		}
		if ( ! activated ) {
			return __( 'Status: Inactive', 'gravity-forms-google-analytics-event-tracking' );
		}
		return __( 'Status: Installed and Active', 'gravity-forms-google-analytics-event-tracking' );
	};

	/**
	 * Get the right icon for the button's state.
	 * @return {JSX.Element} icon.
	 */
	const getButtonIcon = () => {
		if ( loading || installing || activating ) {
			return () => <Loader />;
		}
		if ( ! installed ) {
			return () => <DownloadCloud />;
		}
		if ( ! activated ) {
			return () => <ShieldCheck />;
		}
		return null;
	};

	return (
		<div key={ path } className="gfgaet-plugin-integration">
			<div className="gfgaet-plugin-integration-info">
				<div className="gfgaet-plugin-integration-icon">
					<img src={ icon } alt={ pluginName } />
				</div>
				<div className="gfgaet-plugin-integration-meta">
					<h3>
						<a href={ orgUrl } target="_blank" rel="noopener noreferrer">{ pluginName }</a>
					</h3>
					<p className="description">
						{ description }
					</p>
				</div>
			</div>
			<div className="gfgaet-plugin-integration-actions">
				<div className="gfgaet-plugin-integration-status">
					{ ! loading && (
						<>
							{ getStatusLabel() }
						</>
					) }
				</div>
				{ ( ! activated || ! installed ) && (
					<>
						<div className="gfgaet-plugin-integration-button">
							<Button
								ref={ setInstanceRef }
								onClick={ ( e ) => {
									setError( null );
									e.preventDefault();
									if ( ! installed ) {
										setInstalling( true );
										SendCommand( 'gfgaet_install_plugin', {
											path,
											nonce: installNonce,
										}, ajaxurl, 'text' ).then( ( response ) => {
											// Response is a mixture of HTML and JSON, let's extract json.
											const jsonRegex = /({[^}]+}})/;
											const responseData = response.data;
											const matches = responseData.match( jsonRegex );
											if ( matches ) {
												const { data, success } = JSON.parse( matches[ 0 ] );
												if ( success ) {
													setInstalled( true );
												} else {
													setError( data.message );
												}
											}
											
										} ).catch( ( e ) => { console.log( e ) } ).then( () => {
											setInstalling( false );
										} );
									} else if ( ! activated ) {
										setActivating( true );
										setError( null );
										SendCommand( 'gfgaet_activate_plugin', {
											path,
											nonce: activateNonce,
										} ).then( ( response ) => {
											const { data, success } = response.data;
											if ( success ) {
												setActivated( true );
												window.location.href = 'admin.php?page=gf_settings&subview=GFGAET_UA';
											} else {
												setError( data.message );
											}
										} ).catch( ( e ) => { console.log( e ) } ).then( () => {
											setActivating( false );
										} );
									}
									
								} }
								className={ classnames( 'gfgaet-button gfgaet__btn-secondary', {
									'is-loading': loading || installing || activating,
								} ) }
								disabled={ loading || installing || activating }
								icon={ getButtonIcon() }
							>
								{ getButtonLabel() }
							</Button>
						</div>
					</>
				)}
			</div>
			{ error && (
				<div className="gfgaet-plugin-integration-error">
					<Alerts
						message={ error }
						alertType="error"
					/>
				</div>
			) }
		</div>
	);
};

export default Plugin;
