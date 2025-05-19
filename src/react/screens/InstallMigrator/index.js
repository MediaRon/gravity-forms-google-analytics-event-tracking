import React from 'react';
import { createRoot } from 'react-dom/client';
import { Popover, SlotFillProvider } from '@wordpress/components';
import InstallMigrator from './install-migrator';

const container = document.getElementById('gfgaet-install-migration-notice');

const root = createRoot(container);
root.render(
	<React.StrictMode>
		<SlotFillProvider>
			<InstallMigrator />
			<Popover.Slot />
		</SlotFillProvider>
	</React.StrictMode>
);

