import { Button } from '@wordpress/components';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowsRotate } from '@fortawesome/free-solid-svg-icons/faArrowsRotate';
import { faPlus } from '@fortawesome/free-solid-svg-icons/faPlus';
import Spinner from '../Spinner';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';

const RefreshButton = ( props ) => (
	<Button
		label={props.label || __('Fetch New Projects', 'gravity-forms-google-analytics-event-tracking')}
		icon={!props.loading ? <FontAwesomeIcon icon={faArrowsRotate} /> : <Spinner />}
		onClick={props.onClick}
		isBusy={props.loading}
		className={classnames('button secondary', { 'prefix__fa-spinner': props.loading })}
		iconPosition="right"
		showTooltip={true}
		tooltip={props.label || __('Fetch New Projects', 'gravity-forms-google-analytics-event-tracking')}
		{...props}
	/>
);

const AddButton = ( props ) => (
	<Button
		label={props.label || __('Add Project', 'gravity-forms-google-analytics-event-tracking')}
		icon={<FontAwesomeIcon icon={faPlus} />}
		iconPosition="left"
		onClick={props.onClick}
		showTooltip={true}
		tooltip={props.label || __('Add Project', 'gravity-forms-google-analytics-event-tracking')}
		className="button secondary"
		{...props}
	/>
);

export { RefreshButton, AddButton };