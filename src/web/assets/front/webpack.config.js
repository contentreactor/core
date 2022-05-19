const path = require('path')
const exec = require('child_process').exec
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin')
const { CleanWebpackPlugin } = require('clean-webpack-plugin')
const HtmlWebpackPlugin = require('html-webpack-plugin')
const sass = require('node-sass')
const CopyWebpackPlugin = require('copy-webpack-plugin')
const { readdir } = require('fs').promises
// const prod = true
const mode = typeof prod !== 'undefined' ? 'production' : 'development'

const configPromise = new Promise((resolve, reject) => {
	async function* getFiles(dir) {
		const dirents = await readdir(dir, {
			withFileTypes: true,
		})
		for (const dirent of dirents) {
			const res = path.resolve(dir, dirent.name)
			if (dirent.isDirectory()) {
				yield* getFiles(res)
			} else {
				if (path.extname(res) == '.js') yield res
			}
		}
	}
	async function files() {
		const arr = {}
		for await (const x of getFiles('./src')) {
			arr[path.basename(x, '.js')] = x.replace(/\\/g, '/').split('front').reverse()[0]
		}
		return await arr
	}

	resolve({
		mode: mode,
		stats: {
			children: true,
		},
		entry: files,
		output: {
			filename: 'js/[name].js',
			path: path.resolve(__dirname, 'dist')
		},
		plugins: [
			new CleanWebpackPlugin({
				root: process.cwd(),
				verbose: true,
				dry: false,
				cleanStaleWebpackAssets: false,
				cleanOnceBeforeBuildPatterns: [
					'**/*',
					'!.gitignore',
				],
			}),
			new MiniCssExtractPlugin({
				filename: '[name].css',
			}),
			new HtmlWebpackPlugin({
				title: 'Output Management',
				title: 'Caching',
			}),
			new CopyWebpackPlugin({
				patterns: [
					{
						from: 'src/fonts/**/*',
						to: 'fonts/[name][ext]',
						noErrorOnMissing: true
					}
				]
			}),
		],
		resolve: {
			extensions: ['.js'],
		},
		module: {
			rules: [
				{
					test: /\.(js)$/,
					exclude: /node_modules/,
					use: [
						{
							loader: 'babel-loader',
						},
					],
				},
				{
					test: /\.(css|scss)$/,
					use: [
						MiniCssExtractPlugin.loader,
						'css-loader',
						'sass-loader',
						// 'postcss-loader',
					],
				},
				{
					test: /\.svg$/,
					use: [
						{
							loader: 'babel-loader',
						},
					],
				},
				{
					test: /\.(ttf|eot|svg|woff(2)?)(\?[a-z0-9=&.]+)?$/,
					use: [
						{
							loader: 'file-loader?limit=100000',
						},
					],
				},
				{
					test: /\.(jpg|png|gif)$/,
					use: [
						{
							loader: 'url-loader',
							options: {
								limit: 65536,
								fallback: 'file-loader',
							},
						},
					],
				},
			],
		},
		optimization: {
			minimizer: [new CssMinimizerPlugin()],
		},
	})
})

module.exports = configPromise
