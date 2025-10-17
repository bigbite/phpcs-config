<?php

class DoesNotImplementToStringMagicMethod {
}

class CorrectlyImplementsStringable implements \Stringable {
  public function __toString(): string {
    return __CLASS__;
  }
}

class IncorrectWithWhitespace {
  public function __toString(): string {
    return __CLASS__;
  }
}

class IncorrectWithNoWhitespace{
  public function __toString(): string {
    return __CLASS__;
  }
}

class IncorrectWithNewline
{
  public function __toString(): string {
    return __CLASS__;
  }
}
