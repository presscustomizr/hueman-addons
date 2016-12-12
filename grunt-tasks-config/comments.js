module.exports = {
  php: {
    // Target-specific file lists and/or options go here.
    options: {
        singleline: true,
        multiline: true
    },
    src: [ '<%= paths.inc_php %>skop/czr-skop.php' ] // files to remove comments from
  }
};