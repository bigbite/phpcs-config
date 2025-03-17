<?php

// phpcs:set BigBite.Commenting.DocCommentLineLength lineLimit 120
// phpcs:set BigBite.Commenting.DocCommentLineLength absoluteLineLimit 150

/**
 * This is a comment line that exceeds the default of 80 characters, but is shorter than the configured 120 characters.
 */

/**
 * This is a comment line that exceeds the default maximum of 100 characters, but is shorter than the configured 150. It should trigger a warning.
 */

/**
 * This is a comment line that exceeds the default maximum of 100 characters, and is longer than the configured maximum of 150 characters, so should trigger an error.
 */

// phpcs:set BigBite.Commenting.DocCommentLineLength lineLimit 80
// phpcs:set BigBite.Commenting.DocCommentLineLength absoluteLineLimit 100
