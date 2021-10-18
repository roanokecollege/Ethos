<?php
  namespace Roanokecollege\Ethos;

  use Illuminate\Support\Facades\Facade;

  class EthosFacade extends Facade {

    protected static function getFacadeAccessor () {
      return "Ethos";
    }

  }
