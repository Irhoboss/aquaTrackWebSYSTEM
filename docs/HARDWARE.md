# ESP32 + PH-4502C hardware notes

## Voltages: check before you connect anything
| Part | Fact |
|---|---|
| ESP32 GPIO / ADC | 3.3 V logic. **Not 5 V tolerant.** ADC with 11 dB attenuation is only reasonably linear up to about 3.1 V. |
| PH-4502C module | Powered from **5 V** (VCC). Its analog pin `Po` can swing up to roughly 5 V. Typical behaviour: ~2.5 V with the probe in pH 7 buffer (adjustable with the offset trimmer), voltage changes by roughly 0.16–0.18 V per pH unit. **Measure yours with a multimeter.** |

So `Po` must go through a divider before the ESP32:

```
Po ──[ R1 = 10 kΩ ]──┬──[ R2 = 15 kΩ ]── GND
                     └── GPIO34 (ADC1_CH6)  + 100 nF to GND
```
Ratio = R2 / (R1 + R2) = 0.60, so 5.0 V → 3.0 V. If you use other resistors, set `DIVIDER_RATIO` in the firmware.
If you have measured that your `Po` never exceeds 3.0 V you may use ratio 1.0, but still add the 100 nF capacitor.

## Wiring
| PH-4502C | ESP32 |
|---|---|
| VCC | VIN / 5V pin (USB-powered board) |
| GND | GND (common ground is mandatory) |
| Po | divider → GPIO34 |
| To (temperature), Do (limit) | not used |

* Use an **ADC1** pin (GPIO32–39). ADC2 pins stop working while Wi-Fi is active.
* Keep the BNC cable short, away from the ESP32 antenna, motors and mains wiring. Noise is the main enemy of pH readings.
* The probe tip must stay wet. Store it in storage solution, never in distilled water.

## Calibration (two-point)
1. Upload the sketch and open Serial Monitor at 115200 baud.
2. Probe in pH 6.86 (or 7.00) buffer → wait until "Module mV" is stable → write it into `CAL_MV_A` (and set `CAL_PH_A`).
3. Rinse, probe in pH 4.01 buffer → wait → write into `CAL_MV_B`.
4. Re-upload. Check with a third buffer (e.g. 9.18). Re-calibrate regularly (weekly at first), and whenever the probe is replaced.

The firmware converts `mV → pH` with a straight line through the two points, filters with a median of 25 samples and a light exponential smoothing, and reports an **estimated** pH. Buffers should be at similar temperature; this build has no temperature compensation.

## Limits to be honest about
* A floating or shorted BNC input on this module often reads near the pH 7 voltage, so a "disconnected probe" cannot always be detected from voltage alone. The firmware flags saturated voltages and unstable (noisy) signals as `disconnected` / `error`. Periodic buffer checks are still required.
* The firmware never sends a made-up value: on a fault it sends `ph_value: null` with a `sensor_status`, and the server stores it as a sensor error / invalid reading, never as "safe".
* This is a monitoring aid, not a certified water-quality instrument.
