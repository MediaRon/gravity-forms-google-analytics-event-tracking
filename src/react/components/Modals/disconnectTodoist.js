import { useState, useRef, useEffect } from 'react';
import { useForm, Controller, useWatch } from 'react-hook-form';
import {
	ToggleControl,
	Modal,
	Button,
} from '@wordpress/components';
import SettingsRow from '../SettingsRow';
import { __ } from '@wordpress/i18n';
const DisconnectTodoistModal = ( props ) => {

	const {
		register,
		handleSubmit,
		setValue,
		control,
		clearErrors,
		setError,
		formState: { errors },
	} = useForm({
		defaultValues: {
			revokeToken: false,
			removeData: false,
		}
	});

	const formValues = useWatch({ control });

	const onSubmit = ( data ) => {
		props.onConfirm( data );
	}

	return (
		<Modal
			{ ...props }
		>
			<form id="gravity-forms-google-analytics-event-tracking-add-section-form" onSubmit={handleSubmit(onSubmit)}>
				<SettingsRow>
					<Controller
						name="revokeToken"
						control={control}
						render={({ field: { onChange, value } }) => (
							<>
								<ToggleControl
									label={__('Revoke Token', 'gravity-forms-google-analytics-event-tracking')}
									help={ __( 'Revoking the token will remove the connection with Todoist and you will need to re-authorize the HeyTodos application.', 'gravity-forms-google-analytics-event-tracking' )}
									checked={value}
									onChange={onChange}
								/>
							</>
						)}
					/>
				</SettingsRow>
				<SettingsRow>
					<Controller
						name="removeData"
						control={control}
						render={({ field: { onChange, value } }) => (
							<>
								<ToggleControl
									label={__('Remove Todoist Data', 'gravity-forms-google-analytics-event-tracking')}
									help={ __( 'This will remove all locally stored Todoist data such as projects, sections, collaborators, etc.', 'gravity-forms-google-analytics-event-tracking' )}
									checked={value || formValues.revokeToken}
									onChange={onChange}
								/>
							</>
						)}
					/>
				</SettingsRow>
				<div className="heytodos-gform-settings-save-container heytodos-gform-settings-save-container-add-section">
					<Button
						type="submit"
						variant="primary"
						id="gform-settings-save"
						name="gform-settings-save"
						value="save"
						className="primary button large"
					>
						{__('Disconnect', 'gravity-forms-google-analytics-event-tracking')}
					</Button>
					<Button
						variant="secondary"
						onClick={props.onRequestClose}
					>
						{__('Cancel', 'gravity-forms-google-analytics-event-tracking')}
					</Button>
				</div>
			</form>
		</Modal>
	)
}

export default DisconnectTodoistModal;