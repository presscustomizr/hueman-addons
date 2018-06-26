module.exports = {
	main: {
		src:  [
			'**',
			'!bin/**',

			'!build/**',
			'!__grunt-tasks-config__/**',
			'!node_modules/**',
			'!tests/**',
			'!wpcs/**',

			'!.git/**',
			'!gruntfile.js',
			'!package.json',
			'!.gitignore',
			'!.ftpauth',
			'!.travis.yml',
			'!travis-examples/**',
			'!phpunit.xml',
			'!readme.md',
      '!npm-debug.log',

			'!**/*.db',
      '!patches/**',

      '!addons/skop/_dev/**',

      '!addons/_dev_print_customizer_data.php'
		],
		dest: 'build/<%= pkg.name %>/'
	}
};