<?php
/** @var array  $p           Result from paginate() */
/** @var string $urlPattern  URL with %d placeholder */
echo render_pager($p, $urlPattern);
