import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ProgressBar, ToggleControl } from '@wordpress/components';
import Button from './Button';
import Alerts from './Alerts';
import Section from './Section';
import SettingsRow from './SettingsRow';
import SendCommand from '../utils/SendCommand';

const authNonce = gforms_hey_todos_admin_settings_strings.auth_nonce;

const steps = [
	{
		id: 'tables',
		label: __('Removing existing tables...', 'gravity-forms-google-analytics-event-tracking'),
		command: 'gf_hey_todos_delete_tables',
		commandArgs: {
			nonce: authNonce,
		},
	},
	{
		id: 'user_plan_limits',
		label: __('Syncing Workspace Limits', 'gravity-forms-google-analytics-event-tracking'),
		command: 'gf_hey_todos_install_resource',
		commandArgs: {
			nonce: authNonce,
			resource_type: 'user_plan_limits',
		},
	},
	{
		id: 'projects',
		label: __('Syncing Projects', 'gravity-forms-google-analytics-event-tracking'),
		command: 'gf_hey_todos_install_resource',
		commandArgs: {
			nonce: authNonce,
			resource_type: 'projects',
		},
	},
	{
		id: 'sections',
		label: __('Syncing Sections', 'gravity-forms-google-analytics-event-tracking'),
		command: 'gf_hey_todos_install_resource',
		commandArgs: {
			nonce: authNonce,
			resource_type: 'sections',
		},
	},
	{
		id: 'labels',
		label: __('Syncing Labels', 'gravity-forms-google-analytics-event-tracking'),
		command: 'gf_hey_todos_install_resource',
		commandArgs: {
			nonce: authNonce,
			resource_type: 'labels',
		},
	},
	{
		id: 'collaborators',
		label: __('Syncing Collaborators', 'gravity-forms-google-analytics-event-tracking'),
		command: 'gf_hey_todos_install_resource',
		commandArgs: {
			nonce: authNonce,
			resource_type: 'collaborators',
		},
	},
	{
		id: 'cleanup',
		label: __('Cleaning up...', 'gravity-forms-google-analytics-event-tracking'),
		command: 'gf_hey_todos_perform_cleanup',
		commandArgs: {
			nonce: authNonce,
		},
	},
];

const SyncingInterface = ({ }) => {
	const [ progress, setProgress ] = useState( 0 );
	const [ processing, setProcessing ] = useState( false );
	const [ isComplete, setIsComplete ] = useState( false );
	const [ currentStep, setCurrentStep ] = useState( 0 );
	const [ error, setError ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const [ overwriteTables, setOverwriteTables ] = useState( false );

	const processResources = async ( step = null ) => {
		const startingStep = step ? step : 0;
		const stepsLength = steps.length - startingStep;
		for (let currentProgress = startingStep ; currentProgress < steps.length; currentProgress++) {
			setCurrentStep(currentProgress);
			let processing = true;

			const maxTries = 15; // To avoid infinite loops.
			let tries = 0;
			while (processing && tries < maxTries) {
				const response = await SendCommand(steps[currentProgress].command, {
					...steps[currentProgress].commandArgs,
					tries,
				});

				const { success, data } = response.data;
				if (!success) {
					setError(true);
					setErrorMessage(data);;
					return false;
				}

				const { moreRequests } = data;
				processing = moreRequests;
				tries++;
			}

			if ( startingStep === 0 ) {
				setProgress( (currentProgress + 1) / ( stepsLength ) * 100 );
			} else {
				setProgress( (currentProgress) / ( stepsLength ) * 100 );
			}
		}

		setProcessing(false);
		setIsComplete(true);

		// Refrehsh the page after 1.5 seconds.
		setTimeout( () => {
			window.location.reload();
		}, 1500 );
	};

	const getLabel = () => {
		return steps[currentStep].label;
	};

    return (
        <Section
			title={__('Sync Todoist Data', 'gravity-forms-google-analytics-event-tracking')}
			description={ __( 'Perform a manual sync of your Todoist workspace data.', 'gravity-forms-google-analytics-event-tracking' ) }
		>
			{ ( processing || isComplete ) && (
				<>
					<SettingsRow>
						<ProgressBar
							className="gravity-forms-google-analytics-event-tracking-progress-bar"
							value={isComplete ? 100 : progress}
						/>
					</SettingsRow>
				</>
			) }
			{ error && (
				<div className="heytodos-gform-settings-select-alert-container">
					<Alerts
						alertType="error"
						message={errorMessage}
						className="error-loading"
					/>
					<div className="refresh-settings-container">
						<Button
							onClick={() => {
								setError(false);
								setErrorMessage('');
								processResources(currentStep);
							}}
							label={__('Try Again', 'gravity-forms-google-analytics-event-tracking')}
						/>
					</div>
				</div>
			)}
			{ !error && (
				<>
					{
						processing && (
							<>
								<SettingsRow>
									<Alerts
										alertType="info"
										message={`${__('Progress: ', 'gravity-forms-google-analytics-event-tracking')} ${getLabel()}`}
										className="info-loading"
									/>
								</SettingsRow>
							</>
						)
					}
					{ ( !processing && isComplete ) && (
						<>
							<SettingsRow>
								<Alerts
									alertType="success"
									message={__('Sync complete! Refreshing...', 'gravity-forms-google-analytics-event-tracking')}
									className="success-loading"
								/>
							</SettingsRow>
						</>
					)}
				</>
			)}
			{
				( ! processing && ! error && ! isComplete ) && (
					<>
						<SettingsRow>
							<ToggleControl
								label={__('Overwrite existing tables', 'gravity-forms-google-analytics-event-tracking')}
								checked={overwriteTables}
								onChange={setOverwriteTables}
								help={__('If enabled, existing tables will be deleted before syncing.', 'gravity-forms-google-analytics-event-tracking')}
							/>
						</SettingsRow>
						<SettingsRow>
							<Button
								label={__('Sync Todoist Data', 'gravity-forms-google-analytics-event-tracking')}
								onClick={ () => {
									setProcessing(true);
									if ( overwriteTables ) {
										processResources(0);
									} else {
										processResources(1);
									}
								}}
							/>
						</SettingsRow>
					</>
				)
			}
        </Section>
    );
};

export default SyncingInterface; 