<!-- Annotation has to be on the second line as errors are thrown on line 1 and errors on annotation lines are ignored. -->
phpcs:set BigBite.Files.FileName strict_file_names false

<?php

interface Non_Strict_Interface {}

// phpcs:set BigBite.Files.FileName strict_file_names true
