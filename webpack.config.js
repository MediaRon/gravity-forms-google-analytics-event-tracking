const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const DependencyExtractionPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
module.exports = (env) => {
	return [
		{
			mode: env.mode,
			devtool: 'production' === env.mode ? false : 'source-map',
			output: {
				filename: '[name].js',
				sourceMapFilename: '[file].map[query]',
				clean: true,
			},
			entry: {
				'gfgaet-install-migrator': ['./src/react/screens/InstallMigrator/index.js'],
				'gfgaet-css': ['./src/scss/admin.scss'],
			},
			module: {
				rules: [
					{
						test: /\.(js|jsx)$/,
						loader: 'babel-loader',
						exclude: /(node_modules|bower_components)/,
						options: {
							presets: ['@babel/preset-env', '@babel/preset-react'],
							plugins: [
								'@babel/plugin-transform-class-properties',
								'@babel/plugin-transform-arrow-functions',
							],
						},
					},
					{
						test: /\.scss$/,
						exclude: /(node_modules|bower_components)/,
						use: [
							{
								loader: MiniCssExtractPlugin.loader,
							},
							{
								loader: 'css-loader',
								options: {
									sourceMap: true,
								},
							},
							{
								loader: 'resolve-url-loader',
							},
							{
								loader: 'sass-loader',
								options: {
									sourceMap: true,
								},
							},
						],
					},
					{
						test: /\.css$/,
						use: [
							{
								loader: MiniCssExtractPlugin.loader,
							},
							{
								loader: 'css-loader',
								options: {
									sourceMap: true,
								},
							},
							'sass-loader',
						],
					},
					{
						test: /\.(woff2?|ttf|otf|eot|svg)$/,
						include: [path.resolve(__dirname, 'fonts')],
						exclude: /(node_modules|bower_components)/,
						type: 'asset/resource',
					},
				],
			},
			plugins: [new RemoveEmptyScriptsPlugin(), new MiniCssExtractPlugin(), new DependencyExtractionPlugin()],
		},
	];
};
