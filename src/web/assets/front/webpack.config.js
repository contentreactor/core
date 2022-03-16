const path = require('path')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin')
const { CleanWebpackPlugin } = require('clean-webpack-plugin')
const HtmlWebpackPlugin = require('html-webpack-plugin')
const sass = require('node-sass')
const CopyWebpackPlugin = require('copy-webpack-plugin')
const glob = require('glob')
const tailwindcss = require('tailwindcss')
const { readdir } = require('fs').promises
const { webpack } = require('webpack')
// const mode = 'production'
const mode = 'development'

function basename(path) {
	let separator = '/'
	if (path.includes('\\')) separator = '\\'
	return path.split(separator).reverse()[0].split('.')[0];
}

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
			arr[basename(x).split('.')[0]] = "." + x.replace(/\\/g, '/').split('front').reverse()[0]
		}
		return await arr
	}

	resolve({
		mode: mode,
		stats: {
			children: true
		},
		entry: files,
		output: {
			filename: '[name].js',
			path: path.resolve(__dirname, 'dist'),
			clean: true
		},
		plugins: [
			new CleanWebpackPlugin({
				cleanStaleWebpackAssets: false,
				cleanOnceBeforeBuildPatterns: ['**/*'],
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
						from: 'src/scss/builder/blocks/',
						to: 'dist/builder/blocks/[name].css'
					},
					{
						from: 'src/scss/builder/components/',
						to: 'dist/builder/components/[name].css'
					},
					{
						from: 'src/app.css',
						to: 'dist/app.css'
					}
				].map(pattern => {
					return {
						from: path.resolve(__dirname, pattern.from),
						to: path.resolve(__dirname, pattern.to),
						transform(content, path) {
							const result = sass.renderSync({
								file: path,
								outputStyle: mode == 'production' ? 'compressed' : 'expanded'
							})

							return result.css.toString()
						}
					}
				})
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
					use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader', 'postcss-loader'],
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
			minimizer: [
				new CssMinimizerPlugin(),
			],
		},
	})
})

module.exports = configPromise
