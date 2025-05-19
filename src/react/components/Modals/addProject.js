import { useState, useRef, useEffect } from 'react';
import { useForm, Controller, useWatch } from 'react-hook-form';
import {
	Modal,
	TextControl,
	ToggleControl,
	Button,
	SelectControl,
	TreeSelect,
} from '@wordpress/components';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import SettingsRow from '../SettingsRow';
import TodoistColorPicker from '../TodoistColors';
import ArrowRight from '../ArrowRight';
import Spinner from '../Spinner';
import SendCommand from '../../utils/SendCommand';
import Alerts from '../Alerts';
import { buildProjectHierarchy } from '../../utils/projectUtils';

const LIST_TYPES = [
	{ label: 'List', value: 'list' },
	{ label: 'Board', value: 'board' },
];

const AddProjectModal = ( props ) => {

	const [ projectHasParent, setProjectHasParent ] = useState( false );
	const [ projects, setProjects ] = useState( buildProjectHierarchy( props.projects ) );
	const [ colorName, setColorName ] = useState( 'green' );
	const [ saving, setSaving ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( false );
	const projectNameRef = useRef( null );

	useEffect( () => {
		if ( projectNameRef.current ) {
			projectNameRef.current.focus();
		}
	}, [ projectNameRef ] );

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
			projectName: '',
			projectColorName: 'green',
			projectParentId: '',
			projectIsFavorite: false,
			projectViewStyle: 'list', // list, board.
		}
	});

	const formValues = useWatch({ control });

	const onSubmit = ( data ) => {
		setSaving(true);
		setErrorMessage(false);

		SendCommand('gf_hey_todos_add_project', {
			nonce: gforms_hey_todos_admin_settings_strings.add_project_nonce,
			projectName: data.projectName,
			projectColorName: data.projectColorName,
			projectParentId: data.projectParentId,
			projectIsFavorite: data.projectIsFavorite,
			projectViewStyle: data.projectViewStyle,
		})
			.then((response) => {
				const { data, success } = response.data;
				setSaving(false);
				if (success) {
					props.onProjectAdded( data.projects, data.project );
				} else {
					setError( 'projectName', { message: data } );
					setErrorMessage(data);
					projectNameRef.current.focus();
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
				{__('Save Project and Close', 'gravity-forms-google-analytics-event-tracking')} <ArrowRight />
			</>
		);
	};

	return (
		<Modal
			{ ...props }
		>
			<form id="gravity-forms-google-analytics-event-tracking-add-project-form" onSubmit={handleSubmit(onSubmit)}>
				<SettingsRow>
					<Controller
						name="projectName"
						control={control}
						rules={{ required: true }}
						render={({ field: { onChange, value } }) => (
							<>
								<TextControl
									label={__('Project Name', 'gravity-forms-google-analytics-event-tracking')}
									value={value}
									onChange={ ( event ) => {
										clearErrors( 'projectName' );
										setErrorMessage(false);
										onChange( event );
									}}
									help={ __('The name of the project.', 'gravity-forms-google-analytics-event-tracking') }
									required={true}
									className={
										classnames(
											'heytodos-gform-settings-text-control',
											{
												'is-required': true,
												'has-errors': errors.projectName,
											}
										)
									}
									ref={projectNameRef}
								/>
								{ errors.projectName && (
									<Alerts alertType="error" message={errors.projectName.message} />
								)}
							</>
						)}
					/>
				</SettingsRow>
				<SettingsRow>
					<Controller
						name="projectColorName"
						control={control}
						render={({ field: { onChange, value } }) => (
							<TodoistColorPicker
								colorName={value}
								onChange={ ( colorName ) => {
									setColorName( colorName );
									onChange( colorName );
								}}
								label={__('Project Color', 'gravity-forms-google-analytics-event-tracking')}
								help={ __('The color of the project.', 'gravity-forms-google-analytics-event-tracking') }
							/>
						)}
					/>
				</SettingsRow>
				<SettingsRow>
					<ToggleControl
						label={ __( 'Project Has a Parent', 'gravity-forms-google-analytics-event-tracking')}
						checked={projectHasParent}
						onChange={ ( checked ) => setProjectHasParent( checked ) }
					/>
				</SettingsRow>
				{ projectHasParent && (
					<SettingsRow>
						<Controller
						name="projectParentId"
						control={control}
						render={({ field: { onChange, value } }) => (
							<TreeSelect
								label={__('Project Parent', 'gravity-forms-google-analytics-event-tracking')}
								value={value}
								tree={projects}
								noOptionLabel={__('Select a parent project. Leave as the default to not have a parent.', 'gravity-forms-google-analytics-event-tracking')}
								onChange={ ( newProject ) => {
									onChange( newProject );
								}}
								className="heytodos-gform-settings-tree-select"
								help={ __('The parent project of the new project.', 'gravity-forms-google-analytics-event-tracking') }
								selectedId={value}
								placeholder={__('Select a parent project. Leave as the default to not have a parent.', 'gravity-forms-google-analytics-event-tracking')}
							/>
						)}
						/>
					</SettingsRow>
				)}
				<SettingsRow>
					<Controller
						name="projectIsFavorite"
						control={control}
						render={({ field: { onChange, value } }) => (
							<ToggleControl
								label={__('Project is a Favorite', 'gravity-forms-google-analytics-event-tracking')}
								checked={value}
								onChange={onChange}
							/>
						)}
					/>
				</SettingsRow>
				<SettingsRow>
					<Controller
						name="projectViewStyle"
						control={control}
						render={({ field: { onChange, value } }) => (
							<SelectControl
								label={__('Project View Style', 'gravity-forms-google-analytics-event-tracking')}
								value={value}
								onChange={onChange}
								options={LIST_TYPES}
								help={ __('The view style of the project.', 'gravity-forms-google-analytics-event-tracking') }
							/>
						)}
					/>
				</SettingsRow>
				<div className="heytodos-gform-settings-save-container heytodos-gform-settings-save-container-add-project">
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

export default AddProjectModal;