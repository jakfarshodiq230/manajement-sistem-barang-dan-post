<?php
$cols = Schema::getColumnListing("products");
echo implode(", ", $cols);

