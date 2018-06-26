module.exports = {
  options : {
    force: true //This overrides this task from blocking deletion of folders outside current working dir (CWD). Use with caution.
  },
	main : ['build/**/*']
};