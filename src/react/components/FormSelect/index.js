import classnames from 'classnames';
const FormSelect = ({ className, refreshButton = null, addButton = null, label, id, tooltip, required, options, onChange, error, disabled }) => {
	const gformSelectClasses = classnames(
		'gform-settings-field gform-settings-field__select',
		className
	);

	const inputContainerClasses = classnames(
		'gform-settings-input__container',
		{
			'gform-settings-input__container--with-add-button': addButton,
		}
	);

	return (
		<div className={gformSelectClasses}>
			<div className="gform-settings-field__header">
				<label className="gform-settings-label" htmlFor={id}>
					{label}
					{required && <span className="required">(Required)</span>}
				</label>
				{tooltip && (
					<button
						onClick={() => false}
						onKeyPress={() => false}
						className="gf_tooltip tooltip"
						aria-label={tooltip}
					>
						<i className="gform-icon gform-icon--question-mark" aria-hidden="true" />
					</button>
				)}
			</div>
			<span className={inputContainerClasses}>
				<select
					id={id}
					required={required}
					onChange={onChange}
					disabled={disabled}
				>
					{options}
				</select>
				{refreshButton && (
					<>
						{refreshButton}
					</>
				)}
				{addButton && (
					<>
						{addButton}
					</>
				)}
			</span>
			{error && (
				<div className="gform-settings-validation__error">
					{error}
				</div>
			)}
		</div>
	);
};

export default FormSelect;