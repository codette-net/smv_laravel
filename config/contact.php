<?php

return [
    'mail_to' => env('CONTACT_MAIL_TO', 'sales@salesenmarketingvacatures.nl'),
    'rate_limit_per_minute' => (int) env('CONTACT_RATE_LIMIT_PER_MINUTE', 5),
];
