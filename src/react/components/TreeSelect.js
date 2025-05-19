import { TreeSelect } from '@wordpress/components';

const ProjectTreeSelect = ({ tree, ...props }) => {
    return (
        <TreeSelect
            tree={ tree }
            label={__('Select Project', 'gravity-forms-google-analytics-event-tracking')}
            noOptionLabel={__('Select a project', 'gravity-forms-google-analytics-event-tracking')}
            {...props}
			className="projects-hierarchy-select"
        />
    );
};

export default ProjectTreeSelect; 