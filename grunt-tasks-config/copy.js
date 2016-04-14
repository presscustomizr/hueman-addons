module.exports = {
	main: {
		src:  [
			'**',
			'!bin/**',
			'!build/**',
			'!grunt-tasks-config/**',
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
			'!**/*.db',
      '!patches/**',
      '!inc/init-pro.php',
      '!assets/back/js/parts/**',
      '!npm-debug.log'
		],
		dest: 'build/<%= pkg.name %>/'
	}
};