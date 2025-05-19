import { useState, useRef, useEffect } from 'react';
import { useForm, Controller, useWatch } from 'react-hook-form';
import {
	Modal,
	TextControl,
	Button,
} from '@wordpress/components';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import SettingsRow from '../SettingsRow';
import ArrowRight from '../ArrowRight';
import Spinner from '../Spinner';
import SendCommand from '../../utils/SendCommand';
import Alerts from '../Alerts';

const AddSectionModal = ( props ) => {

	const [ saving, setSaving ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( false );
	const sectionNameRef = useRef( null );

	useEffect( () => {
		if ( sectionNameRef.current ) {
			sectionNameRef.current.focus();
		}
	}, [ sectionNameRef ] );

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
			sectionName: '',
		}
	});

	const formValues = useWatch({ control });

	const onSubmit = ( data ) => {
		setSaving(true);
		setErrorMessage(false);

		SendCommand('gf_hey_todos_add_section', {
			nonce: gforms_hey_todos_admin_settings_strings.add_section_nonce,
			sectionName: data.sectionName,
			projectId: props.projectId,
		})
			.then((response) => {
				const { data, success } = response.data;
				setSaving(false);
				if (success) {
					props.onSectionAdded( data.sections, data.section );
				} else {
					setError( 'sectionName', { message: data } );
					setErrorMessage(data);
					sectionNameRef.current.focus();
				}
			})
			.catch((response) => {
				setSaving(false);
			})
			.then((response) => {
				setSaving(false);
			});
	}

	const getSaveText = () => {
		if (saving) {
			return (
				<>
					<span className="saving">
						{__('Saving…', 'gravity-forms-google-analytics-event-tracking')} <Spinner />
					</span>
				</>
			);
		}
		return (
			<>
				{__('Save Section and Close', 'gravity-forms-google-analytics-event-tracking')} <ArrowRight />
			</>
		);
	};

	return (
		<Modal
			{ ...props }
		>
			<form id="gravity-forms-google-analytics-event-tracking-add-section-form" onSubmit={handleSubmit(onSubmit)}>
				<SettingsRow>
					<Controller
						name="sectionName"
						control={control}
						rules={{ required: true }}
						render={({ field: { onChange, value } }) => (
							<>
								<TextControl
									label={__('Section Name', 'gravity-forms-google-analytics-event-tracking')}
									value={value}
									onChange={ ( event ) => {
										clearErrors( 'sectionName' );
										setErrorMessage(false);
										onChange( event );
									}}
									help={ __('The name of the section.', 'gravity-forms-google-analytics-event-tracking') }
									required={true}
									className={
										classnames(
											'heytodos-gform-settings-text-control',
											{
												'is-required': true,
												'has-errors': errors.sectionName,
											}
										)
									}
									ref={sectionNameRef}
								/>
								{ errors.sectionName && (
									<Alerts alertType="error" message={errors.sectionName.message} />
								)}
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
						disabled={ saving }
					>
						{getSaveText()}
					</Button>
					<Button
						variant="secondary"
						onClick={props.onRequestClose}
						disabled={saving}
					>
						{__('Cancel', 'gravity-forms-google-analytics-event-tracking')}
					</Button>
				</div>
				{ errorMessage && (
					<Alerts alertType="error" message={errorMessage} />
				)}
			</form>
		</Modal>
	)
}

export default AddSectionModal;