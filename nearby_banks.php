<?php
// The donor dashboard links "Nearby Blood Banks" to nearby_banks.php, but the
// real map lives in geo_map.php (BL-16). Thin alias so both entry points land
// on the same page rather than 404-ing.
header('Location: geo_map.php');
exit;
