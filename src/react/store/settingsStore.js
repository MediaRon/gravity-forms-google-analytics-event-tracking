import { createReduxStore, register, select } from '@wordpress/data';
import { buildProjectHierarchy } from '../utils/projectUtils';

/**
 * Format projects data for the store.
 * 
 * @param {Array} projects Array of project objects from the database.
 * @return {Array} Formatted projects array.
 */
const formatProjects = ( projects ) => {
	return projects.map( ( project ) => {
		return {
			...project,
			// Convert string values to proper types.
			project_id: project.project_id.toString(),
			parent_id: project.parent_id === '' ? null : project.parent_id.toString(),
			collapsed: project.collapsed === '1',
			is_shared: project.is_shared === '1',
			is_favorite: project.is_favorite === '1',
			inbox_project: project.inbox_project === '1',
			team_inbox: project.team_inbox === '1',
		};
	} );
};

const DEFAULT_STATE = {
	showSharedProjects: false,
	showPersonalProjects: true,
	projects: formatProjects( gforms_hey_todos_admin_settings_strings.projects ),
	sections: gforms_hey_todos_admin_settings_strings.sections,
	connected: gforms_hey_todos_admin_settings_strings.is_connected,
	license: gforms_hey_todos_admin_settings_strings.license_data.license,
	licenseData: gforms_hey_todos_admin_settings_strings.license_data,
	validLicense: gforms_hey_todos_admin_settings_strings.license_data.valid,
	selectedProjectId: gforms_hey_todos_admin_settings_strings.defaults.project_id,
	selectedSectionId: gforms_hey_todos_admin_settings_strings.defaults.section_id,
	selectedProjectLabel: gforms_hey_todos_admin_settings_strings.defaults.project_label,
	selectedSectionLabel: gforms_hey_todos_admin_settings_strings.defaults.section_label,
	isTeamAccount: gforms_hey_todos_admin_settings_strings.is_team_account,
	projectsHierarchy: buildProjectHierarchy( formatProjects( gforms_hey_todos_admin_settings_strings.projects ) ),
	enableProjectCreation: gforms_hey_todos_admin_settings_strings.enable_project_creation || false,
	enableSectionCreation: gforms_hey_todos_admin_settings_strings.enable_section_creation || false,
	enableLabelCreation: gforms_hey_todos_admin_settings_strings.enable_label_creation || false,
	enableDynamicProjectCreation: gforms_hey_todos_admin_settings_strings.enable_dynamic_project_creation || false,
	enableDynamicSectionCreation: gforms_hey_todos_admin_settings_strings.enable_dynamic_section_creation || false,
	enableDynamicLabelCreation: gforms_hey_todos_admin_settings_strings.enable_dynamic_label_creation || false,
	projectCount: gforms_hey_todos_admin_settings_strings.project_count,
	collaboratorCount: gforms_hey_todos_admin_settings_strings.collaborator_count,
	labelCount: gforms_hey_todos_admin_settings_strings.label_count,
	sectionCount: gforms_hey_todos_admin_settings_strings.section_count,
	maxProjects: gforms_hey_todos_admin_settings_strings.max_projects,
	maxCollaborators: gforms_hey_todos_admin_settings_strings.max_collaborators,
	maxLabels: gforms_hey_todos_admin_settings_strings.max_labels,
	maxSections: gforms_hey_todos_admin_settings_strings.max_sections,
};

const actions = {
	setShowSharedProjects( showSharedProjects ) {
		return {
			type: 'SET_SHOW_SHARED_PROJECTS',
			showSharedProjects,
		};
	},
	setShowPersonalProjects( showPersonalProjects ) {
		return {
			type: 'SET_SHOW_PERSONAL_PROJECTS',
			showPersonalProjects,
		};
	},
	setProjects( projects ) {
		return {
			type: 'SET_PROJECTS',
			projects,
		};
	},
	/**
	 * Set the sections for a project.
	 *
	 * @param {string} projectId The ID of the project.
	 * @param {array} sections The sections to set.
	 */
	setSections( projectId, sections ) {
		return {
			type: 'SET_SECTIONS',
			projectId,
			sections,
		};
	},
	setConnected( connected ) {
		return {
			type: 'SET_CONNECTED',
			connected,
		};
	},
	setLicenseKey( license ) {
		return {
			type: 'SET_LICENSE_KEY',
			license,
		};
	},
	setLicenseData( licenseData ) {
		return {
			type: 'SET_LICENSE_DATA',
			licenseData,
		};
	},
	setValidLicense( validLicense ) {
		return {
			type: 'SET_VALID_LICENSE',
			validLicense,
		};
	},
	setSelectedProjectId( selectedProjectId ) {
		return {
			type: 'SET_SELECTED_PROJECT_ID',
			selectedProjectId,
		};
	},
	setSelectedSectionId( selectedSectionId ) {
		return {
			type: 'SET_SELECTED_SECTION_ID',
			selectedSectionId,
		};
	},
	setSelectedProjectLabel( selectedProjectLabel ) {
		return {
			type: 'SET_SELECTED_PROJECT_LABEL',
			selectedProjectLabel,
		};
	},
	setSelectedSectionLabel( selectedSectionLabel ) {
		return {
			type: 'SET_SELECTED_SECTION_LABEL',
			selectedSectionLabel,
		};
	},
	setProjectsHierarchy(projects) {
		return {
			type: 'SET_PROJECTS_HIERARCHY',
			projects,
		};
	},
	setEnableProjectCreation(enableProjectCreation) {
		return {
			type: 'SET_ENABLE_PROJECT_CREATION',
			enableProjectCreation,
		};
	},
	setEnableSectionCreation(enableSectionCreation) {
		return {
			type: 'SET_ENABLE_SECTION_CREATION',
			enableSectionCreation,
		};
	},
	setEnableLabelCreation(enableLabelCreation) {
		return {
			type: 'SET_ENABLE_LABEL_CREATION',
			enableLabelCreation,
		};
	},
	setEnableDynamicProjectCreation(enableDynamicProjectCreation) {
		return {
			type: 'SET_ENABLE_DYNAMIC_PROJECT_CREATION',
			enableDynamicProjectCreation,
		};
	},
	setEnableDynamicSectionCreation(enableDynamicSectionCreation) {
		return {
			type: 'SET_ENABLE_DYNAMIC_SECTION_CREATION',
			enableDynamicSectionCreation,
		};
	},
	setEnableDynamicLabelCreation(enableDynamicLabelCreation) {
		return {
			type: 'SET_ENABLE_DYNAMIC_LABEL_CREATION',
			enableDynamicLabelCreation,
		};
	},
	setProjectCount(projectCount) {
		return {
			type: 'SET_PROJECT_COUNT',
			projectCount,
		};
	},
	setCollaboratorCount(collaboratorCount) {
		return {
			type: 'SET_COLLABORATOR_COUNT',
			collaboratorCount,
		};
	},
	setLabelCount(labelCount) {
		return {
			type: 'SET_LABEL_COUNT',
			labelCount,
		};
	},
	setSectionCount(sectionCount) {
		return {
			type: 'SET_SECTION_COUNT',
			sectionCount,
		};
	}
};

const STORE_NAME = 'dlxplugins/gf-hey-todos/settings';

const settingsStore = createReduxStore( STORE_NAME, {
	reducer( state = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'SET_SHOW_SHARED_PROJECTS':
				return {
					...state,
					showSharedProjects: action.showSharedProjects,
				};
			case 'SET_SHOW_PERSONAL_PROJECTS':
				return {
					...state,
					showPersonalProjects: action.showPersonalProjects,
				};
			case 'SET_PROJECTS':
				return {
					...state,
					projects: action.projects,
				};
			case 'SET_SECTIONS':
				// Overwrite section array for project ID and new sections.
				const newSections = state.sections;
				newSections[ action.projectId ] = action.sections;	
					return {
						...state,
						sections: newSections,
					};
			case 'SET_CONNECTED':
				return {
					...state,
					connected: action.connected,
				};
			case 'SET_LICENSE_KEY':
				return {
					...state,
					license: action.license,
				};
			case 'SET_LICENSE_DATA':
				return {
					...state,
					licenseData: action.licenseData,
				};
			case 'SET_VALID_LICENSE':
				return {
					...state,
					validLicense: action.validLicense,
				};
			case 'SET_SELECTED_PROJECT_ID':
				return {
					...state,
					selectedProjectId: action.selectedProjectId,
				};
			case 'SET_SELECTED_SECTION_ID':
				return {
					...state,
					selectedSectionId: action.selectedSectionId,
				};
			case 'SET_SELECTED_PROJECT_LABEL':
				return {
					...state,
					selectedProjectLabel: action.selectedProjectLabel,
				};
			case 'SET_SELECTED_SECTION_LABEL':
				return {
					...state,
					selectedSectionLabel: action.selectedSectionLabel,
				};
			case 'SET_PROJECTS_HIERARCHY':
				return {
					...state,
					projectsHierarchy: buildProjectHierarchy(action.projects),
					projects: action.projects,
				};
			case 'SET_ENABLE_PROJECT_CREATION':
				return {
					...state,
					enableProjectCreation: action.enableProjectCreation,
				};
			case 'SET_ENABLE_SECTION_CREATION':
				return {
					...state,
					enableSectionCreation: action.enableSectionCreation,
				};
			case 'SET_ENABLE_LABEL_CREATION':
				return {
					...state,
					enableLabelCreation: action.enableLabelCreation,
				};
			case 'SET_ENABLE_DYNAMIC_PROJECT_CREATION':
				return {
					...state,
					enableDynamicProjectCreation: action.enableDynamicProjectCreation,
				};
			case 'SET_ENABLE_DYNAMIC_SECTION_CREATION':
				return {
					...state,
					enableDynamicSectionCreation: action.enableDynamicSectionCreation,
				};
			case 'SET_ENABLE_DYNAMIC_LABEL_CREATION':
				return {
					...state,
					enableDynamicLabelCreation: action.enableDynamicLabelCreation,
				};
			case 'SET_PROJECT_COUNT':
				return {
					...state,
					projectCount: action.projectCount,
				};
			case 'SET_COLLABORATOR_COUNT':
				return {
					...state,
					collaboratorCount: action.collaboratorCount,
				};
			case 'SET_LABEL_COUNT':
				return {
					...state,
					labelCount: action.labelCount,
				};
			case 'SET_SECTION_COUNT':
				return {
					...state,
					sectionCount: action.sectionCount,
				};
			default:
				return state;
		}
	},
	actions,
	selectors: {
		getProjects( state ) {
			return state.projects;
		},
		getSections( state ) {
			return state.sections;
		},
		getSectionsForProject( state, projectId ) {
			// Check if project ID exists in sections array.
			if ( state.sections.hasOwnProperty( projectId ) ) {
				return state.sections[ projectId ];
			}
			return [];
		},
		getConnected( state ) {
			return state.connected;
		},
		getLicenseKey( state ) {
			return state.license;
		},
		getLicenseData( state ) {
			return state.licenseData;
		},
		getValidLicense( state ) {
			return state.validLicense;
		},
		getSelectedProjectId( state ) {
			return state.selectedProjectId;
		},
		getSelectedSectionId( state ) {
			return state.selectedSectionId;
		},
		getIsTeamAccount( state ) {
			return state.isTeamAccount;
		},
		getIsConnected( state ) {
			return state.isConnected;
		},
		getSelectedProjectLabel( state ) {
			return state.selectedProjectLabel;
		},
		getSelectedSectionLabel( state ) {
			return state.selectedSectionLabel;
		},
		getProjectsHierarchy(state) {
			return state.projectsHierarchy;
		},
		getProjectParent(state, projectId) {
			// Traverse hierarchy to find parent
			const findParent = (projects) => {
				for (const project of projects) {
					if (project.children?.some(child => child.id === projectId)) {
						return project;
					}
					if (project.children) {
						const parent = findParent(project.children);
						if (parent) return parent;
					}
				}
				return null;
			};
			return findParent(state.projectsHierarchy);
		},
		getEnableProjectCreation(state) {
			return state.enableProjectCreation;
		},
		getEnableSectionCreation(state) {
			return state.enableSectionCreation;
		},
		getEnableLabelCreation(state) {
			return state.enableLabelCreation;
		},
		getEnableDynamicProjectCreation(state) {
			return state.enableDynamicProjectCreation;
		},
		getEnableDynamicSectionCreation(state) {
			return state.enableDynamicSectionCreation;
		},
		getEnableDynamicLabelCreation(state) {
			return state.enableDynamicLabelCreation;
		},
		getProjectCount(state) {
			return state.projectCount;
		},
		getCollaboratorCount(state) {
			return state.collaboratorCount;
		},
		getLabelCount(state) {
			return state.labelCount;
		},
		getSectionCount(state) {
			return state.sectionCount;
		},
		getMaxProjects(state) {
			return state.maxProjects;
		},
		getMaxCollaborators(state) {
			return state.maxCollaborators;
		},
		getMaxLabels(state) {
			return state.maxLabels;
		},
		getMaxSections(state) {
			return state.maxSections;
		},
	},
} );

// Add to global namespace if it doesn't exist
window.GFHeyTodos = window.GFHeyTodos || {};
window.GFHeyTodos.stores = window.GFHeyTodos.stores || {};
window.GFHeyTodos.stores.settings = settingsStore;	

// Only register if not already registered
if (!select(STORE_NAME)) {
	register(settingsStore);
}

export default settingsStore;
