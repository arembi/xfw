<?php

namespace Arembi\Xfw\Filter;

class HtmlLayoutFilter implements LayoutFilter {

  public function filter(string $str)
  {
    return htmlspecialchars($str);
  }

}
