<?php

interface FooInterface {
  public function foo();
}

trait FooTrait
{
  final public foo() {
  }
}

enum FooEnum {
  case Bar;
}

class FooClass
{
  public array $foo = [];
}
