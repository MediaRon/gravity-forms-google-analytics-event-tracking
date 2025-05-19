module.exports = {
	env: {
		browser: true,
		es2021: true,
	},
	globals: {
		ajaxurl: 'readonly',
		wpacAdminAdvanced: 'readonly',
		wpacAdminCallbacks: 'readonly',
		wpacAdminHome: 'readonly',
		wpacAdminAppearance: 'readonly',
		wpacAdminLabels: 'readonly',
		wpacAdminSelectors: 'readonly',
		wpacMenuHelper: 'readonly',
		wpacAdminLazyLoad: 'readonly',
		wpacAdminIntegrations: 'readonly',
	},
	extends: [
		'eslint:recommended',
		'plugin:react/recommended',
		'plugin:@wordpress/eslint-plugin/recommended-with-formatting',
	],
	overrides: [
		{
			env: {
				node: true,
			},
			files: [
				'.eslintrc.{js,cjs}',
			],
			parserOptions: {
				sourceType: 'script',
			},
		},
	],
	parserOptions: {
		ecmaVersion: 'latest',
		sourceType: 'module',
	},
	plugins: [
		'react',
	],
	rules: {
	},
};
