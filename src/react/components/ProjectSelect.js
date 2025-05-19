import { TreeSelect } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

const ProjectSelect = ({ value, onChange }) => {
    const { projectsHierarchy } = useSelect((select) => ({
        projectsHierarchy: select('dlxplugins/gravity-forms-google-analytics-event-tracking/settings').getProjectsHierarchy(),
    }));

    return (
        <TreeSelect
            label={__('Select Project', 'gravity-forms-google-analytics-event-tracking')}
            noOptionLabel={__('Select a project', 'gravity-forms-google-analytics-event-tracking')}
            onChange={onChange}
            selectedId={value}
            tree={projectsHierarchy}
        />
    );
};

export default ProjectSelect; 