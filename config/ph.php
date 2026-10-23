<?php

return [
    // Water-quality thresholds from the project document
    'acidic_below'   => 6.5,   // pH < 6.5  -> ACIDIC   (yellow light)
    'alkaline_above' => 8.5,   // pH > 8.5  -> ALKALINE (red light)

    // pH = neutral + ((2.5 - voltage) / 0.18)   (see the firmware flowchart)
    'neutral'        => 7.0,

    // Optional shared secret. If set, the ESP32 must send header X-Device-Key
    'device_key'     => env('PH_DEVICE_KEY'),
];
