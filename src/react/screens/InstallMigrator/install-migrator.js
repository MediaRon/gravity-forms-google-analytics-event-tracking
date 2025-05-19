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

	const getDescription = () => {
		return (
			<>
				{__('Todoist is a task management service that allows you to create, organize, and manage your tasks and projects.', 'gf-hey-todos')}
				{' '}
				{__('Connect to the', 'gf-hey-todos')}
				{' '}
				<a href="https://todoist.com" target="_blank" rel="noopener noreferrer">{__('Todoist Service', 'gf-hey-todos')}</a>
				{' '}
				{__('to create your task from Gravity Forms.', 'gf-hey-todos')}
			</>
		);
	};

	const connectTodoistInterface = () => {
		return (
			<>
				<Section
					title={__('HeyTodos Todoist Settings', 'gf-hey-todos')}
					description={getDescription()}
				>
					<>
						<Button
							label={__('Connect to Todoist', 'gf-hey-todos')}
							type="link"
							href={connectUrl}
						/>
					</>
				</Section>
			</>
		);
	};

	if ( ! gforms_gfgaet_admin_settings_strings.can_install_ga ) {
		return (
			<>
				<Section title={__('Google Analytics Event Tracking Notice', 'gf-hey-todos')}
					description={__('This plugin has been deprecated. Please install the official Google Analytics Add-On for Gravity Forms.', 'gf-hey-todos')}
				>
					<Alerts
						message={__('This plugin has been deprecated. Please install the official Google Analytics Add-On for Gravity Forms.', 'gf-hey-todos')}
						alertType="warning"
					>
						<p>
							<Button
								label={__('View the official Google Analytics Add-On', 'gf-hey-todos')}
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
				<Section title={__('Google Analytics Add-On Installer and Migrator', 'gf-hey-todos')}>
					<>
						<Plugin { ...gaPlugin } />
					</>
				</Section>
			</>
		);
	};

	console.log( 'here' );

	return (
		<>
			<div className="gform_settings_form">
				{initialInterface()}
			</div>
		</>
	);
};

export default InstallMigrator;