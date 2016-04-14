module.exports = {
	// gruntfile: {
	// files: 'Gruntfile.js',
	// tasks: ['jshint:gruntfile'],
	// },
	options: {
		spawn : false,
		// Start a live reload server on the default port 35729
		livereload : true,
	},
	front_js : {
		files : ['<%= paths.front_js %>*.js', '!*.min.js'],
		tasks : ['jshint:front_js','uglify:front_js'],
		//tasks: ['concat:front_js', 'jshint:front', 'ftp_push:those'],
	},
	php : {
		files: ['**/*.php' , '!build/**.*.php'],
		tasks: []
	}
};