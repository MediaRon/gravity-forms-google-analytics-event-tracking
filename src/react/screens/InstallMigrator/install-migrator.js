import React, { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { useDispatch, useSelect } from '@wordpress/data';
import Section from '../../components/Section';
import Button from '../../components/Button';
import Spinner from '../../components/Spinner';
import SendCommand from '../../utils/SendCommand';
import Alerts from '../../components/Alerts';
import Plugin from '../../components/Plugin';

const gaPlugin = 
	{
		pluginName: 'Gravity Forms Official Google Analytics Add-On',
		path: 'gravityformsgoogleanalytics/googleanalytics.php',
		installed: gforms_gfgaet_admin_settings_strings.is_gforms_ga_installed,
		activated: gforms_gfgaet_admin_settings_strings.is_gforms_ga_activated,
		orgUrl: 'https://www.gravityforms.com/add-ons/google-analytics/?utm_source=gravity-forms-event-tracking&utm_medium=plugin-notice&utm_campaign=gf-gaet-notice',
		description: 'The official Google Analytics Add-On for Gravity Forms is a replacement for the Event Tracking Add-On. Please install this plugin as a replacement for the Event Tracking Add-On.',
		installNonce: gforms_gfgaet_admin_settings_strings.install_nonce,
		activateNonce: gforms_gfgaet_admin_settings_strings.activate_nonce,
		icon: gforms_gfgaet_admin_settings_strings.ga_plugin_icon,
	};


const InstallMigrator = () => {

	if ( ! gforms_gfgaet_admin_settings_strings.can_install_ga ) {
		return (
			<>
				<Section title={__('Google Analytics Event Tracking Notice', 'gravity-forms-google-analytics-event-tracking')}
					description={__('This plugin has been deprecated. Please install the official Google Analytics Add-On for Gravity Forms.', 'gravity-forms-google-analytics-event-tracking')}
				>
					<Alerts
						message={__('This plugin has been deprecated. Please install the official Google Analytics Add-On for Gravity Forms.', 'gravity-forms-google-analytics-event-tracking')}
						alertType="warning"
					>
						<p>
							<Button
								label={__('View the official Google Analytics Add-On', 'gravity-forms-google-analytics-event-tracking')}
								type="link"
								href="https://www.gravityforms.com/add-ons/google-analytics/?utm_source=gravity-forms-event-tracking&utm_medium=plugin-notice&utm_campaign=gf-gaet-notice"
								target="_blank"
								rel="noopener noreferrer"
							/>
						</p>
						
					</Alerts>
				</Section>
			</>
		);
	}

	const initialInterface = () => {
		return (
			<>
				<Section title={__('Google Analytics Add-On Installer and Migrator', 'gravity-forms-google-analytics-event-tracking')}>
					<>
						<Plugin { ...gaPlugin } />
					</>
				</Section>
			</>
		);
	};

	if ( gforms_gfgaet_admin_settings_strings.is_gtm_installed ) {
		return (
			<>
				<Section title={__('Google Analytics Add-On Installer and Migrator', 'gravity-forms-google-analytics-event-tracking')}>
					<>
						<Alerts
							message={__('Google Tag Manager is connected. Please use the migration settings below to migrate your data.', 'gravity-forms-google-analytics-event-tracking')}
							alertType="success"
						/>
						<Button
							label={__('Migrate Event Tracking Data', 'gravity-forms-google-analytics-event-tracking')}
							type="button"
							onClick={() => {
								// Todo - Send ajax request to migrate data
							}}
						/>
					</>
				</Section>
			</>
		);
	}



	if ( gforms_gfgaet_admin_settings_strings.is_gforms_ga_installed && gforms_gfgaet_admin_settings_strings.is_gforms_ga_activated ) {
		return (
			<>
				<Section title={__('Google Analytics Add-On Installer and Migrator', 'gravity-forms-google-analytics-event-tracking')}>
					<>
						<Alerts
							message={__('Gravity Forms Google Analytics Add-On is installed and activated. Please connect to Google Tag Manager to continue.', 'gravity-forms-google-analytics-event-tracking')}
							alertType="info"
						/>
						<Button
							label={__('Connect to Google Tag Manager to continue', 'gravity-forms-google-analytics-event-tracking')}
							type="button"
							onClick={() => {
								window.location.href = 'admin.php?page=gf_settings&subview=gravityformsgoogleanalytics';
							}}
						/>
					</>
				</Section>
			</>
		);
	}
	return (
		<>
			<div className="gform_settings_form">
				{initialInterface()}
			</div>
		</>
	);
};

export default InstallMigrator;