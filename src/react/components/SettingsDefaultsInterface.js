/* eslint-disable jsx-a11y/anchor-is-valid */
/* eslint-disable no-undef */
/* eslint-disable camelcase */
/* eslint-disable no-unused-vars */
import React, { useEffect, useState, useDeferredValue } from 'react';
import { useForm, Controller, useWatch } from 'react-hook-form';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	ToggleControl,
	Button,
	Modal,
	TreeSelect,
} from '@wordpress/components';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowsRotate } from '@fortawesome/free-solid-svg-icons/faArrowsRotate';
import { faCog } from '@fortawesome/free-solid-svg-icons/faCog';
import Spinner from './Spinner';
import SendCommand from '../utils/SendCommand';
import ArrowRight from './ArrowRight';
import Section from './Section';
import Alerts from './Alerts';
import Loading from './Loading';
import settingsStore from '../store/settingsStore';
import FormSelect from './FormSelect';
import { RefreshButton, AddButton } from './RefreshButton';
import SettingsRow from './SettingsRow';
import AddProjectModal from './Modals/addProject';
import AddSectionModal from './Modals/addSection';
let projectKey = 0;

const SettingsDefaultsInterface = () => {
	const [loading, setLoading] = useState(true);

	const [saving, setSaving] = useState(false);

	const [isSaved, setIsSaved] = useState(false);

	const [errorMessage, setErrorMessage] = useState('');

	const [projectsLoading, setProjectsLoading] = useState(false);

	const [sectionsLoading, setSectionsLoading] = useState(false);

	const [savingSettings, setSavingSettings] = useState(false);

	const [ isAddProjectModalOpen, setIsAddProjectModalOpen ] = useState(false);
	const [ isAddSectionModalOpen, setIsAddSectionModalOpen ] = useState(false);
	const [ hasProjectBeenAdded, setHasProjectBeenAdded ] = useState(false);
	const [ hasSectionBeenAdded, setHasSectionBeenAdded ] = useState(false);

	const { projects, sections, selectedProjectId, selectedProjectLabel, selectedSectionLabel, selectedSectionId, isTeamAccount, getSectionsForProject, projectsHierarchy,
		enableProjectCreation,
		enableSectionCreation,
		enableLabelCreation,
		enableDynamicProjectCreation,
		enableDynamicSectionCreation,
		enableDynamicLabelCreation
	} = useSelect((select) => {
		const store = select(settingsStore);
		return {
			projects: store.getProjects(),
			projectsHierarchy: store.getProjectsHierarchy(),
			sections: store.getSections(),
			selectedProjectId: store.getSelectedProjectId(),
			selectedSectionId: store.getSelectedSectionId(),
			isTeamAccount: store.getIsTeamAccount(),
			getSectionsForProject: store.getSectionsForProject,
			enableProjectCreation: store.getEnableProjectCreation(),
			enableSectionCreation: store.getEnableSectionCreation(),
			enableLabelCreation: store.getEnableLabelCreation(),
			enableDynamicProjectCreation: store.getEnableDynamicProjectCreation(),
			enableDynamicSectionCreation: store.getEnableDynamicSectionCreation(),
			enableDynamicLabelCreation: store.getEnableDynamicLabelCreation(),
		};
	}, []);

	const { setProjects, setSections, setSelectedProjectId, setSelectedSectionId, setProjectsHierarchy, setSelectedProjectLabel, setSelectedSectionLabel,
		setEnableProjectCreation,
		setEnableSectionCreation,
		setEnableLabelCreation,
		setEnableDynamicProjectCreation,
		setEnableDynamicSectionCreation,
		setEnableDynamicLabelCreation
	} = useDispatch(settingsStore);

	const {
		register,
		handleSubmit,
		setValue,
		clearErrors,
		control,
		setError,
		formState: { errors },
	} = useForm({
		defaultValues: {
			projectId: selectedProjectId,
			sectionId: selectedSectionId,
			projectLabel: selectedProjectLabel,
			sectionLabel: selectedSectionLabel,
			enableProjectCreation: enableProjectCreation,
			enableSectionCreation: enableSectionCreation,
			enableLabelCreation: enableLabelCreation,
			enableDynamicProjectCreation: enableDynamicProjectCreation,
			enableDynamicSectionCreation: enableDynamicSectionCreation,
			enableDynamicLabelCreation: enableDynamicLabelCreation,
		}
	});

	const formValues = useWatch({ control });

	/**
	 * Get the current projects.
	 *
	 * @param {object} options The options to pass to the command.
	 */
	const getCurrentProjects = ( options = {} ) => {
		// Fetch projects from Todoist
		SendCommand('gf_hey_todos_get_todoist_projects', {
			nonce: gforms_hey_todos_admin_settings_strings.ajax_nonce,
			selectedProjectId: selectedProjectId,
			bustCache: options?.bustCache || false,
		})
			.then(response => {
				const { data, success } = response.data;
				if (success) {
					const { projects: newProjects, sections: newSections, selectedProjectId: newSelectedProjectId } = data;
					
					// Build and set hierarchy
					setProjectsHierarchy(newProjects);
					setProjects(newProjects);
					setSections(newSelectedProjectId, newSections);

					// Check if currently selected project exists in fetched data
					if (selectedProjectId) {
						const projectExists = newProjects.some(project => project.project_id === selectedProjectId);
						if (!projectExists) {
							setValue('projectId', '');
							setValue('sectionId', '');
							setSections(newSelectedProjectId, []);
							setSelectedProjectId('');
							setSelectedSectionId('');
						} else {
							setValue('projectId', newSelectedProjectId);
							setSelectedProjectId(newSelectedProjectId);

							// Check if currently selected section exists in fetched data
							if (selectedSectionId) {
								const sectionExists = newSections.some(section => section.section_id === selectedSectionId);
								if (!sectionExists) {
									setValue('sectionId', '');
									setSelectedSectionId('');
								}
							}
						}
					}
				} else {
					setError('projectId', {
						message: data,
					});
				}
			})
			.catch(error => {
				setError('projectId', {
					message: error.message,
				});
			})
			.finally(() => {
				setLoading(false);
				setProjectsLoading(false);
			});
	};

	/**
	 * Get the current sections.
	 */
	const getCurrentSections = () => {
		if (selectedProjectId) {
			if ('' === selectedProjectId) {
				setSections([]);
				return;
			}
			setSectionsLoading(true);
			// Fetch sections for the selected project
			SendCommand('gf_hey_todos_get_todoist_sections', { nonce: gforms_hey_todos_admin_settings_strings.ajax_nonce, projectId: selectedProjectId })
				.then(response => {
					const { data, success } = response.data;
					if (success) {
						setSections(selectedProjectId, data);
					} else {
						setError('sectionId', {
							message: data,
						});
					}
				})
				.catch(error => {
					setError('sectionId', {
						message: error.message,
					});
				})
				.then(() => {
					setSectionsLoading(false);
				});
		}
	}

	useEffect(() => {
		if ( projects.length === 0 ) {
			getCurrentProjects();
		} else {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		// eslint-disable-next-line no-undef
		gform_initialize_tooltips();
	}, [loading, projectsLoading, sectionsLoading]);

	useEffect(() => {
		if ( loading ) {
			return;
		}
		getCurrentSections();
	}, [selectedProjectId]);

	const onSubmit = (inputData) => {
		setSaving(true);
		setErrorMessage(false);

		const projectLabel = projects.find(project => project.id === inputData.projectId)?.name;
		const sectionsForProject = getSectionsForProject(inputData.projectId);
		const sectionLabel = sectionsForProject.find(section => section.id === inputData.sectionId)?.name;

		SendCommand('gf_hey_todos_save_todoist_defaults', {
			nonce: gforms_hey_todos_admin_settings_strings.save_defaults_nonce,
			projectId: inputData.projectId,
			sectionId: inputData.sectionId,
			projectLabel: projectLabel,
			sectionLabel: sectionLabel,
			enableProjectCreation: inputData.enableProjectCreation,
			enableSectionCreation: inputData.enableSectionCreation,
			enableLabelCreation: inputData.enableLabelCreation,
			enableDynamicProjectCreation: inputData.enableDynamicProjectCreation,
			enableDynamicSectionCreation: inputData.enableDynamicSectionCreation,
			enableDynamicLabelCreation: inputData.enableDynamicLabelCreation,
		})
			.then((response) => {
				const { data, success } = response.data;
				if (success) {
					setEnableProjectCreation(inputData.enableProjectCreation);
					setEnableSectionCreation(inputData.enableSectionCreation);
					setEnableLabelCreation(inputData.enableLabelCreation);
					setEnableDynamicProjectCreation(inputData.enableDynamicProjectCreation);
					setEnableDynamicSectionCreation(inputData.enableDynamicSectionCreation);
					setEnableDynamicLabelCreation(inputData.enableDynamicLabelCreation);
					if ( inputData.projectId ) {
						setSelectedProjectId(inputData.projectId);
					}
					if ( inputData.sectionId ) {
						setSelectedSectionId(inputData.sectionId);
					}
					setIsSaved(true);
					setTimeout(() => {
						setIsSaved(false);
					}, 3500);
				} else {
					setIsSaved(false);
					setErrorMessage(data.error);
				}
			})
			.catch((response) => {
				setSaving(false);
				setIsSaved(false);
			})
			.then((response) => {
				setSaving(false);
			});
	};

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
				{__('Save Todoist Settings', 'gravity-forms-google-analytics-event-tracking')} <ArrowRight />
			</>
		);
	};

	/**
	 * Refresh the projects.
	 */
	const onRefreshProjects = () => {
		setProjectsLoading(true);
		getCurrentProjects( { 'bustCache': true } );
	};

	/**
	 * Refresh the sections.
	 */
	const onRefreshSections = () => {
		getCurrentSections();
	};

	const getProjects = () => {
		if (loading) {
			return (
				<Alerts
					message={__('Loading Todoist Projects...', 'gravity-forms-google-analytics-event-tracking')}
					alertType="info"
					className="info-loading"
				/>
			);
		}
		if (projects.length === 0) {
			return (
				<div className="heytodos-gform-settings-select-alert-container">
					<Alerts
						message={__('No projects found. A project is required to set defaults. Please refresh to fetch new projects.', 'gravity-forms-google-analytics-event-tracking')}
						alertType="error"
					/>
					<div className="refresh-settings-container">
						<RefreshButton
							onClick={onRefreshProjects}
							loading={projectsLoading}
						/>
					</div>
				</div>
			);
		}

		return (
			<div className="heytodos-gform-settings-dynamic-select-container">
				<Controller
					name={'projectId'}
					control={control}
					rules={{ required: true }}
					render={({ field: { onChange, value } }) => (
						<div className="heytodos-gform-settings-tree-select-container">
							<div className="heytodos-gform-settings-tree-select-container-inner">
								<TreeSelect
									label={__('Select a Default Project (Optional)', 'gravity-forms-google-analytics-event-tracking')}
									noOptionLabel={__('Select a project', 'gravity-forms-google-analytics-event-tracking')}
									onChange={(newValue) => {
										clearErrors( 'projectId' );
										setSelectedProjectId(newValue);
										onChange(newValue);
									}}
									selectedId={value}
									tree={projectsHierarchy}
									className="heytodos-gform-settings-tree-select"
									key={projectKey}
									disabled={ projectsLoading || sectionsLoading }
								/>
							</div>
							<div className="heytodos-gform-settings-tree-select-container-buttons">
								<RefreshButton
									onClick={ () => {
										clearErrors( 'projectId' );
										onRefreshProjects();
									}}
									loading={projectsLoading}
									disabled={ projectsLoading || sectionsLoading }
								/>
							</div>
							<div className="heytodos-gform-settings-tree-select-container-buttons">
								<AddButton
									onClick={() => {
										setIsAddProjectModalOpen(true);
									}}
								label={__('Add Project', 'gravity-forms-google-analytics-event-tracking')}
									loading={ false }
									disabled={ projectsLoading || sectionsLoading }
								/>
							</div>
						</div>
					)}
				/>
				{
					errors?.projectId && (
						<Alerts
							message={errors?.projectId?.message}
							alertType="error"
						/>
					)
				}
			</div>
		);
	};

	/**
	 * Get the sections for the form.
	 *
	 * @param {string} projectId The project ID.
	 * @returns {React.ReactNode} The sections for the form.
	 */
	const getFormSections = ( projectId ) => {
		if ( sections.hasOwnProperty( projectId ) ) {
			return (
				<>
					<option value="">{__('Select a section', 'gravity-forms-google-analytics-event-tracking')}</option>
				{sections[ projectId ].map(section => (
						<option key={section.section_id} value={section.section_id} selected={selectedSectionId === section.section_id}>{section.name}</option>
					))}
				</>
			);
		}
		return null;
	}

	/**
	 * Get the sections.
	 */
	const getSections = () => {
		// If there is no project selected, don't show the sections
		if (selectedProjectId === '') {
			return null;
		}
		if (sectionsLoading) {
			return (
				<>
					<Alerts
						message={__('Loading Todoist Sections...', 'gravity-forms-google-analytics-event-tracking')}
						alertType="info"
						className="info-loading"
					/>
				</>
			);
		}
		if (sections.length === 0) {
			return (
				<div className="heytodos-gform-settings-select-alert-container">
					<Alerts
						message={__('No sections found in this project. Please refresh to fetch sections.', 'gravity-forms-google-analytics-event-tracking')}
						alertType="info"
						className="info-alt"
					>
						<div className="refresh-settings-container">
							<RefreshButton
								onClick={() => {
									onRefreshSections();
								}}
								loading={sectionsLoading}
								label={__('Fetch New Sections', 'gravity-forms-google-analytics-event-tracking')}
								disabled={ projectsLoading || sectionsLoading }
							/>
							<AddButton
								onClick={() => {
									setIsAddSectionModalOpen(true);
								}}
								label={__('Add Section', 'gravity-forms-google-analytics-event-tracking')}
								loading={false}
								disabled={ projectsLoading || sectionsLoading }
							/>
						</div>
					</Alerts>
				</div>
			);
		}
		if (!sectionsLoading) {
			return (
				<>
					<div className="heytodos-gform-settings-dynamic-select-container">
						<Controller
							name={'sectionId'}
							control={control}
							rules={{
								required: false,
							}}
							render={({ field: { onChange, value } }) => (
								<FormSelect
									label={__('Select a Default Section (Optional)', 'gravity-forms-google-analytics-event-tracking')}
									tooltip={__('<h6>Section</h6>Select the section within the project where tasks will be created.', 'gravity-forms-google-analytics-event-tracking')}
									required={false}
									error={errors?.sectionId && errors?.sectionId.message}
									disabled={sectionsLoading || projectsLoading}
									onChange={ ( newValue ) => {
										clearErrors( 'sectionId' );
										onChange(newValue);
									}}
									options={
										<>
											{ getFormSections( selectedProjectId ) }
										</>
									}
									refreshButton={
										<RefreshButton
											onClick={() => {
												clearErrors( 'sectionId' );
												onRefreshSections();

											}}
											loading={sectionsLoading}
											label={__('Fetch New Sections', 'gravity-forms-google-analytics-event-tracking')}
											disabled={ projectsLoading || sectionsLoading }
										/>
									}
									addButton={
										<AddButton
											onClick={() => {
												setIsAddSectionModalOpen(true);
											}}
											label={__('Add Section', 'gravity-forms-google-analytics-event-tracking')}
											loading={ false }
											disabled={ projectsLoading || sectionsLoading }
										/>
									}
								/>
							)}
						/>
						{
							errors?.sectionId && (
								<Alerts
									message={errors?.sectionId?.message}
									alertType="error"
								/>
							)
						}
					</div>
				</>
			);
		}
		return null;
	}

	return (
		<>
			<Section
				title={ __( 'Todoist Settings', 'gravity-forms-google-analytics-event-tracking' )}
				description={__('Select the Todoist project and section to use for new tasks. This will save some time when creating new feeds.', 'gravity-forms-google-analytics-event-tracking')}
			>
				{ hasProjectBeenAdded && (
					<Alerts
						message={__('Project added successfully.', 'gravity-forms-google-analytics-event-tracking')}
						alertType="success"
					/>
				)}
				{ hasSectionBeenAdded && (
					<Alerts
						message={__('Section added successfully.', 'gravity-forms-google-analytics-event-tracking')}
						alertType="success"
					/>
				)}
				{ isSaved && (
					<Alerts
						message={__('Settings saved successfully.', 'gravity-forms-google-analytics-event-tracking')}
						alertType="success"
					/>
				)}
				<form
					id="gform-settings"
					onSubmit={handleSubmit(onSubmit)}
				>
					<SettingsRow>
						{getProjects()}
					</SettingsRow>
					<SettingsRow>
						{getSections()}
					</SettingsRow>
					<SettingsRow>
						<Controller
							name="enableProjectCreation"
							control={control}
							render={({ field: { onChange, value } }) => (
								<ToggleControl
									label={__('Allow Project Creation', 'gravity-forms-google-analytics-event-tracking')}
									checked={value}
									onChange={onChange}
									help={__('Enable this to allow others to create projects on the feed screen.', 'gravity-forms-google-analytics-event-tracking')}
								/>
							)}
						/>
					</SettingsRow>
					<SettingsRow>
						<Controller
							name="enableSectionCreation"
							control={control}
							render={({ field: { onChange, value } }) => (
								<ToggleControl
									label={__('Allow Section Creation', 'gravity-forms-google-analytics-event-tracking')}
									checked={value}
									onChange={onChange}
									help={__('Enable this to allow others to create sections on the feed screen.', 'gravity-forms-google-analytics-event-tracking')}
								/>
							)}
						/>
					</SettingsRow>
					<SettingsRow>
						<Controller
							name="enableLabelCreation"
							control={control}
							render={({ field: { onChange, value } }) => (
								<ToggleControl
									label={__('Allow Label Creation', 'gravity-forms-google-analytics-event-tracking')}
									checked={value}
									onChange={onChange}
									help={__('Enable this to allow others to create labels on the feed screen.', 'gravity-forms-google-analytics-event-tracking')}
								/>
							)}
						/>
					</SettingsRow>
					<SettingsRow>
						<Controller
							name="enableDynamicProjectCreation"
							control={control}
							render={({ field: { onChange, value } }) => (
								<ToggleControl
									label={__('Allow Dynamic Project Creation', 'gravity-forms-google-analytics-event-tracking')}
									checked={value}
									onChange={onChange}
									help={__('Enable this to allow others to create projects dynamically on the feed screen.', 'gravity-forms-google-analytics-event-tracking')}
								/>
							)}
						/>
					</SettingsRow>
					<SettingsRow>
						<Controller
							name="enableDynamicSectionCreation"
							control={control}
							render={({ field: { onChange, value } }) => (
								<ToggleControl
									label={__('Allow Dynamic Section Creation', 'gravity-forms-google-analytics-event-tracking')}
									checked={value}
									onChange={onChange}
									help={__('Enable this to allow others to create sections dynamically on the feed screen.', 'gravity-forms-google-analytics-event-tracking')}
								/>
							)}
						/>
					</SettingsRow>
					<SettingsRow>
						<Controller
							name="enableDynamicLabelCreation"
							control={control}
							render={({ field: { onChange, value } }) => (
								<ToggleControl
									label={__('Allow Dynamic Label Creation', 'gravity-forms-google-analytics-event-tracking')}
									checked={value}
									onChange={onChange}
									help={__('Enable this to allow others to create labels dynamically on the feed screen.', 'gravity-forms-google-analytics-event-tracking')}
								/>
							)}
						/>
					</SettingsRow>
					<div className="heytodos-gform-settings-save-container">
						<button
							type="submit"
							id="gform-settings-save"
							name="gform-settings-save"
							value="save"
							className="primary button large"
							disabled={saving || projects.length === 0}
						>
							{getSaveText()}
						</button>
					</div>
				</form>
			</Section>

			{ isAddSectionModalOpen && (
				<AddSectionModal
					className="heytodos-add-section-modal 
					hey-todos-form-modal"
					title={__('Add a Section', 'gravity-forms-google-analytics-event-tracking')}
					onClose={() => setIsAddSectionModalOpen(false)}
					onRequestClose={() => setIsAddSectionModalOpen(false)}
					projectId={selectedProjectId}
					focusOnMount={ true }
					onSectionAdded={(
						newSections,
						newSection
					) => {
						setIsAddSectionModalOpen(false);
						setHasSectionBeenAdded(true);
						setSections(selectedProjectId, newSections);
						setValue('sectionId', newSection.section_id );
						setSelectedSectionId( newSection.section_id );

						setTimeout(() => {
							setHasSectionBeenAdded(false);
						}, 3500);
					}}
					isDismissible={ true }
					shouldCloseOnClickOutside={ false }
					shouldCloseOnEsc={ true }
					size="large"
				/>
			)}
			{ isAddProjectModalOpen && (
				<AddProjectModal
					className="heytodos-add-project-modal 
					hey-todos-form-modal"
					title={__('Add a Project', 
					'gravity-forms-google-analytics-event-tracking')}
					onClose={
						() => {
							setIsAddProjectModalOpen
							(false);
						}
					}
					onRequestClose={
						( requestClose ) => {
							setIsAddProjectModalOpen(false);
							return ! saving && requestClose;
						}
					}
					onProjectAdded={
						( newProjects, newProject ) => {
							setIsAddProjectModalOpen(false);
							setHasProjectBeenAdded(true);
							setProjectsHierarchy( newProjects );
							setValue('projectId', newProject.project_id );
							setSelectedProjectId( newProject.project_id );
							projectKey++;

							setTimeout(() => {
								setHasProjectBeenAdded(false);
							}, 3500);
						}
					}
					projects={projects}
					focusOnMount={ true }
					isDismissible={ true }
					shouldCloseOnClickOutside={ false }
					shouldCloseOnEsc={ true }
					size="large"

				/>
			)}
		</>
	);
};

export default SettingsDefaultsInterface;
