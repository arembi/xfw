<?php

namespace Arembi\Xfw\Inc\Filter;

class HtmlLayoutFilter implements LayoutFilter {

  public function filter(string $str): string
  {
    return htmlspecialchars($str);
  }
}
