export const buildProjectHierarchy = (projects) => {
    const hierarchy = [];
	const projectMap = new Map();

	// First pass: Create a map of all projects
	projects.forEach( ( project ) => {
		project.id = project.project_id;
		projectMap.set( project.project_id, { ...project, children: [] } );
	} );

	// Second pass: Build hierarchy
	projects.forEach( ( project ) => {
		const projectWithChildren = projectMap.get( project.project_id );
		
		if ( project.parent_id === null || '' === project.parent_id ) {
			// This is a root level project
			hierarchy.push( projectWithChildren );
		} else {
			// This is a child project - add to parent's children array
			const parent = projectMap.get( project.parent_id );
			if ( parent ) {
				parent.children.push( projectWithChildren );
			}
		}
	} );
	return hierarchy;
}; 