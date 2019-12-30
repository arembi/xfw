<?php

namespace Arembi\Xfw\Misc;

class Google {

  private $searchConsoleMetaVerificationId;

  private $uaId;

  private $tagManagerId;

  private $trackerType;

  public function googleInit()
  {
    if (self::$googleSC && !IS_LOCALHOST) {
      HEAD::addMeta([['google-site-verification', self::$searchConsoleMetaVerificationId]]);
    }

    if (self::$googleAN && !IS_LOCALHOST) {
      HEAD::addJS(self::$googleAN);
    }

  }


  public function insertTrackerCode()
  {

  }


  public function getScMetaId()
  {
    return $this->searchConsoleMetaVerificationId;
  }


  public function setScMetaId($scId)
  {
    $this->searchConsoleMetaVerificationId = $scId;
  }


  public function getUaId()
  {
    return $this->uaId();
  }


  public function setUaId($uaId)
  {
    $this->$uaId = $uaId;
  }

}
