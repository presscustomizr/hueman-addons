module.exports = {
	options: {
		compress: {
			global_defs: {
				"DEBUG": false
		},
		dead_code: true
		}
	},
	front_js: {
		files: [{
			expand: true,
			cwd: '<%= paths.front_js %>',
			src: ['**/*.js', '!*.min.js'],
			dest: '<%= paths.front_js %>',
			ext: '.min.js'
		}]
	},
	any_file : {
		files: { '<%= uglify_requested_paths.dest %>': ['<%= uglify_requested_paths.src %>']
      }
	}
};