module.exports = {
	main: {
		src: [
			'ha-fire.php'
		],
		overwrite: true,
		replacements: [ {
			from: /^.* Version: .*$/m,
			to: '* Version: <%= pkg.version %>'
		} ]
	},
	readme_md : {
		src: [
			'readme.md'
		],
		overwrite: true,
		replacements: [ {
			from: /^.*Stable tag: .*$/m,
			to: '* Stable tag: <%= pkg.version %>'
		} ]
	},
  readme_txt : {
    src: [
      'readme.txt'
    ],
    overwrite: true,
    replacements: [ {
      from: /^.*Stable tag: .*$/m,
      to: 'Stable tag: <%= pkg.version %>'
    } ]
  },
  lang : {
    src: [
      '<%= paths.lang %>*.po'
    ],
    overwrite: true,
    replacements: [ {
      from: /^.* Hueman Addons v.*$/m,
      to: '"Project-Id-Version: Hueman Addons v<%= pkg.version %>\\n"'
    } ]
  },
};