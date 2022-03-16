module.exports = {
	mode: 'jit',
	purge: {
		mode: 'all',
		enabled: true,
		content: [
			'../../../templates/**/*.twig',
			'./src/**/*.js'
		],
	},
	darkMode: false,
	theme: {
		extend: {
			colors: {
				'header-bg': {
					DEFAULT: '#161921'
				},
				'echo-blue': {
					DEFAULT: '#A9AFC3'
				},
				'white-test': {
					DEFAULT: '#FFFFFF'
				},
				'side-bg': {
					DEFAULT: '#080A12'
				},
				'body-color': {
					DEFAULT: '#111319'
				},
				'dev-red': {
					DEFAULT: '#FF5A71'
				},
				'midnight-express': {
					DEFAULT: '#2A2E3D'
				}
			},
			fontFamily: {
				default: ['Mulish', 'sans-serif'],
				heading: ['Poppins', 'sans-serif']
			},
			spacing: {
				50: '50%'
			},
		},
	},
	variants: {
		extend: {},
	},
	plugins: [],
}
