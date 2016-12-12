module.exports = {
  options: {
    separator: '',
  },
  skop_php : {
    src: [
      '<%= paths.skop_dev_php %>skop-0-init-base.php',
      '<%= paths.skop_dev_php %>skop-ajax-changeset-base.php',
      '<%= paths.skop_dev_php %>skop-ajax-changeset-publish.php',
      '<%= paths.skop_dev_php %>skop-ajax-changeset-save.php',
      '<%= paths.skop_dev_php %>skop-ajax-reset.php',
      '<%= paths.skop_dev_php %>skop-customize-preview.php',
      '<%= paths.skop_dev_php %>skop-customize-register.php',
      '<%= paths.skop_dev_php %>skop-options-base.php',
      '<%= paths.skop_dev_php %>skop-options-preview-value.php',
      '<%= paths.skop_dev_php %>skop-options-x-final.php',
      '<%= paths.skop_dev_php %>skop-x-fire.php'
    ],
    dest: '<%= paths.inc_php %>skop/czr-skop.php',
  }
};