<!-- Annotation has to be on the second line as errors are thrown on line 1 and errors on annotation lines are ignored. -->
phpcs:set BigBite.Files.FileName strict_file_names false

<?php

abstract class Non_Strict_Class {}

// phpcs:set BigBite.Files.FileName strict_file_names true
