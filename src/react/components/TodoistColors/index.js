import { useState, useRef } from 'react';
import {
	Button,
	Popover,
	Flex,
	FlexItem,
	BaseControl,
	__experimentalGrid as Grid,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const colors = [
	{ id: 30, name: 'berry_red', hex: '#b8256f' },
	{ id: 31, name: 'red', hex: '#db4035' },
	{ id: 32, name: 'orange', hex: '#ff9933' },
	{ id: 33, name: 'yellow', hex: '#fad000' },
	{ id: 34, name: 'olive_green', hex: '#afb83b' },
	{ id: 35, name: 'lime_green', hex: '#7ecc49' },
	{ id: 36, name: 'green', hex: '#299438' },
	{ id: 37, name: 'mint_green', hex: '#6accbc' },
	{ id: 38, name: 'teal', hex: '#158fad' },
	{ id: 39, name: 'sky_blue', hex: '#14aaf5' },
	{ id: 40, name: 'light_blue', hex: '#96c3eb' },
	{ id: 41, name: 'blue', hex: '#4073ff' },
	{ id: 42, name: 'grape', hex: '#884dff' },
	{ id: 43, name: 'violet', hex: '#af38eb' },
	{ id: 44, name: 'lavender', hex: '#eb96eb' },
	{ id: 45, name: 'magenta', hex: '#e05194' },
	{ id: 46, name: 'salmon', hex: '#ff8d85' },
	{ id: 47, name: 'charcoal', hex: '#808080' },
	{ id: 48, name: 'grey', hex: '#b8b8b8' },
	{ id: 49, name: 'taupe', hex: '#c9c9c9' }
];

const ColorSwatches = ({ selectedColor, onColorSelect }) => {
	return (
		<div className="swatch-container">
			<Grid gap={2} columns={3}>
				{colors.map((color) => (
					<div key={color.id} className="color-swatch" onClick={() => onColorSelect(color)}>
						<Button
							onClick={() => onColorSelect(color)}
							className={`color-swatch${selectedColor?.id === color.id ? ' is-selected' : ''}`}
							style={{ backgroundColor: color.hex }}
							label={__(`Color: ${color.name} - ${color.hex}`, 'gravity-forms-google-analytics-event-tracking')}
							aria-label={__(`Color: ${color.name}`, 'gravity-forms-google-analytics-event-tracking')}
						/>
					</div>
				))}
			</Grid>
		</div>
	);
};

const TodoistColorPicker = ({ colorName, onChange, label = '', help = '' }) => {
	const [isOpen, setIsOpen] = useState(false);
	const [selectedColor, setSelectedColor] = useState( colors.find(color => color.name === colorName) );
	const [buttonRef, setButtonRef] = useState( null );

	return (
		<>
			<BaseControl
				className="gravity-forms-google-analytics-event-tracking-color-picker"
				label={label || __('Color', 'gravity-forms-google-analytics-event-tracking')}
				help={help || ''}
			>
				<Button
					ref={setButtonRef}
					onClick={() => setIsOpen(!isOpen)}
					className="color-button"
					style={{ backgroundColor: selectedColor?.hex || '#fff' }}
					label={ __( 'Select a color', 'gravity-forms-google-analytics-event-tracking' ) }
					aria-label={ __( 'Select a color', 'gravity-forms-google-analytics-event-tracking' ) }
				/>
				{isOpen && (
					<Popover
						onClose={() => setIsOpen(false)}
						className="gravity-forms-google-analytics-event-tracking-color-picker-popover"
						placement="right-start"
						anchor={buttonRef}
						header={__('Select a color', 'gravity-forms-google-analytics-event-tracking')}
						offset={ 12 }
						noArrow={false}
						variant="toolbar"
					>
						<ColorSwatches
							selectedColor={ selectedColor }
							onColorSelect={(color) => {
								onChange(color.name);
								setSelectedColor(color);
								setIsOpen(false);
							}}
							/>
						</Popover>
					)}
				</BaseControl>
		</>
	);
};

export default TodoistColorPicker;